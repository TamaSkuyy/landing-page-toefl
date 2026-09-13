<?php

return [
    'environment' => env('DUITKU_ENV', 'sandbox'),
    'merchant_code' => env('DUITKU_MERCHANT_CODE'),
    'api_key' => env('DUITKU_API_KEY'),
    'expiry_period' => (int) env('DUITKU_EXPIRY_PERIOD', 60),
];
