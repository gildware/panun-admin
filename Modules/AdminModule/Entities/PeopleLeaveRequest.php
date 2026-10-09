<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleLeaveRequest extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'leave_type',
        'starts_on',
        'ends_on',
        'days',
        'hours',
        'from_time',
        'to_time',
        'year_split',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'days' => 'float',
        'hours' => 'float',
        'year_split' => 'array',
        'decided_at' => 'datetime',
    ];

    /**
     * @return array<int, float>
     */
    public function daysByYear(): array
    {
        $split = $this->year_split;
        if (is_array($split) && $split !== []) {
            $days = [];
            foreach ($split as $year => $count) {
                $days[(int) $year] = round((float) $count, 1);
            }

            return $days;
        }

        return [(int) $this->starts_on->year => round((float) $this->days, 1)];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
