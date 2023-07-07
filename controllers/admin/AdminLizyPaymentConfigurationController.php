<?php

declare(strict_types = 1);

final class AdminLizyPaymentConfigurationController extends ModuleAdminController
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
                    'LIZY_DEBUG_MODE' => [
                        'type' => 'bool',
                        'title' => $this->l('Enable debug mode'),
                        'validation' => 'isBool',
                        'cast' => 'intval',
                        'required' => false,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }
}