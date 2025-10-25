<?php

return [
    'db' => [
        'type' => 'sqlite',
        'path' => __DIR__ . '/../database.sqlite',
    ],
    'app' => [
        'name' => 'Standalone Ecommerce',
        'env' => 'development',
        'debug' => true,
        'url' => 'http://localhost:5000',
    ],
    'session' => [
        'name' => 'ecommerce_session',
        'lifetime' => 3600,
    ],
];
