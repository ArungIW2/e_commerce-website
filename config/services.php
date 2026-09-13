<?php

return [
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],
    'biteship' => [
        'api_key' => env('BITESHIP_API_KEY'),
        'base_url' => env('BITESHIP_BASE_URL', 'https://api.biteship.com'),
        'origin_postal_code' => env('BITESHIP_ORIGIN_POSTAL_CODE'),
        'couriers' => env('BITESHIP_COURIERS', 'jne,jnt,sicepat,anteraja'),
        'default_item_weight_grams' => env('BITESHIP_DEFAULT_ITEM_WEIGHT_GRAMS', 1000),
        'timeout' => env('BITESHIP_TIMEOUT', 10),
    ],
];
