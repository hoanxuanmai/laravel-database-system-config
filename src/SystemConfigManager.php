<?php

namespace HXM\DatabaseSystemConfig;

use HXM\DatabaseSystemConfig\Models\SystemConfig;
use HXM\DatabaseSystemConfig\Models\SystemConfigValue;
use Illuminate\Support\Facades\Cache;

class SystemConfigManager
{
    protected $_cacheSystemConfigValuessByGroup = null;
    protected $_cacheSystemConfigValuessByType = [];

    /**
     * Summary of get
     * @param string $key
     * @return mixed
     */
    public function get(string $key, $default = null, string $indexDefault = 'default')
    {
        [$key, $index] = $this->parseGroupIndex($key);
        $data = $this->_getCacheData($key);

        if (is_null($index) && $indexDefault != '' && count($data) == 1  && array_key_exists($indexDefault, $data)) {
            return $data[$indexDefault];
        }
        return data_get($data, $index, $default);
    }

    /**
     * Delete all
     *
     * @param string $group
     * @return void
     */
    public function forget(string $group)
    {
        [$group, $index] = $this->parseGroupIndex($group);
        if (is_null($index)) {
            SystemConfig::where('group', $group)->delete();
        } else {
            SystemConfig::where('group', $group)->where('index', $index)->delete();
        }
        $this->_clearCache($group);
        return $this->all();
    }

    /**
     * Summary of set
     * @param string $group
     * @param mixed $value
     * @return mixed
     */
    public function set(string $keyInput, $value)
    {
        [$group, $index] = $this->parseGroupIndex($keyInput);

        if (is_null($index) && is_array($value)) {
            SystemConfig::where('group', $group)->delete();
            foreach ($value as $index => $vl) {
                $this->_saveToDatabase($group, $index, $vl);
            }
        } else {
            if (is_null($index)) {
                SystemConfig::where('group', $group)->where('index', '!=', 'default')->delete();
            }
            $this->_saveToDatabase($group, $index ?? 'default', $value);
        }
        
        $this->_clearCache($group);
        
        return $this->get($keyInput);
    }

    public function all(): array
    {
        return collect($this->groups())->mapWithKeys(function ($dt) {
            return [$dt => $this->get($dt, [], '')];
        })->toArray();
    }

    public function groups($force = false): array
    {
        if ($force) {
            $this->_clearCache();
        }

        return Cache::rememberForever(static::class, function () {
            $data = [];
            foreach ($this->_getSystemConfigsByGroup() as $group => $systemConfigs) {

                $this->_getCacheData($group, $systemConfigs);
                $data[] = $group;
            };
            return $data;
        });
    }

    protected function _getSystemConfigsByGroup($group = null): array
    {
        $this->_cacheSystemConfigValuessByGroup == null && $this->_cacheSystemConfigValuessByGroup = SystemConfig::toBase()->oldest()->get()->groupBy('group')->toArray();
        if ($group && !isset($this->_cacheSystemConfigValuessByGroup[$group])) {
            $this->_cacheSystemConfigValuessByGroup[$group] = SystemConfig::toBase()->where('group', $group)->oldest()->get()->toArray();
        }
        return $group == null ? $this->_cacheSystemConfigValuessByGroup : $this->_cacheSystemConfigValuessByGroup[$group];
    }

    protected function _getSystemConfigValuessByType(string $type): array
    {
        if($type === 'null') {
            return [];
        }
        $table = SystemConfig::getValueTableDataByType($type)[0];
        if (isset($this->_cacheSystemConfigValuessByType[$table])) {
            return $this->_cacheSystemConfigValuessByType[$table];
        }
        
        $this->_cacheSystemConfigValuessByType[$table] = (new SystemConfigValue())->setTable($table)->toBase()->get()->pluck('value', 'parent_id')->toArray();
        return $this->_cacheSystemConfigValuessByType[$table];
    }

    protected function _saveToDatabase(string $group, string $index, $value)
    {
        $systemConfig = SystemConfig::updateOrCreate(
            ['group' => $group, 'index' => $index],
            ['value' => $value]
        );

        if (isset($this->_cacheSystemConfigValuessByType[$systemConfig->value_type])) {
            $this->_cacheSystemConfigValuessByType[$systemConfig->value_type][$systemConfig->id] = $value;
        }
    }


    protected function _clearCache(string $group = null): void
    {
        if ($group != null) {
            Cache::forget($this->_getCacheKey($group));
            unset($this->_cacheSystemConfigValuessByGroup[$group]);
        }
        Cache::forget(static::class);
    }

    protected function _getCacheKey(string $group): string
    {
        return SystemConfig::class . "|{$group}";
    }


    protected function _getCacheData(string $group, $initValues = null): array
    {

        return Cache::rememberForever($this->_getCacheKey($group), function () use ($group, $initValues) {

            $data = [];
            if ($initValues != null) {
                $systemConfigs = collect($initValues);
            } else {
                $systemConfigs = collect($this->_getSystemConfigsByGroup($group) ?? []);
            }
            
            foreach ($systemConfigs->groupBy('value_type') as $type => $collection) {
               
                $systemConfigValues = $this->_getSystemConfigValuessByType($type);
                $collection->each(function ($model) use (&$data, $systemConfigValues) {
                    $value = $systemConfigValues[$model->id] ?? null;
                    if ($value !== null) {
                        $value = (new SystemConfig())
                            ->forceFill((array) $model)
                            ->valueInstance()
                            ->make(['parent_id' => $model->id, 'value' => $value])
                            ->value;
                    }    
                
                    data_set($data, $model->index, $value);
                });
            }
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
