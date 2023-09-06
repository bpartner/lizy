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


use Lyzi\Builders\Config;
use Lyzi\Enums\Options;
use Lyzi\Install\Installer;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}


final class Lyzi extends PaymentModule
{

    public function __construct()
    {
        $this->name = 'lyzi';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Lyzi.io';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '9.99.99',
        ];
        $this->bootstrap = true;
        $this->controllers = [
        ];

        parent::__construct();

        $this->displayName = $this->l('Lyzi Payment');
        $this->description = $this->l('Make payment with Lyzi.io');
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (parent::install()) {
            return Installer::make($this)->install();
        }

        return false;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (parent::uninstall()) {
            return Installer::make($this)->uninstall();
        }

        return false;
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        if (Tools::isSubmit('submit' . $this->name)) {
            $this->saveConfiguration();
            $this->context->smarty->assign('confirmation', $this->displayConfirmation('Settings updated'));
        }

        $this->context->smarty->assign('webhook_endpoint', Configuration::get(Options::WEBHOOK, 0));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
                                . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => 'Settings',
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'col' => 8,
                        'type' => 'text',
                        'name' => Options::BUTTON_ID,
                        'label' => 'Button ID',
                        'desc' => 'Please enter your Button ID. You can find it in your <a>Lyzi</a> settings page.',
                    ]
                ],
                'submit' => [
                    'title' => 'Save',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        return [Options::BUTTON_ID => Configuration::get(Options::BUTTON_ID, null)];
    }

    protected function saveConfiguration()
    {
        $values = $this->getConfigFormValues();

        foreach (array_keys($values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        $this->context->smarty->assign('confirmation', $this->displayConfirmation('Settings updated'));
    }

    public function hookPaymentOptions(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) /**|| false === $this->checkCurrency($cart)*/) {
            return [];
        }

        $paymentOption = new PaymentOption();
        $paymentOption->setModuleName($this->name);
        $paymentOption->setCallToActionText($this->l('Pay by Crypto with Lyzi Payment'));

        $paymentOption->setAction($this->context->link->getModuleLink($this->name, 'paymentmethod', [], true));
        $paymentOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo.webp'));

        return [$paymentOption];
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
            if ($currency_order->id === $currency_module['id_currency']) {
                return true;
            }
        }

        return false;
    }
}
