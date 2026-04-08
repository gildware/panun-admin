<?php

namespace Modules\WhatsAppModule\Services;

use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;

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
            return $this->touchAiOpenLead($existing, $name);
        }

        return Lead::create([
            'name' => trim((string) ($name ?: ('WhatsApp ' . $leadPhone))),
            'phone_number' => $leadPhone,
            'source_id' => Source::ensureAiChatSource()->id,
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

        // 1) Reuse an already-open lead of the requested type (newest first).
        $sameTypeOpen = Lead::where('phone_number', $leadPhone)
            ->where('lead_type', $leadType)
            ->orderByDesc('id')
            ->get()
            ->first(fn (Lead $lead) => $this->isLeadOpen($lead));

        if ($sameTypeOpen) {
            return $this->touchAiOpenLead($sameTypeOpen, $name);
        }

        // 2) Upgrade the AI "unknown" thread lead in place — do not create a second row for the same chat.
        if (in_array($leadType, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)) {
            $unknownOpen = Lead::where('phone_number', $leadPhone)
                ->where('lead_type', Lead::TYPE_UNKNOWN)
                ->orderBy('id')
                ->get()
                ->first(fn (Lead $lead) => $this->isLeadOpen($lead));

            if ($unknownOpen) {
                return $this->convertUnknownOpenLeadToType($unknownOpen, $leadType, $name);
            }
        }

        $lead = Lead::create([
            'name' => trim((string) ($name ?: ('WhatsApp ' . $leadPhone))),
            'phone_number' => $leadPhone,
            'source_id' => Source::ensureAiChatSource()->id,
            'lead_type' => $leadType,
            'date_time_of_lead_received' => now(),
            'handled_by' => 'AI',
            'created_by' => null,
        ]);
        $this->seedDefaultTypeHistoryForTypedLead($lead);

        return $lead;
    }

    /**
     * Keep AI-handled fields fresh on an existing open lead.
     */
    protected function touchAiOpenLead(Lead $lead, ?string $name): Lead
    {
        $dirty = false;
        if (empty($lead->handled_by)) {
            $lead->handled_by = 'AI';
            $dirty = true;
        }
        if ($lead->source_id === null) {
            $lead->source_id = Source::ensureAiChatSource()->id;
            $dirty = true;
        }
        if ($name !== null && trim((string) $name) !== '') {
            $trimmed = trim((string) $name);
            $current = trim((string) ($lead->name ?? ''));
            if ($current === '' || str_starts_with($current, 'WhatsApp ')) {
                $lead->name = $trimmed;
                $dirty = true;
            }
        }
        if ($dirty) {
            $lead->save();
        }

        return $lead;
    }

    protected function convertUnknownOpenLeadToType(Lead $lead, string $newType, ?string $name): Lead
    {
        $lead->lead_type = $newType;
        if ($name !== null && trim((string) $name) !== '') {
            $lead->name = trim((string) $name);
        }
        if (empty($lead->handled_by)) {
            $lead->handled_by = 'AI';
        }
        if ($lead->source_id === null) {
            $lead->source_id = Source::ensureAiChatSource()->id;
        }
        $lead->save();
        $this->seedDefaultTypeHistoryForTypedLead($lead);

        return $lead->fresh();
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
