<?php
/**
 * Duitku Setting & API Credentials
 */

return [
    'mode'                  => env('DUITKU_MODE', 'sandbox'),

    'sandbox_merchant_code'     => env('DUITKU_SANDBOX_MERCHANT_CODE', ''),
    'sandbox_api_key'           => env('DUITKU_SANDBOX_API_KEY', ''),

    'live_merchant_code'    => env('DUITKU_LIVE_MERCHANT_CODE', ''),
    'live_api_key'          => env('DUITKU_LIVE_API_KEY', ''),
];
