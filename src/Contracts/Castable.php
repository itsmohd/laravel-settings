<?php

namespace Smartisan\Settings\Contracts;

interface Castable
{
    /**
     * Apply casting rules when storing the payload into the settings repository.
     */
    public function set(mixed $payload): mixed;

    /**
     * Apply casting rules when retrieving the payload from the settings repository.
     */
    public function get(mixed $payload): mixed;
}
