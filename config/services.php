<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mudurluk_classifier' => [
        'url' => env('ML_CLASSIFIER_URL', 'http://127.0.0.1:8100'),
        'api_key' => env('ML_CLASSIFIER_API_KEY'),
        'timeout' => env('ML_CLASSIFIER_TIMEOUT', 5),
        'python_path' => env('ML_PYTHON_PATH', base_path('ml/mudurluk-siniflandirici/.venv/bin/python')),
        'project_path' => env('ML_PROJECT_PATH', base_path('ml/mudurluk-siniflandirici')),
        'model_dir' => env('ML_MODEL_DIR', base_path('ml/mudurluk-siniflandirici/kaydedilen_model')),
        'retrain_timeout' => env('ML_RETRAIN_TIMEOUT', 1200),
        'retrain_via_queue' => env('ML_CLASSIFIER_RETRAIN_VIA_QUEUE', true),
    ],

];
