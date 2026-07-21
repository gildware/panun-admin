<?php

namespace Modules\TaskBoardModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\Lead;

class TaskTicketLink extends Model
{
    use HasUuid;

    protected $fillable = [
        'ticket_id',
        'linkable_type',
        'linkable_id',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TaskTicket::class, 'ticket_id');
    }

    public function resolveLabel(): string
    {
        if ($this->linkable_type === 'booking') {
            $booking = Booking::query()->find($this->linkable_id);

            return $booking?->readable_id ?: ('Booking '.$this->linkable_id);
        }

        if ($this->linkable_type === 'lead') {
            $lead = Lead::query()->find($this->linkable_id);

            return $lead?->name ?: ('Lead #'.$this->linkable_id);
        }

        return $this->linkable_type.':'.$this->linkable_id;
    }

    public function resolveUrl(): ?string
    {
        try {
            if ($this->linkable_type === 'booking') {
                return route('admin.booking.details', [$this->linkable_id, 'web_page' => 'details']);
            }

            if ($this->linkable_type === 'lead') {
                return route('admin.lead.show', [$this->linkable_id]);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
