<?php

namespace Modules\ProviderManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class FeedbackTagConfig extends Model
{
    protected $table = 'feedback_tag_configs';

    protected $fillable = [
        'entity_type',
        'feedback_type',
        'tag_key',
        'label',
        'score',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'score' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];
}

