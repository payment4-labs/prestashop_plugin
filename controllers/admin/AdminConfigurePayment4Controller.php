<?php


class AdminConfigurePayment4Controller extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        if (empty(Currency::checkPaymentCurrencies($this->module->id))) {
            $this->warnings[] = $this->l('No currency has been set for this module.');
        }

        $this->fields_options = [
            $this->module->name => [
                'fields' => [
                    Payment4::PAYMENT4_EXTERNAL_ENABLED => [
                        'type' => 'bool',
                        'title' => $this->l('Enable Payment4 Module'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'required' => false,
                    ],
                    Payment4::PAYMENT4_SANDBOX_MODE => [
                        'type' => 'bool',
                        'title' => $this->l('SandBox Mode'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'required' => false,
                    ],
                    Payment4::PAYMENT4_API_KEY => [
                        'type'   => 'text',
                        'title' => $this->l('API Key'),
                        'desc'   => $this->l('Enter a valid API Key'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }
}
