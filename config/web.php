<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'glass-system',
    'name' => 'GlassSystem',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'pl-PL',
    'defaultRoute' => 'site/index',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'ZMIEN-TO-NA-LOSOWY-CIAG-ZNAKOW',
            'csrfParam' => '_csrf-taskreminder',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-taskreminder', 'httpOnly' => true],
        ],
        'session' => [
            'name' => 'taskreminder-session',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => (bool)(getenv('MAILER_USE_FILE_TRANSPORT') ?? true),
            'transport' => [
                'scheme' => getenv('SMTP_ENCRYPTION') ?: 'tls',
                'host' => getenv('SMTP_HOST') ?: 'localhost',
                'username' => getenv('SMTP_USERNAME') ?: '',
                'password' => getenv('SMTP_PASSWORD') ?: '',
                'port' => (int)(getenv('SMTP_PORT') ?: 587),
            ],
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
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'dashboard/index',
                'dashboard/mobile' => 'dashboard/mobile',
                'task/<id:\d+>' => 'task/view',
                'task/create' => 'task/create',
                'task/<id:\d+>/update' => 'task/update',
                'task/<id:\d+>/delete' => 'task/delete',
                'task/<id:\d+>/run' => 'task/run',
                'task/<id:\d+>/complete' => 'task/complete',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;