<?php

namespace HXM\DatabaseSystemConfig\Models;

use \DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemConfig extends Model
{
    const VALUE_TABLES = ['int', 'bool', 'string', 'datetime', 'float', 'text'];
    protected $fillable = ['group', 'index', 'value_type', 'description', 'value'];
    protected $rawValue;

    protected $cacheValueInstance = [];
    function getValueAttribute()
    {
        return $this->rawValue;
    }

    protected function setValueAttribute($value)
    {
        $this->attributes['value_type'] = $this->parseValueType($value);
        $this->rawValue = $value;
    }

    public function valueInstance(string $value_type = null): HasOne
    {
        $instanceType = $value_type ?? $this->attributes['value_type'] ?? '';
        if (isset($this->cacheValueInstance[$instanceType])) {
            return $this->cacheValueInstance[$instanceType];
        }

        $instance = $this->newRelatedInstance(SystemConfigValue::class);

        $instance->setTable(self::getValueTableDataByType($instanceType)[0]);
        empty($instanceType) || $instance->mergeCasts(['value' =>  $instanceType]);

        $foreignKey = 'parent_id';

        $localKey = $this->getKeyName();

        return $this->cacheValueInstance[$instanceType] = $this->newHasOne($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }

    public static function getValueTableDataByType(string $dataType): array
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
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_float($value) || is_double($value)) {
            return 'float';
        }
        if (is_numeric($value) && is_int($value)) {
            return 'int';
        }
        if (is_string($value) && Str::length($value) <= Schema::getFacadeRoot()::$defaultStringLength) {
            return 'string';
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

    public static function booted()
    {

        static::saved(function (self $model) {

            if ($model->value_type == 'null') {
                if($model->index === 'default') {
                   self::where('group', $model->group)->where('index', '!=', 'default')->delete();
                } 
                $model->valueInstance()->delete();      
                $model->setRelation('valueInstance', null);
                return;
            }
            if ($model->wasChanged('value_type')){
                $model->valueInstance($model->getRawOriginal('value_type'))->delete();
            }
            $relation = $model->valueInstance();

            $valueInstance = $model->valueInstance()->updateOrCreate([$relation->getForeignKeyName() => $model->getKey()],['value' => $model->rawValue]);

            $model->setRelation('valueInstance', $valueInstance);
        });
    }
}
