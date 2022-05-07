<?php
    return [
            //'settings' => [
                'debug' => true,
                'determineRouteBeforeAppMiddleware' => true,
                'displayErrorDetails' => true,
                'view' => [
                    'path' => __DIR__ . '/../templates/views',
                    'twig' => [
                    'cache' => false
                    ]
                ],
                'db_msqlsrv' => [
                    'driver' => 'sqlserver',
                    'host' => '172.16.127.33',
                    'port' => '1433',
                    'database' => 'IntercomAdminDb',
                    'username' => 'sistemas',
                    'password' => '1nt3rc0m*',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                ],
                'db_mysql' => [
                    'driver' => 'mysql',
                    'host' => '172.25.0.13:7999',
                    'database' => 'xtream_iptvpro',
                    'username' => 'user_iptvpro',
                    'password' => 'J6b1xAaAPJZL6b6gDlO',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                ],
                'api_unms' => '85ccde42-3fcc-431c-b8e4-e4cb0de056a8'
            //]
    ];