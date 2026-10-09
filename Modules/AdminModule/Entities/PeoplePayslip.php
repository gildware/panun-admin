<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * People marked No billable are kept off payroll.
     */
    public function scopeForPayroll(Builder $query): Builder
    {
        $hidden = PeopleProfile::query()->where('billing_type', 'non_billable')->pluck('user_id');
        if ($hidden->isNotEmpty()) {
            $query->whereNotIn($query->qualifyColumn('user_id'), $hidden);
        }

        return $query;
    }
}
