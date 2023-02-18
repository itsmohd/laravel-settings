<?php

namespace Smartisan\Settings\Facades;

use Illuminate\Support\Facades\Facade;

class Settings extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'settings';
    }
}
