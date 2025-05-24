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
if (!defined('_PS_VERSION_')) {
    exit;
}
class AdminConfigurePayment4Controller extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        if (empty(Currency::checkPaymentCurrencies($this->module->id))) {
            $this->warnings[] = $this->module->getTranslator()->trans(
                'No currency has been set for this module.',
                [],
                'Modules.Payment4.Adminconfigurepayment4controller'
            );
        }

        $this->fields_options = [
            $this->module->name => [
                'fields' => [
                    Payment4::PAYMENT4_EXTERNAL_ENABLED => [
                        'type' => 'bool',
                        'title' => $this->module->getTranslator()->trans(
                            'Enable Payment4 Module',
                            [],
                            'Modules.Payment4.Adminconfigurepayment4controller'
                        ),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'required' => false,
                    ],
                    Payment4::PAYMENT4_SANDBOX_MODE => [
                        'type' => 'bool',
                        'title' => $this->module->getTranslator()->trans(
                            'SandBox Mode',
                            [],
                            'Modules.Payment4.Adminconfigurepayment4controller'
                        ),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'required' => false,
                    ],
                    Payment4::PAYMENT4_API_KEY => [
                        'type' => 'text',
                        'title' => $this->module->getTranslator()->trans(
                            'API Key',
                            [],
                            'Modules.Payment4.Adminconfigurepayment4controller'
                        ),
                        'desc' => $this->module->getTranslator()->trans(
                            'Enter a valid API Key',
                            [],
                            'Modules.Payment4.Adminconfigurepayment4controller'
                        ),
                    ],
                ],
                'submit' => [
                    'title' => $this->module->getTranslator()->trans(
                        'Save',
                        [],
                        'Modules.Payment4.Adminconfigurepayment4controller'
                    ),
                ],
            ],
        ];
    }
}
