<?php
/**
 * Duitku Setting & API Credentials
 */

return [
    'mode'           => env('DUITKU_MODE', 'development'),

    'dev_merchant_code'     => env('DUITKU_SANDBOX_MERCHANT_CODE', ''),
    'dev_api_key'           => env('DUITKU_SANDBOX_API_KEY', ''),

    'prod_merchant_code'     => env('DUITKU_LIVE_MERCHANT_CODE', ''),
    'prod_api_key'           => env('DUITKU_LIVE_API_KEY', ''),
];
