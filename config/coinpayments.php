<?php
/**
 * CoinPayments Setting & API Credentials
 */

return [
    'api_public_key' => env('COINPAYMENTS_PUBLIC_KEY', false),
    'api_private_key' => env('COINPAYMENTS_PRIVATE_KEY', false),
    'merchant_id' => env('COINPAYMENTS_MERCHANT_ID', false),
];
