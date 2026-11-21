<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PeopleTimesheetSetting extends Model
{
    use HasUuid;

    protected $fillable = [
        'min_hours',
        'min_hours_full_time',
        'min_hours_part_time',
        'week_off',
        'starts_on',
        'popup_enabled',
        'popup_interval_seconds',
    ];

    protected $casts = [
        'min_hours' => 'float',
        'min_hours_full_time' => 'float',
        'min_hours_part_time' => 'float',
        'week_off' => 'array',
        'starts_on' => 'date',
        'popup_enabled' => 'boolean',
        'popup_interval_seconds' => 'integer',
    ];

    public function popupIsEnabled(): bool
    {
        $attributes = $this->getAttributes();
        if (! array_key_exists('popup_enabled', $attributes) || $attributes['popup_enabled'] === null) {
            return true;
        }

        return (bool) $this->popup_enabled;
    }

    public function popupIntervalSeconds(): int
    {
        $attributes = $this->getAttributes();
        if (! array_key_exists('popup_interval_seconds', $attributes) || $attributes['popup_interval_seconds'] === null) {
            return 1800;
        }

        return max(5, min(86400, (int) $this->popup_interval_seconds));
    }

    /**
     * @return array{value: int, unit: string}
     */
    public function popupIntervalParts(): array
    {
        return self::splitPopupInterval($this->popupIntervalSeconds());
    }

    /**
     * @return array{value: int, unit: string}
     */
    public static function splitPopupInterval(int $seconds): array
    {
        $seconds = max(5, min(86400, $seconds));
        if ($seconds >= 3600 && $seconds % 3600 === 0) {
            return ['value' => intdiv($seconds, 3600), 'unit' => 'hours'];
        }
        if ($seconds >= 60 && $seconds % 60 === 0) {
            return ['value' => intdiv($seconds, 60), 'unit' => 'minutes'];
        }

        return ['value' => $seconds, 'unit' => 'seconds'];
    }

    public static function popupIntervalToSeconds(int $value, string $unit): int
    {
        $seconds = match ($unit) {
            'hours' => $value * 3600,
            'minutes' => $value * 60,
            default => $value,
        };

        return max(5, min(86400, $seconds));
    }
}
