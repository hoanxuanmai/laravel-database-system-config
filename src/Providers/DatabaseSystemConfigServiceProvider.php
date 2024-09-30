<?php

namespace HXM\DatabaseSystemConfig\Providers;

use HXM\DatabaseSystemConfig\Facades\DatabaseSystemConfig;
use HXM\DatabaseSystemConfig\SystemConfigManager;
use Illuminate\Support\ServiceProvider;

class DatabaseSystemConfigServiceProvider extends ServiceProvider
{

    function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/database_system_config.php', 'database_system_config');

        $this->app->singleton('DatabaseSystemConfigManager', function ($app) {
            return new SystemConfigManager();
        });
    }

    function boot()
    {

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->publishes([
                __DIR__ . '/../../config/database_system_config.php' => config_path('database_system_config.php'),
            ], 'database_system_config');
        }


        if (config('database_system_config.merge_config')) {
            $this->doMergeConfig();
        }
    }

    protected function doMergeConfig()
    {
        $config = $this->app->get('config');
        foreach (DatabaseSystemConfig::groups() as $group) {
            $config->set($group, array_merge($config->get($group, []), DatabaseSystemConfig::get($group)));
        }
    }
}
