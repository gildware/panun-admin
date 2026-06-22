<?php

namespace Modules\CallCenterModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recording extends Model
{
    protected $table = 'call_center_recordings';

    protected $fillable = [
        'external_id',
        'call_id',
        'recording_url',
        'duration_seconds',
        'file_size_bytes',
        'format',
        'storage_provider',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }
}
