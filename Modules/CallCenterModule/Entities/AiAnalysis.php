<?php

namespace Modules\CallCenterModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    protected $table = 'call_center_ai_analyses';

    protected $fillable = [
        'external_id',
        'call_id',
        'transcript',
        'summary',
        'intent',
        'sentiment',
        'sentiment_score',
        'suggested_actions',
        'generated_notes',
        'language',
        'processed_at',
    ];

    protected $casts = [
        'suggested_actions' => 'array',
        'sentiment_score' => 'float',
        'processed_at' => 'datetime',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }
}
