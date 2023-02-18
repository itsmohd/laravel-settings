<?php

namespace Smartisan\Settings;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Model;
use Smartisan\Settings\Contracts\Repository;
use Smartisan\Settings\Exceptions\CastHandlerException;

class Settings
{
    /**
     * Settings repository instance.
     */
    protected Repository $repository;

    /**
     * Application cache repository instance.
     */
    protected CacheRepository $cache;

    /**
     * Settings entry filter instance.
     */
    protected EntryFilter $filter;

    /**
     * Create a new settings manager instance.
     */
    public function __construct(Repository $repository, CacheRepository $cache)
    {
        $this->repository = $repository;

        $this->cache = $cache;

        $this->filter = app(EntryFilter::class);
    }

    /**
     * Store settings entry for the given key.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function set(string|array $key, mixed $value = null): void
    {
        $this->forgetCacheIfEnabled($key);

        $this->repository
            ->withFilter($this->filter)
            ->set($key, $value);

        $this->filter->clear();
    }

    /**
     * Retrieve settings entry for the given key.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function get(string|array $key, mixed $default = null): mixed
    {
        if (config('settings.cache.enabled')) {
            return $this->cache->rememberForever($this->resolveCacheKey($key), function () use ($key, $default) {
                return $this->getEntries($key, $default);
            });
        }

        return $this->getEntries($key, $default);
    }

    /**
     * Destroy the settings entry for the given key.
     */
    public function forget(string|array $key): void
    {
        $this->forgetCacheIfEnabled($key);

        $this->repository
            ->withFilter($this->filter)
            ->forget($key);

        $this->filter->clear();
    }

    /**
     * Retrieve all settings entry.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function all(): array
    {
        if (config('settings.cache.enabled')) {
            return $this->cache->rememberForever($this->resolveCacheKey(null), function () {
                return $this->getAllEntries();
            });
        }

        return $this->getAllEntries();
    }

    /**
     * Determines whether the given settings entry exists or not.
     */
    public function exists(string $key): bool
    {
        $entry = $this->get($key);

        return isset($entry);
    }

    /**
     * Set the model owner of the settings entry.
     */
    public function for(Model $model): Settings
    {
        $this->filter->setModel($model);

        return $this;
    }

    /**
     * Set the group name of the settings entry.
     */
    public function group(string $name): Settings
    {
        $this->filter->setGroup($name);

        return $this;
    }

    /**
     * Set the exempted settings entries.
     */
    public function except(string|array ...$excepts): Settings
    {
        $this->filter->setExcepts(...$excepts);

        return $this;
    }

    /**
     * Resolve settings entry caching key.
     */
    public function resolveCacheKey(string|null|array $keys): string
    {
        $prefix = config('settings.cache.prefix');

        $keys = is_array($keys) ? implode(',', $keys) : $keys;

        $group = $this->filter->getGroup();

        $for = $this->filter->getModel() ? get_class($this->filter->getModel()) : null;

        $excepts = implode(',', $this->filter->getExcepts());

        return "${prefix}settings.keys=${keys}&group=${group}&excepts=${excepts}&for=$for";
    }

    /**
     * Retrieve the evalulated settings entries for the given key.
     */
    protected function getEntries(string|array $key, mixed $default): mixed
    {
        $payload = $this->repository
            ->withFilter($this->filter)
            ->get($key, $default);

        $this->filter->clear();

        $this->stripSettingsPayload($payload);

        return $payload;
    }

    /**
     * Retrieve all settings entries.
     */
    protected function getAllEntries(): array
    {
        $payload = $this->repository
            ->withFilter($this->filter)
            ->all();

        $this->filter->clear();

        $this->stripSettingsPayload($payload);

        return $payload;
    }

    /**
     * Clear the given caching key values.
     */
    protected function forgetCacheIfEnabled(string|array $key): void
    {
        if (config('settings.cache.enabled')) {
            $cacheKey = $this->resolveCacheKey(is_array($key) ? array_keys($key) : $key);

            if ($this->cache->has($cacheKey)) {
                $this->cache->forget($cacheKey);
            }
        }
    }

    /**
     * Evaluate the payload and strip additional attributes.
     */
    protected function stripSettingsPayload(string|null|array &$payload)
    {
        if (is_array($payload)) {
            if (array_key_exists('$value', $payload) && array_key_exists('$cast', $payload)) {
                $castType = $payload['$cast'];

                if ($castType) {
                    if (! array_key_exists($castType, config('settings.casts'))) {
                        throw CastHandlerException::missing($castType);
                    }

                    return $payload = app(config('settings.casts')[$castType])
                        ->get($payload['$value']);
                }

                $payload = $payload['$value'];
            } else {
                array_walk($payload, [$this, 'stripSettingsPayload']);
            }
        }
    }
}
