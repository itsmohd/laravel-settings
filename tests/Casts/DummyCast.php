<?php

namespace Smartisan\Settings\Tests\Casts;

use Smartisan\Settings\Contracts\Castable;

class DummyCast implements Castable
{
    public function set(mixed $payload): string
    {
        return 'dummy value';
    }

    public function get(mixed $payload): string
    {
        return 'dummy value';
    }
}
