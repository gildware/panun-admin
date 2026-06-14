<?php

namespace Modules\CartModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class CustomerCartContact extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = ['customer_id', 'contacted_by', 'contacted_at', 'note'];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function contactedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }
}
