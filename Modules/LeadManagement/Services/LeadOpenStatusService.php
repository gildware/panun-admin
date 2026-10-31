<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\PartyAlternatePhone;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\UserManagement\Entities\User;

class LeadOpenStatusService
{
    /**
     * Limit a leads query to rows that are "open" per {@see isLeadOpenByTypeHistory()}.
     */
    public function restrictQueryToOpenLeads(Builder $leadQuery): void
    {
        $candidateLeads = (clone $leadQuery)->get(['id', 'lead_type']);
        $meta = $this->buildLeadStatusMeta($candidateLeads);
        $openIds = array_keys(array_filter($meta, static fn (array $m) => $m['is_open']));
        if ($openIds === []) {
            $leadQuery->whereRaw('1 = 0');
        } else {
            $leadQuery->whereIn('id', $openIds);
        }
    }

    /**
     * @param Collection<int, Lead> $leads
     * @return array<int, array{is_open: bool, label: string, badge_class: string}>
     */
    public function buildLeadStatusMeta(Collection $leads): array
    {
        $leadIds = $leads->pluck('id')->all();
        if (empty($leadIds)) {
            return [];
        }

        $this->hydrateUnknownCloseState($leads);

        $histories = LeadTypeHistory::whereIn('lead_id', $leadIds)
            ->whereIn('type', [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER])
            ->orderByDesc('created_at')
            ->get();

        $latestByComposite = [];
        foreach ($histories as $history) {
            $compositeKey = $history->lead_id . '|' . $history->type;
            if (!isset($latestByComposite[$compositeKey])) {
                $latestByComposite[$compositeKey] = $history;
            }
        }

        $customerStatusIds = [];
        $providerStatusIds = [];
        foreach ($latestByComposite as $key => $history) {
            $data = is_array($history->data) ? $history->data : [];
            if (str_ends_with((string) $key, '|' . Lead::TYPE_CUSTOMER) && !empty($data['customer_lead_status_id'])) {
                $customerStatusIds[] = (int) $data['customer_lead_status_id'];
            }
            if (str_ends_with((string) $key, '|' . Lead::TYPE_PROVIDER) && !empty($data['provider_lead_status_id'])) {
                $providerStatusIds[] = (int) $data['provider_lead_status_id'];
            }
        }

        $customerStatuses = !empty($customerStatusIds)
            ? CustomerLeadStatus::whereIn('id', array_unique($customerStatusIds))->get()->keyBy('id')
            : collect();
        $providerStatuses = !empty($providerStatusIds)
            ? ProviderLeadStatus::whereIn('id', array_unique($providerStatusIds))->get()->keyBy('id')
            : collect();

        $meta = [];
        foreach ($leads as $lead) {
            $history = $latestByComposite[$lead->id . '|' . $lead->lead_type] ?? null;
            $isOpen = $this->isLeadOpenByTypeHistory($lead, $history, $customerStatuses, $providerStatuses);
            $label = $isOpen ? 'Open' : 'Closed';
            $badgeClass = $isOpen ? 'bg-danger' : 'bg-success';
            if (! $isOpen && $lead->isNonResponsive()) {
                $label = 'Non Responsive';
                $badgeClass = 'bg-secondary';
            }
            $meta[(int) $lead->id] = [
                'is_open' => $isOpen,
                'label' => $label,
                'badge_class' => $badgeClass,
            ];
        }

        return $meta;
    }

    public function isLeadOpen(Lead $lead): bool
    {
        $meta = $this->buildLeadStatusMeta(collect([$lead]));

        return (bool) ($meta[(int) $lead->id]['is_open'] ?? false);
    }

    /**
     * Attach this lead on an inbound call/WhatsApp thread instead of minting.
     * Only open leads qualify — closed / booked / future leads do not absorb
     * the next enquiry (same gate as {@see shouldCreateNewLead()}).
     */
    public function firstLeadForIncomingCall(Collection $leads): ?Lead
    {
        return $this->firstOpenLead($leads);
    }

    public function firstOpenLead(Collection $leads): ?Lead
    {
        if ($leads->isEmpty()) {
            return null;
        }

        $meta = $this->buildLeadStatusMeta($leads);

        return $leads->first(
            fn (Lead $lead) => (bool) ($meta[(int) $lead->id]['is_open'] ?? false)
        );
    }

    /**
     * Whether this lead may absorb an inbound call/chat. Only open leads may;
     * closed / booked / cancelled / future / non-responsive may not.
     */
    public function leadCanReceiveCall(Lead $lead): bool
    {
        return $this->isLeadOpen($lead);
    }

