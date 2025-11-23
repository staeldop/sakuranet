<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // Разрешаем API запросы и CSRF куки
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'avatar/*'],

    'allowed_methods' => ['*'],

    // ВАЖНО: Разрешаем запросы с любого источника (для разработки)
    // Если не сработает, замени '*' на ['http://localhost:3000']
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Если используешь токены (Bearer), ставь false.
    // Если используешь куки (SPA аутентификация), ставь true.
    // Для твоего текущего кода (plainTextToken) лучше false.
    'supports_credentials' => false,
];