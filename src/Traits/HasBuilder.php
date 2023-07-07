<?php

declare(strict_types = 1);

namespace Lizy\Traits;

trait HasBuilder
{
    public static function make()
    {
        return new static();
    }
}