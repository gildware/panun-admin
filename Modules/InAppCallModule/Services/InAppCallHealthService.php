<?php

namespace Modules\InAppCallModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\InAppCallModule\Entities\InAppCall;
use Modules\InAppCallModule\Entities\InAppCallSignal;

class InAppCallHealthService
{
    public function __construct(
        private readonly InAppCallService $inAppCallService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $checks = [
            $this->checkFeatureEnabled(),
            $this->checkDatabase(),
            $this->checkSignalingStorage(),
            $this->checkFcmPush(),
            $this->checkStun(),
            $this->checkTurnConfigured(),
            $this->checkTurnReachable(),
            $this->checkBroadcasting(),
            $this->checkWebSocketLocal(),
            $this->checkWebSocketPublic(),
            $this->checkHttpSignalingFallback(),
            $this->checkRecentCallActivity(),
        ];

        return [
            'overall' => $this->resolveOverall($checks),
            'checked_at' => now()->toIso8601String(),
            'checked_at_label' => now()->format('Y-m-d H:i:s'),
            'summary' => $this->buildSummary($checks),
            'checks' => $checks,
            'recommendations' => $this->buildRecommendations($checks),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkFeatureEnabled(): array
    {
        $envEnabled = (bool) config('inappcallmodule.enabled', true);
        $businessEnabled = $this->inAppCallService->isEnabled();

        if (! $envEnabled) {
            return $this->item(
                id: 'feature',
                name: translate('In_app_calling_feature'),
                status: 'error',
                message: translate('IN_APP_CALL_ENABLED_is_false'),
                detail: translate('Set_IN_APP_CALL_ENABLED_true_in_env'),
                required: true,
                category: 'core',
            );
        }

        if (! $businessEnabled) {
            return $this->item(
                id: 'feature',
                name: translate('In_app_calling_feature'),
                status: 'error',
                message: translate('In_app_calling_is_not_configured'),
                detail: translate('Enable_in_app_calling_in_business_settings'),
                required: true,
                category: 'core',
            );
        }

        return $this->item(
            id: 'feature',
            name: translate('In_app_calling_feature'),
            status: 'ok',
            message: translate('In_app_calling_is_enabled'),
            detail: null,
            required: true,
            category: 'core',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDatabase(): array
    {
        try {
            if (! Schema::hasTable('in_app_calls')) {
                return $this->item(
                    id: 'database',
                    name: translate('Call_database'),
                    status: 'error',
                    message: translate('in_app_calls_table_missing'),
                    detail: translate('Run_php_artisan_migrate'),
                    required: true,
                    category: 'core',
                );
            }

            InAppCall::query()->limit(1)->count();

            return $this->item(
                id: 'database',
                name: translate('Call_database'),
                status: 'ok',
                message: translate('Database_connection_ok'),
                detail: null,
                required: true,
                category: 'core',
            );
        } catch (\Throwable $e) {
            return $this->item(
                id: 'database',
                name: translate('Call_database'),
                status: 'error',
                message: translate('Database_check_failed'),
                detail: $e->getMessage(),
                required: true,
                category: 'core',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkSignalingStorage(): array
    {
        try {
            if (! Schema::hasTable('in_app_call_signals')) {
                return $this->item(
                    id: 'signaling_storage',
                    name: translate('WebRTC_signaling_storage'),
                    status: 'error',
                    message: translate('in_app_call_signals_table_missing'),
                    detail: translate('Run_php_artisan_migrate'),
                    required: true,
                    category: 'signaling',
                );
            }

            InAppCallSignal::query()->limit(1)->count();

            return $this->item(
                id: 'signaling_storage',
                name: translate('WebRTC_signaling_storage'),
                status: 'ok',
                message: translate('Signaling_API_ready'),
                detail: translate('Offer_answer_ICE_stored_via_REST'),
                required: true,
                category: 'signaling',
            );
        } catch (\Throwable $e) {
            return $this->item(
                id: 'signaling_storage',
                name: translate('WebRTC_signaling_storage'),
                status: 'error',
                message: translate('Signaling_storage_check_failed'),
                detail: $e->getMessage(),
                required: true,
                category: 'signaling',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkFcmPush(): array
    {
        $config = business_config('push_notification', 'third_party');
        $live = collect($config->live_values ?? []);
        $serviceFile = data_get($live, 'service_file_content');

        if (! filled($serviceFile)) {
            return $this->item(
                id: 'fcm_push',
                name: translate('FCM_push_notifications'),
                status: 'error',
                message: translate('Firebase_service_account_not_configured'),
                detail: translate('Configure_firebase_push_in_third_party_settings'),
                required: true,
                category: 'signaling',
            );
        }

        $decoded = json_decode((string) $serviceFile, true);
        $projectId = data_get($decoded, 'project_id');
        $clientEmail = data_get($decoded, 'client_email');
        $privateKey = data_get($decoded, 'private_key');

        if (! filled($projectId) || ! filled($clientEmail) || ! filled($privateKey)) {
            return $this->item(
                id: 'fcm_push',
                name: translate('FCM_push_notifications'),
                status: 'error',
                message: translate('Firebase_service_account_incomplete'),
                detail: translate('Service_account_needs_project_id_client_email_private_key'),
                required: true,
                category: 'signaling',
            );
        }

        if (! function_exists('getAccessToken')) {
            return $this->item(
                id: 'fcm_push',
                name: translate('FCM_push_notifications'),
                status: 'warning',
                message: translate('FCM_helper_not_loaded'),
                detail: null,
                required: true,
                category: 'signaling',
            );
        }

        try {
            $token = getAccessToken($clientEmail, $privateKey);
            if (! filled($token)) {
                return $this->item(
                    id: 'fcm_push',
                    name: translate('FCM_push_notifications'),
                    status: 'error',
                    message: translate('FCM_authentication_failed'),
                    detail: translate('Check_firebase_service_account_credentials'),
                    required: true,
                    category: 'signaling',
                );
            }

            return $this->item(
                id: 'fcm_push',
                name: translate('FCM_push_notifications'),
                status: 'ok',
                message: translate('FCM_authentication_ok'),
                detail: translate('Project') . ': ' . $projectId,
                required: true,
                category: 'signaling',
            );
        } catch (\Throwable $e) {
            return $this->item(
                id: 'fcm_push',
                name: translate('FCM_push_notifications'),
                status: 'error',
                message: translate('FCM_authentication_failed'),
                detail: $e->getMessage(),
                required: true,
                category: 'signaling',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkStun(): array
    {
        $iceServers = config('inappcallmodule.ice_servers', []);
        $hasStun = false;
        $stunUrls = [];

        foreach ($iceServers as $server) {
            $urls = $server['urls'] ?? null;
            $urlList = is_array($urls) ? $urls : (filled($urls) ? [$urls] : []);
            foreach ($urlList as $url) {
                $url = strtolower((string) $url);
                if (str_starts_with($url, 'stun:')) {
                    $hasStun = true;
                    $stunUrls[] = $url;
                }
            }
        }

        if (! $hasStun) {
            return $this->item(
                id: 'stun',
                name: translate('STUN_server'),
                status: 'warning',
                message: translate('No_STUN_server_configured'),
                detail: translate('Set_STUN_URL_in_env'),
                required: true,
                category: 'media',
            );
        }

        return $this->item(
            id: 'stun',
            name: translate('STUN_server'),
            status: 'ok',
            message: translate('STUN_configured'),
            detail: implode(', ', array_unique($stunUrls)),
            required: true,
            category: 'media',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkTurnConfigured(): array
    {
        $turnUrl = trim((string) env('TURN_URL', ''));
        $turnUser = trim((string) env('TURN_USERNAME', ''));
        $turnCred = trim((string) env('TURN_CREDENTIAL', ''));

        if ($turnUrl === '' || $turnUser === '' || $turnCred === '') {
            return $this->item(
                id: 'turn_config',
                name: translate('TURN_server'),
                status: 'warning',
                message: translate('TURN_not_configured'),
                detail: translate('TURN_required_for_calls_across_networks'),
                required: false,
                category: 'media',
            );
        }

        return $this->item(
            id: 'turn_config',
            name: translate('TURN_server'),
            status: 'ok',
            message: translate('TURN_credentials_configured'),
            detail: $turnUrl,
            required: false,
            category: 'media',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkTurnReachable(): array
    {
        $turnUrl = trim((string) env('TURN_URL', ''));
        if ($turnUrl === '') {
            return $this->item(
                id: 'turn_reachable',
                name: translate('TURN_connectivity'),
                status: 'disabled',
                message: translate('Skipped_TURN_not_configured'),
                detail: null,
                required: false,
                category: 'media',
            );
        }

        $hostPort = $this->parseTurnHostPort($turnUrl);
        if ($hostPort === null) {
            return $this->item(
                id: 'turn_reachable',
                name: translate('TURN_connectivity'),
                status: 'warning',
                message: translate('Could_not_parse_TURN_URL'),
                detail: $turnUrl,
                required: false,
                category: 'media',
            );
        }

        ['host' => $host, 'port' => $port] = $hostPort;
        $reachable = $this->canConnectTcp($host, $port, 3);

        if (! $reachable) {
            return $this->item(
                id: 'turn_reachable',
                name: translate('TURN_connectivity'),
                status: 'warning',
                message: translate('TURN_host_not_reachable_on_TCP'),
                detail: "$host:$port — " . translate('Verify_coturn_is_running'),
                required: false,
                category: 'media',
            );
        }

        return $this->item(
            id: 'turn_reachable',
            name: translate('TURN_connectivity'),
            status: 'ok',
            message: translate('TURN_host_reachable'),
            detail: "$host:$port",
            required: false,
            category: 'media',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkBroadcasting(): array
    {
        $wsEnabled = (bool) config('inappcallmodule.websocket.enabled', false);
        if (! $wsEnabled) {
            return $this->item(
                id: 'broadcasting',
                name: translate('Laravel_broadcasting'),
                status: 'disabled',
                message: translate('WebSocket_disabled_using_HTTP_polling'),
                detail: translate('Set_IN_APP_CALL_WEBSOCKET_ENABLED_true_to_enable'),
                required: false,
                category: 'signaling',
            );
        }

        $driver = (string) config('broadcasting.default', '');
        $pusherKey = (string) config('broadcasting.connections.pusher.key', '');
        $pusherSecret = (string) config('broadcasting.connections.pusher.secret', '');
        $pusherAppId = (string) config('broadcasting.connections.pusher.app_id', '');

        if ($driver !== 'pusher') {
            return $this->item(
                id: 'broadcasting',
                name: translate('Laravel_broadcasting'),
                status: 'error',
                message: translate('BROADCAST_DRIVER_must_be_pusher'),
                detail: translate('Current_driver') . ': ' . ($driver ?: 'null'),
                required: true,
                category: 'signaling',
            );
        }

        if ($pusherKey === '' || $pusherSecret === '' || $pusherAppId === '') {
            return $this->item(
                id: 'broadcasting',
                name: translate('Laravel_broadcasting'),
                status: 'error',
                message: translate('Pusher_credentials_incomplete'),
                detail: translate('Set_PUSHER_APP_ID_KEY_SECRET_in_env'),
                required: true,
                category: 'signaling',
            );
        }

        return $this->item(
            id: 'broadcasting',
            name: translate('Laravel_broadcasting'),
            status: 'ok',
            message: translate('Broadcasting_configured_for_pusher'),
            detail: 'app_id: ' . $pusherAppId,
            required: true,
            category: 'signaling',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkWebSocketLocal(): array
    {
        $wsEnabled = (bool) config('inappcallmodule.websocket.enabled', false);
        if (! $wsEnabled) {
            return $this->item(
                id: 'websocket_local',
                name: translate('Soketi_local'),
                status: 'disabled',
                message: translate('WebSocket_signaling_disabled'),
                detail: null,
                required: false,
                category: 'signaling',
            );
        }

        $host = (string) env('PUSHER_HOST', '127.0.0.1');
        $port = (int) env('PUSHER_PORT', 6001);
        $scheme = strtolower((string) env('PUSHER_SCHEME', 'http'));
        $baseUrl = ($scheme === 'https' ? 'https' : 'http') . "://{$host}:{$port}/";

        try {
            $response = Http::timeout(4)->get($baseUrl);
            $status = $response->status();

            if ($status >= 200 && $status < 500) {
                return $this->item(
                    id: 'websocket_local',
                    name: translate('Soketi_local'),
                    status: 'ok',
                    message: translate('Soketi_responding_locally'),
                    detail: $baseUrl . ' (HTTP ' . $status . ')',
                    required: true,
                    category: 'signaling',
                );
            }

            return $this->item(
                id: 'websocket_local',
                name: translate('Soketi_local'),
                status: 'error',
                message: translate('Soketi_not_responding'),
                detail: $baseUrl . ' (HTTP ' . $status . ')',
                required: true,
                category: 'signaling',
            );
        } catch (\Throwable $e) {
            return $this->item(
                id: 'websocket_local',
                name: translate('Soketi_local'),
                status: 'error',
                message: translate('Cannot_reach_Soketi_on_server'),
                detail: $baseUrl . ' — ' . $e->getMessage(),
                required: true,
                category: 'signaling',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkWebSocketPublic(): array
    {
        $wsEnabled = (bool) config('inappcallmodule.websocket.enabled', false);
        if (! $wsEnabled) {
            return $this->item(
                id: 'websocket_public',
                name: translate('WebSocket_public_endpoint'),
                status: 'disabled',
                message: translate('Not_required_when_WebSocket_disabled'),
                detail: null,
                required: false,
                category: 'signaling',
            );
        }

        $publicHost = trim((string) env('PUSHER_PUBLIC_HOST', ''));
        $publicPort = (int) env('PUSHER_PUBLIC_PORT', 443);
        $publicScheme = strtolower((string) env('PUSHER_PUBLIC_SCHEME', 'https'));
        $pusherKey = (string) config('broadcasting.connections.pusher.key', '');

        if ($publicHost === '' || $pusherKey === '') {
            return $this->item(
                id: 'websocket_public',
                name: translate('WebSocket_public_endpoint'),
                status: 'warning',
                message: translate('PUSHER_PUBLIC_HOST_not_set'),
                detail: translate('Mobile_apps_need_public_WebSocket_host'),
                required: true,
                category: 'signaling',
            );
        }

        $portSuffix = ($publicScheme === 'https' && $publicPort === 443) || ($publicScheme === 'http' && $publicPort === 80)
            ? ''
            : ':' . $publicPort;
        $url = "{$publicScheme}://{$publicHost}{$portSuffix}/app/{$pusherKey}";

        try {
            $response = Http::timeout(6)
                ->withHeaders([
                    'Connection' => 'Upgrade',
                    'Upgrade' => 'websocket',
                    'Sec-WebSocket-Version' => '13',
                    'Sec-WebSocket-Key' => base64_encode(random_bytes(16)),
                ])
                ->get($url);

            $status = $response->status();
            if (in_array($status, [101, 400, 426], true)) {
                return $this->item(
                    id: 'websocket_public',
                    name: translate('WebSocket_public_endpoint'),
                    status: 'ok',
                    message: translate('Public_WebSocket_endpoint_reachable'),
                    detail: $url . ' (HTTP ' . $status . ')',
                    required: true,
                    category: 'signaling',
                );
            }

            if ($status === 500) {
                return $this->item(
                    id: 'websocket_public',
                    name: translate('WebSocket_public_endpoint'),
                    status: 'warning',
                    message: translate('WebSocket_proxy_may_be_misconfigured'),
                    detail: $url . ' — ' . translate('Check_htaccess_or_nginx_proxy'),
                    required: true,
                    category: 'signaling',
                );
            }

            return $this->item(
                id: 'websocket_public',
                name: translate('WebSocket_public_endpoint'),
                status: 'warning',
                message: translate('Unexpected_response_from_public_WebSocket'),
                detail: $url . ' (HTTP ' . $status . ')',
                required: true,
                category: 'signaling',
            );
        } catch (\Throwable $e) {
            return $this->item(
                id: 'websocket_public',
                name: translate('WebSocket_public_endpoint'),
                status: 'error',
                message: translate('Public_WebSocket_not_reachable'),
                detail: $url . ' — ' . $e->getMessage(),
                required: true,
                category: 'signaling',
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkHttpSignalingFallback(): array
    {
        $wsEnabled = (bool) config('inappcallmodule.websocket.enabled', false);
        $publicConfig = $this->inAppCallService->publicConfig();
        $iceCount = count($publicConfig['ice_servers'] ?? []);

        if ($wsEnabled) {
            return $this->item(
                id: 'http_fallback',
                name: translate('HTTP_polling_fallback'),
                status: 'ok',
                message: translate('HTTP_polling_available_as_backup'),
                detail: translate('Apps_poll_signals_every_800ms_when_needed'),
                required: false,
                category: 'signaling',
            );
        }

        return $this->item(
            id: 'http_fallback',
            name: translate('HTTP_polling_fallback'),
            status: 'ok',
            message: translate('HTTP_polling_is_primary_signaling'),
            detail: translate('ICE_servers_in_config') . ': ' . $iceCount,
            required: true,
            category: 'signaling',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function checkRecentCallActivity(): array
    {
        try {
            $last24h = InAppCall::query()->where('created_at', '>=', now()->subDay())->count();
            $answered24h = InAppCall::query()
                ->where('created_at', '>=', now()->subDay())
                ->whereIn('status', [InAppCall::STATUS_ACCEPTED, InAppCall::STATUS_ENDED])
                ->count();
            $active24h = InAppCall::query()
                ->where('created_at', '>=', now()->subDay())
                ->where('status', InAppCall::STATUS_ACCEPTED)
                ->count();
            $signals24h = InAppCallSignal::query()->where('created_at', '>=', now()->subDay())->count();

            $detail = translate('Last_24_hours') . ": {$last24h} " . translate('calls')
                . ', ' . $answered24h . ' ' . translate('answered')
                . ($active24h > 0 ? ' (' . $active24h . ' ' . translate('in_progress') . ')' : '')
                . ', ' . $signals24h . ' ' . translate('WebRTC_signals');

            return $this->item(
                id: 'activity',
                name: translate('Recent_call_activity'),
                status: 'ok',
                message: $last24h > 0 ? translate('Calls_recorded_recently') : translate('No_calls_in_last_24_hours'),
                detail: $detail,
                required: false,
                category: 'core',
            );
        } catch (\Throwable $e) {
            return $this->item(
                id: 'activity',
                name: translate('Recent_call_activity'),
                status: 'warning',
                message: translate('Could_not_read_call_activity'),
                detail: $e->getMessage(),
                required: false,
                category: 'core',
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     * @return array{ok: int, warning: int, error: int, disabled: int}
     */
    private function buildSummary(array $checks): array
    {
        $summary = ['ok' => 0, 'warning' => 0, 'error' => 0, 'disabled' => 0];
        foreach ($checks as $check) {
            $status = $check['status'] ?? 'warning';
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     * @return list<string>
     */
    private function buildRecommendations(array $checks): array
    {
        $byId = collect($checks)->keyBy('id');
        $tips = [];

        if (($byId['turn_config']['status'] ?? '') === 'warning') {
            $tips[] = translate('Deploy_coturn_and_set_TURN_URL_in_env');
        }
        if (($byId['websocket_local']['status'] ?? '') === 'error') {
            $tips[] = translate('Start_Soketi_via_supervisor_or_disable_WebSocket');
        }
        if (($byId['websocket_public']['status'] ?? '') === 'error') {
            $tips[] = translate('Configure_PUSHER_PUBLIC_and_reverse_proxy');
        }
        if (($byId['fcm_push']['status'] ?? '') === 'error') {
            $tips[] = translate('Upload_firebase_service_account_in_admin');
        }
        if (($byId['feature']['status'] ?? '') === 'error') {
            $tips[] = translate('Enable_in_app_calling_before_testing');
        }

        return array_values(array_unique($tips));
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     */
    private function resolveOverall(array $checks): string
    {
        foreach ($checks as $check) {
            if (($check['required'] ?? false) && ($check['status'] ?? '') === 'error') {
                return 'unhealthy';
            }
        }

        foreach ($checks as $check) {
            if (($check['status'] ?? '') === 'error') {
                return 'degraded';
            }
        }

        foreach ($checks as $check) {
            if (($check['status'] ?? '') === 'warning' && ($check['required'] ?? false)) {
                return 'degraded';
            }
        }

        foreach ($checks as $check) {
            if (($check['status'] ?? '') === 'warning') {
                return 'degraded';
            }
        }

        return 'healthy';
    }

    /**
     * @return array{host: string, port: int}|null
     */
    private function parseTurnHostPort(string $turnUrl): ?array
    {
        $normalized = preg_replace('#^(turn|turns):#i', '', $turnUrl);
        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        if (str_contains($normalized, '@')) {
            $normalized = substr($normalized, strrpos($normalized, '@') + 1);
        }

        $normalized = explode('?', $normalized)[0];
        $normalized = explode(';', $normalized)[0];

        if (str_starts_with($normalized, '[')) {
            $end = strpos($normalized, ']');
            if ($end === false) {
                return null;
            }
            $host = substr($normalized, 1, $end - 1);
            $rest = substr($normalized, $end + 1);
            $port = str_starts_with($rest, ':') ? (int) substr($rest, 1) : 3478;

            return ['host' => $host, 'port' => $port > 0 ? $port : 3478];
        }

        if (str_contains($normalized, ':')) {
            [$host, $port] = explode(':', $normalized, 2);

            return ['host' => $host, 'port' => (int) $port ?: 3478];
        }

        return ['host' => $normalized, 'port' => 3478];
    }

    private function canConnectTcp(string $host, int $port, int $timeoutSeconds): bool
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeoutSeconds);
        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        string $id,
        string $name,
        string $status,
        string $message,
        ?string $detail,
        bool $required,
        string $category,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'detail' => $detail,
            'required' => $required,
            'category' => $category,
            'status_label' => translate('health_status_' . $status),
            'category_label' => translate('health_category_' . $category),
        ];
    }
}
