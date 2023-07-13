<?php

declare(strict_types = 1);

namespace Lyzi\Install;

use Configuration;
use Context;
use Options;
use PaymentModule;

final class Installer
{
    private $paymentModule;

    /**
     * List of hooks used in this Module
     */
    private $hooks = [
        'paymentOptions',
    ];

    public function __construct(PaymentModule $paymentModule)
    {
        $this->paymentModule = $paymentModule;
    }

    public static function make(PaymentModule $paymentModule)
    {
        return new Installer($paymentModule);
    }

    public function install()
    {
        return $this->paymentModule->registerHook($this->hooks)
               && !Configuration::updateValue(
                Options::WEBHOOK,
                Context::getContext()->link->getModuleLink(
                    'lyzi',
                    'webhook',
                    [],
                    true
                )
            );
    }

    public function uninstall()
    {
        return !Configuration::deleteByName(Options::FORM_ID)
               && !Configuration::deleteByName(Options::WEBHOOK);
    }
}