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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'chat_model' => env('OPENROUTER_HELP_MODEL', 'openai/gpt-4o-mini'),
        'embedding_model' => env('OPENROUTER_EMBEDDING_MODEL', 'openai/text-embedding-3-small'),
        'site_url' => env('OPENROUTER_HTTP_REFERER', env('APP_URL', 'http://localhost')),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 240),
        'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 30),
        'embedding_retries' => (int) env('OPENROUTER_EMBEDDING_RETRIES', 2),
    ],

    'help_feedback' => [
        'notify_email' => env('HELP_FEEDBACK_NOTIFY_EMAIL'),
    ],

    'domain_assistant' => [
        'enabled' => (bool) env('DOMAIN_ASSISTANT_ENABLED', false),
        'model' => env('DOMAIN_ASSISTANT_MODEL', 'anthropic/claude-3.5-sonnet'),
        'tools_enabled' => (bool) env('DOMAIN_ASSISTANT_TOOLS_ENABLED', true),
        'daily_user_limit' => (int) env('DOMAIN_ASSISTANT_DAILY_LIMIT', 50),
        'history_messages' => (int) env('DOMAIN_ASSISTANT_HISTORY_MESSAGES', 24),
        'max_tool_iterations' => (int) env('DOMAIN_ASSISTANT_MAX_TOOL_ITERATIONS', 5),
    ],

];