    /**
     * @param Collection<int, CustomerLeadStatus>|null $customerStatuses
     * @param Collection<int, ProviderLeadStatus>|null $providerStatuses
     */
    public function isLeadOpenByTypeHistory(
        Lead $lead,
        ?LeadTypeHistory $typeHistory,
        ?Collection $customerStatuses = null,
        ?Collection $providerStatuses = null
    ): bool {
        if ($lead->lead_type === Lead::TYPE_UNKNOWN) {
            $this->ensureUnknownCloseState($lead);

            return ! $lead->isNonResponsive();
        }

        if (in_array($lead->lead_type, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER, Lead::TYPE_FUTURE_PROVIDER], true)) {
            return false;
        }

        $data = ($typeHistory && is_array($typeHistory->data)) ? $typeHistory->data : [];

        if ($lead->lead_type === Lead::TYPE_CUSTOMER) {
            $this->ensureUnknownCloseState($lead);
            if ($lead->isNonResponsive()) {
                return false;
            }

            $statusId = $data['customer_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $customerStatuses?->get((int) $statusId) ?? CustomerLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

            return ! in_array($baseType, ['completed', 'booked', 'cancel'], true);
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            $this->ensureUnknownCloseState($lead);
            if ($lead->isNonResponsive()) {
                return false;
            }

            $statusId = $data['provider_lead_status_id'] ?? null;
            if (!$statusId) {
                return true;
            }
            $status = $providerStatuses?->get((int) $statusId) ?? ProviderLeadStatus::find($statusId);
            $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

            return ! in_array($baseType, ['completed', 'booked', 'cancel'], true);
        }

        return false;
    }

    /**
     * Mint a new CRM lead only when the phone has neither an open lead nor an open booking.
     */
    public static function shouldCreateNewLead(bool $hasOpenLead, bool $hasOpenBooking): bool
    {
        return ! $hasOpenLead && ! $hasOpenBooking;
    }

    public static function nonResponsiveReopenDays(): int
    {
        return max(0, (int) config('leadmanagement.inbound_non_responsive_reopen_days', 7));
    }

    /**
     * Pure open/closed rule for inbound reuse: pending/hold-style statuses may absorb
     * the next call; booked/completed, cancelled, invalid, future, and non-responsive may not.
     */
    public static function canReuseLeadForCall(string $leadType, bool $isNonResponsive, ?string $baseType): bool
    {
        if ($isNonResponsive || $leadType === Lead::TYPE_INVALID) {
            return false;
        }

        if (in_array($leadType, [Lead::TYPE_FUTURE_CUSTOMER, Lead::TYPE_FUTURE_PROVIDER], true)) {
            return false;
        }

        if ($leadType === Lead::TYPE_UNKNOWN) {
            return true;
        }

        if (! in_array($leadType, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)) {
            return false;
        }

        $base = strtolower((string) ($baseType ?? 'pending'));

        return ! in_array($base, ['completed', 'booked', 'cancel'], true);
    }

    /**
     * Auto-reopen eligibility for a non-responsive lead on inbound contact.
     * Provider: always. Customer / Unknown: within the configured day window.
     */
    public function isEligibleNonResponsiveReopen(Lead $lead, ?CarbonInterface $at = null): bool
    {
        $this->hydrateUnknownCloseState(collect([$lead]));
        if (! $lead->isNonResponsive() || ! $lead->non_responsive_at) {
            return false;
        }

        if ($lead->lead_type === Lead::TYPE_PROVIDER) {
            return true;
        }

        if (! in_array($lead->lead_type, [Lead::TYPE_UNKNOWN, Lead::TYPE_CUSTOMER], true)) {
            return false;
        }

        $at = Carbon::parse($at ?? now());

        return $lead->non_responsive_at->greaterThanOrEqualTo(
            $at->copy()->subDays(self::nonResponsiveReopenDays())
        );
    }

    /**
     * Newest non-responsive lead that may be auto-reopened for this phone.
     *
     * @param  Collection<int, Lead>  $leads
     */
    public function firstReopenableNonResponsiveLead(Collection $leads): ?Lead
    {
        if ($leads->isEmpty()) {
            return null;
        }

        $this->hydrateUnknownCloseState($leads);

        return $leads
            ->sortByDesc(fn (Lead $lead) => (int) $lead->id)
            ->first(fn (Lead $lead) => $this->isEligibleNonResponsiveReopen($lead));
    }

