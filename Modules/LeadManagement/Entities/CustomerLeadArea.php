<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ZoneManagement\Entities\Zone;

class CustomerLeadArea extends Model
{
    protected $fillable = [
        'name',
        'zone_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    /**
     * Find an area by name inside a zone, or create it under that zone.
     * Without a zone, only unscoped areas are matched.
     */
    public static function resolveByName(string $name, ?string $zoneId = null): ?self
    {
        $name = trim($name);
        $zoneId = $zoneId !== null && $zoneId !== '' ? $zoneId : null;
        if ($name === '') {
            return null;
        }

        $existing = static::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($zoneId, fn ($query) => $query->where('zone_id', $zoneId), fn ($query) => $query->whereNull('zone_id'))
            ->first();
        if ($existing) {
            return $existing;
        }

        return static::create([
            'name' => mb_substr($name, 0, 255),
            'zone_id' => $zoneId,
            'is_active' => true,
        ]);
    }

    /**
     * Resolve an Area input (an existing id or a new free-typed name from Select2 tags) into an area id.
     */
    public static function resolveId(mixed $raw, ?string $zoneId = null): ?int
    {
        $raw = trim((string) ($raw ?? ''));
        if ($raw === '') {
            return null;
        }
        if (ctype_digit($raw)) {
            $existingId = static::query()->whereKey($raw)->value('id');
            if ($existingId !== null) {
                return (int) $existingId;
            }
        }

        return static::resolveByName($raw, $zoneId)?->id;
    }

    /**
     * @param  mixed  $rawValues  array of ids/names, a single value, or null
     * @return array<int, int>
     */
    public static function resolveIds(mixed $rawValues): array
    {
        if ($rawValues === null || $rawValues === '') {
            return [];
        }
        if (! is_array($rawValues)) {
            $rawValues = [$rawValues];
        }

        $ids = [];
        foreach ($rawValues as $raw) {
            $id = static::resolveId($raw);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function activeOrdered()
    {
        return static::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
