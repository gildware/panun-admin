<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeopleLeavePolicy extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'leave_type_id',
        'accrual_type',
        'days',
    ];

    protected $casts = [
        'days' => 'float',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(PeopleLeaveType::class, 'leave_type_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PeopleLeaveAssignment::class, 'leave_policy_id');
    }

    public function summary(): string
    {
        $label = strtolower($this->leaveType->name ?? 'leave');
        $when = $this->accrual_type === 'monthly' ? 'each month' : 'each year';

        return self::formatDays((float) $this->days).' '.$label.' '.$when;
    }

    public static function formatDays(float $days): string
    {
        $days = round($days, 1);

        return abs($days - round($days)) < 0.001
            ? (string) (int) round($days)
            : number_format($days, 1);
    }
}
