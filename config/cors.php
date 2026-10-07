<?php

// Only the Novastaq websites may call the store's /api endpoints from a browser
// (used by the novastaq.com contact, referral and careers forms).
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['POST', 'OPTIONS'],
    'allowed_origins' => ['https://novastaq.com', 'https://www.novastaq.com', 'http://localhost:5173', 'http://localhost:5174'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Accept'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
