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
                        'GET set-chat-push-token' => 'set-chat-push-token',
                        'GET get-users' => 'get-users',
                        'POST update-user' => 'update-user',
                        'POST update-profile' => 'update-profile',
                        'GET new-password' => 'new-password',
                        'GET set-user-status' => 'set-user-status',
                        'GET get-code' => 'get-code',
                        'GET send-code' => 'send-code',
                        'GET set-role' => 'set-role',
                        'GET appointment-doctor' => 'appointment-doctor',
                        'GET cancel-appointment-doctor' => 'cancel-appointment-doctor',
                        'GET cancel-appointment' => 'cancel-appointment',
                        'GET approve-appointment' => 'approve-appointment',
                        'GET end-appointment' => 'end-appointment',
                        'GET get-my-patients' => 'get-my-patients',
                        'GET get-abonents' => 'get-abonents',
                        'GET get-doctors' => 'get-doctors',
                        'POST save-doctor-by-admin' => 'save-doctor-by-admin',
                        'GET set-voip-token' => 'set-voip-token',
                        'GET call-user' => 'call-user',
                        'GET hungup-user' => 'hungup-user',
                        'GET set-user-timezone' => 'set-user-timezone',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/helper',
                    'extraPatterns' => [
                        'GET geocoder' => 'geocoder',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/units',
                    'extraPatterns' => [
                        'GET get-units' => 'get-units',
                        'POST save' => 'save',
                        'GET delete-unit' => 'delete-unit',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/anketa',
                    'extraPatterns' => [
                        'GET get-parts' => 'get-parts',
                        'POST save' => 'save',
                        'GET delete-part' => 'delete-part',
                        'GET get-sections' => 'get-sections',
                        'POST save-section' => 'save-section',
                        'GET delete-section' => 'delete-section',
                        'GET get-questions' => 'get-questions',
                        'POST save-question' => 'save-question',
                        'GET delete-question' => 'delete-question',
                        'GET get-avatar' => 'get-avatar',
                        'GET save-answer' => 'save-answer',
                        'GET get-avatar-for-pacient' => 'get-avatar-for-pacient',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/pacient',
                    'extraPatterns' => [
                        'GET get-my-pacients' => 'get-my-pacients',
                        'GET get-my-notes' => 'get-my-notes',
                        'GET delete-note' => 'delete-note',
                        'GET note-pacient' => 'note-pacient',
                        'GET notify' => 'notify',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/pill',
                    'extraPatterns' => [
                        'POST pill-to-pacient' => 'pill-to-pacient',
                        'POST pill-by-pacient' => 'pill-by-pacient',
                        'GET delete-pill' => 'delete-pill',
                        'GET notify-pill' => 'notify-pill',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/diets',
                    'extraPatterns' => [
                        'GET get-diets' => 'get-diets',
                        'GET get-diet-by-id' => 'get-diet-by-id',
                        'POST save' => 'save',
                        'GET delete-diet' => 'delete-diet',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/tasks',
                    'extraPatterns' => [
                        'GET get-tasks' => 'get-tasks',
                        'POST save' => 'save',
                        'GET delete-task' => 'delete-task',
                        'GET update-tasks' => 'update-tasks',
                        'GET set-diet-by-doctor' => 'set-diet-by-doctor',
                        'GET set-food-by-doctor' => 'set-food-by-doctor',
                        'GET set-food-by-pacient' => 'set-food-by-pacient',
                        'GET set-water-by-doctor' => 'set-water-by-doctor',
                        'GET set-water-by-pacient' => 'set-water-by-pacient',
                        'GET get-user-tasks-by-doctor' => 'get-user-tasks-by-doctor',
                        'GET get-user-tasks-by-user' => 'get-user-tasks-by-user',
                        'GET delete-task-by-doctor' => 'delete-task-by-doctor',
                        'GET delete-task-by-pacient' => 'delete-task-by-pacient',
                        'GET pacient-allow-pill' => 'pacient-allow-pill',
                        'GET pacient-deny-pill' => 'pacient-deny-pill',
                        'GET set-param-for-task-by-pacient' => 'set-param-for-task-by-pacient',
                        'GET set-param-for-monitor-by-pacient' => 'set-param-for-monitor-by-pacient',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/anketa-balls',
                    'extraPatterns' => [
                        'GET get-balls' => 'get-balls',
                        'POST save' => 'save',
                        'GET delete-ball' => 'delete-ball',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/transactions',
                    'extraPatterns' => [
                        'GET payed-pacient' => 'payed-pacient',
                        'GET update-statuses' => 'update-statuses',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/analis',
                    'extraPatterns' => [
                        'GET get-analisys' => 'get-analisys',
                        'POST save' => 'save',
                        'GET delete-analis' => 'delete-analis',
                        'GET set-analis-to-pacient' => 'set-analis-to-pacient',
                        'GET get-analisys-for-pacient' => 'get-analisys-for-pacient',
                        'GET get-analis-for-pacient' => 'get-analis-for-pacient',
                        'GET get-my-analis' => 'get-my-analis',
                        'GET set-analis-by-pacient' => 'set-analis-by-pacient',
                        'GET set-param-by-pacient' => 'set-param-by-pacient',
                        'POST upload-photo' => 'upload-photo',
                        'GET get-photos' => 'get-photos',
                        'GET get-photos-by-pacient' => 'get-photos-by-pacient',
                        'GET set-norm-for-analis-by-doctor' => 'set-norm-for-analis-by-doctor',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/analisys-group',
                    'extraPatterns' => [
                        'GET get-analisys-group' => 'get-analisys-group',
                        'POST save' => 'save',
                        'GET delete-analisys-group' => 'delete-analisys-group',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/specialities',
                    'extraPatterns' => [
                        'GET get-specs' => 'get-specs',
                        'POST save' => 'save',
                        'GET delete-spec' => 'delete-spec',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/rank',
                    'extraPatterns' => [
                        'GET get-ranks' => 'get-ranks',
                        'POST save' => 'save',
                        'GET delete-rank' => 'delete-rank',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/digree',
                    'extraPatterns' => [
                        'GET get-digrees' => 'get-digrees',
                        'POST save' => 'save',
                        'GET delete-digree' => 'delete-digree',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/settings',
                    'extraPatterns' => [
                        'GET get-permissions' => 'get-permissions',
                        'POST update-permission' => 'update-permission',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/polar',
                    'extraPatterns' => [
                        'GET create-webhook' => 'create-webhook',
                        'POST webhook' => 'webhook',
                        'GET create-activity-transaction' => 'create-activity-transaction',
                        'GET get-url' => 'get-url',
                        'GET register' => 'register',
                        'GET get-notifications' => 'get-notifications',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/garmin',
                    'extraPatterns' => [
                        'GET get-url' => 'get-url',
                        'POST dailies' => 'dailies',
                        'POST dailies2' => 'dailies2',
                        'GET test' => 'test',
                        'GET request-get' => 'request-get',
                        'GET callback' => 'callback',
                        'GET get-notifications' => 'get-notifications',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/monitor',
                    'extraPatterns' => [
                        'GET get-monitors' => 'get-monitors',
                        'POST save' => 'save',
                        'GET delete-monitor' => 'delete-monitor',
                        'GET set-steps' => 'set-steps',
                        'GET set-norm-for-monitor-by-doctor' => 'set-norm-for-monitor-by-doctor',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/notes',
                    'extraPatterns' => [
                        'GET get-notes' => 'get-notes',
                        'POST save' => 'save',
                        'GET delete-note' => 'delete-note',
                        'GET get-diary' => 'get-diary',
                        'POST save-diary' => 'save-diary',
                        'GET delete-diary' => 'delete-diary',
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/chat',
                    'extraPatterns' => [
                        'GET get-chats' => 'get-chats',
                        'GET get-chat-with-user' => 'get-chat-with-user',
                        'GET get-chat-photos-with-user' => 'get-chat-photos-with-user',
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
                        'POST register-patient' => 'register-patient',
                        'POST register-doctor' => 'register-doctor',
                        'POST save-patient' => 'save-patient',
                        'POST save-doctor' => 'save-doctor',
                        'GET get-profile' => 'get-profile',
                        'GET get-doctors' => 'get-doctors',
                        'GET get-doctor' => 'get-doctor',
                        'POST set-webrtc-data' => 'set-webrtc-data',
                        'GET get-monitors' => 'get-monitors',
                        'GET get-pacient-monitors' => 'get-pacient-monitors',
                        'GET get-pacient-monitor-by-id' => 'get-pacient-monitor-by-id',
                        'GET get-all-monitors' => 'get-all-monitors',
                        'GET get-all-monitors-for-pacient' => 'get-all-monitors-for-pacient',
                        'GET set-source' => 'set-source',
                        'POST update-my-monitors' => 'update-my-monitors',
                        'POST update-pacient-monitors' => 'update-pacient-monitors',
                        'GET enter-monitor-param' => 'enter-monitor-param',
                        'GET get-picture' => 'get-picture',
                        'GET get-patient-history' => 'get-patient-history',
                        'GET get-specs' => 'get-specs',
                        'GET get-degree' => 'get-degree',
                        'GET get-category' => 'get-category',
                        'GET set-notification' => 'set-notification',
                    ]
                ]
            ],
        ],
    ],
    'params' => $params,
];
