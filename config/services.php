<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

    // Configuration de l'API d'authentification centralisée
    'auth_api' => [
        'base_url' => env('AUTH_API_BASE_URL', 'http://authentication-api'),
    ],

    // Paramètres métier de la Crèche Petite Lune (issus de la fiche d'inscription officielle)
    'creche' => [
        'opening_time' => env('CRECHE_OPENING_TIME', '07:30'),
        'closing_time' => env('CRECHE_CLOSING_TIME', '18:30'),
        'pickup_window_start' => env('CRECHE_PICKUP_WINDOW_START', '18:00'),
        'overtime_hourly_rate' => (int) env('CRECHE_OVERTIME_HOURLY_RATE', 2000),
        'daily_meal_rate' => (int) env('CRECHE_DAILY_MEAL_RATE', 1500),
        'default_registration_fee' => (int) env('CRECHE_REGISTRATION_FEE', 50000),
    ],

];
