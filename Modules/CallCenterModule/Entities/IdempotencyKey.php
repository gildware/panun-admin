<?php

namespace Modules\CallCenterModule\Entities;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    public $timestamps = false;

    protected $table = 'call_center_idempotency_keys';

    protected $fillable = [
        'idempotency_key',
        'endpoint',
        'response_status',
        'response_body',
    ];

    protected $casts = [
        'response_body' => 'array',
        'created_at' => 'datetime',
    ];
}
