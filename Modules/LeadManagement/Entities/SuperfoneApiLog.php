<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class SuperfoneApiLog extends Model
{
    protected $fillable = [
        'direction',
        'kind',
        'event',
        'cdr_uuid',
        'method',
        'url',
        'http_status',
        'result',
        'message',
        'payload',
        'duration_ms',
    ];

    protected $casts = [
        'payload' => 'array',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
    ];
}
