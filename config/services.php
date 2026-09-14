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
        'origin_contact_name' => env('BITESHIP_ORIGIN_CONTACT_NAME'),
        'origin_contact_phone' => env('BITESHIP_ORIGIN_CONTACT_PHONE'),
        'origin_contact_email' => env('BITESHIP_ORIGIN_CONTACT_EMAIL'),
        'origin_address' => env('BITESHIP_ORIGIN_ADDRESS'),
        'shipper_organization' => env('BITESHIP_SHIPPER_ORGANIZATION'),
        'couriers' => env('BITESHIP_COURIERS', 'jne,jnt,sicepat,anteraja'),
        'default_item_weight_grams' => env('BITESHIP_DEFAULT_ITEM_WEIGHT_GRAMS', 1000),
        'timeout' => env('BITESHIP_TIMEOUT', 10),
        'webhook_signature_key' => env('BITESHIP_WEBHOOK_SIGNATURE_KEY', 'X-Biteship-Signature'),
        'webhook_signature_secret' => env('BITESHIP_WEBHOOK_SIGNATURE_SECRET'),
    ],
    'api' => [
        'token_ttl_days' => (int) env('API_TOKEN_TTL_DAYS', 30),
        'max_active_tokens' => (int) env('API_MAX_ACTIVE_TOKENS', 5),
    ],
];
