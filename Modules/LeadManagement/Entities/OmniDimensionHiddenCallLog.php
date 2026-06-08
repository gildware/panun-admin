<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OmniDimensionHiddenCallLog extends Model
{
    protected $table = 'omnidimension_hidden_call_logs';

    protected $fillable = [
        'omnidim_call_log_id',
        'hidden_by',
    ];

    /**
     * @return array<int, int>
     */
    public static function hiddenIds(): array
    {
        return static::query()
            ->pluck('omnidim_call_log_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'hidden_by', 'id');
    }
}
