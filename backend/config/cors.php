<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'avatar/*'],
    'allowed_methods' => ['*'],

    // Указываем точный домен, а не звездочку (это надежнее для credentials)
    'allowed_origins' => ['https://billing.sakuranet.space', 'http://localhost:3000'],

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,

    // ВКЛЮЧАЕМ поддержку кук/авторизации
    'supports_credentials' => true,
];