    /**
     * Clear non-responsive close so the lead is open again for follow-up.
     *
     * @param  'inbound'|'staff'  $via
     */
    public function reopenNonResponsiveForInbound(Lead $lead, string $via = 'inbound'): Lead
    {
        $this->hydrateUnknownCloseState(collect([$lead]));
        if (! $lead->isNonResponsive()) {
            return $lead;
        }

        $lead->non_responsive_at = null;
        $lead->non_responsive_by = null;
        $lead->non_responsive_remarks = null;
        $lead->next_followup_at = app(LeadFollowupService::class)->defaultNextFollowupAt();
        $lead->save();

        app(LeadChangeLogService::class)->record((int) $lead->id, [
            'non_responsive' => [
                'label' => 'Non_Responsive',
                'old' => 'Closed as non-responsive',
                'new' => $via === 'staff'
                    ? 'Reopened by staff'
                    : 'Reopened on inbound contact',
            ],
        ]);

        return $lead->fresh() ?? $lead;
    }

    /**
     * Shared Superfone + WhatsApp decision for a phone's known leads.
     *
     * @param  Collection<int, Lead>  $leads
     * @return array{
     *     action: 'open'|'booking'|'reopen'|'mint',
     *     lead: ?Lead,
     *     has_open_lead: bool,
     *     has_open_booking: bool
     * }
     */
    public function resolveInboundLead(Collection $leads, string $phoneDigits): array
    {
        $openLead = $this->firstOpenLead($leads);
        $hasOpenBooking = $this->phoneHasOpenBooking($phoneDigits);

        if ($openLead) {
            return [
                'action' => 'open',
                'lead' => $openLead,
                'has_open_lead' => true,
                'has_open_booking' => $hasOpenBooking,
            ];
        }

        if ($hasOpenBooking) {
            return [
                'action' => 'booking',
                'lead' => $this->existingLeadForOpenBookingPhone($phoneDigits),
                'has_open_lead' => false,
                'has_open_booking' => true,
            ];
        }

        $reopen = $this->firstReopenableNonResponsiveLead($leads);
        if ($reopen) {
            return [
                'action' => 'reopen',
                'lead' => $reopen,
                'has_open_lead' => false,
                'has_open_booking' => false,
            ];
        }

        return [
            'action' => 'mint',
            'lead' => null,
            'has_open_lead' => false,
            'has_open_booking' => false,
        ];
    }

