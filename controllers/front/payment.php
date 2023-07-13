<?php

declare(strict_types = 1);

final class LyziPaymentModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('module:lyzi/views/templates/front/payment.tpl');
    }
}