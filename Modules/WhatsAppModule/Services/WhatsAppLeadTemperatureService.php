<?php

namespace Modules\WhatsAppModule\Services;

use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadChangeLog;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Services\LeadChangeLogService;
use Modules\WhatsAppModule\Entities\ProviderLead;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Support\WhatsAppThreadPhoneKeys;

/**
 * Sets customer/provider lead temperature from what the WhatsApp AI has already saved.
 * Temperature only moves up, except the untouched default Warm may become Cold on a bare enquiry.
 */
class WhatsAppLeadTemperatureService
{
    /** @var array<string, int> */
    private const RANKS = [
        Lead::TEMPERATURE_COLD => 1,
        Lead::TEMPERATURE_WARM => 2,
        Lead::TEMPERATURE_HOT => 3,
        Lead::TEMPERATURE_HOTTER => 4,
    ];

    public function __construct(
        protected WhatsAppLeadLifecycleService $leadLifecycle,
        protected LeadChangeLogService $changeLog,
    ) {}

    /**
     * @return list<array{lead_id: int, lead_type: string, lead_temperature: string}>
     */
    public function applyFromDiscussion(string $phone): array
    {
        $applied = [];

        $customer = $this->applyCustomer($phone);
        if ($customer !== null) {
            $applied[] = $customer;
        }

        $provider = $this->applyProvider($phone);
        if ($provider !== null) {
            $applied[] = $provider;
        }

        return $applied;
    }

    /**
     * @return array{lead_id: int, lead_type: string, lead_temperature: string}|null
     */
    private function applyCustomer(string $phone): ?array
    {
        $lead = $this->openLead($phone, Lead::TYPE_CUSTOMER);
        if (!$lead) {
            return null;
        }

        $temperature = $this->strongestCustomerTemperature($phone);
        if ($temperature === null) {
            $blob = $this->recentInboundText($phone);
            if ($blob === '' || WhatsAppAiBookingIntentDetector::looksLikeProviderOnboarding($blob)) {
                return null;
            }
            if (
                WhatsAppAiBookingIntentDetector::looksLikeCustomerServiceNeed($blob)
                || WhatsAppAiBookingIntentDetector::looksLikeCatalogBrowseOnly($blob)
            ) {
                $temperature = Lead::TEMPERATURE_COLD;
            }
        }

        if ($temperature === null || !$this->raise($lead, $temperature)) {
            return null;
        }

        return [
            'lead_id' => (int) $lead->id,
            'lead_type' => Lead::TYPE_CUSTOMER,
            'lead_temperature' => $temperature,
        ];
    }

    /**
     * @return array{lead_id: int, lead_type: string, lead_temperature: string}|null
     */
    private function applyProvider(string $phone): ?array
    {
        $lead = $this->openLead($phone, Lead::TYPE_PROVIDER);
        if (!$lead) {
            return null;
        }

        $temperature = $this->strongestProviderTemperature($phone);
        if ($temperature === null && WhatsAppAiBookingIntentDetector::looksLikeProviderOnboarding($this->recentInboundText($phone))) {
            $temperature = Lead::TEMPERATURE_COLD;
        }

        if ($temperature === null || !$this->raise($lead, $temperature)) {
            return null;
        }

        return [
            'lead_id' => (int) $lead->id,
            'lead_type' => Lead::TYPE_PROVIDER,
            'lead_temperature' => $temperature,
        ];
    }

    public static function temperatureForCustomerBooking(WhatsAppBooking $booking): ?string
    {
        $status = (string) ($booking->status ?? '');
        if (in_array($status, [WhatsAppBooking::STATUS_TENTATIVE_PENDING_HUMAN, WhatsAppBooking::STATUS_HUMAN_CONFIRMED], true)) {
            return Lead::TEMPERATURE_HOTTER;
        }
        if ($status === WhatsAppBooking::STATUS_CANCELLED) {
            return null;
        }

        $service = trim((string) ($booking->service ?? '')) !== '';
        $address = trim((string) ($booking->address ?? '')) !== '';
        $when = $booking->prefered_datetime !== null;
        $detail = trim((string) ($booking->service_description ?? '')) !== '';
        $name = trim((string) ($booking->name ?? '')) !== '';

        if ($service && ($address || $when || $detail)) {
            return Lead::TEMPERATURE_HOT;
        }
        if ($service || $address || $when || $detail || $name || $status === WhatsAppBooking::STATUS_DRAFT) {
            return Lead::TEMPERATURE_WARM;
        }

        return null;
    }

