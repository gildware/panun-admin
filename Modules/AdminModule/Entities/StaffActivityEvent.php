<?php

namespace Modules\AdminModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class StaffActivityEvent extends Model
{
    public const TYPE_LEAD_ASSIGNED = 'lead.assigned';

    public const TYPE_WHATSAPP_ASSIGNED_FROM_AI = 'whatsapp.assigned_from_ai';

    public const TYPE_WHATSAPP_ASSIGNED_FROM_EMPLOYEE = 'whatsapp.assigned_from_employee';

    public const TYPE_WHATSAPP_CHAT_CLOSED = 'whatsapp.chat_closed';

    protected $table = 'staff_activity_events';

    protected $fillable = [
        'employee_id',
        'actor_id',
        'event_type',
        'subject_type',
        'subject_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
