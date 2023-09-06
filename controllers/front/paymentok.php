<?php

declare(strict_types = 1);

use Lyzi\Helpers\Crypter;

final class LyziPaymentOkModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $params = json_decode(file_get_contents('php://input'));
            $dbOrder = $this->getDBOrderByTransactionId($params->code);

            if ($dbOrder) {
                $order = new Order($dbOrder['id_order']);

                $orderStatus = null;
                switch ($params->status) {
                    case 'PAID':
                        $orderStatus = (int)Configuration::get('PS_OS_PAYMENT');
                        break;
                    case 'EXPIRED':
                    case 'CANCELED':
                        $orderStatus = (int)Configuration::get('PS_OS_CANCELED');
                        break;
                    case 'ERROR':
                        $orderStatus = (int)Configuration::get('PS_OS_ERROR');
                        break;
                    default:
                        break;
                }

                if ($orderStatus) {
                    $order->setCurrentState($orderStatus);
                }
            }
            die();
        }
    }

    public function initContent()
    {
        $dbOrder = $this->getDBOrderByTransactionId(Tools::getValue('code'));

        if ($dbOrder) {
            $order = new Order($dbOrder['id_order']);
            $cart = new Cart(Cart::getCartIdByOrderId($order->id));
            $module = Module::getInstanceByName('lyzi');

            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int) $cart->id .
                '&id_module=' . (int) $module->id . '&id_order=' . (int) $order->id . '&key=' . $order->secure_key);
        }

        Tools::redirect('index.php');
    }

    public function getDBOrderByTransactionId(string $transactionId)
    {
        return Db::getInstance()->getRow(
            'SELECT ord.id_order from `' . _DB_PREFIX_ . 'orders` ord INNER JOIN `' . _DB_PREFIX_ . 'order_payment` pym
            ON (ord.reference = pym.order_reference) WHERE pym.transaction_id = "' . pSql($transactionId) . '"'
        );
    }
}
