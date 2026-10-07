<?php

namespace Modules\AdminModule\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Modules\BookingModule\Entities\BookingCancellationReason;
use Modules\BookingModule\Entities\BookingCustomerCancellationReason;
use Modules\BookingModule\Entities\BookingProviderCancellationReason;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\SubscribedService;

class CategoryBusinessReportAnalyticsService
{
    public function __construct(private GeographicBusinessReportAnalyticsService $geographic)
    {
    }

    /**
     * @param  array<string, mixed>  $filters  zone_ids, category_ids, subcategory_ids
     * @return array<string, mixed>
     */
    public function build(Carbon $from, Carbon $to, array $filters = []): array
    {
        [$leads, $bookings] = $this->geographic->filteredRows($from, $to, [
            'zone_ids' => $filters['zone_ids'] ?? [],
            'category_ids' => $filters['category_ids'] ?? [],
        ]);

        $subcategoryIds = array_values(array_filter((array) ($filters['subcategory_ids'] ?? [])));
        if ($subcategoryIds !== []) {
            $allowed = array_map('strval', $subcategoryIds);
            $leads = array_values(array_filter(
                $leads,
                fn (array $row) => in_array((string) ($row['subcategory_key'] ?? ''), $allowed, true)
            ));
            $bookings = array_values(array_filter(
                $bookings,
                fn (array $row) => in_array((string) ($row['subcategory_key'] ?? ''), $allowed, true)
            ));
        }

        $subscriptions = $this->loadSubscriptions($filters);
        $leads = $this->attachLeadCancelReasons($leads);
        $bookings = $this->attachProviderNames($bookings, $subscriptions);
        $bookings = $this->attachBookingCancelReasons($bookings);

        return $this->aggregate($leads, $bookings, $subscriptions, $from, $to);
    }

