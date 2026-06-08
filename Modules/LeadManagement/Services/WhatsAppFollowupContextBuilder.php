<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Support\Collection;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\District;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Services\WhatsAppLeadLifecycleService;
use Modules\ZoneManagement\Entities\Zone;

class WhatsAppFollowupContextBuilder
{
    public function __construct(
        private readonly WhatsAppLeadLifecycleService $leadLifecycle,
        private readonly OutboundCallContextService $outboundCallContext,
        private readonly WhatsAppFollowupSummaryService $summaryService
    ) {}

    /**
     * @param  array<string, mixed>  $candidate
     * @return array{context: array<string, string>, lead_summary_preview: string, conversation_recap: string}
     */
    public function buildForCandidate(array $candidate, bool $includeAiSummary = true): array
    {
        $lead = isset($candidate['lead_id']) ? Lead::query()->find((int) $candidate['lead_id']) : null;
        $waPhone = (string) ($candidate['phone'] ?? '');

        $structured = $lead ? $this->structuredFromLead($lead) : [];
        $structured['call_reason'] = OutboundCallContextService::CALL_REASON_WHATSAPP_FOLLOWUP;

        $name = WhatsAppLeadLifecycleService::realCustomerName($candidate['display_name'] ?? null);
        if ($name !== null && empty($structured['customer_name'])) {
            $structured['customer_name'] = $name;
        }

        if (!empty($structured['customer_name'])) {
            $realName = WhatsAppLeadLifecycleService::realCustomerName($structured['customer_name']);
            if ($realName === null) {
                unset($structured['customer_name']);
            } else {
                $structured['customer_name'] = $realName;
            }
        }

        $aiSummary = null;
        if ($includeAiSummary) {
            $cached = $this->summaryService->getCachedSummary($waPhone);
            if ($cached['is_current'] && $cached['summary'] !== null && $cached['summary'] !== '') {
                $aiSummary = $cached['summary'];
            }
        }
        $fallbackRecap = $this->buildFallbackRecap($waPhone, $candidate);

        if ($aiSummary !== null && $aiSummary !== '') {
            $structured['lead_summary'] = $aiSummary;
            $conversationRecap = $aiSummary;
        } else {
            $structured['lead_summary'] = $fallbackRecap;
            $conversationRecap = $fallbackRecap;
        }

        $silentLabel = (string) ($candidate['silent_duration_label'] ?? '');
        if ($silentLabel !== '') {
            $structured['notes'] = trim(($structured['notes'] ?? '') . ' Silent on WhatsApp for ' . $silentLabel . '.');
        }

        $context = $this->outboundCallContext->build($structured);
        $preview = $this->buildPreviewText($structured, $conversationRecap);

        return [
            'context' => $context,
            'lead_summary_preview' => $preview,
            'conversation_recap' => $conversationRecap,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function cachedSummaryOnly(array $candidate): ?array
    {
        $waPhone = (string) ($candidate['phone'] ?? '');
        if ($waPhone === '') {
            return null;
        }

        $cached = $this->summaryService->getCachedSummary($waPhone);
        if ($cached['summary'] === null || $cached['summary'] === '') {
            return null;
        }

        return [
            'summary' => $cached['summary'],
            'is_current' => $cached['is_current'],
            'needs_refresh' => $cached['needs_refresh'],
            'ai_generated' => true,
            'from_cache' => true,
        ];
    }

    /**
     * Generate (or return valid cache / incremental update) on explicit user action.
     *
     * @return array<string, mixed>|null
     */
    public function generateSummaryForCandidate(array $candidate): ?array
    {
        $waPhone = (string) ($candidate['phone'] ?? '');
        if ($waPhone === '') {
            return null;
        }

        $result = $this->summaryService->summarizeWithMeta($waPhone, $candidate);
        $aiSummary = $result['summary'] ?? null;

        $text = ($aiSummary !== null && $aiSummary !== '')
            ? $aiSummary
            : $this->buildFallbackRecap($waPhone, $candidate);

        return [
            'summary' => $text,
            'ai_generated' => $aiSummary !== null && $aiSummary !== '',
            'from_cache' => (bool) ($result['from_cache'] ?? false),
            'ai_called' => (bool) ($result['ai_called'] ?? false),
            'is_current' => true,
            'needs_refresh' => false,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function summaryOnly(array $candidate): ?array
    {
        return $this->cachedSummaryOnly($candidate);
    }

    /**
     * @return array<int, array{role: string, text: string, at: string}>
     */
    public function conversationPreview(string $waPhone, int $limit = 40): array
    {
        return $this->loadRecentMessages($waPhone, $limit)
            ->map(fn (WhatsAppMessage $m) => [
                'role' => $m->direction === 'IN' ? 'customer' : (strtoupper((string) ($m->sent_by ?? '')) === 'AI' ? 'ai' : 'agent'),
                'text' => trim((string) $m->message_text),
                'at' => $m->created_at?->toIso8601String() ?? '',
            ])
            ->filter(fn (array $row) => $row['text'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function buildFallbackRecap(string $waPhone, array $candidate): string
    {
        $messages = $this->loadRecentMessages($waPhone, 20);
        $parts = [];
        $lastAi = trim((string) ($candidate['last_ai_message'] ?? ''));
        if ($lastAi !== '') {
            $parts[] = 'Last AI message: ' . $this->truncate($lastAi, 280);
        }

        $customerLines = $messages->filter(fn (WhatsAppMessage $m) => $m->direction === 'IN')
            ->map(fn (WhatsAppMessage $m) => trim((string) $m->message_text))
            ->filter()
            ->take(-3)
            ->values();

        if ($customerLines->isNotEmpty()) {
            $parts[] = 'Recent customer messages: ' . $customerLines->map(fn (string $t) => $this->truncate($t, 120))->implode(' | ');
        }

        return $this->truncate(implode(' ', $parts), 1900);
    }

    /**
     * @return Collection<int, WhatsAppMessage>
     */
    private function loadRecentMessages(string $waPhone, int $limit): Collection
    {
        if ($waPhone === '') {
            return collect();
        }

        return WhatsAppMessage::query()
            ->where('phone', $waPhone)
            ->orderByDesc('id')
            ->limit(max(1, min($limit, 50)))
            ->get(['id', 'message_text', 'direction', 'sent_by', 'created_at'])
            ->reverse()
            ->values();
    }

    /**
     * @return array<string, string>
     */
    private function structuredFromLead(Lead $lead): array
    {
        $out = [
            'notes' => trim((string) ($lead->remarks ?? '')),
        ];

        $customerName = WhatsAppLeadLifecycleService::realCustomerName($lead->name ?? null);
        if ($customerName !== null) {
            $out['customer_name'] = $customerName;
        }

        $history = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->where('type', $lead->lead_type)
            ->orderByDesc('created_at')
            ->first();

        $data = is_array($history?->data) ? $history->data : [];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $statusId = $data['customer_lead_status_id'] ?? null;
            if ($statusId) {
                $status = CustomerLeadStatus::query()->find($statusId);
                if ($status) {
                    $out['lead_status'] = (string) $status->name;
                }
            }

            $categoryId = $data['service_category'] ?? null;
            if ($categoryId) {
                $cat = Category::query()->find($categoryId);
                if ($cat) {
                    $out['service_category'] = (string) $cat->name;
                }
            }

            $subId = $data['service_subcategory'] ?? null;
            $serviceId = $data['service_name'] ?? null;
            $details = [];
            if ($subId) {
                $sub = Category::query()->find($subId);
                if ($sub) {
                    $details[] = (string) $sub->name;
                }
            }
            if ($serviceId) {
                $svc = \Modules\ServiceManagement\Entities\Service::query()->find($serviceId);
                if ($svc) {
                    $details[] = (string) $svc->name;
                }
            }
            if (!empty($data['service_description'])) {
                $details[] = (string) $data['service_description'];
            }
            if ($details !== []) {
                $out['service_details'] = implode(' — ', $details);
            }

            if (!empty($data['zone_id'])) {
                $zone = Zone::query()->find($data['zone_id']);
                if ($zone) {
                    $out['area'] = (string) ($zone->name ?? '');
                }
            }

            if (!empty($data['estimated_service_at'])) {
                try {
                    $dt = \Carbon\Carbon::parse($data['estimated_service_at']);
                    $out['preferred_date'] = $dt->format('Y-m-d');
                    $out['preferred_time'] = $dt->format('H:i');
                } catch (\Throwable) {
                    // ignore
                }
            }
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $statusId = $data['provider_lead_status_id'] ?? null;
            if ($statusId) {
                $status = ProviderLeadStatus::query()->find($statusId);
                if ($status) {
                    $out['lead_status'] = (string) $status->name;
                }
            }

            if (!empty($data['district_id'])) {
                $district = District::query()->find($data['district_id']);
                if ($district) {
                    $out['district'] = (string) $district->name;
                }
            }

            if (!empty($data['full_address'])) {
                $out['area'] = (string) $data['full_address'];
            }

            if (!empty($data['service_areas'])) {
                $out['service_details'] = (string) $data['service_areas'];
            }
        }

        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            $out['lead_status'] = 'Unknown';
        }

        $tags = $lead->customerLeadTags()->pluck('name')->filter()->implode(', ');
        if ($tags !== '') {
            $out['notes'] = trim(($out['notes'] ?? '') . ' Tags: ' . $tags);
        }

        return array_filter($out, fn (string $v) => trim($v) !== '');
    }

    /**
     * @param  array<string, string>  $structured
     */
    private function buildPreviewText(array $structured, string $recap): string
    {
        if (trim($recap) !== '') {
            return $this->truncate($recap, 320);
        }

        $chunks = [];
        if (!empty($structured['service_category'])) {
            $chunks[] = $structured['service_category'];
        }
        if (!empty($structured['lead_status'])) {
            $chunks[] = $structured['lead_status'];
        }

        return implode(' · ', $chunks) ?: '—';
    }

    private function truncate(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? $text;
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1) . '…';
    }
}
