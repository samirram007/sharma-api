<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection for Modules
    |--------------------------------------------------------------------------
    |
    | This connection will be used for all modules that do not have a specific
    | mapping defined below. It defaults to the application's primary database
    | connection defined in the DB_CONNECTION environment variable.
    |
    */

    'default' => env('DB_CONNECTION', 'mariadb'),

    /*
    |--------------------------------------------------------------------------
    | Module-Specific Database Connections
    |--------------------------------------------------------------------------
    |
    | Here you may map individual modules to specific database connections.
    | The key should be the module name (e.g., "User", "StockItem", "Voucher"),
    | and the value should be a connection name defined in config/database.php.
    |
    | Example:
    |   'map' => [
    |       'Analytics' => 'mysql',
    |       'AuditLog'  => 'sqlite',
    |   ],
    |
    */

    'map' => [

    ],

];
