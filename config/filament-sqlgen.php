<?php

return [
    'gemini_api_key' => env('GEMINI_API_KEY'),

    // Gemini API Endpoint
    'gemini_api_endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent'),

    // Timeout and Retry Settings
    'gemini_api_timeout' => env('GEMINI_API_TIMEOUT', 30), // Default timeout of 30 seconds
    'gemini_api_retry_attempts' => env('GEMINI_API_RETRY_ATTEMPTS', 3), // Number of retry attempts

    // Logging Options
    'logging_enabled' => env('GEMINI_LOGGING_ENABLED', false), // If enabled, log all Gemini API calls

    // Cache settings (for example, cache the result of questions for X minutes)
    'cache_enabled' => env('GEMINI_CACHE_ENABLED', true),
    'cache_duration' => env('GEMINI_CACHE_DURATION', 60), // Cache duration in minutes
];
