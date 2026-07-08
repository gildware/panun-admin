<?php

namespace Modules\ServiceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Faq extends Model
{
    use HasFactory;
    use HasUuid;

    protected $casts = [
        'is_active' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $fillable = [
        'question',
        'answer',
        'service_id',
        'is_active',
        'sort_order',
    ];

    protected function scopeOfStatus($query, $status)
    {
        $query->where('is_active', $status);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('created_at');
    }

    protected static function newFactory()
    {
        return \Modules\ServiceManagement\Database\factories\FaqFactory::new();
    }
}
