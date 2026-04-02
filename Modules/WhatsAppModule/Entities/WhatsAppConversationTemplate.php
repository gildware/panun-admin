<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class WhatsAppConversationTemplate extends Model
{
    protected $table = 'whatsapp_conversation_templates';

    protected $fillable = [
        'title',
        'body',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        $table = $query->getModel()->getTable();
        if (! Schema::hasColumn($table, 'is_active')) {
            return $query;
        }

        return $query->where($table . '.is_active', true);
    }
}
