<?php

namespace HXM\DatabaseSystemConfig\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Summary of SystemConfig
 * @method static mixed get(string $key, mixed $default = null, string $defaultIndex = 'default')
 * @method static array all()
 * @method static array groups()
 * @method static array forget(string $key)
 * @method static array set(string $key, mixed $value)
 *
 * @see \HXM\DatabaseSystemConfig\SystemConfigManager
 */
class DatabaseSystemConfig extends Facade
{
    static function getFacadeAccessor()
    {
        return 'DatabaseSystemConfigManager';
    }
}
