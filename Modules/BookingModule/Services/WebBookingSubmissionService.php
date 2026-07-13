<?php

namespace Modules\BookingModule\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\WebBooking;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\Source;
use Modules\LeadManagement\Services\LeadFollowupService;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;

class WebBookingSubmissionService
{
    public function __construct(
        protected WhatsAppLeadLifecycleService $leadLifecycle,
    ) {
    }

    /**
     * @param  array{name:string,phone:string,service:string,area:string,message:string,preferred_date?:string|null}  $payload
     */
    public function submit(array $payload): WebBooking
    {
        return DB::transaction(function () use ($payload) {
            $phone = $this->normalizePhone($payload['phone']);
            $lead = $this->ensureCustomerLead($phone, $payload);
            $webBooking = WebBooking::create([
                'reference_id' => $this->generateReferenceId(),
                'name' => $payload['name'],
                'phone' => $phone,
                'service_category' => $payload['service'],
                'area' => $payload['area'],
                'details' => $payload['message'],
                'preferred_date' => $payload['preferred_date'] ?? null,
                'status' => WebBooking::STATUS_PENDING_REVIEW,
                'lead_id' => $lead?->id,
            ]);

            if ($lead) {
                $this->syncLeadIntakeData($lead, $payload);
            }

            admin_inbox_notify_web_booking_submitted($webBooking);

            return $webBooking->fresh(['lead']);
        });
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 10) {
            return trim($phone);
        }

        return substr($digits, -10);
    }

    /**
     * @param  array{name:string,phone:string,service:string,area:string,message:string,preferred_date?:string|null}  $payload
     */
    protected function ensureCustomerLead(string $phone, array $payload): ?Lead
    {
        if ($phone === '') {
            return null;
        }

        if (User::findByContactPhoneScoped($phone, PROVIDER_USER_TYPES) !== null) {
            return null;
        }

        $sourceId = Source::ensureWebsiteDirectBookingSource()->id;
        $openLead = Lead::where('phone_number', $phone)
            ->orderByDesc('id')
            ->get()
            ->first(fn (Lead $lead) => $this->leadLifecycle->isLeadOpen($lead));

        if ($openLead) {
            $dirty = false;
            if (trim((string) ($openLead->name ?? '')) === '' && $payload['name'] !== '') {
                $openLead->name = $payload['name'];
                $dirty = true;
            }
            $openLead->source_id = $sourceId;
            $dirty = true;
            if ($dirty) {
                $openLead->save();
            }

            if ($openLead->lead_type !== Lead::TYPE_CUSTOMER) {
                $openLead->lead_type = Lead::TYPE_CUSTOMER;
                $openLead->save();
                $this->leadLifecycle->seedDefaultTypeHistoryForTypedLead($openLead);
            }

            return $openLead->fresh();
        }

        $lead = Lead::create([
            'name' => $payload['name'],
            'phone_number' => $phone,
            'source_id' => $sourceId,
            'lead_type' => Lead::TYPE_CUSTOMER,
            'date_time_of_lead_received' => now(),
            'handled_by' => null,
            'remarks' => $this->buildRemarks($payload),
            'created_by' => null,
            'next_followup_at' => app(LeadFollowupService::class)->defaultNextFollowupAt(),
        ]);

        LeadTypeHistory::create([
            'lead_id' => $lead->id,
            'type' => Lead::TYPE_CUSTOMER,
            'data' => $this->customerHistoryData($payload),
            'created_by' => null,
        ]);

        return $lead;
    }

    /**
     * @param  array{name:string,phone:string,service:string,area:string,message:string,preferred_date?:string|null}  $payload
     */
    protected function syncLeadIntakeData(Lead $lead, array $payload): void
    {
        $lead->remarks = $this->buildRemarks($payload);
        $lead->save();

        $history = LeadTypeHistory::where('lead_id', $lead->id)
            ->where('type', Lead::TYPE_CUSTOMER)
            ->latest()
            ->first();

        $data = array_merge(
            ($history && is_array($history->data)) ? $history->data : [
                'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
                'booking_status' => 'pending',
            ],
            $this->customerHistoryData($payload),
        );

        if ($history) {
            $history->update(['data' => $data]);
        } else {
            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => Lead::TYPE_CUSTOMER,
                'data' => $data,
                'created_by' => null,
            ]);
        }
    }

    /**
     * @param  array{service:string,area:string,message:string,preferred_date?:string|null}  $payload
     * @return array<string, mixed>
     */
    protected function customerHistoryData(array $payload): array
    {
        return array_filter([
            'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
            'booking_status' => 'pending',
            'service_description' => trim($payload['message']),
            'service_category_name' => trim($payload['service']),
            'area_text' => trim($payload['area']),
            'estimated_service_at' => $payload['preferred_date'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array{service:string,area:string,message:string,preferred_date?:string|null}  $payload
     */
    protected function buildRemarks(array $payload): string
    {
        $parts = array_filter([
            'Service: ' . trim($payload['service']),
            'Area: ' . trim($payload['area']),
            trim($payload['message']),
            !empty($payload['preferred_date']) ? 'Preferred: ' . trim((string) $payload['preferred_date']) : null,
        ]);

        return implode("\n", $parts);
    }

    protected function generateReferenceId(): string
    {
        do {
            $reference = 'WB-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (WebBooking::where('reference_id', $reference)->exists());

        return $reference;
    }
}
