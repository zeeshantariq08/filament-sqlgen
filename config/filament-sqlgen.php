<?php

return [
    'gemini_api_key' => env('GEMINI_API_KEY'),

    // Gemini API Endpoint
    'gemini_api_endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent'),

];
