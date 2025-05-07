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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if ( ! defined('_PS_VERSION_')) {
    exit;
}

class Payment4 extends PaymentModule
{
    const PAYMENT4_EXTERNAL_ENABLED = 'PAYMENT4_EXTERNAL_ENABLED';
    const PAYMENT4_SANDBOX_MODE = 'PAYMENT4_SANDBOX_MODE';
    const PAYMENT4_API_KEY = 'PAYMENT4_API_KEY';
    const MODULE_ADMIN_CONTROLLER = 'AdminConfigurePayment4';

    const HOOKS = [
        'actionObjectShopAddAfter',
        'paymentOptions',
        'displayAdminOrderMainBottom',
        'displayCustomerAccount',
        'displayOrderConfirmation',
        'displayOrderDetail',
        'displayPaymentReturn',
        'displayPDFInvoice',
    ];

    public function __construct()
    {
        $this->name                   = 'payment4';
        $this->tab                    = 'payments_gateways';
        $this->version                = '1.0.0';
        $this->author                 = 'Amirhossein Taghizadeh';
        $this->need_instance          = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->controllers            = [
            'external',
            'verify',
        ];

        parent::__construct();

        $this->displayName = $this->l('Payment4');
        $this->description = $this->l('Payment4 gateway Pay with CryptoCurrencies');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * @return bool
     */
    public function install()
    {
        if ( ! extension_loaded('curl')) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');

            return false;
        }

        return parent::install()
            && $this->registerHook(static::HOOKS)
            && $this->installConfiguration()
            && $this->installTabs();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallConfiguration()
            && $this->installTabs();
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        // Redirect to our ModuleAdminController when click on Configure button
        Tools::redirectAdmin($this->context->link->getAdminLink(static::MODULE_ADMIN_CONTROLLER));
    }

    /**
     * This hook called after a new Shop is created
     *
     * @param  array  $params
     */
    public function hookActionObjectShopAddAfter(array $params)
    {
        if (empty($params['object'])) {
            return;
        }

        /** @var Shop $shop */
        $shop = $params['object'];

        if (false === Validate::isLoadedObject($shop)) {
            return;
        }

        $this->addCheckboxCarrierRestrictionsForModule([(int)$shop->id]);
        $this->addCheckboxCountryRestrictionsForModule([(int)$shop->id]);

        if ($this->currencies_mode === 'checkbox') {
            $this->addCheckboxCurrencyRestrictionsForModule([(int)$shop->id]);
        } elseif ($this->currencies_mode === 'radio') {
            $this->addRadioCurrencyRestrictionsForModule([(int)$shop->id]);
        }
    }

