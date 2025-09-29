<?php

return [
    'allowed_redirects' => array_filter(array_map('trim', explode(',', env('OAUTH_ALLOWED_REDIRECTS', 'http://localhost:4200/oauth/callback')))),

    'default_redirect' => env('OAUTH_DEFAULT_REDIRECT', 'http://localhost:4200/oauth/callback'),

    'token_param' => env('OAUTH_TOKEN_QUERY_PARAM', 'token'),

    'status_param' => env('OAUTH_STATUS_QUERY_PARAM', 'status'),

    'message_param' => env('OAUTH_MESSAGE_QUERY_PARAM', 'message'),

    'providers' => [
        'google',
        'microsoft',
        'apple',
    ],

    'state_ttl_seconds' => (int) env('OAUTH_STATE_TTL', 300),
];
