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
            'max' => '8.99.99',
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
        $builder = Config::make()->setup($this);

        if (Tools::isSubmit('submit' . $this->name)) {
            $builder->store();
            $this->context->smarty->assign('confirmation', $this->displayConfirmation('Settings updated'));
        }

        $str = $this->context->link->getModuleLink('lyzi', 'webhook', [], true);
        $this->context->smarty->assign('webhook_endpoint', $str);
        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
    }

    public function hookPaymentOptions(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) /**|| false === $this->checkCurrency($cart)*/) {
            return [];
        }

        $externalOption = new PaymentOption();
        $externalOption->setModuleName($this->name);
        $externalOption->setCallToActionText($this->l('Lyzi payment'));
        $externalOption->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true));
        $externalOption->setInputs([
            'token' => [
                'name' => 'token',
                'type' => 'hidden',
                'value' => '[5cbfniD+(gEV<59lYbG/,3VmHiE<U46;#G9*#NP#X.FA§]sb%ZG?5Q{xQ4#VM|7',
            ],
        ]);
        $str = $this->context->link->getModuleLink('lyzi', 'webhook', [], true);
        $this->context->smarty->assign('webhook_endpoint', $str);
        $externalOption->setAdditionalInformation($this->context->smarty->fetch('module:lyzi/views/templates/front/payment.tpl'));
        //$externalOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/option/external
        //.png'));

        return [$externalOption];
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