# Laravel Database System Config

Stored all configs on your database.

Able to store:
- value can be bool, array, object, datetime, int, float
- set flexible value with dot key
- merge the config value with the system, so that the data can be accessed via Laravel's ```config()``` helper


## Installation


```bash
composer require hxm/database-system-config
```


* Run migrations to create database table:
```bash
php artisan migrate
```

* Publishing the config file

```bash
php artisan vendor:publish --provider="HXM\DatabaseSystemConfig\Providers\DatabaseSystemConfigServiceProvider" --tag="database_system_config"
```

* to disable auto merge config to system, you set value of ```merge_config``` to ```false```;

```php
<?php 

return [
    'merge_config' => true, // change to false to disable it
    //If you disable it, you will not be able to access the value directly through the system config($key)
];
```

# Using

* to save a value into the system
```php
 \HXM\DatabaseSystemConfig\Facades\DatabaseSystemConfig::set('group.key.index', $value);
```

* to get a key:
```php
 \HXM\DatabaseSystemConfig\Facades\DatabaseSystemConfig::get('group.key.index', $defaultValue);
```
* to get all values:
```php
 \HXM\DatabaseSystemConfig\Facades\DatabaseSystemConfig::all();
```
* to get all groups:
```php
 \HXM\DatabaseSystemConfig\Facades\DatabaseSystemConfig::groups();
```

* If the config value of ```merge_config``` is ```true```, you can access the value with:
```php
 config()->get($key, $defaultValue);
```

## Please let me know if there is any problem or need any help. Your contribution is valuable to make the package better.


Please note currently for Laravel 7+ until tested and verified in lower versions. 
