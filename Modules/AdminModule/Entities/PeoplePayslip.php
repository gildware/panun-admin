<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeoplePayslip extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'period',
        'gross',
        'deductions',
        'net',
        'status',
        'published_at',
        'breakdown',
        'lop_days',
        'held',
    ];

    protected $casts = [
        'gross' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net' => 'decimal:2',
        'lop_days' => 'decimal:1',
        'held' => 'boolean',
        'breakdown' => 'array',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
