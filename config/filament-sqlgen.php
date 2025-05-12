<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    | Supported: "gemini", "openai"
    */
    'provider' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Configuration
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent'),
        'temperature' => env('GEMINI_TEMPERATURE', 0.2),
        'max_output_tokens' => env('GEMINI_MAX_TOKENS', 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        'endpoint' => env('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'temperature' => env('OPENAI_TEMPERATURE', 0.2),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection for Logging
    |--------------------------------------------------------------------------
    */
    'database_connection' => env('DB_CONNECTION_LOGS', 'mysql'),

];
