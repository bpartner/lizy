<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */


use Lyzi\Helpers\Crypter;
use Lyzi\Enums\Options;

if (!defined('_PS_VERSION_')) {
    exit;
}

class LyziPaymentMethodModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (!$this->module->active) {
            $this->error_message = 'The Lyzi module is not active.';

            return;
        }

        $cart = $this->context->cart;
        $price = $cart->getOrderTotal();
        $customer = new Customer($cart->id_customer);

        $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_PREPARATION'),
            $price,
            'lyzi',
            null,
            [],
            $this->context->currency->id,
            false,
            $customer->secure_key
        );

        $orderId = Order::getIdByCartId($cart->id);

        $this->context->smarty->assign('buttonId', Configuration::get(Options::BUTTON_ID, null));
        $this->context->smarty->assign('orderId', Crypter::encrypt($orderId));
        $this->context->smarty->assign('currency', Currency::getIsoCodeById((int) $cart->id_currency));
        $this->context->smarty->assign('price', $price);
        $this->context->smarty->assign('callbackUrl', htmlspecialchars_decode(Configuration::get(Options::WEBHOOK)));
        $this->context->smarty->assign(
            'setTransactionCallback',
            Context::getContext()->link->getModuleLink('lyzi', 'paymentsettransactionid', ['ajax'=>true], true)
        );

        $this->context->controller->addJS(_MODULE_DIR_ . 'lyzi/views/js/sdk.js');

        $this->setTemplate('module:lyzi/views/templates/front/payment.tpl');

        $this->displayContent();
    }
}
