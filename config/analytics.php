<?php

use App\Analytics\EventType;

$mode = env('PROJECT_MODE', 'ctwa');
$paymentMode = env('PAYMENT_MODE', 'none');

return [
    'enabled' => (bool) env('ANALYTICS_ENABLED', true),
    'client_id' => env('CLIENT_ID', 'pbm-demo'),
    'mode' => in_array($mode, ['ctwa', 'form'], true) ? $mode : 'ctwa',
    'payment_mode' => in_array($paymentMode, ['none', 'external', 'internal'], true) ? $paymentMode : 'none',
    'external_payment_url' => env('EXTERNAL_PAYMENT_URL'),
    'thank_you_path' => env('THANK_YOU_PATH', '/terima-kasih'),
    'product_name' => env('PRODUCT_NAME', 'PBM Product'),
    'product_price' => max(0, (int) env('PRODUCT_PRICE', 0)),
    'whatsapp_number' => env('WHATSAPP_NUMBER'),
    'whatsapp_default_message' => env('WHATSAPP_DEFAULT_MESSAGE', 'Halo, saya tertarik.'),
    'external_checkout_url' => env('EXTERNAL_CHECKOUT_URL'),
    'engagement_threshold' => (int) env('ANALYTICS_ENGAGEMENT_THRESHOLD', 15),
    'heartbeat_interval' => (int) env('ANALYTICS_HEARTBEAT_INTERVAL', 30),
    'session_timeout' => (int) env('ANALYTICS_SESSION_TIMEOUT', 30),
    'scroll_bounce_threshold' => (int) env('ANALYTICS_SCROLL_BOUNCE_THRESHOLD', 25),
    'section_view_enabled' => (bool) env('ANALYTICS_SECTION_VIEW_ENABLED', true),
    'minimum_winner_visits' => (int) env('ANALYTICS_MINIMUM_WINNER_VISITS', 30),
    'retention_days' => min(90, max(1, (int) env('ANALYTICS_RETENTION_DAYS', 90))),
    'primary_metric' => $mode === 'form' ? EventType::Lead->value : 'total_lead',
    'capabilities' => [
        EventType::Payment->value => $mode === 'form' && $paymentMode === 'internal',
        'revenue' => $mode === 'form' && $paymentMode === 'internal',
        'total_lead' => $mode === 'ctwa',
        EventType::SectionView->value => (bool) env('ANALYTICS_SECTION_VIEW_ENABLED', true),
    ],
];
