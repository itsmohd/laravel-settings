<?php

namespace Smartisan\Settings\Casts;

use Carbon\Carbon;
use Smartisan\Settings\Contracts\Castable;

class CarbonCast implements Castable
{
    /**
     * Apply casting rules when storing the payload into the settings repository.
     *
     * @param  Carbon  $payload
     */
    public function set(mixed $payload): string
    {
        return $payload->format(DATE_ATOM);
    }

    /**
     * Apply casting rules when retrieving the payload from the settings repository.
     *
     * @param  string  $payload
     */
    public function get(mixed $payload): Carbon
    {
        return new Carbon($payload);
    }
}
