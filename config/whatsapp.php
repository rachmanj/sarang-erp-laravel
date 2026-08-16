<?php

return [
    'provider' => env('WA_PROVIDER', 'fonnte'),
    'api_key' => env('WA_API_KEY', ''),
    'sender_number' => env('WA_SENDER_NUMBER', ''),
    'owner_phone' => env('WA_OWNER_PHONE', ''),
    'daily_report_time' => env('WA_DAILY_REPORT_TIME', '08:00'),
    'poll_interval' => (int) env('WA_POLL_INTERVAL', 60),
    'module_enabled' => filter_var(env('WA_MODULE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'expiry' => env('WA_EXPIRY'),
];
