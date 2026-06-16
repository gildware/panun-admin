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
}
