<?php

return [
    'sso_server' => env('SSO_SERVER'),
    'sso_broker_id' => env('SSO_BROKER_ID'),
    'sso_broker_secret' => env('SSO_BROKER_SECRET'),
    'server_prefix' => env('SSO_SERVER_PREFIX', '/'),
    'type' => env('SSO_TYPE', 'broker'),
    'log_erros' => env('APP_DEBUG', false),
];
