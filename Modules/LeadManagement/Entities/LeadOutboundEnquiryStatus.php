<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class LeadOutboundEnquiryStatus extends Model
{
    public const LINK_LEAD = 'lead';

    public const LINK_BOOKING = 'booking';

    protected $table = 'lead_outbound_enquiry_statuses';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'link_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function effectiveLinkType(): ?string
    {
        if (in_array($this->link_type, [self::LINK_LEAD, self::LINK_BOOKING], true)) {
            return $this->link_type;
        }

        $name = strtolower(trim((string) $this->name));

        if (str_contains($name, 'new lead') || str_contains($name, 'lead created')) {
            return self::LINK_LEAD;
        }

        if (str_contains($name, 'booked service') || str_contains($name, 'booking')) {
            return self::LINK_BOOKING;
        }

        return null;
    }

    public function requiresLeadLink(): bool
    {
        return $this->effectiveLinkType() === self::LINK_LEAD;
    }

    public function requiresBookingLink(): bool
    {
        return $this->effectiveLinkType() === self::LINK_BOOKING;
    }
}
