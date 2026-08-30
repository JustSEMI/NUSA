<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    /*
    |--------------------------------------------------------------------------
    | AI API Configuration (Anthropic-compatible)
    |--------------------------------------------------------------------------
    |
    | Generic AI API configuration supporting multiple providers.
    | Default: Shiteru.id (Anthropic-compatible)
    |
    */
    'ai' => [
        'base_url' => env('AI_BASE_URL'),
        'api_key' => env('AI_API_KEY'),
        'api_key2' => env('AI_API_KEY2'),
        'model' => env('AI_DEFAULT_MODEL', 'qwen-3.8-flash'),
        'max_tokens' => env('AI_MAX_TOKENS', 50000),
        'max_message_length' => env('AI_MAX_MESSAGE_LENGTH', 50000),
        'timeout' => env('AI_TIMEOUT', 60),
        'heartbeat_timeout' => env('AI_HEARTBEAT_TIMEOUT', 30),
        'context_limit' => env('AI_CONTEXT_LIMIT', 10),
        'available_models' => [
            'qwen-3.8-flash' => ['name' => 'Qwen 3.8 Flash', 'multiplier' => '0.75x'],
            'qwen-3.8-max' => ['name' => 'Qwen 3.8 Max', 'multiplier' => '1x'],
            'qwen-3.5-flash' => ['name' => 'Qwen 3.5 Flash', 'multiplier' => '1x'],
            'qwen-3.5-plus' => ['name' => 'Qwen 3.5 Plus', 'multiplier' => '1x'],
            'qwen-flash' => ['name' => 'Qwen Flash', 'multiplier' => '1x'],
            'grok-4.6' => ['name' => 'Grok 4.6', 'multiplier' => '1x'],
        ],
    ],

];
