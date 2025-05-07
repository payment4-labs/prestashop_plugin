<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Amirhosein Taghizadeh <amirtz.dev@gmail.com>
 * @copyright Payment4 2025
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * This Controller simulate an Validation payment gateway
 */
class Payment4ExternalModuleFrontController extends ModuleFrontController
{
    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);

        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $amount = $this->context->cart->getOrderTotal(true);

        // Payment4
        $url     = "https://service.payment4.com/api/v1/payment";
        $headers = [
            "x-api-key: " . Configuration::get(Payment4::PAYMENT4_API_KEY),
            "Content-Type: application/json",
        ];

        $currency = $this->context->currency->iso_code;
        if ($currency === "IRR"){
            $currency = "IRT";
            (int) $amount /= 10;
        }
        // callback
        $callbackUrl = $this->context->link->getModuleLink($this->module->name, 'verify', [], true);
        $parsedUrl = parse_url($callbackUrl);
        $query = $parsedUrl['query'] ?? '';

        $callback_params = [];
        parse_str($query, $callback_params);
        $callback_base_url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];

        $payload = [
            "amount"      => $amount,
            "callbackUrl" => $callback_base_url,
            "language"    => $this->getLanguageCode(),
            "currency"    => $currency,
            "sandBox"     => (bool)Configuration::get(Payment4::PAYMENT4_SANDBOX_MODE),
        ];

        if (!empty($callback_params)){
            $payload['callbackParams'] = $callback_params;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($response);
        if ($err) {
            $this->errors[] = "خطا در اتصال به درگاه پرداخت : <br> $err";

            return;
        }
        if ($httpcode == 400 && ! $responseData->status) {
            $this->errors[] = $responseData->errorCode;
            $this->errors[] = $responseData->message;

            return;
        }

        // http code is 200 Process the payment data
        if ($httpcode == 201) {
            $id         = $responseData->id;
            $paymentUid = $responseData->paymentUid;
            header("Location: $responseData->paymentUrl");
            exit();
        }

    }

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            'action' => $this->context->link->getPageLink('order'),
        ]);

        $this->setTemplate('module:payment4/views/templates/front/external.tpl');
    }

    public function callBackUrl()
    {
        return (Configuration::get(
                'PS_SSL_ENABLED'
            ) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__ . 'module/payment4/verify';
    }

    /**
     * Get the validated language code
     * @return string Language code (e.g., "EN", "FA")
     */
    private function getLanguageCode(): string
    {
        $language = 'EN'; // Default fallback

        try {
            // Get the current language ISO code (e.g., "en", "fa")
            $prestashopLanguage = 'en'; // Fallback
            if ($this->context->language && !empty($this->context->language->iso_code)) {
                $prestashopLanguage = $this->context->language->iso_code;
            } else {
                $defaultLangId = (int)Configuration::get('PS_LANG_DEFAULT');
                $languageObj = new Language($defaultLangId);
                if ($languageObj->iso_code) {
                    $prestashopLanguage = $languageObj->iso_code;
                }
            }
            $prestashopLanguage = strtoupper($prestashopLanguage);

            // Fetch the languages JSON file
            $url = 'https://storage.payment4.com/wp/languages.json';
            $response = Tools::file_get_contents($url);

            // Check if the response is valid
            if ($response === false) {
                PrestaShopLogger::addLog('Failed to fetch languages JSON from ' . $url, 3);
                return $language; // Return default "EN"
            }

            // Decode JSON response
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                PrestaShopLogger::addLog('Invalid JSON response from ' . $url, 3);
                return $language; // Return default "EN"
            }

            // Validate the data
            if (is_array($data) && count($data) > 1) {
                if (in_array($prestashopLanguage, $data)) {
                    $language = $prestashopLanguage;
                }
            }

        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error fetching language code: ' . $e->getMessage(), 3);
        }

        return $language;
    }

    /**
     * Check if the context is valid
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        if (!Configuration::get(Payment4::PAYMENT4_EXTERNAL_ENABLED)) {
            return false;
        }

        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }


}
