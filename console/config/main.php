<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\console\controllers\FixtureController',
            'namespace' => 'common\fixtures',
        ],
        'async-worker' => [
            'class' => 'bazilio\async\commands\AsyncWorkerCommand',
        ],
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
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params' => $params,
];
