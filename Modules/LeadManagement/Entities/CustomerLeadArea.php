<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerLeadArea extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Find an existing active area by name (case-insensitive) or create a new one.
     */
    public static function resolveByName(string $name): ?self
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $existing = static::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return $existing;
        }

        return static::create(['name' => $name, 'is_active' => true]);
    }

    /**
     * Resolve an Area input (an existing id or a new free-typed name from Select2 tags) into an area id.
     */
    public static function resolveId(mixed $raw): ?int
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

        return static::resolveByName($raw)?->id;
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
