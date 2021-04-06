<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'name' => 'My pubs',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'basePath' => '@app/modules/v1',
            'class' => 'api\modules\v1\Module'
        ]
    ],
    'components' => [
        'user' => [
            'identityClass' => 'common\models\User',
            'enableSession' => false,
            'loginUrl' => null,
            'enableAutoLogin' => false,
        ],
        'client' => [
            'class' => 'yii\web\User',
            'identityClass' => 'common\models\Client',
            'enableSession' => false,
            'loginUrl' => null,
            'enableAutoLogin' => false,
        ],
        'request' => [
            'class' => '\yii\web\Request',
            'enableCookieValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/user',
                    'extraPatterns' => [
                        'GET login' => 'login',
                        'GET logout' => 'logout',
                        'GET get-profile' => 'get-profile',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/helper',
                    'extraPatterns' => [
                        'GET geocoder' => 'geocoder',
                        'GET search-address' => 'search-address',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/databases',
                    'extraPatterns' => [
                        'GET get-databases' => 'get-databases',
                        'POST save' => 'save',
                        'GET delete-database' => 'delete-database',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/dic-enum',
                    'extraPatterns' => [
                        'GET get-enums' => 'get-enums',
                        'POST save' => 'save',
                        'GET delete-enum' => 'delete-enum',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/firmware',
                    'extraPatterns' => [
                        'POST upload-firmware' => 'upload-firmware',
                        'GET get-firmwares' => 'get-firmwares',
                        'POST save' => 'save',
                        'GET delete-firmware' => 'delete-firmware',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/settings',
                    'extraPatterns' => [
                        'GET get-settings' => 'get-settings',
                        'POST save' => 'save',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/engineer',
                    'extraPatterns' => [
                        'GET get-engineers' => 'get-engineers',
                        'POST save' => 'save',
                        'GET delete-engineer' => 'delete-engineer',
                        'GET set-engineer-status' => 'set-engineer-status',
                        'GET new-password' => 'new-password',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/manager',
                    'extraPatterns' => [
                        'GET get-managers' => 'get-managers',
                        'POST save' => 'save',
                        'GET delete-manager' => 'delete-manager',
                        'GET set-manager-status' => 'set-manager-status',
                        'GET new-password' => 'new-password',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/partner',
                    'extraPatterns' => [
                        'GET get-partners' => 'get-partners',
                        'POST save' => 'save',
                        'GET delete-partner' => 'delete-partner',
                        'GET set-partner-status' => 'set-partner-status',
                        'GET new-password' => 'new-password',
                        'GET test' => 'test',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/models',
                    'extraPatterns' => [
                        'GET get-models' => 'get-models',
                        'POST save' => 'save',
                        'GET delete-model' => 'delete-model',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/device',
                    'extraPatterns' => [
                        'GET get-devices' => 'get-devices',
                        'POST save' => 'save',
                        'GET delete-device' => 'delete-device',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/permissions',
                    'extraPatterns' => [
                        'GET get-permissions' => 'get-permissions',
                        'POST update-permission' => 'update-permission',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/api',
                    'extraPatterns' => [
                        'GET gray-image' => 'gray-image',
                        'GET get-code' => 'get-code',
                        'GET login' => 'login',
                        'GET logout' => 'logout',
                    ]
                ]
            ],
        ],
    ],
    'params' => $params,
];
