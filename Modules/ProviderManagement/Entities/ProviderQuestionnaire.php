<?php

namespace Modules\ProviderManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class ProviderQuestionnaire extends Model
{
    use HasUuid;

    protected $table = 'provider_questionnaires';

    protected $fillable = [
        'provider_id',
        'answers',
        'recorded_by',
        'updated_by',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