    /**
     * @return Collection<int, Lead>
     */
    public function leadsForPhoneDigits(string $phoneDigits): Collection
    {
        $suffix = PartyPhoneDirectory::normalizeDigits($phoneDigits);
        if ($suffix === null) {
            return collect();
        }

        $keys = $this->allPhoneKeysForLookup($suffix);

        return Lead::query()
            ->where(function ($query) use ($keys) {
                foreach ($keys as $digits) {
                    $query->orWhere(function ($inner) use ($digits) {
                        $this->wherePhoneEndsWith($inner, 'phone_number', $digits);
                    });
                }
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Whether this phone already has a non-terminal booking (customer account, alternate phone, or linked lead).
     */
    public function phoneHasOpenBooking(string $phoneDigits): bool
    {
        $suffix = PartyPhoneDirectory::normalizeDigits($phoneDigits);
        if ($suffix === null) {
            return false;
        }

        $customerIds = $this->customerIdsForPhone($suffix);
        if ($customerIds !== []
            && Booking::query()
                ->whereIn('customer_id', $customerIds)
                ->whereNotIn('booking_status', Booking::CLOSED_STATUSES_FOR_INBOUND_LEAD_MATCH)
                ->exists()
        ) {
            return true;
        }

        $leadIds = $this->leadIdsForPhone($suffix);
        if ($leadIds === []) {
            return false;
        }

        return Booking::query()
            ->whereIn('lead_id', $leadIds)
            ->whereNotIn('booking_status', Booking::CLOSED_STATUSES_FOR_INBOUND_LEAD_MATCH)
            ->exists();
    }

    /**
     * Prefer the lead already tied to an open booking for this phone (do not mint another).
     */
    public function existingLeadForOpenBookingPhone(string $phoneDigits): ?Lead
    {
        $suffix = PartyPhoneDirectory::normalizeDigits($phoneDigits);
        if ($suffix === null) {
            return null;
        }

        $customerIds = $this->customerIdsForPhone($suffix);
        $openBooking = null;
        if ($customerIds !== []) {
            $openBooking = Booking::query()
                ->whereIn('customer_id', $customerIds)
                ->whereNotIn('booking_status', Booking::CLOSED_STATUSES_FOR_INBOUND_LEAD_MATCH)
                ->whereNotNull('lead_id')
                ->orderByDesc('created_at')
                ->first(['id', 'lead_id']);
        }

        if (! $openBooking) {
            $leadIds = $this->leadIdsForPhone($suffix);
            if ($leadIds !== []) {
                $openBooking = Booking::query()
                    ->whereIn('lead_id', $leadIds)
                    ->whereNotIn('booking_status', Booking::CLOSED_STATUSES_FOR_INBOUND_LEAD_MATCH)
                    ->orderByDesc('created_at')
                    ->first(['id', 'lead_id']);
            }
        }

        if ($openBooking?->lead_id) {
            return Lead::query()->find($openBooking->lead_id);
        }

        return Lead::query()
            ->where(function ($query) use ($suffix) {
                foreach ($this->allPhoneKeysForLookup($suffix) as $digits) {
                    $query->orWhere(function ($inner) use ($digits) {
                        $this->wherePhoneEndsWith($inner, 'phone_number', $digits);
                    });
                }
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return list<string>
     */
    private function customerIdsForPhone(string $suffix): array
    {
        $ids = User::query()
            ->whereIn('user_type', CUSTOMER_USER_TYPES)
            ->where(function ($query) use ($suffix) {
                $this->wherePhoneEndsWith($query, 'phone', $suffix);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $altIds = PartyAlternatePhone::query()
            ->where('owner_type', PartyAlternatePhone::OWNER_CUSTOMER)
            ->where('phone_digits', $suffix)
            ->pluck('owner_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return array_values(array_unique(array_merge($ids, $altIds)));
    }

    /**
     * Lead IDs whose phone matches this number or any other phone of the same party.
     *
     * @return list<int>
     */
    private function leadIdsForPhone(string $suffix): array
    {
        $keys = $this->allPhoneKeysForLookup($suffix);
        if ($keys === []) {
            return [];
        }

        return Lead::query()
            ->where(function ($query) use ($keys) {
                foreach ($keys as $digits) {
                    $query->orWhere(function ($inner) use ($digits) {
                        $this->wherePhoneEndsWith($inner, 'phone_number', $digits);
                    });
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return list<string>
     */
    private function allPhoneKeysForLookup(string $suffix): array
    {
        $keys = [$suffix];
        $directory = app(PartyPhoneDirectory::class);
        $owner = $directory->findOwner($suffix);
        if ($owner) {
            $keys = array_merge($keys, $directory->phonesForOwner($owner['owner_type'], $owner['owner_id']));
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function wherePhoneEndsWith($query, string $column, string $digits): void
    {
        $query->where(function ($inner) use ($column, $digits) {
            if (DB::connection()->getDriverName() === 'mysql') {
                $inner->whereRaw(
                    "RIGHT(REGEXP_REPLACE(COALESCE({$column}, ''), '[^0-9]', ''), ?) = ?",
                    [strlen($digits), $digits]
                );

                return;
            }

            $inner->where($column, $digits)->orWhere($column, 'like', '%'.$digits);
        });
    }

    /**
     * Partial lead selects used by list filters often omit the close columns.
     *
     * @param  Collection<int, Lead>  $leads
     */
    public function hydrateUnknownCloseState(Collection $leads): void
    {
        $missingIds = [];
        foreach ($leads as $lead) {
            if (! $lead->supportsNonResponsiveClose()) {
                continue;
            }
            if (! array_key_exists('non_responsive_at', $lead->getAttributes())) {
                $missingIds[] = (int) $lead->id;
            }
        }

        if ($missingIds === []) {
            return;
        }

        $flags = Lead::query()->whereIn('id', $missingIds)->pluck('non_responsive_at', 'id');
        foreach ($leads as $lead) {
            if (! $lead->supportsNonResponsiveClose()) {
                continue;
            }
            if (array_key_exists('non_responsive_at', $lead->getAttributes())) {
                continue;
            }
            $lead->setAttribute('non_responsive_at', $flags->get($lead->id));
        }
    }

    private function ensureUnknownCloseState(Lead $lead): void
    {
        if (array_key_exists('non_responsive_at', $lead->getAttributes())) {
            return;
        }

        $lead->setAttribute(
            'non_responsive_at',
            Lead::query()->whereKey($lead->id)->value('non_responsive_at')
        );
    }
}
