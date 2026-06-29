<?php

return [
    'enabled' => env('IN_APP_CALL_ENABLED', true),
    'ring_timeout_seconds' => (int) env('IN_APP_CALL_RING_TIMEOUT_SECONDS', 60),
    'ice_servers' => [
        ['urls' => 'stun:stun.l.google.com:19302'],
        ['urls' => 'stun:stun1.l.google.com:19302'],
    ],
];