    /**
     * @param  list<array<string, mixed>>  $leads
     * @param  list<array<string, mixed>>  $bookings
     * @param  list<array<string, mixed>>  $subscriptions
     * @return array<string, mixed>
     */
    public function aggregate(array $leads, array $bookings, array $subscriptions, Carbon $from, Carbon $to): array
    {
        $unspecified = GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY;
        $leadTypeCounts = array_fill_keys(GeographicBusinessReportAnalyticsService::LEAD_TYPES, 0);
        $customerStatusCounts = array_fill_keys(GeographicBusinessReportAnalyticsService::CUSTOMER_STATUSES, 0);
        $bookingGroupCounts = array_fill_keys(GeographicBusinessReportAnalyticsService::BOOKING_GROUPS, 0);
        $bookingStatusCounts = [];

        $categoryBuckets = [];
        $subcategoryBuckets = [];
        $missingLeadCategory = 0;
        $missingBookingCategory = 0;

        $dayKeys = $this->dayKeys($from, $to);
        $dailyLeads = array_fill_keys($dayKeys, 0);
        $dailyBookings = array_fill_keys($dayKeys, 0);
        $dayKeySet = array_fill_keys($dayKeys, true);
        $dailyCategoryLeads = [];
        $dailyCategoryBookings = [];
        $dailySubcategoryLeads = [];
        $dailySubcategoryBookings = [];
        $categoryLabels = [];
        $subcategoryLabels = [];

        foreach ($leads as $lead) {
            $type = $this->normalizeLeadType((string) ($lead['lead_type'] ?? Lead::TYPE_UNKNOWN));
            $leadTypeCounts[$type]++;
            $status = $this->normalizeCustomerStatus($lead['customer_status'] ?? null);
            if ($type === Lead::TYPE_CUSTOMER && $status !== null) {
                $customerStatusCounts[$status]++;
            }

            [$categoryKey, $categoryLabel, $missingCategory] = $this->dimension(
                (string) ($lead['category_key'] ?? $unspecified),
                (string) ($lead['category_label'] ?? '')
            );
            [$subcategoryKey, $subcategoryLabel] = $this->dimension(
                (string) ($lead['subcategory_key'] ?? $unspecified),
                (string) ($lead['subcategory_label'] ?? '')
            );
            if ($missingCategory) {
                $missingLeadCategory++;
            }

            $categoryLabels[$categoryKey] = $categoryLabel;
            $subcategoryLabels[$subcategoryKey] = $subcategoryLabel;
            $day = $this->bucketDay($lead['received_at'] ?? null, $dayKeySet);
            if ($day !== null) {
                $dailyLeads[$day]++;
            }
            $this->bumpDaily($dailyCategoryLeads, $dayKeys, $categoryKey, $day);
            $this->bumpDaily($dailySubcategoryLeads, $dayKeys, $subcategoryKey, $day);

            $this->touch($categoryBuckets, $categoryKey, $categoryLabel, $categoryKey, $categoryLabel);
            $this->touch($subcategoryBuckets, $subcategoryKey, $subcategoryLabel, $categoryKey, $categoryLabel);
            $this->applyLead($categoryBuckets[$categoryKey], $type, $status);
            $this->applyLead($subcategoryBuckets[$subcategoryKey], $type, $status);
            $this->recordLeadCancellation($categoryBuckets[$categoryKey], $lead, $type, $status);
            $this->recordLeadCancellation($subcategoryBuckets[$subcategoryKey], $lead, $type, $status);
        }

        $categoryBookingProviders = [];
        $subcategoryBookingProviders = [];
        $categoryPeople = [];
        $subcategoryPeople = [];

        foreach ($bookings as $booking) {
            $statusName = $this->normalizeBookingStatus((string) ($booking['status'] ?? 'pending'));
            $group = $this->bookingGroup($statusName);
            $bookingGroupCounts[$group]++;
            $bookingStatusCounts[$statusName] = ($bookingStatusCounts[$statusName] ?? 0) + 1;
            $amount = (float) ($booking['amount'] ?? 0);
            $money = $this->financials($booking);

            [$categoryKey, $categoryLabel, $missingCategory] = $this->dimension(
                (string) ($booking['category_key'] ?? $unspecified),
                (string) ($booking['category_label'] ?? '')
            );
            [$subcategoryKey, $subcategoryLabel] = $this->dimension(
                (string) ($booking['subcategory_key'] ?? $unspecified),
                (string) ($booking['subcategory_label'] ?? '')
            );
            if ($missingCategory) {
                $missingBookingCategory++;
            }

            $categoryLabels[$categoryKey] = $categoryLabel;
            $subcategoryLabels[$subcategoryKey] = $subcategoryLabel;
            $day = $this->bucketDay($booking['created_at'] ?? null, $dayKeySet);
            if ($day !== null) {
                $dailyBookings[$day]++;
            }
            $this->bumpDaily($dailyCategoryBookings, $dayKeys, $categoryKey, $day);
            $this->bumpDaily($dailySubcategoryBookings, $dayKeys, $subcategoryKey, $day);

            $this->touch($categoryBuckets, $categoryKey, $categoryLabel, $categoryKey, $categoryLabel);
            $this->touch($subcategoryBuckets, $subcategoryKey, $subcategoryLabel, $categoryKey, $categoryLabel);
            $this->applyBooking($categoryBuckets[$categoryKey], $group, $amount, $money);
            $this->applyBooking($subcategoryBuckets[$subcategoryKey], $group, $amount, $money);
            $this->recordBookingStatus($categoryBuckets[$categoryKey], $statusName);
            $this->recordBookingStatus($subcategoryBuckets[$subcategoryKey], $statusName);
            if ($group === 'cancelled') {
                $this->recordBookingCancellation($categoryBuckets[$categoryKey], $booking);
                $this->recordBookingCancellation($subcategoryBuckets[$subcategoryKey], $booking);
            }

            $providerId = trim((string) ($booking['provider_id'] ?? ''));
            if ($providerId === '') {
                continue;
            }

            $categoryBookingProviders[$categoryKey][$providerId] = true;
            $subcategoryBookingProviders[$subcategoryKey][$providerId] = true;
            $providerName = trim((string) ($booking['provider_name'] ?? ''));
            $this->applyBookingToPerson(
                $categoryPeople,
                $providerId.'|'.$categoryKey,
                $providerId,
                $providerName,
                $categoryKey,
                $categoryLabel,
                $subcategoryKey === $unspecified ? '' : $subcategoryKey,
                $subcategoryKey === $unspecified ? '' : $subcategoryLabel,
                $group,
                $amount,
                $money
            );
            $this->applyBookingToPerson(
                $subcategoryPeople,
                $providerId.'|'.$subcategoryKey,
                $providerId,
                $providerName,
                $categoryKey,
                $categoryLabel,
                $subcategoryKey,
                $subcategoryLabel,
                $group,
                $amount,
                $money
            );
        }

        $subscribedIds = [];
        $activeIds = [];
        $approvedIds = [];

        foreach ($subscriptions as $subscription) {
            $providerId = trim((string) ($subscription['provider_id'] ?? ''));
            if ($providerId === '') {
                continue;
            }

            [$categoryKey, $categoryLabel] = $this->dimension(
                (string) ($subscription['category_key'] ?? $unspecified),
                (string) ($subscription['category_label'] ?? '')
            );
            [$subcategoryKey, $subcategoryLabel] = $this->dimension(
                (string) ($subscription['subcategory_key'] ?? $unspecified),
                (string) ($subscription['subcategory_label'] ?? '')
            );
            $categoryLabels[$categoryKey] = $categoryLabel;
            $subcategoryLabels[$subcategoryKey] = $subcategoryLabel;

            $this->touch($categoryBuckets, $categoryKey, $categoryLabel, $categoryKey, $categoryLabel);
            $this->touch($subcategoryBuckets, $subcategoryKey, $subcategoryLabel, $categoryKey, $categoryLabel);

            $active = (bool) ($subscription['is_active'] ?? false);
            $approved = (bool) ($subscription['is_approved'] ?? false);
            $name = trim((string) ($subscription['name'] ?? ''));

            $categoryBuckets[$categoryKey]['subscribed_ids'][$providerId] = true;
            $subcategoryBuckets[$subcategoryKey]['subscribed_ids'][$providerId] = true;
            if ($active) {
                $categoryBuckets[$categoryKey]['active_ids'][$providerId] = true;
                $subcategoryBuckets[$subcategoryKey]['active_ids'][$providerId] = true;
                $activeIds[$providerId] = true;
            }
            if ($approved) {
                $categoryBuckets[$categoryKey]['approved_ids'][$providerId] = true;
                $subcategoryBuckets[$subcategoryKey]['approved_ids'][$providerId] = true;
                $approvedIds[$providerId] = true;
            }
            $subscribedIds[$providerId] = true;

            $this->touchPerson(
                $categoryPeople,
                $providerId.'|'.$categoryKey,
                $providerId,
                $name,
                $categoryKey,
                $categoryLabel,
                $subcategoryKey === $unspecified ? '' : $subcategoryKey,
                $subcategoryKey === $unspecified ? '' : $subcategoryLabel,
                true,
                $active,
                $approved
            );
            $this->touchPerson(
                $subcategoryPeople,
                $providerId.'|'.$subcategoryKey,
                $providerId,
                $name,
                $categoryKey,
                $categoryLabel,
                $subcategoryKey,
                $subcategoryLabel,
                true,
                $active,
                $approved
            );
        }

        $categoryRows = $this->finalizeRows($categoryBuckets, $categoryBookingProviders);
        $subcategoryRows = $this->finalizeRows($subcategoryBuckets, $subcategoryBookingProviders);

        $activeWithBooking = 0;
        foreach (array_keys($activeIds) as $providerId) {
            $tookBooking = false;
            foreach ($categoryBookingProviders as $providers) {
                if (isset($providers[$providerId])) {
                    $tookBooking = true;
                    break;
                }
            }
            if ($tookBooking) {
                $activeWithBooking++;
            }
        }

        $bookingProviderIds = [];
        foreach ($categoryBookingProviders as $providers) {
            foreach (array_keys($providers) as $providerId) {
                $bookingProviderIds[$providerId] = true;
            }
        }

        $totalLeads = count($leads);
        $totalBookings = count($bookings);

        return [
            'summary' => [
                'leads' => $totalLeads,
                'unknown' => $leadTypeCounts[Lead::TYPE_UNKNOWN],
                'customer' => $leadTypeCounts[Lead::TYPE_CUSTOMER],
                'provider' => $leadTypeCounts[Lead::TYPE_PROVIDER],
                'invalid' => $leadTypeCounts[Lead::TYPE_INVALID],
                'future_customer' => $leadTypeCounts[Lead::TYPE_FUTURE_CUSTOMER],
                'pending' => $customerStatusCounts['pending'],
                'hold' => $customerStatusCounts['hold'],
                'booked' => $customerStatusCounts['booked'],
                'cancelled_leads' => $customerStatusCounts['cancelled'],
                'bookings' => $totalBookings,
                'booking_pending' => $bookingGroupCounts['pending'],
                'booking_completed' => $bookingGroupCounts['completed'],
                'booking_cancelled' => $bookingGroupCounts['cancelled'],
                'booking_amount_completed' => round(array_sum(array_column($categoryRows, 'booking_amount_completed')), 2),
                'revenue' => round(array_sum(array_column($categoryRows, 'revenue')), 2),
                'admin_commission' => round(array_sum(array_column($categoryRows, 'admin_commission')), 2),
                'provider_earning' => round(array_sum(array_column($categoryRows, 'provider_earning')), 2),
                'lead_conversion_rate' => $this->pct($customerStatusCounts['booked'], $leadTypeCounts[Lead::TYPE_CUSTOMER]),
                'booking_completion_rate' => $this->pct($bookingGroupCounts['completed'], $totalBookings),
                'providers' => count($subscribedIds),
                'providers_active' => count($activeIds),
                'providers_approved' => count($approvedIds),
                'providers_with_booking' => count($bookingProviderIds),
                'providers_active_with_booking' => $activeWithBooking,
                'providers_active_idle' => max(0, count($activeIds) - $activeWithBooking),
                'missing_lead_category' => $missingLeadCategory,
                'missing_booking_category' => $missingBookingCategory,
            ],
            'lead_type_breakdown' => $this->chartRows([
                ['key' => Lead::TYPE_UNKNOWN, 'label' => translate('Unknown'), 'total' => $leadTypeCounts[Lead::TYPE_UNKNOWN], 'color' => '#858796'],
                ['key' => Lead::TYPE_CUSTOMER, 'label' => translate('Customer'), 'total' => $leadTypeCounts[Lead::TYPE_CUSTOMER], 'color' => '#4e73df'],
                ['key' => Lead::TYPE_PROVIDER, 'label' => translate('Provider'), 'total' => $leadTypeCounts[Lead::TYPE_PROVIDER], 'color' => '#36b9cc'],
                ['key' => Lead::TYPE_INVALID, 'label' => translate('Invalid'), 'total' => $leadTypeCounts[Lead::TYPE_INVALID], 'color' => '#e74a3b'],
                ['key' => Lead::TYPE_FUTURE_CUSTOMER, 'label' => translate('Future_Customer'), 'total' => $leadTypeCounts[Lead::TYPE_FUTURE_CUSTOMER], 'color' => '#6f42c1'],
            ]),
            'booking_status_breakdown' => $this->bookingStatusChart($bookingStatusCounts),
            'daily' => [
                'labels' => $this->displayDayLabels($dayKeys),
                'keys' => $dayKeys,
                'leads' => array_values($dailyLeads),
                'bookings' => array_values($dailyBookings),
                'by_category' => $this->dailySeries($dailyCategoryLeads, $dailyCategoryBookings, $categoryLabels, $dayKeys),
                'by_subcategory' => $this->dailySeries($dailySubcategoryLeads, $dailySubcategoryBookings, $subcategoryLabels, $dayKeys),
            ],
            'category' => [
                'rows' => $categoryRows,
                'providers' => $this->finalizePeople($categoryPeople),
                'targeting' => $this->targetingRows($categoryRows),
                'insights' => $this->buildInsights($categoryRows, $missingLeadCategory, $missingBookingCategory),
            ],
            'subcategory' => [
                'rows' => $subcategoryRows,
                'providers' => $this->finalizePeople($subcategoryPeople),
                'targeting' => $this->targetingRows($subcategoryRows),
                'insights' => $this->buildInsights($subcategoryRows, $missingLeadCategory, $missingBookingCategory),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{type: string, text: string}|null
     */
    public function recommend(array $row): ?array
    {
        $leads = (int) ($row['leads'] ?? 0);
        $customer = (int) ($row['customer'] ?? 0);
        $booked = (int) ($row['booked'] ?? 0);
        $bookings = (int) ($row['bookings'] ?? 0);
        $completed = (int) ($row['booking_completed'] ?? 0);
        $cancelled = (int) ($row['booking_cancelled'] ?? 0);
        $activeWithBooking = (int) ($row['providers_active_with_booking'] ?? 0);
        $activeIdle = (int) ($row['providers_active_idle'] ?? 0);
        $conversion = $customer > 0 ? $this->pct($booked, $customer) : 0.0;
        $completion = $bookings > 0 ? $this->pct($completed, $bookings) : 0.0;
        $cancelRate = $bookings > 0 ? $this->pct($cancelled, $bookings) : 0.0;
        $label = (string) ($row['label'] ?? translate('Not_Specified'));

        if ($leads < 3 && $bookings < 3 && $activeIdle < 3) {
            return null;
        }

        if ($bookings >= 5 && $activeWithBooking <= 1) {
            return [
                'type' => 'risk',
                'text' => sprintf(
                    '%s: %d %s, %d %s. %s',
                    $label,
                    $bookings,
                    translate('Bookings'),
                    $activeWithBooking,
                    translate('Category_active_with_recent_booking'),
                    translate('Category_target_add_providers')
                ),
            ];
        }

        if ($activeIdle >= 3 && $bookings === 0) {
            return [
                'type' => 'opportunity',
                'text' => sprintf(
                    '%s: %d %s. %s',
                    $label,
                    $activeIdle,
                    translate('Category_active_with_no_recent_booking'),
                    translate('Category_target_idle_providers')
                ),
            ];
        }

        if ($bookings >= 5 && $cancelRate >= 40.0) {
            return [
                'type' => 'risk',
                'text' => sprintf(
                    '%s: %.1f%% %s. %s',
                    $label,
                    $cancelRate,
                    translate('cancellation_rate'),
                    translate('Geographic_target_review_fulfillment')
                ),
            ];
        }

        if ($customer >= 5 && $conversion < 20.0) {
            return [
                'type' => 'opportunity',
                'text' => sprintf(
                    '%s: %d %s, %.1f%% %s. %s',
                    $label,
                    $customer,
                    translate('customer_leads'),
                    $conversion,
                    translate('conversion'),
                    translate('Geographic_target_improve_closing')
                ),
            ];
        }

        if ($completed >= 5 && $completion >= 60.0 && $cancelRate <= 35.0) {
            return [
                'type' => 'grow',
                'text' => sprintf(
                    '%s: %d %s (%.1f%% %s). %s',
                    $label,
                    $completed,
                    translate('completed'),
                    $completion,
                    translate('completion_rate'),
                    translate('Category_target_increase_ads')
                ),
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function loadSubscriptions(array $filters): array
    {
        $categoryIds = array_values(array_filter((array) ($filters['category_ids'] ?? [])));
        $subcategoryIds = array_values(array_filter((array) ($filters['subcategory_ids'] ?? [])));

        $query = SubscribedService::query()->where('is_subscribed', 1);
        if ($categoryIds !== []) {
            $query->whereIn('category_id', $categoryIds);
        }
        if ($subcategoryIds !== []) {
            $query->whereIn('sub_category_id', $subcategoryIds);
        }

        $subs = $query->get(['provider_id', 'category_id', 'sub_category_id']);
        if ($subs->isEmpty()) {
            return [];
        }

        $providers = Provider::query()
            ->whereIn('id', $subs->pluck('provider_id')->filter()->unique()->all())
            ->get(['id', 'company_name', 'contact_person_name', 'is_active', 'is_approved'])
            ->keyBy(fn ($provider) => (string) $provider->id);

        $categoryLookupIds = $subs->pluck('category_id')
            ->merge($subs->pluck('sub_category_id'))
            ->filter()
            ->unique()
            ->all();
        $categories = Category::withoutGlobalScopes()
            ->whereIn('id', $categoryLookupIds)
            ->get(['id', 'name', 'parent_id'])
            ->keyBy(fn ($category) => (string) $category->id);
        $parentIds = $categories
            ->map(fn ($category) => $category->parent_id)
            ->filter(fn ($id) => $id && ! $categories->has((string) $id))
            ->unique()
            ->values()
            ->all();
        if ($parentIds !== []) {
            $parents = Category::withoutGlobalScopes()->whereIn('id', $parentIds)->get(['id', 'name', 'parent_id']);
            foreach ($parents as $parent) {
                $categories->put((string) $parent->id, $parent);
            }
        }

        $rows = [];
        $seen = [];
        foreach ($subs as $sub) {
            $provider = $providers->get((string) $sub->provider_id);
            if (! $provider) {
                continue;
            }

            $subcategoryId = trim((string) ($sub->sub_category_id ?? ''));
            $categoryId = trim((string) ($sub->category_id ?? ''));
            if ($categoryId === '' && $subcategoryId !== '') {
                $categoryId = trim((string) ($categories->get($subcategoryId)?->parent_id ?? ''));
            }
            if ($categoryId === '') {
                $categoryId = GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY;
            }
            if ($subcategoryId === '') {
                $subcategoryId = GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY;
            }

            $dedupe = $provider->id.'|'.$categoryId.'|'.$subcategoryId;
            if (isset($seen[$dedupe])) {
                continue;
            }
            $seen[$dedupe] = true;

            $name = trim((string) ($provider->company_name ?: $provider->contact_person_name ?: ''));
            $rows[] = [
                'provider_id' => (string) $provider->id,
                'name' => $name !== '' ? $name : translate('Provider'),
                'is_active' => (int) $provider->is_active === 1,
                'is_approved' => (int) $provider->is_approved === 1,
                'category_key' => $categoryId,
                'category_label' => $this->categoryName($categories, $categoryId),
                'subcategory_key' => $subcategoryId,
                'subcategory_label' => $this->categoryName($categories, $subcategoryId),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $bookings
     * @param  list<array<string, mixed>>  $subscriptions
     * @return list<array<string, mixed>>
     */
    private function attachProviderNames(array $bookings, array $subscriptions): array
    {
        $names = [];
        foreach ($subscriptions as $subscription) {
            $id = (string) ($subscription['provider_id'] ?? '');
            if ($id !== '' && ! isset($names[$id])) {
                $names[$id] = (string) ($subscription['name'] ?? '');
            }
        }

        $missing = [];
        foreach ($bookings as $booking) {
            $id = trim((string) ($booking['provider_id'] ?? ''));
            if ($id !== '' && ! isset($names[$id])) {
                $missing[$id] = true;
            }
        }
        if ($missing !== []) {
            $providers = Provider::query()
                ->whereIn('id', array_keys($missing))
                ->get(['id', 'company_name', 'contact_person_name']);
            foreach ($providers as $provider) {
                $name = trim((string) ($provider->company_name ?: $provider->contact_person_name ?: ''));
                $names[(string) $provider->id] = $name !== '' ? $name : translate('Provider');
            }
        }

        foreach ($bookings as &$booking) {
            $id = trim((string) ($booking['provider_id'] ?? ''));
            $booking['provider_name'] = $id !== '' ? ($names[$id] ?? translate('Provider')) : '';
        }
        unset($booking);

        return $bookings;
    }

    /**
     * @param  list<array<string, mixed>>  $leads
     * @return list<array<string, mixed>>
     */
    private function attachLeadCancelReasons(array $leads): array
    {
        $customerIds = [];
        $providerIds = [];
        foreach ($leads as $lead) {
            if (trim((string) ($lead['cancel_reason_key'] ?? '')) !== '') {
                continue;
            }
            $id = trim((string) ($lead['cancel_reason_id'] ?? ''));
            if ($id === '') {
                continue;
            }
            if (($lead['cancel_reason_kind'] ?? '') === 'provider') {
                $providerIds[] = $id;
            } else {
                $customerIds[] = $id;
            }
        }

        $customerReasons = $customerIds === []
            ? collect()
            : LeadCancellationReason::query()->whereIn('id', array_unique($customerIds))->get()->keyBy(fn ($reason) => (string) $reason->id);
        $providerReasons = $providerIds === []
            ? collect()
            : ProviderCancellationReason::query()->whereIn('id', array_unique($providerIds))->get()->keyBy(fn ($reason) => (string) $reason->id);

        foreach ($leads as &$lead) {
            if (trim((string) ($lead['cancel_reason_key'] ?? '')) !== '') {
                continue;
            }
            $id = trim((string) ($lead['cancel_reason_id'] ?? ''));
            $kind = (string) ($lead['cancel_reason_kind'] ?? '');
            if ($id === '') {
                $lead['cancel_reason_key'] = '';
                $lead['cancel_reason_label'] = '';
                continue;
            }
            $name = $kind === 'provider'
                ? trim((string) ($providerReasons->get($id)?->name ?? ''))
                : trim((string) ($customerReasons->get($id)?->name ?? ''));
            $lead['cancel_reason_key'] = ($kind !== '' ? $kind : 'customer').':'.$id;
            $lead['cancel_reason_label'] = $name !== '' ? $name : translate('Not_Specified');
        }
        unset($lead);

        return $leads;
    }

    /**
     * @param  list<array<string, mixed>>  $bookings
     * @return list<array<string, mixed>>
     */
    private function attachBookingCancelReasons(array $bookings): array
    {
        $cancelledIds = [];
        foreach ($bookings as $booking) {
            if (trim((string) ($booking['cancel_reason_key'] ?? '')) !== '') {
                continue;
            }
            $status = $this->normalizeBookingStatus((string) ($booking['status'] ?? ''));
            $id = trim((string) ($booking['id'] ?? ''));
            if ($id !== '' && $this->bookingGroup($status) === 'cancelled') {
                $cancelledIds[] = $id;
            }
        }
        if ($cancelledIds === []) {
            return $bookings;
        }

        $histories = BookingStatusHistory::query()
            ->whereIn('booking_id', array_values(array_unique($cancelledIds)))
            ->orderByDesc('id')
            ->get([
                'id',
                'booking_id',
                'booking_status',
                'booking_cancellation_reason_id',
                'booking_provider_cancellation_reason_id',
                'booking_customer_cancellation_reason_id',
                'status_change_remarks',
            ])
            ->groupBy(fn ($history) => (string) $history->booking_id);

        $adminIds = [];
        $customerIds = [];
        $providerIds = [];
        foreach ($histories as $rows) {
            $history = $this->cancellationHistory($rows);
            if (! $history) {
                continue;
            }
            if ($history->booking_cancellation_reason_id) {
                $adminIds[] = (int) $history->booking_cancellation_reason_id;
            }
            if ($history->booking_customer_cancellation_reason_id) {
                $customerIds[] = (int) $history->booking_customer_cancellation_reason_id;
            }
            if ($history->booking_provider_cancellation_reason_id) {
                $providerIds[] = (int) $history->booking_provider_cancellation_reason_id;
            }
        }

        $adminReasons = $adminIds === []
            ? collect()
            : BookingCancellationReason::query()->whereIn('id', array_unique($adminIds))->get()->keyBy(fn ($reason) => (int) $reason->id);
        $customerReasons = $customerIds === []
            ? collect()
            : BookingCustomerCancellationReason::query()->whereIn('id', array_unique($customerIds))->get()->keyBy(fn ($reason) => (int) $reason->id);
        $providerReasons = $providerIds === []
            ? collect()
            : BookingProviderCancellationReason::query()->whereIn('id', array_unique($providerIds))->get()->keyBy(fn ($reason) => (int) $reason->id);

        foreach ($bookings as &$booking) {
            if (trim((string) ($booking['cancel_reason_key'] ?? '')) !== '') {
                continue;
            }
            $status = $this->normalizeBookingStatus((string) ($booking['status'] ?? ''));
            if ($this->bookingGroup($status) !== 'cancelled') {
                continue;
            }
            $history = $this->cancellationHistory($histories->get((string) ($booking['id'] ?? '')) ?? collect());
            $booking['cancel_remarks'] = trim((string) ($history?->status_change_remarks ?? ''));
            $adminId = (int) ($history?->booking_cancellation_reason_id ?? 0);
            $customerId = (int) ($history?->booking_customer_cancellation_reason_id ?? 0);
            $providerId = (int) ($history?->booking_provider_cancellation_reason_id ?? 0);
            if ($adminId > 0) {
                $reason = $adminReasons->get($adminId);
                $booking['cancel_reason_key'] = 'admin:'.$adminId;
                $booking['cancel_reason_label'] = trim((string) ($reason?->name ?? '')) ?: translate('Not_Specified');
                $booking['cancel_responsible'] = (string) ($reason?->responsible ?? '');
            } elseif ($customerId > 0) {
                $reason = $customerReasons->get($customerId);
                $booking['cancel_reason_key'] = 'customer:'.$customerId;
                $booking['cancel_reason_label'] = trim((string) ($reason?->name ?? '')) ?: translate('Not_Specified');
                $booking['cancel_responsible'] = 'customer';
            } elseif ($providerId > 0) {
                $reason = $providerReasons->get($providerId);
                $booking['cancel_reason_key'] = 'provider:'.$providerId;
                $booking['cancel_reason_label'] = trim((string) ($reason?->name ?? '')) ?: translate('Not_Specified');
                $booking['cancel_responsible'] = 'provider';
            } else {
                $booking['cancel_reason_key'] = '';
                $booking['cancel_reason_label'] = '';
                $booking['cancel_responsible'] = '';
            }
        }
        unset($booking);

        return $bookings;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $histories
     */
    private function cancellationHistory($histories): ?BookingStatusHistory
    {
        if ($histories === null || $histories->isEmpty()) {
            return null;
        }

        $cancelled = $histories->first(function ($history) {
            $status = strtolower((string) $history->booking_status);

            return in_array($status, ['canceled', 'cancelled', 'refunded'], true);
        });
        if ($cancelled) {
            return $cancelled;
        }

        return $histories->first(function ($history) {
            return $history->booking_cancellation_reason_id
                || $history->booking_customer_cancellation_reason_id
                || $history->booking_provider_cancellation_reason_id;
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<string, mixed>  $categories
     */
    private function categoryName($categories, string $id): string
    {
        if ($id === '' || $id === GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY) {
            return translate('Not_Specified');
        }
        $name = trim((string) ($categories->get($id)?->name ?? ''));

        return $name !== '' ? $name : translate('Not_Specified');
    }

    /**
     * @return array{0: string, 1: string, 2?: bool}
     */
    private function dimension(string $key, string $label): array
    {
        $missing = $key === '' || $key === GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY;
        if ($missing) {
            $key = GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY;
        }
        $label = trim($label);

        return [$key, $label !== '' ? $label : translate('Not_Specified'), $missing];
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     */
    private function touch(array &$buckets, string $key, string $label, string $categoryKey, string $categoryLabel): void
    {
        if (isset($buckets[$key])) {
            if ($buckets[$key]['label'] === translate('Not_Specified') && $label !== translate('Not_Specified')) {
                $buckets[$key]['label'] = $label;
            }
            if ($buckets[$key]['category_label'] === translate('Not_Specified') && $categoryLabel !== translate('Not_Specified')) {
                $buckets[$key]['category_key'] = $categoryKey;
                $buckets[$key]['category_label'] = $categoryLabel;
            }

            return;
        }

        $buckets[$key] = [
            'key' => $key,
            'label' => $label,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'leads' => 0,
            'bookings' => 0,
            'booking_completed' => 0,
            'booking_cancelled' => 0,
            'booking_pending' => 0,
            'booking_amount_completed' => 0.0,
            'revenue' => 0.0,
            'admin_commission' => 0.0,
            'provider_earning' => 0.0,
            'provider_lead_cancelled' => 0,
            'booking_statuses' => [],
            'lead_cancel_reasons' => [],
            'booking_cancel_reasons' => [],
            'subscribed_ids' => [],
            'active_ids' => [],
            'approved_ids' => [],
        ];
        foreach (GeographicBusinessReportAnalyticsService::LEAD_TYPES as $type) {
            $buckets[$key][$type] = 0;
        }
        foreach (GeographicBusinessReportAnalyticsService::CUSTOMER_STATUSES as $status) {
            $buckets[$key][$status] = 0;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function applyLead(array &$row, string $type, ?string $status): void
    {
        $row['leads']++;
        $row[$type]++;
        if ($status !== null && $type === Lead::TYPE_CUSTOMER) {
            $row[$status]++;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function applyBooking(array &$row, string $group, float $amount, array $money): void
    {
        $row['bookings']++;
        $row['booking_'.$group]++;
        if ($group === 'completed') {
            $row['booking_amount_completed'] += $amount;
        }
        $this->addFinancials($row, $money);
    }

    /**
     * @param  array<string, mixed>  $booking
     * @return array{revenue: float, admin_commission: float, provider_earning: float}
     */
    private function financials(array $booking): array
    {
        return [
            'revenue' => (float) ($booking['revenue'] ?? 0),
            'admin_commission' => (float) ($booking['admin_commission'] ?? 0),
            'provider_earning' => (float) ($booking['provider_earning'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{revenue: float, admin_commission: float, provider_earning: float}  $money
     */
    private function addFinancials(array &$row, array $money): void
    {
        $row['revenue'] += $money['revenue'];
        $row['admin_commission'] += $money['admin_commission'];
        $row['provider_earning'] += $money['provider_earning'];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function roundFinancials(array &$row): void
    {
        $row['revenue'] = round((float) ($row['revenue'] ?? 0), 2);
        $row['admin_commission'] = round((float) ($row['admin_commission'] ?? 0), 2);
        $row['provider_earning'] = round((float) ($row['provider_earning'] ?? 0), 2);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $lead
     */
    private function recordLeadCancellation(array &$row, array $lead, string $type, ?string $status): void
    {
        $reasonKey = trim((string) ($lead['cancel_reason_key'] ?? ''));
        $isCustomerCancel = $type === Lead::TYPE_CUSTOMER && $status === 'cancelled';
        $isProviderCancel = $type === Lead::TYPE_PROVIDER && $reasonKey !== '';
        if (! $isCustomerCancel && ! $isProviderCancel) {
            return;
        }
        if ($isProviderCancel && ! $isCustomerCancel) {
            $row['provider_lead_cancelled']++;
        }

        $this->bumpReason(
            $row['lead_cancel_reasons'],
            $reasonKey !== '' ? $reasonKey : GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY,
            (string) ($lead['cancel_reason_label'] ?? ''),
            $isProviderCancel && ! $isCustomerCancel ? 'provider_lead' : 'customer_lead',
            (string) ($lead['cancel_reason_kind'] ?? ($isProviderCancel ? 'provider' : 'customer')),
            (string) ($lead['cancel_remarks'] ?? '')
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function recordBookingStatus(array &$row, string $status): void
    {
        $row['booking_statuses'][$status] = ($row['booking_statuses'][$status] ?? 0) + 1;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $booking
     */
    private function recordBookingCancellation(array &$row, array $booking): void
    {
        $reasonKey = trim((string) ($booking['cancel_reason_key'] ?? ''));
        $this->bumpReason(
            $row['booking_cancel_reasons'],
            $reasonKey !== '' ? $reasonKey : GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY,
            (string) ($booking['cancel_reason_label'] ?? ''),
            'booking',
            (string) ($booking['cancel_responsible'] ?? ''),
            (string) ($booking['cancel_remarks'] ?? '')
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $reasons
     */
    private function bumpReason(array &$reasons, string $key, string $label, string $source, string $responsible, string $remarks): void
    {
        if (! isset($reasons[$key])) {
            $reasons[$key] = [
                'key' => $key,
                'label' => trim($label) !== '' ? trim($label) : translate('Not_Specified'),
                'source' => $source,
                'responsible' => $responsible,
                'total' => 0,
                'remarks' => [],
            ];
        }
        $reasons[$key]['total']++;
        $remarks = trim($remarks);
        if ($remarks !== '' && ! in_array($remarks, $reasons[$key]['remarks'], true) && count($reasons[$key]['remarks']) < 4) {
            $reasons[$key]['remarks'][] = $remarks;
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $reasons
     * @return list<array<string, mixed>>
     */
    private function finalizeReasons(array $reasons, int $total): array
    {
        $rows = array_values($reasons);
        usort($rows, function (array $a, array $b) {
            $count = (int) $b['total'] <=> (int) $a['total'];
            if ($count !== 0) {
                return $count;
            }

            return strcasecmp((string) $a['label'], (string) $b['label']);
        });
        foreach ($rows as &$row) {
            $row['share'] = $this->pct((int) $row['total'], $total);
            $row['remarks_text'] = implode(' · ', $row['remarks']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  array<string, array<string, mixed>>  $people
     */
    private function touchPerson(
        array &$people,
        string $key,
        string $providerId,
        string $name,
        string $categoryKey,
        string $categoryLabel,
        string $subcategoryKey,
        string $subcategoryLabel,
        bool $subscribed,
        bool $active,
        bool $approved
    ): void {
        if (! isset($people[$key])) {
            $people[$key] = $this->emptyPerson(
                $providerId,
                $name,
                $categoryKey,
                $categoryLabel,
                $subcategoryKey,
                $subcategoryLabel,
                $subscribed,
                $active,
                $approved
            );
        } else {
            if ($subscribed) {
                $people[$key]['subscribed'] = true;
            }
            if ($active) {
                $people[$key]['is_active'] = true;
            }
            if ($approved) {
                $people[$key]['is_approved'] = true;
            }
            if ($name !== '' && ($people[$key]['name'] === '' || $people[$key]['name'] === translate('Provider'))) {
                $people[$key]['name'] = $name;
            }
        }

        if ($subcategoryLabel !== '' && $subcategoryLabel !== translate('Not_Specified')) {
            $labels = $people[$key]['subcategory_labels'];
            if (! in_array($subcategoryLabel, $labels, true)) {
                $labels[] = $subcategoryLabel;
                $people[$key]['subcategory_labels'] = $labels;
            }
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $people
     */
    private function applyBookingToPerson(
        array &$people,
        string $key,
        string $providerId,
        string $name,
        string $categoryKey,
        string $categoryLabel,
        string $subcategoryKey,
        string $subcategoryLabel,
        string $group,
        float $amount,
        array $money
    ): void {
        $this->touchPerson(
            $people,
            $key,
            $providerId,
            $name,
            $categoryKey,
            $categoryLabel,
            $subcategoryKey,
            $subcategoryLabel,
            false,
            false,
            false
        );
        $people[$key]['bookings']++;
        $people[$key]['booking_'.$group]++;
        $people[$key]['took_booking'] = true;
        if ($group === 'completed') {
            $people[$key]['booking_amount_completed'] += $amount;
        }
        $this->addFinancials($people[$key], $money);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPerson(
        string $providerId,
        string $name,
        string $categoryKey,
        string $categoryLabel,
        string $subcategoryKey,
        string $subcategoryLabel,
        bool $subscribed,
        bool $active,
        bool $approved
    ): array {
        return [
            'provider_id' => $providerId,
            'name' => $name !== '' ? $name : translate('Provider'),
            'is_active' => $active,
            'is_approved' => $approved,
            'subscribed' => $subscribed,
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'subcategory_key' => $subcategoryKey,
            'subcategory_label' => $subcategoryLabel,
            'subcategory_labels' => [],
            'bookings' => 0,
            'booking_completed' => 0,
            'booking_cancelled' => 0,
            'booking_pending' => 0,
            'booking_amount_completed' => 0.0,
            'revenue' => 0.0,
            'admin_commission' => 0.0,
            'provider_earning' => 0.0,
            'took_booking' => false,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @param  array<string, array<string, true>>  $bookingProviders
     * @return list<array<string, mixed>>
     */
    private function finalizeRows(array $buckets, array $bookingProviders): array
    {
        $rows = [];
        foreach ($buckets as $key => $row) {
            $subscribed = $row['subscribed_ids'];
            $active = $row['active_ids'];
            $approved = $row['approved_ids'];
            $bookedProviders = $bookingProviders[$key] ?? [];
            $activeWithBooking = array_intersect_key($active, $bookedProviders);
            $row['providers'] = count($subscribed);
            $row['providers_active'] = count($active);
            $row['providers_approved'] = count($approved);
            $row['providers_with_booking'] = count($bookedProviders);
            $row['providers_active_with_booking'] = count($activeWithBooking);
            $row['providers_active_idle'] = count(array_diff_key($active, $bookedProviders));
            $row['providers_not_subscribed_with_booking'] = count(array_diff_key($bookedProviders, $subscribed));
            $row['lead_conversion_rate'] = $this->pct((int) $row['booked'], (int) $row['customer']);
            $row['booking_completion_rate'] = $this->pct((int) $row['booking_completed'], (int) $row['bookings']);
            $row['booking_cancel_rate'] = $this->pct((int) $row['booking_cancelled'], (int) $row['bookings']);
            $row['lead_cancel_rate'] = $this->pct((int) $row['cancelled'], (int) $row['customer']);
            $row['booking_amount_completed'] = round((float) $row['booking_amount_completed'], 2);
            $this->roundFinancials($row);
            $statuses = $row['booking_statuses'] ?? [];
            $row['status_pending'] = (int) ($statuses['pending'] ?? 0);
            $row['status_accepted'] = (int) ($statuses['accepted'] ?? 0);
            $row['status_ongoing'] = (int) ($statuses['ongoing'] ?? 0);
            $row['status_on_hold'] = (int) ($statuses['on_hold'] ?? 0);
            $row['status_pending_cancellation'] = (int) ($statuses['pending_cancellation'] ?? 0);
            $row['status_completed'] = (int) ($statuses['completed'] ?? 0);
            $row['status_canceled'] = (int) ($statuses['canceled'] ?? 0) + (int) ($statuses['cancelled'] ?? 0);
            $row['status_refunded'] = (int) ($statuses['refunded'] ?? 0);
            $knownStatuses = $row['status_pending'] + $row['status_accepted'] + $row['status_ongoing'] + $row['status_on_hold']
                + $row['status_pending_cancellation'] + $row['status_completed'] + $row['status_canceled'] + $row['status_refunded'];
            $row['status_other'] = max(0, (int) $row['bookings'] - $knownStatuses);
            $leadCancels = (int) $row['cancelled'] + (int) ($row['provider_lead_cancelled'] ?? 0);
            $row['lead_cancel_reasons'] = $this->finalizeReasons($row['lead_cancel_reasons'] ?? [], $leadCancels);
            $row['booking_cancel_reasons'] = $this->finalizeReasons($row['booking_cancel_reasons'] ?? [], (int) $row['booking_cancelled']);
            unset($row['booking_statuses']);
            $row['booking_amount_completed'] = round((float) $row['booking_amount_completed'], 2);
            $worked = (int) $row['providers_active_with_booking'];
            $row['bookings_per_working_provider'] = $worked > 0 ? round(((int) $row['bookings']) / $worked, 1) : 0.0;
            unset($row['subscribed_ids'], $row['active_ids'], $row['approved_ids']);
            $rows[] = $row;
        }

        usort($rows, function (array $a, array $b) {
            $scoreA = ((int) $a['bookings'] * 1000) + (int) $a['leads'] + (int) $a['providers'];
            $scoreB = ((int) $b['bookings'] * 1000) + (int) $b['leads'] + (int) $b['providers'];
            if ($scoreA === $scoreB) {
                return strcasecmp((string) $a['label'], (string) $b['label']);
            }

            return $scoreB <=> $scoreA;
        });

        return $rows;
    }

    /**
     * @param  array<string, array<string, mixed>>  $people
     * @return list<array<string, mixed>>
     */
    private function finalizePeople(array $people): array
    {
        $rows = array_values($people);
        foreach ($rows as &$row) {
            $labels = $row['subcategory_labels'];
            sort($labels);
            $row['subcategory_labels'] = $labels;
            $row['subcategories'] = $labels !== [] ? implode(', ', $labels) : ($row['subcategory_label'] !== '' ? $row['subcategory_label'] : '—');
            $row['booking_amount_completed'] = round((float) $row['booking_amount_completed'], 2);
            $this->roundFinancials($row);
            $row['took_booking'] = (int) $row['bookings'] > 0;
        }
        unset($row);

        usort($rows, function (array $a, array $b) {
            $category = strcasecmp((string) $a['category_label'], (string) $b['category_label']);
            if ($category !== 0) {
                return $category;
            }
            if ((bool) $a['took_booking'] !== (bool) $b['took_booking']) {
                return $a['took_booking'] ? -1 : 1;
            }
            $bookings = (int) $b['bookings'] <=> (int) $a['bookings'];
            if ($bookings !== 0) {
                return $bookings;
            }

            return strcasecmp((string) $a['name'], (string) $b['name']);
        });

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function targetingRows(array $rows): array
    {
        $targeted = [];
        foreach ($rows as $row) {
            if (($row['key'] ?? '') === GeographicBusinessReportAnalyticsService::UNSPECIFIED_KEY) {
                continue;
            }
            $recommendation = $this->recommend($row);
            if ($recommendation === null) {
                continue;
            }
            $targeted[] = array_merge($row, ['recommendation' => $recommendation]);
        }

        usort($targeted, function (array $a, array $b) {
            $order = ['risk' => 0, 'opportunity' => 1, 'grow' => 2];
            $typeA = $order[$a['recommendation']['type'] ?? ''] ?? 9;
            $typeB = $order[$b['recommendation']['type'] ?? ''] ?? 9;
            if ($typeA !== $typeB) {
                return $typeA <=> $typeB;
            }

            return ((int) $b['bookings'] + (int) $b['leads']) <=> ((int) $a['bookings'] + (int) $a['leads']);
        });

        return $targeted;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{type: string, text: string}>
     */
    private function buildInsights(array $rows, int $missingLeadCategory, int $missingBookingCategory): array
    {
        $insights = [];
        if ($missingLeadCategory > 0 || $missingBookingCategory > 0) {
            $insights[] = [
                'type' => 'warning',
                'text' => sprintf(
                    '%d %s, %d %s. %s',
                    $missingLeadCategory,
                    translate('Category_leads_missing_category'),
                    $missingBookingCategory,
                    translate('Category_bookings_missing_category'),
                    translate('Category_capture_hint')
                ),
            ];
        }

        $targeted = $this->targetingRows($rows);

        foreach (array_slice($targeted, 0, 4) as $row) {
            $insights[] = [
                'type' => $row['recommendation']['type'] === 'grow' ? 'success' : ($row['recommendation']['type'] === 'risk' ? 'warning' : 'info'),
                'text' => $row['recommendation']['text'],
            ];
        }

        return $insights;
    }

    /**
     * @param  list<array{key: string, label: string, total: int, color: string}>  $rows
     * @return list<array{key: string, label: string, total: int, color: string}>
     */
    private function chartRows(array $rows): array
    {
        return array_values(array_filter($rows, fn (array $row) => (int) $row['total'] > 0));
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{key: string, label: string, total: int, color: string}>
     */
    private function bookingStatusChart(array $counts): array
    {
        $palette = [
            'pending' => '#f6c23e',
            'accepted' => '#4e73df',
            'ongoing' => '#36b9cc',
            'on_hold' => '#fd7e14',
            'pending_cancellation' => '#e83e8c',
            'completed' => '#1cc88a',
            'canceled' => '#e74a3b',
            'cancelled' => '#e74a3b',
            'refunded' => '#858796',
        ];
        $rows = [];
        arsort($counts);
        foreach ($counts as $status => $total) {
            if ($total <= 0) {
                continue;
            }
            $rows[] = [
                'key' => $status,
                'label' => match ($status) {
                    'pending' => translate('Pending'),
                    'accepted' => translate('Accepted'),
                    'ongoing' => translate('Ongoing'),
                    'on_hold' => translate('On_hold'),
                    'completed' => translate('completed'),
                    'canceled', 'cancelled' => translate('Canceled'),
                    default => ucfirst(str_replace('_', ' ', $status)),
                },
                'total' => $total,
                'color' => $palette[$status] ?? '#5a5c69',
            ];
        }

        return $rows;
    }

    private function normalizeLeadType(string $type): string
    {
        return in_array($type, GeographicBusinessReportAnalyticsService::LEAD_TYPES, true) ? $type : Lead::TYPE_UNKNOWN;
    }

    private function normalizeCustomerStatus(mixed $status): ?string
    {
        $value = is_string($status) ? $status : null;
        if ($value === null || ! in_array($value, GeographicBusinessReportAnalyticsService::CUSTOMER_STATUSES, true)) {
            return null;
        }

        return $value;
    }

    private function normalizeBookingStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === 'cancelled') {
            return 'canceled';
        }

        return $status !== '' ? $status : 'pending';
    }

    private function bookingGroup(string $status): string
    {
        if (in_array($status, ['canceled', 'cancelled', 'refunded'], true)) {
            return 'cancelled';
        }
        if ($status === 'completed') {
            return 'completed';
        }

        return 'pending';
    }

    private function pct(int $part, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, 1);
    }

    /**
     * @return list<string>
     */
    private function dayKeys(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $days = $start->diffInDays($end) + 1;
        if ($days > 62) {
            $keys = [];
            $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
            while ($cursor->lte($end)) {
                $keys[] = $cursor->format('o-\WW');
                $cursor->addWeek();
            }

            return $keys;
        }

        $keys = [];
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $keys[] = $day->format('Y-m-d');
        }

        return $keys;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function displayDayLabels(array $keys): array
    {
        return array_map(function (string $key) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)) {
                return Carbon::parse($key)->format('d M');
            }
            if (preg_match('/^(\d{4})-W(\d{2})$/', $key, $m)) {
                return 'W'.$m[2];
            }

            return $key;
        }, $keys);
    }

    /**
     * @param  array<string, true>  $dayKeySet
     */
    private function bucketDay(mixed $value, array $dayKeySet): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
        $ymd = $date->format('Y-m-d');
        if (isset($dayKeySet[$ymd])) {
            return $ymd;
        }
        $week = $date->copy()->startOfWeek(Carbon::MONDAY)->format('o-\WW');

        return isset($dayKeySet[$week]) ? $week : null;
    }

    /**
     * @param  array<string, array<string, int>>  $store
     * @param  list<string>  $dayKeys
     */
    private function bumpDaily(array &$store, array $dayKeys, string $key, ?string $day): void
    {
        if ($day === null) {
            return;
        }
        if (! isset($store[$key])) {
            $store[$key] = array_fill_keys($dayKeys, 0);
        }
        if (isset($store[$key][$day])) {
            $store[$key][$day]++;
        }
    }

    /**
     * @param  array<string, array<string, int>>  $leadByKey
     * @param  array<string, array<string, int>>  $bookingByKey
     * @param  array<string, string>  $labels
     * @param  list<string>  $dayKeys
     * @return array{lead_series: list<array<string, mixed>>, booking_series: list<array<string, mixed>>}
     */
    private function dailySeries(array $leadByKey, array $bookingByKey, array $labels, array $dayKeys): array
    {
        $keys = array_values(array_unique(array_merge(array_keys($leadByKey), array_keys($bookingByKey))));
        $scored = [];
        foreach ($keys as $key) {
            $scored[$key] = array_sum($leadByKey[$key] ?? []) + array_sum($bookingByKey[$key] ?? []);
        }
        arsort($scored);
        $zeros = array_fill_keys($dayKeys, 0);
        $leadSeries = [];
        $bookingSeries = [];
        foreach (array_keys($scored) as $key) {
            $label = $labels[$key] ?? translate('Not_Specified');
            $leadSeries[] = [
                'key' => $key,
                'label' => $label,
                'data' => array_values(array_replace($zeros, $leadByKey[$key] ?? [])),
            ];
            $bookingSeries[] = [
                'key' => $key,
                'label' => $label,
                'data' => array_values(array_replace($zeros, $bookingByKey[$key] ?? [])),
            ];
        }

        return [
            'lead_series' => $leadSeries,
            'booking_series' => $bookingSeries,
        ];
    }
}
