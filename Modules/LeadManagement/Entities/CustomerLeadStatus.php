<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerLeadStatus extends Model
{
    public const BASE_TYPE_PENDING = 'pending';

    protected $fillable = [
        'name',
        'description',
        'base_type',
        'color',
        'is_active',
    ];

    protected $casts = [
        'base_type' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * Active status row with base_type pending (default for new / converted customer leads).
     */
    public static function defaultPendingStatusId(): ?int
    {
        $id = static::query()
            ->where('is_active', true)
            ->where('base_type', self::BASE_TYPE_PENDING)
            ->orderBy('id')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
}
