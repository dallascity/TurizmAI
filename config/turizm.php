<?php

return [
    /*
    | Ana model yoğun veya erişilemez ise yedek modeller sırayla denenir.
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'fallback_models' => env('GEMINI_FALLBACK_MODELS', 'gemini-flash-lite-latest,gemini-flash-latest'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 25),
    ],
];
