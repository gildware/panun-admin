<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\LeadManagement\Services\OutboundCallContextService;

class OmniDimensionCallDispatch extends Model
{
    protected $table = 'omnidimension_call_dispatches';

    protected $fillable = [
        'omnidim_request_id',
        'omnidim_call_log_id',
        'to_number_e164',
        'call_context',
        'dispatched_by',
    ];

    protected $casts = [
        'omnidim_request_id' => 'integer',
        'omnidim_call_log_id' => 'integer',
        'call_context' => 'array',
    ];

    /**
     * Attach stored dispatch context onto normalized OmniDimension call log rows.
     *
     * @param  array<int, array<string, mixed>>  $callLogs
     * @return array<int, array<string, mixed>>
     */
    public static function attachContextToCallLogs(array $callLogs): array
    {
        if ($callLogs === []) {
            return $callLogs;
        }

        $requestIds = [];
        $callLogIds = [];

        foreach ($callLogs as $call) {
            $requestId = (int) ($call['call_request_id'] ?? 0);
            if ($requestId > 0) {
                $requestIds[] = $requestId;
            }

            $callLogId = (int) ($call['id'] ?? 0);
            if ($callLogId > 0) {
                $callLogIds[] = $callLogId;
            }
        }

        $byRequestId = [];
        $byCallLogId = [];

        if ($requestIds !== []) {
            foreach (static::query()->whereIn('omnidim_request_id', array_values(array_unique($requestIds)))->get() as $row) {
                if ($row->omnidim_request_id) {
                    $byRequestId[(int) $row->omnidim_request_id] = $row->normalizedContext();
                }
            }
        }

        if ($callLogIds !== []) {
            foreach (static::query()->whereIn('omnidim_call_log_id', array_values(array_unique($callLogIds)))->get() as $row) {
                if ($row->omnidim_call_log_id) {
                    $byCallLogId[(int) $row->omnidim_call_log_id] = $row->normalizedContext();
                }
            }
        }

        return array_map(function (array $call) use ($byRequestId, $byCallLogId): array {
            $requestId = (int) ($call['call_request_id'] ?? 0);
            $callLogId = (int) ($call['id'] ?? 0);

            $context = [];
            if ($requestId > 0 && isset($byRequestId[$requestId])) {
                $context = $byRequestId[$requestId];
            } elseif ($callLogId > 0 && isset($byCallLogId[$callLogId])) {
                $context = $byCallLogId[$callLogId];
            }

            $call['dispatch_context'] = $context;

            return $call;
        }, $callLogs);
    }

    /**
     * @return array<string, string>
     */
    public function normalizedContext(): array
    {
        $raw = is_array($this->call_context) ? $this->call_context : [];
        $out = [];

        foreach (OutboundCallContextService::CONTEXT_KEYS as $key) {
            $value = trim((string) ($raw[$key] ?? ''));
            if ($value !== '') {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'dispatched_by', 'id');
    }
}
