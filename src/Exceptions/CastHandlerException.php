<?php

namespace Smartisan\Settings\Exceptions;

use Exception;

class CastHandlerException extends Exception
{
    /**
     * Create invalid cast handler exception instance.
     */
    public static function invalid(string $handler): CastHandlerException
    {
        return new self("Cast handler $handler is invalid. Make sure the cast handler implements Castable interface.");
    }

    /**
     * Create missing cast handler exception instance.
     */
    public static function missing(string $castType): CastHandlerException
    {
        return new self("Cast handler for $castType is missing. Make sure to set the handler in settings config file.");
    }
}
