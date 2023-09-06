<?php

declare(strict_types = 1);

use Lyzi\Helpers\Crypter;

final class LyziPaymentSetTransactionIdModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function postProcess()
    {
        $params = json_decode(file_get_contents('php://input'));

        $orderId = Crypter::decrypt($params->orderId);
        $transactionId = $params->transactionId;

        $order = new Order($orderId);

        /**
         * @var OrderPayment $payment
         */
        $payment = $order->getOrderPayments()[0];
        $payment->transaction_id = $transactionId;
        $payment->save();
    }
}
