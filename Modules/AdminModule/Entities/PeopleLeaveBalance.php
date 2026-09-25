<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleLeaveBalance extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'year',
        'casual_allowance',
        'casual_used',
        'sick_allowance',
        'sick_used',
        'earned_allowance',
        'earned_used',
        'extra',
    ];

    protected $casts = [
        'casual_allowance' => 'float',
        'casual_used' => 'float',
        'sick_allowance' => 'float',
        'sick_used' => 'float',
        'earned_allowance' => 'float',
        'earned_used' => 'float',
        'extra' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allowance(string $type): float
    {
        if ($this->usesColumn($type)) {
            return round((float) $this->{$type.'_allowance'}, 1);
        }

        return round((float) data_get($this->extra, $type.'.allowance', 0), 1);
    }

    public function used(string $type): float
    {
        if ($this->usesColumn($type)) {
            return (float) $this->{$type.'_used'};
        }

        return (float) data_get($this->extra, $type.'.used', 0);
    }

    public function remaining(string $type): float
    {
        return $this->allowance($type) - $this->used($type);
    }

    public function addAllowance(string $type, float $days): void
    {
        $days = round($days, 1);
        if ($this->usesColumn($type)) {
            $column = $type.'_allowance';
            $this->{$column} = round((float) $this->{$column} + $days, 1);

            return;
        }

        $extra = $this->extra ?? [];
        $extra[$type]['allowance'] = round((float) ($extra[$type]['allowance'] ?? 0) + $days, 1);
        $extra[$type]['used'] = (float) ($extra[$type]['used'] ?? 0);
        $this->extra = $extra;
    }

    public function addUsed(string $type, float $days): void
    {
        $days = round($days, 1);
        if ($this->usesColumn($type)) {
            $column = $type.'_used';
            $this->{$column} = round((float) $this->{$column} + $days, 1);

            return;
        }

        $extra = $this->extra ?? [];
        $extra[$type]['allowance'] = (float) ($extra[$type]['allowance'] ?? 0);
        $extra[$type]['used'] = round((float) ($extra[$type]['used'] ?? 0) + $days, 1);
        $this->extra = $extra;
    }

    public function restoreUsed(string $type, float $days): void
    {
        $days = round($days, 1);
        if ($this->usesColumn($type)) {
            $column = $type.'_used';
            $this->{$column} = max(0, round((float) $this->{$column} - $days, 1));

            return;
        }

        $extra = $this->extra ?? [];
        $extra[$type]['allowance'] = (float) ($extra[$type]['allowance'] ?? 0);
        $extra[$type]['used'] = max(0, round((float) ($extra[$type]['used'] ?? 0) - $days, 1));
        $this->extra = $extra;
    }

    private function usesColumn(string $type): bool
    {
        return in_array($type, ['casual', 'sick', 'earned'], true);
    }
}
