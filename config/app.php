<?php

return [
    'name' => getenv('APP_NAME') ?: 'Aurora',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN) ?? false,
    'url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',

    'log_level' => getenv('LOG_LEVEL') ?: 'error',
    'log_channels' => getenv('APP_ENV') === 'production'
        ? ['stack', 'sentry']
        : ['single'],

    'force_https' => true,
    'secure_headers' => true,

    'features' => [
        'loyalty_points' => filter_var(getenv('FEATURE_LOYALTY_POINTS'), FILTER_VALIDATE_BOOLEAN) ?? true,
        'inventory_tracking' => filter_var(getenv('FEATURE_INVENTORY_TRACKING'), FILTER_VALIDATE_BOOLEAN) ?? true,
        'staff_commission' => filter_var(getenv('FEATURE_STAFF_COMMISSION'), FILTER_VALIDATE_BOOLEAN) ?? true,
        'customer_portal' => filter_var(getenv('FEATURE_CUSTOMER_PORTAL'), FILTER_VALIDATE_BOOLEAN) ?? true,
    ],

    'api' => [
        'version' => 'v1',
        'base_path' => '/api/v1',
    ],

    'security' => [
        'bcrypt_rounds' => (int)getenv('BCRYPT_ROUNDS') ?: 12,
        'jwt_algorithm' => getenv('JWT_ALGORITHM') ?: 'HS256',
        'jwt_expiration' => (int)getenv('JWT_EXPIRATION') ?: 3600,
    ],
];
