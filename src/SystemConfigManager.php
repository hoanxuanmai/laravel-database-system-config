<?php

namespace HXM\DatabaseSystemConfig;

use HXM\DatabaseSystemConfig\Models\SystemConfig;
use Illuminate\Support\Facades\Cache;

class SystemConfigManager
{
    /**
     * Summary of get
     * @param string $key
     * @return mixed
     */
    function get(string $key, $default = null, string $indexDefault = 'default')
    {
        [$key, $index] = $this->parseGroupIndex($key);
        $data = $this->_getCacheData($key);
        if (is_null($index) && $indexDefault != '' && isset($data[$indexDefault])) {
            return $data[$indexDefault];
        }
        return data_get($data, $index, $default);
    }

    function forget(string $group)
    {
        [$group, $index] = $this->parseGroupIndex($group);
        if (is_null($index)) {
            SystemConfig::withoutGlobalScopes()->where('group', $group)->delete();
        } else {
            SystemConfig::withoutGlobalScopes()->where('group', $group)->where('index', $index)->delete();
        }
        $this->_clearCache($group);
        return $this->all();
    }

    /**
     * Summary of set
     * @param string $group
     * @param mixed $value
     * @return void
     */
    function set(string $keyInput, $value): array
    {
        [$group, $index] = $this->parseGroupIndex($keyInput);
        if (is_null($index) && is_array($value)) {
            foreach ($value as $index => $vl) {
                $this->_saveToDatabase($group, $index, $vl);
            }
        } else {
            $this->_saveToDatabase($group, $index ?? 'default', $value);
        }
        $this->_clearCache($group);
        return $this->get($group);
    }

    function all(): array
    {
        return collect($this->groups())->mapWithKeys(function ($dt) {
            return [$dt => $this->get($dt)];
        })->toArray();
    }

    function groups($force = false): array
    {
        if ($force) {
            $this->_clearCache();
        }
        return Cache::rememberForever(static::class, function () {
            return SystemConfig::withoutGlobalScopes()->select('group')->distinct()->toBase()->get()->map(function ($dt) {
                return $dt->group;
            })->toArray();
        });
    }

    protected function _saveToDatabase(string $group, string $index, $value)
    {
        return tap(SystemConfig::firstOrNew(['group' => $group, 'index' => $index]), function ($instance) use ($value) {

            $instance->value = $value;

            $instance->save();
        });
    }


    protected function _clearCache(string $group = null): void
    {
        $group && Cache::forget($this->_getCacheKey($group));
        Cache::forget(static::class);
    }

    protected function _getCacheKey(string $group): string
    {
        return SystemConfig::class . "|{$group}";
    }


    protected function _getCacheData(string $group): array
    {
        return Cache::rememberForever($this->_getCacheKey($group), function () use ($group) {

            $data = [];

            SystemConfig::where('group', $group)
                ->oldest('updated_at')
                ->get()
                ->each(function (SystemConfig $model) use (&$data) {
                    data_set($data, $model->index, $model->value);
                });

            return $data;
        });
    }

    protected function parseGroupIndex(string $input)
    {
        preg_match('/(\w*)((\.+)(.*))?/', $input, $matches);
        $index = 'default';
        if ($matches) {
            $key = $matches[1];
            $index = $matches[4] ?? null;
        }
        return [$key, $index ?: null];
    }
}
