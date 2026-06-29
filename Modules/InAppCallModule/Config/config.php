<?php

$iceServers = [
    ['urls' => env('STUN_URL', 'stun:stun.l.google.com:19302')],
];

$turnUrl = trim((string) env('TURN_URL', ''));
$turnUsername = trim((string) env('TURN_USERNAME', ''));
$turnCredential = trim((string) env('TURN_CREDENTIAL', ''));

if ($turnUrl !== '' && $turnUsername !== '' && $turnCredential !== '') {
    $turnEntry = [
        'urls' => $turnUrl,
        'username' => $turnUsername,
        'credential' => $turnCredential,
    ];

    $turnTlsUrl = trim((string) env('TURN_TLS_URL', ''));
    if ($turnTlsUrl !== '') {
        $turnEntry['urls'] = [$turnUrl, $turnTlsUrl];
    }

    $iceServers[] = $turnEntry;
}

return [
    'enabled' => env('IN_APP_CALL_ENABLED', true),
    'ring_timeout_seconds' => (int) env('IN_APP_CALL_RING_TIMEOUT_SECONDS', 60),
    'ice_servers' => $iceServers,
];
