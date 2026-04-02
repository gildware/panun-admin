<?php

namespace Modules\WhatsAppModule\Services;

use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;

/**
 * CRM lead creation / typing for WhatsApp traffic (shared by internal API, webhooks, AI tools).
 */
class WhatsAppLeadLifecycleService
{
    public function normalizeLeadPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }

    public function ensureUnknownLeadForPhone(string $whatsAppPhone, ?string $name = null): ?Lead
    {
        $leadPhone = $this->normalizeLeadPhone($whatsAppPhone);
        if (!$leadPhone) {
            return null;
        }

        $existing = Lead::where('phone_number', $leadPhone)
            ->orderByDesc('id')
            ->get()
            ->first(fn (Lead $lead) => $this->isLeadOpen($lead));

        if ($existing) {
            if (empty($existing->handled_by)) {
                $existing->handled_by = 'AI';
                $existing->save();
            }

            return $existing;
        }

        return Lead::create([
            'name' => trim((string) ($name ?: ('WhatsApp ' . $leadPhone))),
            'phone_number' => $leadPhone,
            'lead_type' => Lead::TYPE_UNKNOWN,
            'date_time_of_lead_received' => now(),
            'handled_by' => 'AI',
            'created_by' => null,
        ]);
    }

    public function ensureLeadTypeForPhone(string $whatsAppPhone, string $leadType, ?string $name = null): ?Lead
    {
        $leadPhone = $this->normalizeLeadPhone($whatsAppPhone);
        if (!$leadPhone) {
            return null;
        }

        $existing = Lead::where('phone_number', $leadPhone)
            ->where('lead_type', $leadType)
            ->orderByDesc('id')
            ->get()
            ->first(fn (Lead $lead) => $this->isLeadOpen($lead));

        if ($existing) {
            if (empty($existing->handled_by)) {
                $existing->handled_by = 'AI';
                $existing->save();
            }

            return $existing;
        }

        $lead = Lead::create([
            'name' => trim((string) ($name ?: ('WhatsApp ' . $leadPhone))),
            'phone_number' => $leadPhone,
            'lead_type' => $leadType,
            'date_time_of_lead_received' => now(),
            'handled_by' => 'AI',
            'created_by' => null,
        ]);
        $this->seedDefaultTypeHistoryForTypedLead($lead);

        return $lead;
    }

    public function seedDefaultTypeHistoryForTypedLead(Lead $lead): void
    {
        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => Lead::TYPE_CUSTOMER,
                'data' => [
                    'customer_lead_status_id' => CustomerLeadStatus::defaultPendingStatusId(),
                    'booking_status' => 'pending',
                ],
                'created_by' => null,
            ]);
        } elseif ($lead->lead_type === Lead::TYPE_PROVIDER) {
            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => Lead::TYPE_PROVIDER,
                'data' => [
                    'provider_lead_status_id' => ProviderLeadStatus::defaultPendingStatusId(),
                ],
                'created_by' => null,
            ]);
        }
    }

    public function isLeadOpen(Lead $lead): bool
    {
        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            return true;
        }

        if (in_array($lead->lead_type, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER], true)) {
            return false;
        }

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $history = LeadTypeHistory::where('lead_id', $lead->id)
                ->where('type', Lead::TYPE_CUSTOMER)
                ->latest()
                ->first();
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $statusId = $data['customer_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = CustomerLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $history = LeadTypeHistory::where('lead_id', $lead->id)
                ->where('type', Lead::TYPE_PROVIDER)
                ->latest()
                ->first();
            $data = ($history && is_array($history->data)) ? $history->data : [];
            $statusId = $data['provider_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = ProviderLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

            return !in_array($baseType, ['completed', 'cancel'], true);
        }

        return false;
    }
}
