<?php

declare(strict_types = 1);

namespace Lyzi\Install;

use Configuration;
use Context;
use Lyzi\Enums\Options;
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
        return Configuration::updateValue(
            Options::WEBHOOK,
            Context::getContext()->link->getModuleLink(
                'lyzi',
                'paymentok',
                [],
                true
            )
        ) && $this->paymentModule->registerHook($this->hooks);
    }

    public function uninstall()
    {
        return Configuration::deleteByName(Options::FORM_ID)
               && Configuration::deleteByName(Options::WEBHOOK);
    }
}
