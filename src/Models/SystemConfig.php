<?php

namespace HXM\DatabaseSystemConfig\Models;

use \DateTime;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemConfig extends Model
{
    const VALUE_TABLES = ['int', 'bool', 'string', 'datetime', 'float', 'text'];
    protected $fillable = ['group', 'index', 'value_type', 'description'];
    protected $rawValue;

    function getValueAttribute()
    {
        return $this->rawValue;
    }

    protected function setValueAttribute($value)
    {
        $this->attributes['value_type'] = $this->parseValueType($value);
        $this->rawValue = $value;
    }

    function valueInstance()
    {
        $instance = $this->newRelatedInstance(SystemConfigValue::class);
        $instance->setTable(self::getValueTableDataByType($this->attributes['value_type'] ?? '')[0]);
        isset($this->attributes['value_type']) && $instance->mergeCasts(['value' =>  $this->attributes['value_type']]);

        $foreignKey = 'parent_id';

        $localKey = $this->getKeyName();

        return $this->newHasOne($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }

    static function getValueTableDataByType(string $dataType): array
    {
        $type = 'text';
        if (isset(static::VALUE_TABLES[$dataType]) || in_array($dataType, static::VALUE_TABLES))
            $type = static::VALUE_TABLES[$dataType] ?? $dataType;

        return [
            "system_config_{$type}_values", // name of values table
            "{$type}_value" //alias column value
        ];
    }

    protected function parseValueType($value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_integer($value)) {
            return 'int';
        }
        if (is_string($value) && Str::length($value) <= Schema::getFacadeRoot()::$defaultStringLength) {
            return 'string';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'array';
        }
        if ($value instanceof DateTime) {
            return 'datetime';
        }
        if ($value instanceof Collection) {
            return 'collection';
        }
        if (is_object($value)) {
            return 'object';
        }
        return 'text';
    }

    static function booted()
    {
        static::addGlobalScope('with-value', function (Builder $builder) {
            $model = $builder->getModel();
            $builder->select($model->qualifyColumn('*'));
            foreach (static::VALUE_TABLES as $type) {
                [$table, $aliasColumValue] = self::getValueTableDataByType($type);
                $builder->leftJoin($table, "{$table}.parent_id", '=', $model->qualifyColumn('id'));
                $builder->addSelect("$table.value as {$aliasColumValue}");
            }
        });

        static::saved(function (self $model) {

            if ($model->value_type == 'null') {
                $model->valueInstance()->delete();
                $model->setRelation('valueInstance', null);
                return;
            }

            $valueInstance = $model->valueInstance()->make();

            if (!$model->wasRecentlyCreated) {
                $valueInstance = $valueInstance->firstOrNew([$valueInstance->getKeyName() => $valueInstance->getKey()]);
            }

            $valueInstance->fill(['value' => $model->rawValue]);

            $valueInstance->save();

            $model->setRelation('valueInstance', $valueInstance);
        });

        static::retrieved(function (self $model) {
            $valueInstance = $model->valueInstance()->make(['value' => $model->attributes[self::getValueTableDataByType($model->attributes['value_type'] ?? '')[1]] ?? null]);
            $valueInstance->exists = true;
            $model->rawValue = $valueInstance->value;
            $model->setRelation('valueInstance', $valueInstance);
        });
    }
}
