<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'payment_webhooks' => [
        // Clave usada para validar firma HMAC de webhooks entrantes
        'secret' => env('PAYMENT_WEBHOOK_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
        'redirect' => env('APPLE_REDIRECT_URI'),
    ],

    'api_tokens' => [
        'default_rate' => env('API_TOKEN_RATE_LIMIT', 120),
        'default_decay' => env('API_TOKEN_RATE_LIMIT_DECAY', 60),
        'default_expiration_days' => env('API_TOKEN_EXPIRATION_DAYS', 90),
        'policies' => [
            // Dashboard
            [
                'methods' => ['GET'],
                'pattern' => 'api/dashboard*',
                'abilities' => ['api:read-dashboard'],
            ],

            // Settings & API tokens
            [
                'methods' => ['GET', 'PUT', 'POST', 'DELETE'],
                'pattern' => 'api/settings*',
                'abilities' => ['api:manage-settings'],
            ],

            // Invoices
            [
                'methods' => ['GET'],
                'pattern' => 'api/invoices*',
                'abilities' => ['api:read-invoices'],
            ],
            [
                'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
                'pattern' => 'api/invoices*',
                'abilities' => ['api:write-invoices'],
            ],
            [
                'methods' => ['GET'],
                'pattern' => 'api/invoices-stats',
                'abilities' => ['api:read-invoices'],
            ],
            [
                'methods' => ['POST'],
                'pattern' => 'api/invoices/import',
                'abilities' => ['api:import-invoices'],
            ],

            // Clients
            [
                'methods' => ['GET'],
                'pattern' => 'api/clients*',
                'abilities' => ['api:read-clients'],
            ],
            [
                'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
                'pattern' => 'api/clients*',
                'abilities' => ['api:write-clients'],
            ],

            // Quotes
            [
                'methods' => ['GET'],
                'pattern' => 'api/quotes*',
                'abilities' => ['api:read-quotes'],
            ],
            [
                'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
                'pattern' => 'api/quotes*',
                'abilities' => ['api:write-quotes'],
            ],
            [
                'methods' => ['GET'],
                'pattern' => 'api/quotes-stats',
                'abilities' => ['api:read-quotes'],
            ],
            [
                'methods' => ['POST'],
                'pattern' => 'api/quotes/import',
                'abilities' => ['api:import-quotes'],
            ],

            // Payments
            [
                'methods' => ['GET'],
                'pattern' => 'api/payments*',
                'abilities' => ['api:read-payments'],
            ],
            [
                'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
                'pattern' => 'api/payments*',
                'abilities' => ['api:write-payments'],
            ],

            // Import batches (lectura de estados)
            [
                'methods' => ['GET'],
                'pattern' => 'api/import-batches*',
                'abilities' => ['api:read-invoices'],
            ],

            // Companies
            [
                'methods' => ['GET'],
                'pattern' => 'api/companies*',
                'abilities' => ['api:read-companies'],
            ],
        ],
        'alerts' => [
            'windows' => [
                'short_minutes' => env('API_TOKEN_ALERT_WINDOW_SHORT', 60),
                'long_minutes' => env('API_TOKEN_ALERT_WINDOW_LONG', 1440),
            ],
            'error_rate' => [
                'threshold_percent' => env('API_TOKEN_ALERT_ERROR_RATE_PERCENT', 20),
                'min_requests' => env('API_TOKEN_ALERT_ERROR_RATE_MIN_REQUESTS', 25),
            ],
            'error_count' => [
                'threshold' => env('API_TOKEN_ALERT_ERROR_COUNT', 10),
            ],
            'server_error_count' => [
                'threshold' => env('API_TOKEN_ALERT_SERVER_ERROR_COUNT', 3),
            ],
            'request_spike' => [
                'threshold' => env('API_TOKEN_ALERT_REQUEST_SPIKE', 5000),
            ],
        ],
    ],
];
