<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * User table in the WhatsApp (Neon) database only.
 * Not related to the main app's User model. Columns: id, phone, name, created_at, updated_at, alternate_phone, address, type (CUSTOMER/PROVIDER).
 */
class WhatsAppUser extends Model
{
    public function getTable()
    {
        return config('whatsappmodule.tables.users', 'whatsapp_users');
    }

    protected $fillable = [
        'phone',
        'name',
        'alternate_phone',
        'address',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
