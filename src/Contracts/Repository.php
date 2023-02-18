<?php

namespace Smartisan\Settings\Contracts;

interface Repository
{
    /**
     * Retrieve settings entry for the given key.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function get(string|array $key, mixed $default = null): mixed;

    /**
     * Store settings entry for the given key.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function set(string|array $key, mixed $value = null): void;

    /**
     * Destroy the settings entry for the given key.
     */
    public function forget(string|array $key): void;

    /**
     * Retrieve all settings entry.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function all(): array;
}
