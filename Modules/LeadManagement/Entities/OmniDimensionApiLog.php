<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class OmniDimensionApiLog extends Model
{
    public $timestamps = false;

    protected $table = 'omnidimension_api_logs';

    protected $fillable = [
        'method',
        'path',
        'query_params',
        'request_body',
        'http_status',
        'response_body',
        'ok',
        'error',
        'duration_ms',
        'triggered_by',
        'created_at',
    ];

    protected $casts = [
        'query_params' => 'array',
        'http_status' => 'integer',
        'ok' => 'boolean',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
