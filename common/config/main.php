<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'bootstrap' => [
        'queue', // The component registers its own console commands
    ],
    'container' => [
        'singletons' => [
            'singleton' => [
                'class' => 'console\components\Singleton'
            ],
            console\components\SingletonUser::Class => [
                'class' => 'console\components\SingletonUser'
            ],
            console\components\SingletonQueue::Class => [
                'class' => 'console\components\SingletonQueue'
            ],
        ],
    ],
    'components' => [
        // 'simple' => ['class' => 'console\components\Simple'],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
            'dataTimeout' => -1, // important for daemon and blocking queries
        ],
        'async' => [
            'class' => 'bazilio\async\AsyncComponent',
            'transportClass' => 'bazilio\async\transports\AsyncRedisTransport',
            'transportConfig' => [
                'connection' => 'redis',
            ]
        ],
        'queue' => [
            'class' => 'yii\queue\redis\Queue',
            'as log' => 'yii\queue\LogBehavior',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'session' => [
            'class' => 'yii\web\Session',
        ],
    ],
];
