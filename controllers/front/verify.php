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

/**
 * This Controller receive customer after approval on bank payment page
 */
class Payment4VerifyModuleFrontController extends ModuleFrontController
{
    /**
     * @var PaymentModule
     */
    public $module;

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect(
                $this->context->link->getPageLink(
                    'order',
                    true,
                    (int)$this->context->language->id,
                    [
                        'step' => 1,
                    ]
                )
            );
        }
        $customer = new Customer($this->context->cart->id_customer);
        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect(
                $this->context->link->getPageLink(
                    'order',
                    true,
                    (int)$this->context->language->id,
                    [
                        'step' => 1,
                    ]
                )
            );
        }
        $paymentUid = Tools::getValue('paymentUid', null);
        if ( ! is_null($paymentUid)) {
            $amount = $this->context->cart->getOrderTotal(true);

            $url      = "https://service.payment4.com/api/v1/payment/verify";
            $headers  = [
                "x-api-key: " . Configuration::get(Payment4::PAYMENT4_API_KEY),
                "Content-Type: application/json",
            ];
            $currency = $this->context->currency->iso_code;
            if ($currency === "IRR") {
                $currency = "IRT";
                (int)$amount /= 10;
            }

            $payload = [
                "paymentUid" => $paymentUid,
                "amount"     => $amount,
                "currency"   => $currency,
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            $err      = curl_error($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseData = json_decode($response);

            if ($err) {
                $this->errors[] = $this->module->getTranslator()->trans('Error Connecting to Payment Gateway', [], 'Modules.Payment4.Verify');
                $this->errors[] = $err;
                return;
            }
            if ($httpcode == 400 && ! $responseData->status) {
                $this->errors[] = $responseData->errorCode;
                $this->errors[] = $responseData->message;

                return;
            }

            // http code is 200 Process the payment data
            $paymentStatus    = $responseData->paymentStatus;
            $amountDifference = $responseData->amountDifference ?? 0;
            $verified         = $responseData->verified;
            $message = json_encode([
                'Payment Status'    => $paymentStatus,
                'Amount Difference' => $amountDifference,
                'Verified'          => $verified ? 'Yes' : 'No',
                'Transaction ID' => $paymentUid,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($verified) {
                // Validate cart
                $cartId = (int)$this->context->cart->id;
                if ($cartId <= 0 || ! Validate::isLoadedObject($this->context->cart)) {
                    $this->errors[] = $this->module->getTranslator()->trans('Cart cannot be loaded', [], 'Modules.Payment4.Verify');

                    return;
                }
                $this->module->validateOrder(
                    (int)$this->context->cart->id,
                    (int)$this->getOrderState(),
                    (float)$this->context->cart->getOrderTotal(true, Cart::BOTH),
                    'Payment4',
                    $message,
                    [
                        'transaction_id'    => $paymentUid,
                    ],
                    (int)$this->context->currency->id,
                    false,
                    $customer->secure_key
                );

                Tools::redirect(
                    $this->context->link->getPageLink(
                        'order-confirmation',
                        true,
                        (int)$this->context->language->id,
                        [
                            'id_cart'   => (int)$this->context->cart->id,
                            'id_module' => (int)$this->module->id,
                            'id_order'  => (int)$this->module->currentOrder,
                            'key'       => $customer->secure_key,
                        ]
                    )
                );
            } else {
                $this->errors[] = $this->module->getTranslator()->trans('Your Payment is not confirmed. Payment Status: ', [], 'Modules.Payment4.Verify') . $paymentStatus;
            }
        }
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

    /**
     * Get OrderState identifier
     *
     * @return int
     */
    private function getOrderState()
    {
        return (int)Configuration::get('PS_OS_WS_PAYMENT');
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
        $this->setTemplate('module:payment4/views/templates/front/verify.tpl');
    }
}