    /**
     * @param  array  $params
     *
     * @return array Should always return an array
     */
    public function hookPaymentOptions($params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart) || ! $this->active) {
            return [];
        }

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->l('Pay by Crypto'))
            ->setAction($this->context->link->getModuleLink($this->name, 'external', array(), true))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/small-logo.png'));

        return [
            $newOption,
        ];
    }


    /**
     * This hook is used to display additional information on BO Order View, under Payment block
     *
     * @param  array  $params
     *
     * @return string
     * @since PrestaShop 1.7.7 This hook replace displayAdminOrderLeft on migrated BO Order View
     *
     */
    public function hookDisplayAdminOrderMainBottom(array $params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order((int)$params['id_order']);

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }
        
        // Get Order Message
        $messages = MessageCore::getMessagesByOrderId($order->id, true);
        $firstMessage = !empty($messages) ? $messages[0] : null;
        $firstMessageData = $firstMessage ? json_decode($firstMessage['message'], true) : null;

        $this->context->smarty->assign([
            'moduleName'        => $this->name,
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc'     => $this->getPathUri() . 'logo.png',
            'message' => $firstMessageData,
        ]);

        return $this->context->smarty->fetch(
            'module:payment4/views/templates/hook/displayAdminOrderMainBottom.tpl'
        );
    }

    /**
     * This hook is used to display information in customer account
     *
     * @param  array  $params
     *
     * @return string
     */
    public function hookDisplayCustomerAccount(array $params)
    {
        $this->context->smarty->assign([
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc'     => $this->getPathUri() . 'logo.png',
            'transactionsLink'  => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->context->smarty->fetch('module:payment4/views/templates/hook/displayCustomerAccount.tpl');
    }

    /**
     * This hook is used to display additional information on order confirmation page
     *
     * @param  array  $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction  = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName'  => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:payment4/views/templates/hook/displayOrderConfirmation.tpl');
    }

    /**
     * This hook is used to display additional information on FO (Guest Tracking and Account Orders)
     *
     * @param  array  $params
     *
     * @return string
     */
    public function hookDisplayOrderDetail(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction  = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName'  => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:payment4/views/templates/hook/displayOrderDetail.tpl');
    }

    /**
     * This hook is used to display additional information on bottom of order confirmation page
     *
     * @param  array  $params
     *
     * @return string
     */
    public function hookDisplayPaymentReturn(array $params)
    {
        if (empty($params['order'])) {
            return '';
        }

        /** @var Order $order */
        $order = $params['order'];

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction  = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName'       => $this->name,
            'transaction'      => $transaction,
            'transactionsLink' => $this->context->link->getModuleLink(
                $this->name,
                'account'
            ),
        ]);

        return $this->context->smarty->fetch('module:payment4/views/templates/hook/displayPaymentReturn.tpl');
    }

    /**
     * This hook is used to display additional information on Invoice PDF
     *
     * @param  array  $params
     *
     * @return string
     */
    public function hookDisplayPDFInvoice(array $params)
    {
        if (empty($params['object'])) {
            return '';
        }

        /** @var OrderInvoice $orderInvoice */
        $orderInvoice = $params['object'];

        if (false === Validate::isLoadedObject($orderInvoice)) {
            return '';
        }

        $order = $orderInvoice->getOrder();

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $transaction = '';

        if ($order->getOrderPaymentCollection()->count()) {
            /** @var OrderPayment $orderPayment */
            $orderPayment = $order->getOrderPaymentCollection()->getFirst();
            $transaction  = $orderPayment->transaction_id;
        }

        $this->context->smarty->assign([
            'moduleName'  => $this->name,
            'transaction' => $transaction,
        ]);

        return $this->context->smarty->fetch('module:payment4/views/templates/hook/displayPDFInvoice.tpl');
    }

    /**
     * Check if currency is allowed in Payment Preferences
     *
     * @param  Cart  $cart
     *
     * @return bool
     */
    private function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        /** @var array $currencies_module */
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (empty($currencies_module)) {
            return false;
        }

        foreach ($currencies_module as $currency_module) {
            if ($currency_order->id == $currency_module['id_currency']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Install Tabs
     *
     * @return bool
     */
    public function installTabs()
    {
        if (Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER)) {
            return true;
        }

        $tab             = new Tab();
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->module     = $this->name;
        $tab->active     = true;
        $tab->id_parent  = -1;
        $tab->name       = array_fill_keys(
            Language::getIDs(false),
            $this->displayName
        );

        return (bool)$tab->add();
    }

    /**
     * Uninstall Tabs
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int)Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool)$tab->delete();
        }

        return true;
    }

    /**
     * Install default module configuration
     *
     * @return bool
     */
    private function installConfiguration()
    {
        return (bool)Configuration::updateGlobalValue(static::PAYMENT4_EXTERNAL_ENABLED, '1')
            && (bool)Configuration::updateGlobalValue(static::PAYMENT4_SANDBOX_MODE, '0')
            && (bool)Configuration::updateGlobalValue(static::PAYMENT4_API_KEY, '');
    }

    /**
     * Uninstall module configuration
     *
     * @return bool
     */
    private function uninstallConfiguration()
    {
        return (bool)Configuration::deleteByName(static::PAYMENT4_EXTERNAL_ENABLED)
            && (bool)Configuration::deleteByName(static::PAYMENT4_SANDBOX_MODE)
            && (bool)Configuration::deleteByName(static::PAYMENT4_API_KEY);
    }

}