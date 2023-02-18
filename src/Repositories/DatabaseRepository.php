<?php

namespace Smartisan\Settings\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Smartisan\Settings\CastHandler;

class DatabaseRepository extends Repository
{
    /**
     * The database connection.
     */
    protected string|null $connection;

    /**
     * The database table name.
     */
    protected string|null $table;

    /**
     * Entries cast handler instance.
     */
    protected CastHandler $castHandler;

    /**
     * Create a new database repository instance.
     */
    public function __construct()
    {
        $this->connection = config('database.connection');

        $this->table = config('settings.repositories.database.table');

        $this->castHandler = app(CastHandler::class);
    }

    /**
     * Retrieve settings entry for the given key.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function get(string|array $key, mixed $default = null): mixed
    {
        $entries = $this->getEntries($key, $default);

        if (is_array($key)) {
            return $entries->toArray();
        }

        if ($entries->isNotEmpty()) {
            return $entries->first();
        }

        return $default;
    }

    /**
     * Store settings entry for the given key.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function set(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            collect($key)->each(function ($value, $key) {
                $this->updateOrInsertSettingsEntry($key, $value);
            });

            return;
        }

        $this->updateOrInsertSettingsEntry($key, $value);
    }

    /**
     * Destroy the settings entry for the given key.
     */
    public function forget(string|array $key): void
    {
        $key = collect($key);

        $model = $this->entryFilter->getModel();

        DB::connection($this->connection)
            ->table($this->table)
            ->whereIn('key', $key->toArray())
            ->where(function (Builder $builder) use ($model) {
                if ($model) {
                    return $builder->where('settingable_type', get_class($model))
                        ->where('settingable_id', $model->getKey());
                }

                return $builder->where('settingable_type', null)
                    ->where('settingable_id', null);
            })
            ->delete();
    }

    /**
     * Retrieve all settings entry.
     * The configured values of entry filter will be used to filter the settings entries.
     */
    public function all(): array
    {
        return $this->getEntries(null, null)->toArray();
    }

    /**
     * Retrieve the evalulated settings entries for the given key.
     */
    protected function getEntries(string|null|array $keys, mixed $default): Collection
    {
        $keys = collect($keys);

        $group = $this->entryFilter->getGroup();

        $model = $this->entryFilter->getModel();

        $excepts = $this->entryFilter->getExcepts();

        return DB::connection($this->connection)
            ->table($this->table)
            ->when($keys->count(), function (Builder $builder) use ($keys) {
                $builder->whereIn('key', $keys);
            })
            ->when(count($excepts), function (Builder $builder) use ($excepts) {
                $builder->whereNotIn('key', $excepts);
            })
            ->where('group', $group)
            ->where(function (Builder $builder) use ($model) {
                if ($model) {
                    return $builder->where('settingable_type', get_class($model))
                        ->where('settingable_id', $model->getKey());
                }

                return $builder->where('settingable_type', null)
                    ->where('settingable_id', null);
            })
            ->pluck('payload', 'key')
            ->when($keys->count() === 0, function (Collection $collection) {
                return $collection->flatMap(function ($payload, $key) {
                    return [$key => json_decode($payload, true)];
                });
            })
            ->when($keys->count(), function ($collection) use ($keys, $default) {
                return $collection->pipe(function ($entries) use ($keys, $default) {
                    return $keys->flatMap(function ($key) use ($entries, $default) {
                        if ($entries->has($key)) {
                            return [$key => json_decode($entries->get($key), true)];
                        }

                        return [$key => $default];
                    });
                });
            });
    }

    /**
     * Update the given settings entry or insert when it does not exist.
     */
    protected function updateOrInsertSettingsEntry(string $key, mixed $value): bool
    {
        return DB::connection($this->connection)
            ->table($this->table)
            ->updateOrInsert([
                'key' => $key,
                'group' => $this->entryFilter->getGroup(),
                'settingable_type' => $this->entryFilter->getModel() ? get_class($this->entryFilter->getModel()) : null,
                'settingable_id' => $this->entryFilter->getModel() ? $this->entryFilter->getModel()->getKey() : null,
            ], [
                'payload' => json_encode($this->castHandler->handle($value)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
