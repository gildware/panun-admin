<?php

namespace Modules\BookingModule\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\AppCustomRequest;
use Modules\BookingModule\Entities\AppCustomRequestMessage;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\Source;
use Modules\LeadManagement\Services\LeadFollowupService;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;

class AppCustomRequestSubmissionService
{
    public function __construct(
        protected WhatsAppLeadLifecycleService $leadLifecycle,
    ) {
    }

    /**
     * @param  array{customer_id?:string|null,name:string,phone:string,category_id?:string|null,category_name:string,description:string}  $payload
     */
    public function submit(array $payload): AppCustomRequest
    {
        $request = DB::transaction(function () use ($payload) {
            $phone = $this->normalizePhone($payload['phone']);
            $lead = $this->safeEnsureCustomerLead($phone, $payload);

            $request = AppCustomRequest::create([
                'reference_id' => $this->generateReferenceId(),
                'customer_id' => $payload['customer_id'] ?? null,
                'name' => $payload['name'],
                'phone' => $phone,
                'category_id' => $payload['category_id'] ?? null,
                'category_name' => $payload['category_name'],
                'description' => $payload['description'],
                'status' => AppCustomRequest::STATUS_PENDING,
                'lead_id' => $lead?->id,
            ]);

            AppCustomRequestMessage::create([
                'app_custom_request_id' => $request->id,
                'sender_type' => AppCustomRequestMessage::SENDER_CUSTOMER,
                'sender_id' => $payload['customer_id'] ?? null,
                'message' => $payload['description'],
            ]);

            if ($lead) {
                $this->safeSyncLeadIntakeData($lead, $payload);
            }

            return $request->fresh(['lead']);
        });

        try {
            send_app_custom_request_submitted_notifications($request);
        } catch (\Throwable $e) {
            report($e);
        }

        return $request;
    }

    protected function safeEnsureCustomerLead(string $phone, array $payload): ?Lead
    {
        try {
            return $this->ensureCustomerLead($phone, $payload);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    protected function safeSyncLeadIntakeData(Lead $lead, array $payload): void
    {
        try {
            $this->syncLeadIntakeData($lead, $payload);
        } catch (\Throwable $e) {
            report($e);
        }
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
     * @param  array{name:string,phone:string,category_name:string,description:string}  $payload
     */
    protected function ensureCustomerLead(string $phone, array $payload): ?Lead
    {
        if ($phone === '') {
            return null;
        }

        if (User::findByContactPhoneScoped($phone, PROVIDER_USER_TYPES) !== null) {
            return null;
        }

        $sourceId = Source::ensureAppCustomRequestSource()->id;
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
     * @param  array{category_name:string,description:string}  $payload
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
     * @param  array{category_name:string,description:string}  $payload
     * @return array<string, mixed>
     */
    protected function customerHistoryData(array $payload): array
    {
        return array_filter([
            'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
            'booking_status' => 'pending',
            'service_description' => trim($payload['description']),
            'service_category_name' => trim($payload['category_name']),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array{category_name:string,description:string}  $payload
     */
    protected function buildRemarks(array $payload): string
    {
        $parts = array_filter([
            'Category: ' . trim($payload['category_name']),
            trim($payload['description']),
        ]);

        return implode("\n", $parts);
    }

    protected function generateReferenceId(): string
    {
        do {
            $reference = 'ACR-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (AppCustomRequest::where('reference_id', $reference)->exists());

        return $reference;
    }
}
