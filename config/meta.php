<?php

return [
    'pixel_id' => env('META_PIXEL_ID'),
    'access_token' => env('META_ACCESS_TOKEN'),
    'test_event_code' => env('META_TEST_EVENT_CODE'),
    'enabled' => (bool) env('META_CAPI_ENABLED', true),
    'log_enabled' => (bool) env('META_CAPI_LOG_ENABLED', false),
    'graph_version' => env('META_GRAPH_VERSION', 'v23.0'),
];
