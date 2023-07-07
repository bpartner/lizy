<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types = 1);


use Lizy\Builders\Config;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}


final class Lizy extends PaymentModule
{
    /**
     * List of hooks used in this Module
     */
    public $hooks = [
        'paymentOptions',
    ];
    public function __construct()
    {
        $this->name = 'lizy';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Lizy.io';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;
        $this->controllers = [
        ];

        parent::__construct();

        $this->displayName = $this->l('Lizy Payment');
        $this->description = $this->l('Make payment with Lizy.io');
    }

    /**
     * @return bool
     */
    public function install()
    {
        return (bool) parent::install()
               && !Configuration::updateValue(Options::WEBHOOK, '')
               && (bool) $this->registerHook($this->hooks);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return (bool) parent::uninstall();
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        $builder = Config::make();

        // Redirect to our ModuleAdminController when click on Configure button
        $output = '';

        // this part is executed only when the form is submitted
        if (Tools::isSubmit('submit' . $this->name)) {
            // retrieve the value set by the user
            $configValue = (string) Tools::getValue('LIZY_CONFIG');

            // check that the value is valid
            if (empty($configValue) || !Validate::isGenericName($configValue)) {
                // invalid value, show an error
                $output = $this->displayError($this->l('Invalid Configuration value'));
            } else {
                // value is ok, update it and display a confirmation message
                Configuration::updateValue('LIZY_CONFIG', $configValue);
                $output = $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        // display any message, then the form
        return $output . $this->displayForm();
        
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminLizyPaymentConfiguration'));
    }

    public function hookPaymentOptions(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart)) {
            return [];
        }

        $paymentOptions = [];

        return $paymentOptions;
    }

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

    public function displayForm()
    {
        // Init Fields form array
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                ],
                'input'  => [
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Configuration value'),
                        'name'     => 'LIZY_CONFIG',
                        'size'     => 20,
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        // Default language
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');

        // Load current value into the form
        $helper->fields_value['LIZY_CONFIG'] = Tools::getValue('LIZY_CONFIG', Configuration::get('LIZY_CONFIG'));

        return $helper->generateForm([$form]);
    }
}