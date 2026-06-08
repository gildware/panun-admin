<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;

/**
 * OmniDimension voice API — list agents, phone numbers, and dispatch outbound calls.
 *
 * @see https://docs.omnidim.io/docs/api-reference
 */
class OmniDimensionService
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.omnidimension.api_key')) !== '';
    }

    public function clearCallLogsCache(): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        Cache::increment($this->cachePrefix() . ':call_logs_version');
    }

    /**
     * @return array{ok: bool, agents: array<int, array{id: int, name: string, bot_call_type: string}>, error: ?string}
     */
    public function listAgents(?string &$error = null): array
    {
        $error = null;
        if (!$this->isConfigured()) {
            $error = 'omnidimension_not_configured';

            return ['ok' => false, 'agents' => [], 'error' => $error];
        }

        $cacheKey = $this->cachePrefix() . ':agents';
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetchAgents($error);
        if ($result['ok']) {
            Cache::put($cacheKey, $result, (int) config('services.omnidimension.cache_agents_ttl', 300));
        }

        return $result;
    }

    /**
     * @return array{ok: bool, phone_numbers: array<int, array{id: int, name: string, phone_number: string, number_provider: string}>, error: ?string}
     */
    public function listPhoneNumbers(?string &$error = null): array
    {
        $error = null;
        if (!$this->isConfigured()) {
            $error = 'omnidimension_not_configured';

            return ['ok' => false, 'phone_numbers' => [], 'error' => $error];
        }

        $cacheKey = $this->cachePrefix() . ':phone_numbers';
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetchPhoneNumbers($error);
        if ($result['ok']) {
            Cache::put($cacheKey, $result, (int) config('services.omnidimension.cache_phone_numbers_ttl', 300));
        }

        return $result;
    }

    /**
     * @param  array{
     *     agent_id?: int|null,
     *     call_status?: string|null,
     *     page?: int,
     *     page_size?: int,
     *     search?: string|null,
     *     filter_type?: string|null,
     *     forwarded_only?: bool
     * }  $filters
     * @return array{ok: bool, calls: array<int, array<string, mixed>>, total: int, error: ?string}
     */
    public function listCallLogsFiltered(array $filters = [], ?string &$error = null): array
    {
        $error = null;
        if (!$this->isConfigured()) {
            $error = 'omnidimension_not_configured';

            return ['ok' => false, 'calls' => [], 'total' => 0, 'error' => $error];
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(150, max(1, (int) ($filters['page_size'] ?? pagination_limit())));
        $search = trim((string) ($filters['search'] ?? ''));
        $filterType = (string) ($filters['filter_type'] ?? '');
        if ($filterType === '' && !empty($filters['forwarded_only'])) {
            $filterType = 'forwarded';
        }
        $needsPostFilter = $search !== '' || $filterType !== '';

        if (!$needsPostFilter) {
            return $this->listCallLogs([
                'page' => $page,
                'page_size' => $pageSize,
                'agent_id' => $filters['agent_id'] ?? null,
                'call_status' => $filters['call_status'] ?? null,
            ], $error);
        }

        $matching = [];
        $apiPage = 1;
        $apiPageSize = 150;
        $totalApiRecords = null;
        $maxApiPages = 25;

        while ($apiPage <= $maxApiPages) {
            $result = $this->listCallLogs([
                'page' => $apiPage,
                'page_size' => $apiPageSize,
                'agent_id' => $filters['agent_id'] ?? null,
                'call_status' => $filters['call_status'] ?? null,
            ], $error);

            if (!$result['ok']) {
                return $result;
            }

            if ($totalApiRecords === null) {
                $totalApiRecords = (int) ($result['total'] ?? 0);
            }

            foreach ($result['calls'] as $call) {
                if ($filterType === 'forwarded' && !$this->isForwardedCall($call)) {
                    continue;
                }
                if ($filterType === 'callback' && !$this->isPendingCallbackCall($call)) {
                    continue;
                }
                if ($search !== '' && !$this->callMatchesSearch($call, $search)) {
                    continue;
                }
                $matching[] = $call;
            }

            if (count($result['calls']) < $apiPageSize) {
                break;
            }

            if ($totalApiRecords > 0 && ($apiPage * $apiPageSize) >= $totalApiRecords) {
                break;
            }

            $apiPage++;
        }

        return [
            'ok' => true,
            'calls' => $matching,
            'total' => count($matching),
            'error' => null,
        ];
    }

    public function isForwardedCall(array $call): bool
    {
        if (!empty($call['is_call_transfer'])) {
            return true;
        }

        $extracted = is_array($call['extracted_variables'] ?? null) ? $call['extracted_variables'] : [];
        $transferRequested = strtoupper(trim((string) ($extracted['transfer_requested'] ?? '')));
        if (in_array($transferRequested, ['YES', 'TRUE', '1'], true)) {
            return true;
        }

        $leadStatus = strtoupper(trim((string) ($extracted['lead_status'] ?? '')));
        if ($leadStatus === 'TRANSFERRED_TO_HUMAN') {
            return true;
        }

        $dispatch = is_array($call['dispatch_context'] ?? null) ? $call['dispatch_context'] : [];
        $dispatchLeadStatus = strtoupper(trim((string) ($dispatch['lead_status'] ?? '')));

        return $dispatchLeadStatus === 'TRANSFERRED_TO_HUMAN';
    }

    public function isCallbackRequestedCall(array $call): bool
    {
        $extracted = is_array($call['extracted_variables'] ?? null) ? $call['extracted_variables'] : [];
        $callbackRequested = strtoupper(trim((string) ($extracted['callback_requested'] ?? '')));
        if (in_array($callbackRequested, ['YES', 'TRUE', '1'], true)) {
            return true;
        }

        $leadStatus = strtoupper(trim((string) ($extracted['lead_status'] ?? '')));
        if (str_contains($leadStatus, 'CALLBACK')) {
            return true;
        }

        $callbackDate = trim((string) ($extracted['callback_date'] ?? ''));
        $callbackTime = trim((string) ($extracted['callback_time'] ?? ''));
        if ($this->extractedVariableHasValue($callbackDate) || $this->extractedVariableHasValue($callbackTime)) {
            return true;
        }

        $dispatch = is_array($call['dispatch_context'] ?? null) ? $call['dispatch_context'] : [];
        $dispatchLeadStatus = strtoupper(trim((string) ($dispatch['lead_status'] ?? '')));

        return str_contains($dispatchLeadStatus, 'CALLBACK');
    }

    public function isPendingCallbackCall(array $call): bool
    {
        return $this->isCallbackRequestedCall($call) && !$this->isForwardedCall($call);
    }

    public function callMatchesSearch(array $call, string $search): bool
    {
        $needle = strtolower(trim($search));
        if ($needle === '') {
            return true;
        }

        $parts = [
            (string) ($call['bot_name'] ?? ''),
            (string) ($call['from_number'] ?? ''),
            (string) ($call['to_number'] ?? ''),
            (string) ($call['call_status'] ?? ''),
            (string) ($call['call_direction'] ?? ''),
            (string) ($call['transcript'] ?? ''),
            (string) ($call['sentiment_analysis_details'] ?? ''),
        ];

        foreach (is_array($call['dispatch_context'] ?? null) ? $call['dispatch_context'] : [] as $value) {
            $parts[] = (string) $value;
        }

        foreach (is_array($call['extracted_variables'] ?? null) ? $call['extracted_variables'] : [] as $value) {
            $parts[] = (string) $value;
        }

        return str_contains(strtolower(implode(' ', $parts)), $needle);
    }

    /**
     * @param  array{agent_id?: int, call_status?: string, page?: int, page_size?: int}  $filters
     * @return array{ok: bool, calls: array<int, array<string, mixed>>, total: int, error: ?string}
     */
    public function listCallLogs(array $filters = [], ?string &$error = null): array
    {
        $error = null;
        if (!$this->isConfigured()) {
            $error = 'omnidimension_not_configured';

            return ['ok' => false, 'calls' => [], 'total' => 0, 'error' => $error];
        }

        $version = (int) Cache::get($this->cachePrefix() . ':call_logs_version', 0);
        $cacheKey = $this->cachePrefix() . ':call_logs:v' . $version . ':' . md5(json_encode($filters));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetchCallLogs($filters, $error);
        if ($result['ok']) {
            Cache::put($cacheKey, $result, (int) config('services.omnidimension.cache_call_logs_ttl', 45));
        }

        return $result;
    }

    public function getRecordingUrl(int $callLogId, ?string &$error = null): ?string
    {
        $error = null;
        if (!$this->isConfigured() || $callLogId <= 0) {
            $error = 'omnidimension_not_configured';

            return null;
        }

        $cacheKey = $this->cachePrefix() . ':recording:' . $callLogId;
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = $this->request('GET', '/calls/logs/' . $callLogId);
        if (!$response['ok']) {
            $error = $response['error'] ?? 'omnidimension_recording_failed';

            return null;
        }

        $row = $response['body']['call_log_data'][0] ?? null;
        if (!is_array($row)) {
            $error = 'omnidimension_recording_not_found';

            return null;
        }

        $normalized = $this->normalizeCallLogRow($row);
        $url = $normalized['recording_url'] ?? null;
        if (!is_string($url) || $url === '') {
            $error = 'omnidimension_recording_unavailable';

            return null;
        }

        Cache::put($cacheKey, $url, 600);

        return $url;
    }

    /**
     * @return array{ok: bool, agents: array<int, array{id: int, name: string, bot_call_type: string}>, error: ?string}
     */
    private function fetchAgents(?string &$error = null): array
    {
        $agents = [];
        $page = 1;
        $pageSize = 150;

        do {
            $response = $this->request('GET', '/agents', [
                'pageno' => $page,
                'pagesize' => $pageSize,
            ]);

            if (!$response['ok']) {
                $error = $response['error'] ?? 'omnidimension_agents_failed';

                return ['ok' => false, 'agents' => [], 'error' => $error];
            }

            $body = $response['body'] ?? [];
            foreach ($body['bots'] ?? [] as $bot) {
                if (!is_array($bot) || !isset($bot['id'])) {
                    continue;
                }
                $agents[] = [
                    'id' => (int) $bot['id'],
                    'name' => (string) ($bot['name'] ?? ('Agent #' . $bot['id'])),
                    'bot_call_type' => (string) ($bot['bot_call_type'] ?? ''),
                ];
            }

            $total = (int) ($body['total_records'] ?? count($agents));
            $page++;
        } while (count($agents) < $total && $page <= 10);

        usort($agents, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return ['ok' => true, 'agents' => $agents, 'error' => null];
    }

    /**
     * @return array{ok: bool, phone_numbers: array<int, array{id: int, name: string, phone_number: string, number_provider: string}>, error: ?string}
     */
    private function fetchPhoneNumbers(?string &$error = null): array
    {
        $numbers = [];
        $page = 1;
        $pageSize = 150;

        do {
            $response = $this->request('GET', '/phone_number/list', [
                'pageno' => $page,
                'pagesize' => $pageSize,
            ]);

            if (!$response['ok']) {
                $error = $response['error'] ?? 'omnidimension_phone_numbers_failed';

                return ['ok' => false, 'phone_numbers' => [], 'error' => $error];
            }

            $body = $response['body'] ?? [];
            foreach ($body['phone_numbers'] ?? [] as $row) {
                if (!is_array($row) || !isset($row['id'])) {
                    continue;
                }
                $numbers[] = [
                    'id' => (int) $row['id'],
                    'name' => (string) ($row['name'] ?? ''),
                    'phone_number' => (string) ($row['phone_number'] ?? ''),
                    'number_provider' => (string) ($row['number_provider'] ?? ''),
                ];
            }

            $hasMore = count($body['phone_numbers'] ?? []) === $pageSize;
            $page++;
        } while ($hasMore && $page <= 10);

        usort($numbers, fn (array $a, array $b) => strcasecmp($a['phone_number'], $b['phone_number']));

        return ['ok' => true, 'phone_numbers' => $numbers, 'error' => null];
    }

    /**
     * @param  array{agent_id?: int, call_status?: string, page?: int, page_size?: int}  $filters
     * @return array{ok: bool, calls: array<int, array<string, mixed>>, total: int, error: ?string}
     */
    private function fetchCallLogs(array $filters, ?string &$error = null): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(150, max(1, (int) ($filters['page_size'] ?? 30)));

        $query = [
            'pageno' => $page,
            'pagesize' => $pageSize,
        ];

        if (!empty($filters['agent_id'])) {
            $query['agentid'] = (int) $filters['agent_id'];
        }
        if (!empty($filters['call_status'])) {
            $query['call_status'] = (string) $filters['call_status'];
        }

        $response = $this->request('GET', '/calls/logs', $query);

        if (!$response['ok']) {
            $error = $response['error'] ?? 'omnidimension_call_logs_failed';

            return ['ok' => false, 'calls' => [], 'total' => 0, 'error' => $error];
        }

        $body = $response['body'] ?? [];
        $calls = [];

        foreach ($body['call_log_data'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $calls[] = $this->normalizeCallLogRow($row);
        }

        return [
            'ok' => true,
            'calls' => $calls,
            'total' => (int) ($body['total_records'] ?? count($calls)),
            'error' => null,
        ];
    }

    /**
     * @param  array{
     *     name: string,
     *     phone_number_id: int|string,
     *     contact_list: array<int, array<string, mixed>>,
     *     is_scheduled?: bool,
     *     scheduled_datetime?: string,
     *     timezone?: string,
     *     concurrent_call_limit?: int,
     *     enabled_reschedule_call?: bool,
     *     retry_config?: array<string, mixed>
     * }  $payload
     * @return array{ok: bool, campaign_id: ?int, status: ?string, error: ?string, body: ?array}
     */
    public function createBulkCall(array $payload, ?string &$error = null): array
    {
        $error = null;

        if (!$this->isConfigured()) {
            $error = 'omnidimension_not_configured';

            return ['ok' => false, 'campaign_id' => null, 'status' => null, 'error' => $error, 'body' => null];
        }

        $response = $this->request('POST', '/calls/bulk_call/create', [], $payload);

        if (!$response['ok']) {
            $error = $response['error'] ?? 'omnidimension_bulk_call_failed';

            return [
                'ok' => false,
                'campaign_id' => null,
                'status' => null,
                'error' => $error,
                'body' => $response['body'] ?? null,
            ];
        }

        $this->clearBulkCallsCache();

        $body = $response['body'] ?? [];

        return [
            'ok' => (bool) (($body['status'] ?? '') === 'success' || ($body['success'] ?? false)),
            'campaign_id' => isset($body['id']) ? (int) $body['id'] : null,
            'status' => isset($body['current_status']) ? (string) $body['current_status'] : null,
            'error' => null,
            'body' => is_array($body) ? $body : null,
        ];
    }

    /**
     * @param  array{page?: int, page_size?: int, status?: string}  $filters
     * @return array{ok: bool, campaigns: array<int, array<string, mixed>>, total: int, error: ?string}
     */
    public function listBulkCalls(array $filters = [], ?string &$error = null): array
    {
        $error = null;
        if (!$this->isConfigured()) {
            $error = 'omnidimension_not_configured';

            return ['ok' => false, 'campaigns' => [], 'total' => 0, 'error' => $error];
        }

        $version = (int) Cache::get($this->cachePrefix() . ':bulk_calls_version', 0);
        $cacheKey = $this->cachePrefix() . ':bulk_calls:v' . $version . ':' . md5(json_encode($filters));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->fetchBulkCalls($filters, $error);
        if ($result['ok']) {
            Cache::put($cacheKey, $result, (int) config('services.omnidimension.cache_call_logs_ttl', 45));
        }

        return $result;
    }

    public function clearBulkCallsCache(): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        Cache::increment($this->cachePrefix() . ':bulk_calls_version');
    }

    /**
     * @param  array<string, mixed>  $callContext
     * @return array{ok: bool, request_id: ?int, status: ?string, error: ?string, body: ?array}
     */
    public function dispatchCall(
        int $agentId,
        string $toNumberE164,
        ?int $fromNumberId = null,
        array $callContext = [],
        ?string &$error = null
    ): array {
        $error = null;

        if (!$this->isConfigured()) {
            $error = 'omnidimension_not_configured';

            return ['ok' => false, 'request_id' => null, 'status' => null, 'error' => $error, 'body' => null];
        }

        $payload = [
            'agent_id' => $agentId,
            'to_number' => $toNumberE164,
        ];

        if ($fromNumberId !== null && $fromNumberId > 0) {
            $payload['from_number_id'] = $fromNumberId;
        }

        if ($callContext !== []) {
            $payload['call_context'] = $callContext;
        }

        $response = $this->request('POST', '/calls/dispatch', [], $payload);

        if (!$response['ok']) {
            $error = $response['error'] ?? 'omnidimension_dispatch_failed';

            return [
                'ok' => false,
                'request_id' => null,
                'status' => null,
                'error' => $error,
                'body' => $response['body'] ?? null,
            ];
        }

        $this->clearCallLogsCache();

        $body = $response['body'] ?? [];

        return [
            'ok' => (bool) ($body['success'] ?? true),
            'request_id' => isset($body['requestId']) ? (int) $body['requestId'] : null,
            'status' => isset($body['status']) ? (string) $body['status'] : null,
            'error' => null,
            'body' => is_array($body) ? $body : null,
        ];
    }

    public function normalizeToE164(string $phone): ?string
    {
        $digits = app(WhatsAppCloudService::class)->normalizeRecipientPhone($phone);
        if ($digits === null || $digits === '') {
            return null;
        }

        return '+' . $digits;
    }

    private function cachePrefix(): string
    {
        $key = trim((string) config('services.omnidimension.api_key'));

        return 'omnidim:' . substr(hash('sha256', $key), 0, 12);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeCallLogRow(array $row): array
    {
        $recordingUrl = $row['recording_url'] ?? false;
        if (is_string($recordingUrl) && $recordingUrl !== '' && str_starts_with($recordingUrl, '/')) {
            $origin = preg_replace('#/api/v1$#', '', rtrim((string) config('services.omnidimension.base_url', 'https://backend.omnidim.io/api/v1'), '/')) ?? 'https://backend.omnidim.io';
            $recordingUrl = rtrim($origin, '/') . $recordingUrl;
        } elseif ($recordingUrl === false || $recordingUrl === '') {
            $recordingUrl = null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'bot_name' => (string) ($row['bot_name'] ?? ''),
            'time_of_call' => (string) ($row['time_of_call'] ?? ''),
            'from_number' => (string) ($row['from_number'] ?? ''),
            'to_number' => (string) ($row['to_number'] ?? ''),
            'call_direction' => (string) ($row['call_direction'] ?? ''),
            'call_status' => (string) ($row['call_status'] ?? ''),
            'call_duration' => (string) ($row['call_duration'] ?? ''),
            'call_duration_in_seconds' => (int) ($row['call_duration_in_seconds'] ?? 0),
            'recording_url' => is_string($recordingUrl) ? $recordingUrl : null,
            'sentiment_score' => (string) ($row['sentiment_score'] ?? ''),
            'sentiment_analysis_details' => (string) ($row['sentiment_analysis_details'] ?? ''),
            'channel_type' => (string) ($row['channel_type'] ?? ''),
            'is_voicemail' => (bool) ($row['is_voicemail'] ?? false),
            'is_call_transfer' => (bool) ($row['is_call_transfer'] ?? false),
            'call_request_id' => $this->normalizeCallRequestId($row['call_request_id'] ?? null),
            'transcript' => $this->formatCallTranscript((string) ($row['call_conversation'] ?? '')),
            'extracted_variables' => $this->normalizeExtractedVariables($row['extracted_variables'] ?? null),
            'dispatch_context' => [],
        ];
    }

    private function normalizeCallRequestId(mixed $raw): ?int
    {
        if (is_int($raw) && $raw > 0) {
            return $raw;
        }

        if (is_string($raw) && ctype_digit($raw) && (int) $raw > 0) {
            return (int) $raw;
        }

        if (is_array($raw)) {
            $id = $raw['id'] ?? null;
            if (is_int($id) && $id > 0) {
                return $id;
            }
            if (is_string($id) && ctype_digit($id) && (int) $id > 0) {
                return (int) $id;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeExtractedVariables(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $out[$key] = trim((string) ($value ?? ''));
            }
        }

        uksort($out, function (string $a, string $b) use ($out): int {
            $filledA = $this->extractedVariableHasValue($out[$a]);
            $filledB = $this->extractedVariableHasValue($out[$b]);
            if ($filledA !== $filledB) {
                return $filledA ? -1 : 1;
            }

            return strcasecmp($a, $b);
        });

        return $out;
    }

    private function extractedVariableHasValue(string $value): bool
    {
        $text = trim($value);
        if ($text === '') {
            return false;
        }

        return !in_array(strtolower($text), ['—', '-', 'n/a', 'na', 'none', 'null'], true);
    }

    private function formatCallTranscript(string $raw): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $raw) ?? $raw;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param  array{page?: int, page_size?: int, status?: string}  $filters
     * @return array{ok: bool, campaigns: array<int, array<string, mixed>>, total: int, error: ?string}
     */
    private function fetchBulkCalls(array $filters, ?string &$error = null): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(150, max(1, (int) ($filters['page_size'] ?? 30)));

        $query = [
            'pageno' => $page,
            'pagesize' => $pageSize,
        ];

        if (!empty($filters['status'])) {
            $query['status'] = (string) $filters['status'];
        }

        $response = $this->request('GET', '/calls/bulk_call', $query);

        if (!$response['ok']) {
            $error = $response['error'] ?? 'omnidimension_bulk_calls_failed';

            return ['ok' => false, 'campaigns' => [], 'total' => 0, 'error' => $error];
        }

        $body = $response['body'] ?? [];
        $campaigns = [];

        foreach ($body['records'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $campaigns[] = $this->normalizeBulkCallRow($row);
        }

        return [
            'ok' => true,
            'campaigns' => $campaigns,
            'total' => (int) ($body['total_records'] ?? count($campaigns)),
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeBulkCallRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'bot_name' => (string) ($row['bot_name'] ?? ''),
            'twilio_number' => (string) ($row['twilio_number'] ?? ''),
            'is_scheduled' => (bool) ($row['is_scheduled'] ?? false),
            'scheduled_datetime' => is_string($row['scheduled_datetime'] ?? null) ? $row['scheduled_datetime'] : null,
            'create_date' => (string) ($row['create_date'] ?? ''),
            'concurrent_call_limit' => (int) ($row['concurrent_call_limit'] ?? 1),
            'total_calls' => (int) ($row['total_calls'] ?? 0),
            'completed_calls' => (int) ($row['completed_calls'] ?? 0),
            'total_calls_made' => (int) ($row['total_calls_made'] ?? 0),
            'total_calls_to_dispatch' => (int) ($row['total_calls_to_dispatch'] ?? 0),
            'total_pending_calls' => (int) ($row['total_pending_calls'] ?? 0),
            'total_not_reachable_calls' => (int) ($row['total_not_reachable_calls'] ?? 0),
            'failed_reason' => is_string($row['failed_reason'] ?? null) ? $row['failed_reason'] : null,
        ];
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @param  array<string, mixed>  $json
     * @return array{ok: bool, body: ?array, error: ?string, http_status: ?int}
     */
    private function request(string $method, string $path, array $query = [], array $json = []): array
    {
        $apiKey = trim((string) config('services.omnidimension.api_key'));
        $baseUrl = rtrim((string) config('services.omnidimension.base_url', 'https://backend.omnidim.io/api/v1'), '/');
        $url = $baseUrl . $path;

        try {
            $pending = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('services.omnidimension.timeout', 30));

            $response = match (strtoupper($method)) {
                'GET' => $pending->get($url, $query),
                'POST' => $pending->post($url, $json),
                default => throw new \InvalidArgumentException('Unsupported HTTP method: ' . $method),
            };

            $body = $response->json();
            if (!is_array($body)) {
                $body = null;
            }

            if ($response->failed()) {
                $error = 'omnidimension_http_' . $response->status();
                Log::warning('OmniDimension API request failed', [
                    'method' => $method,
                    'path' => $path,
                    'http_status' => $response->status(),
                    'body' => $body ?? $response->body(),
                ]);

                return [
                    'ok' => false,
                    'body' => $body,
                    'error' => $error,
                    'http_status' => $response->status(),
                ];
            }

            return [
                'ok' => true,
                'body' => $body,
                'error' => null,
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('OmniDimension API exception', [
                'method' => $method,
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'body' => null,
                'error' => 'omnidimension_exception',
                'http_status' => null,
            ];
        }
    }
}
