<?php

declare(strict_types = 1);

namespace Lyzi\Builders;

use Configuration;
use Lyzi;
use Lyzi\Traits\HasBuilder;
use Options;
use Tools;

final class Config
{
    use HasBuilder;

    /** @var Lyzi */
    private $module;

    public function store()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    private function getConfigFormValues()
    {
        return [
            Options::WEBHOOK => Configuration::get(Options::WEBHOOK, null),
            Options::FORM_ID => Configuration::get(Options::FORM_ID, null),
        ];
    }

    public function setup(Lyzi $lyzi)
    {
        $this->module = $lyzi;

        return $this;
    }
}