    public static function temperatureForProviderLead(ProviderLead $lead): ?string
    {
        $status = (string) ($lead->status ?? '');
        if (in_array($status, [ProviderLead::STATUS_TENTATIVE_PENDING_HUMAN, ProviderLead::STATUS_HUMAN_CONFIRMED], true)) {
            return Lead::TEMPERATURE_HOTTER;
        }

        $filled = 0;
        foreach (['name', 'service', 'address'] as $field) {
            if (trim((string) ($lead->{$field} ?? '')) !== '') {
                $filled++;
            }
        }

        if ($filled >= 2) {
            return Lead::TEMPERATURE_HOT;
        }
        if ($filled >= 1 || $status === ProviderLead::STATUS_DRAFT) {
            return Lead::TEMPERATURE_WARM;
        }

        return null;
    }

    private function strongestCustomerTemperature(string $phone): ?string
    {
        $keys = WhatsAppThreadPhoneKeys::keys($phone);
        if ($keys === []) {
            return null;
        }

        $best = null;
        $bestRank = 0;
        $rows = WhatsAppBooking::query()
            ->whereIn('phone', $keys)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        foreach ($rows as $booking) {
            $temperature = self::temperatureForCustomerBooking($booking);
            $rank = self::RANKS[$temperature] ?? 0;
            if ($rank > $bestRank) {
                $best = $temperature;
                $bestRank = $rank;
            }
        }

        return $best;
    }

    private function strongestProviderTemperature(string $phone): ?string
    {
        $keys = WhatsAppThreadPhoneKeys::keys($phone);
        if ($keys === []) {
            return null;
        }

        $best = null;
        $bestRank = 0;
        $rows = ProviderLead::query()
            ->whereIn('phone', $keys)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        foreach ($rows as $providerLead) {
            $temperature = self::temperatureForProviderLead($providerLead);
            $rank = self::RANKS[$temperature] ?? 0;
            if ($rank > $bestRank) {
                $best = $temperature;
                $bestRank = $rank;
            }
        }

        return $best;
    }

    private function openLead(string $phone, string $leadType): ?Lead
    {
        $leadPhone = $this->leadLifecycle->normalizeLeadPhone($phone);
        if (!$leadPhone) {
            return null;
        }

        return Lead::query()
            ->where('phone_number', $leadPhone)
            ->where('lead_type', $leadType)
            ->orderByDesc('id')
            ->get()
            ->first(fn (Lead $lead) => $this->leadLifecycle->isLeadOpen($lead));
    }

    private function recentInboundText(string $phone): string
    {
        $keys = WhatsAppThreadPhoneKeys::keys($phone);
        if ($keys === []) {
            return '';
        }

        $rows = WhatsAppMessage::query()
            ->whereIn('phone', $keys)
            ->where('direction', 'IN')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['message_text']);

        $parts = [];
        foreach ($rows->reverse() as $row) {
            $text = trim((string) $row->message_text);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return implode("\n", $parts);
    }

    private function raise(Lead $lead, string $temperature): bool
    {
        if (!in_array($lead->lead_type, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)) {
            return false;
        }
        if (!isset(self::RANKS[$temperature]) || !$this->shouldApply($lead, $temperature)) {
            return false;
        }

        $history = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->where('type', $lead->lead_type)
            ->latest()
            ->first();
        $oldData = $history && is_array($history->data) ? $history->data : [];
        $oldForDiff = $oldData;
        if (($oldForDiff['lead_temperature'] ?? '') === '' && $lead->lead_temperature) {
            $oldForDiff['lead_temperature'] = $lead->lead_temperature;
        }
        $newData = $oldData;
        $newData['lead_temperature'] = $temperature;

        $this->changeLog->record(
            (int) $lead->id,
            $this->changeLog->buildTypeHistoryDiff($lead->lead_type, $oldForDiff, $newData)
        );

        if ($history) {
            $history->update(['data' => $newData]);
        } else {
            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => $lead->lead_type,
                'data' => $newData,
                'created_by' => null,
            ]);
        }

        $lead->lead_temperature = $temperature;
        $lead->save();

        return true;
    }

    private function shouldApply(Lead $lead, string $next): bool
    {
        $current = $lead->lead_temperature;
        $currentRank = is_string($current) ? (self::RANKS[$current] ?? 0) : 0;
        $nextRank = self::RANKS[$next] ?? 0;

        if ($nextRank > $currentRank) {
            return true;
        }

        return $next === Lead::TEMPERATURE_COLD
            && ($current === null || $current === '' || $current === Lead::TEMPERATURE_WARM)
            && !$this->temperatureWasClassified($lead);
    }

    private function temperatureWasClassified(Lead $lead): bool
    {
        return LeadChangeLog::query()
            ->where('lead_id', $lead->id)
            ->whereNotNull('changes->lead_temperature')
            ->exists();
    }
}
