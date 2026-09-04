<?php

namespace Modules\LeadManagement\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadArea;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadHuntingInterest;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\ZoneManagement\Entities\Zone;
use App\Support\AdminMenuCounts;

class LeadHuntingBoardService
{
    /** @var array<string, array{0: float, 1: float}|null> */
    private array $zoneCentroids = [];

    /** @var array<string, ?Category> */
    private array $categoriesById = [];

    /** @var array<string, ?Zone> */
    private array $zonesById = [];

    /** @var array<string, ?CustomerLeadArea> */
    private array $areasById = [];

    /** @var array<string, ?Service> */
    private array $servicesById = [];

    public const UNPUBLISH_FOUND_PROVIDER = 'found_provider';

    public const UNPUBLISH_CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function unpublishReasons(): array
    {
        return [
            self::UNPUBLISH_FOUND_PROVIDER,
            self::UNPUBLISH_CANCELLED,
        ];
    }

    public function schemaReady(): bool
    {
        return Schema::hasTable('leads') && Schema::hasColumn('leads', 'hunting_status');
    }

    /**
     * @param  array<string, mixed>  $customerData
     * @return array<string, bool>
     */
    public function huntReadyFlags(array $customerData): array
    {
        return [
            'subcategory' => $this->filled($customerData['service_subcategory'] ?? null),
            'zone' => $this->filled($customerData['zone_id'] ?? null),
            'area' => $this->filled($customerData['area_id'] ?? null),
            'datetime' => $this->filled($customerData['estimated_service_at'] ?? null),
            'job_text' => $this->filled($customerData['service_description'] ?? null)
                || $this->filled($customerData['service_name'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $customerData
     */
    public function isHuntReady(array $customerData): bool
    {
        foreach ($this->huntReadyFlags($customerData) as $ok) {
            if (! $ok) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function latestCustomerData(Lead $lead): array
    {
        $history = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->where('type', Lead::TYPE_CUSTOMER)
            ->latest()
            ->first();

        return ($history && is_array($history->data)) ? $history->data : [];
    }

    /**
     * @param  list<int>  $leadIds
     * @return array<int, array<string, mixed>>
     */
    public function latestCustomerDataByLeadIds(array $leadIds): array
    {
        if ($leadIds === []) {
            return [];
        }

        $latestIds = LeadTypeHistory::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('lead_id', $leadIds)
            ->where('type', Lead::TYPE_CUSTOMER)
            ->groupBy('lead_id')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return [];
        }

        $histories = LeadTypeHistory::query()->whereIn('id', $latestIds)->get();

        $out = [];
        foreach ($histories as $history) {
            $out[(int) $history->lead_id] = is_array($history->data) ? $history->data : [];
        }

        return $out;
    }

    public function publishedQuery(): Builder
    {
        return Lead::query()
            ->where('lead_type', Lead::TYPE_CUSTOMER)
            ->where('hunting_status', Lead::HUNTING_PUBLISHED);
    }

    public function publishedCount(): int
    {
        if (! $this->schemaReady()) {
            return 0;
        }

        return $this->publishedQuery()->count();
    }

    /**
     * @throws \RuntimeException
     */
    public function startHunting(Lead $lead): Lead
    {
        if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
            throw new \RuntimeException(translate('Lead_must_be_a_customer_lead'));
        }

        $data = $this->latestCustomerData($lead);
        if (! $this->isHuntReady($data)) {
            throw new \RuntimeException(translate('Complete_hunt_ready_fields_before_starting_provider_hunting'));
        }

        if ($lead->hunting_status === Lead::HUNTING_PUBLISHED) {
            return $lead;
        }

        $oldStatus = $lead->hunting_status ?: Lead::HUNTING_OFF;
        $lead->hunting_status = Lead::HUNTING_PUBLISHED;
        $lead->hunting_started_at = now();
        $lead->hunting_started_by = Auth::id() ? (string) Auth::id() : null;
        $lead->hunting_unpublished_at = null;
        $lead->hunting_unpublished_by = null;
        $lead->hunting_unpublish_reason = null;
        $lead->hunting_unpublish_notes = null;
        $lead->save();

        app(LeadChangeLogService::class)->record($lead->id, [
            'hunting_status' => [
                'label' => 'Provider_hunting',
                'old' => $this->statusLabel($oldStatus),
                'new' => $this->statusLabel(Lead::HUNTING_PUBLISHED),
            ],
        ]);

        AdminMenuCounts::forget();

        try {
            send_open_request_published_notifications($lead);
        } catch (\Throwable $e) {
            report($e);
        }

        return $lead->fresh();
    }

    /**
     * @throws \RuntimeException
     */
    public function unpublish(Lead $lead, string $reason, ?string $notes = null): Lead
    {
        if ($lead->hunting_status !== Lead::HUNTING_PUBLISHED) {
            throw new \RuntimeException(translate('Lead_is_not_on_the_hunting_board'));
        }

        if (! in_array($reason, self::unpublishReasons(), true)) {
            throw new \RuntimeException(translate('Select_a_valid_unpublish_reason'));
        }

        $lead->hunting_status = Lead::HUNTING_UNPUBLISHED;
        $lead->hunting_unpublished_at = now();
        $lead->hunting_unpublished_by = Auth::id() ? (string) Auth::id() : null;
        $lead->hunting_unpublish_reason = $reason;
        $lead->hunting_unpublish_notes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;
        $lead->save();

        app(LeadChangeLogService::class)->record($lead->id, [
            'hunting_status' => [
                'label' => 'Provider_hunting',
                'old' => $this->statusLabel(Lead::HUNTING_PUBLISHED),
                'new' => $this->statusLabel(Lead::HUNTING_UNPUBLISHED).' ('.$this->reasonLabel($reason).')',
            ],
        ]);

        AdminMenuCounts::forget();

        return $lead->fresh();
    }

    public function clearHuntingIfLeavingCustomer(Lead $lead, string $newType): void
    {
        if ($newType === Lead::TYPE_CUSTOMER || ! $this->schemaReady()) {
            return;
        }

        if (($lead->hunting_status ?? Lead::HUNTING_OFF) === Lead::HUNTING_OFF) {
            return;
        }

        $oldStatus = $lead->hunting_status;
        $lead->hunting_status = Lead::HUNTING_OFF;
        $lead->hunting_unpublished_at = now();
        $lead->hunting_unpublished_by = Auth::id() ? (string) Auth::id() : null;
        $lead->hunting_unpublish_reason = self::UNPUBLISH_CANCELLED;
        $lead->hunting_unpublish_notes = 'Lead type changed';
        $lead->save();

        app(LeadChangeLogService::class)->record($lead->id, [
            'hunting_status' => [
                'label' => 'Provider_hunting',
                'old' => $this->statusLabel($oldStatus),
                'new' => $this->statusLabel(Lead::HUNTING_OFF),
            ],
        ]);

        AdminMenuCounts::forget();
    }

    public function matchingProviderCount(?string $subCategoryId, ?string $zoneId): int
    {
        return $this->matchingProvidersQuery($subCategoryId, $zoneId)->count();
    }

    /**
     * Approved, active providers subscribed to the lead subcategory and covering its zone.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Provider>
     */
    public function matchingProvidersForLead(Lead $lead)
    {
        $data = $this->latestCustomerData($lead);
        $subCategoryId = (string) ($data['service_subcategory'] ?? '');
        $zoneId = (string) ($data['zone_id'] ?? '');

        $query = $this->matchingProvidersQuery($subCategoryId, $zoneId)->with('owner');

        $rejectedProviderIds = LeadHuntingInterest::query()
            ->where('lead_id', $lead->id)
            ->where('status', LeadHuntingInterest::STATUS_REJECTED)
            ->pluck('provider_id');
        if ($rejectedProviderIds->isNotEmpty()) {
            $query->whereNotIn('id', $rejectedProviderIds);
        }

        return $query->get();
    }

    /**
     * Matching providers who have not shown interest or rejected this job.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Provider>
     */
    public function pendingActionProvidersForLead(Lead $lead)
    {
        $data = $this->latestCustomerData($lead);
        $query = $this->matchingProvidersQuery(
            (string) ($data['service_subcategory'] ?? ''),
            (string) ($data['zone_id'] ?? '')
        )->with('owner');

        $actedProviderIds = LeadHuntingInterest::query()
            ->where('lead_id', $lead->id)
            ->whereIn('status', [
                LeadHuntingInterest::STATUS_INTERESTED,
                LeadHuntingInterest::STATUS_REJECTED,
            ])
            ->pluck('provider_id');
        if ($actedProviderIds->isNotEmpty()) {
            $query->whereNotIn('id', $actedProviderIds);
        }

        return $query->get();
    }

    public function pendingActionProviderCountForLead(Lead $lead): int
    {
        $data = $this->latestCustomerData($lead);
        $query = $this->matchingProvidersQuery(
            (string) ($data['service_subcategory'] ?? ''),
            (string) ($data['zone_id'] ?? '')
        );

        $actedProviderIds = LeadHuntingInterest::query()
            ->where('lead_id', $lead->id)
            ->whereIn('status', [
                LeadHuntingInterest::STATUS_INTERESTED,
                LeadHuntingInterest::STATUS_REJECTED,
            ])
            ->pluck('provider_id');
        if ($actedProviderIds->isNotEmpty()) {
            $query->whereNotIn('id', $actedProviderIds);
        }

        return $query->count();
    }

    private function matchingProvidersQuery(?string $subCategoryId, ?string $zoneId): Builder
    {
        $query = Provider::query()
            ->ofStatus(1)
            ->ofApproval(1);

        if (! $this->filled($subCategoryId) || ! Schema::hasTable('subscribed_services')) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereHas('subscribed_services', function ($q) use ($subCategoryId) {
            $q->where('sub_category_id', $subCategoryId)->where('is_subscribed', 1);
        });

        if ($this->filled($zoneId)) {
            $query->where(function ($z) use ($zoneId) {
                $z->where('zone_id', $zoneId)
                    ->orWhereHas('zones', fn ($zq) => $zq->where('zones.id', $zoneId));
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     job_text: string,
     *     subcategory_name: string,
     *     category_name: string,
     *     zone_name: string,
     *     area_name: string,
     *     estimated_at: ?\Carbon\Carbon,
     *     estimated_value: ?float,
     *     service_name: string
     * }
     */
    public function publicJobFields(array $data): array
    {
        $sub = $this->categoryById($data['service_subcategory'] ?? null);
        $cat = $this->categoryById($data['service_category'] ?? null);
        $zone = $this->zoneById($data['zone_id'] ?? null);
        $area = $this->areaById($data['area_id'] ?? null);
        $service = $this->serviceById($data['service_name'] ?? null);

        $description = trim((string) ($data['service_description'] ?? ''));
        $jobText = $description !== ''
            ? $description
            : trim((string) ($service?->name ?? ''));

        $estimated = null;
        if (! empty($data['estimated_service_at'])) {
            try {
                $estimated = \Carbon\Carbon::parse($data['estimated_service_at']);
            } catch (\Throwable) {
                $estimated = null;
            }
        }

        return [
            'job_text' => $jobText !== '' ? $jobText : '—',
            'subcategory_name' => $sub?->name ?? '—',
            'category_name' => $cat?->name ?? '—',
            'zone_name' => $zone?->name ?? '—',
            'area_name' => $area?->name ?? '—',
            'estimated_at' => $estimated,
            'estimated_value' => $this->parseEstimatedServiceValue($data['estimated_service_value'] ?? null),
            'service_name' => $service?->name ?? '—',
        ];
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            Lead::HUNTING_PUBLISHED => translate('Hunting'),
            Lead::HUNTING_UNPUBLISHED => translate('Unpublished'),
            default => translate('Off'),
        };
    }

    public function reasonLabel(?string $reason): string
    {
        return match ($reason) {
            self::UNPUBLISH_FOUND_PROVIDER => translate('Found_provider'),
            self::UNPUBLISH_CANCELLED => translate('Cancelled'),
            default => (string) $reason,
        };
    }

    private function filled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $dataByLead
     */
    private function warmupPublicLookups(array $dataByLead): void
    {
        $categoryIds = [];
        $zoneIds = [];
        $areaIds = [];
        $serviceIds = [];

        foreach ($dataByLead as $data) {
            $this->collectLookupId($categoryIds, $data['service_subcategory'] ?? null);
            $this->collectLookupId($categoryIds, $data['service_category'] ?? null);
            $this->collectLookupId($zoneIds, $data['zone_id'] ?? null);
            $this->collectLookupId($areaIds, $data['area_id'] ?? null);
            $this->collectLookupId($serviceIds, $data['service_name'] ?? null);
        }

        $this->loadCategoriesByIds($categoryIds);
        $this->loadZonesByIds($zoneIds);
        $this->loadAreasByIds($areaIds);
        $this->loadServicesByIds($serviceIds);
    }

    /**
     * @param  array<string, mixed>  $into
     */
    private function collectLookupId(array &$into, mixed $id): void
    {
        if (! $this->filled($id)) {
            return;
        }
        $into[(string) $id] = $id;
    }

    /**
     * @param  array<string, mixed>  $ids
     */
    private function loadCategoriesByIds(array $ids): void
    {
        $missing = $this->missingLookupKeys($ids, $this->categoriesById);
        if ($missing === []) {
            return;
        }
        foreach (Category::withoutGlobalScopes()->whereIn('id', array_values($missing))->get() as $row) {
            $this->categoriesById[(string) $row->id] = $row;
        }
        $this->markMissingLookups($missing, $this->categoriesById);
    }

    /**
     * @param  array<string, mixed>  $ids
     */
    private function loadZonesByIds(array $ids): void
    {
        $missing = $this->missingLookupKeys($ids, $this->zonesById);
        if ($missing === []) {
            return;
        }
        foreach (Zone::withoutGlobalScopes()->whereIn('id', array_values($missing))->get() as $row) {
            $this->zonesById[(string) $row->id] = $row;
        }
        $this->markMissingLookups($missing, $this->zonesById);
    }

    /**
     * @param  array<string, mixed>  $ids
     */
    private function loadAreasByIds(array $ids): void
    {
        $missing = $this->missingLookupKeys($ids, $this->areasById);
        if ($missing === []) {
            return;
        }
        foreach (CustomerLeadArea::query()->whereIn('id', array_values($missing))->get() as $row) {
            $this->areasById[(string) $row->id] = $row;
        }
        $this->markMissingLookups($missing, $this->areasById);
    }

    /**
     * @param  array<string, mixed>  $ids
     */
    private function loadServicesByIds(array $ids): void
    {
        $missing = $this->missingLookupKeys($ids, $this->servicesById);
        if ($missing === []) {
            return;
        }
        foreach (Service::withoutGlobalScopes()->whereIn('id', array_values($missing))->get() as $row) {
            $this->servicesById[(string) $row->id] = $row;
        }
        $this->markMissingLookups($missing, $this->servicesById);
    }

    /**
     * @param  array<string, mixed>  $ids
     * @param  array<string, mixed>  $cache
     * @return array<string, mixed>
     */
    private function missingLookupKeys(array $ids, array $cache): array
    {
        $missing = [];
        foreach ($ids as $key => $id) {
            if (! array_key_exists((string) $key, $cache)) {
                $missing[(string) $key] = $id;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $ids
     * @param  array<string, mixed>  $cache
     */
    private function markMissingLookups(array $ids, array &$cache): void
    {
        foreach ($ids as $key => $_) {
            if (! array_key_exists((string) $key, $cache)) {
                $cache[(string) $key] = null;
            }
        }
    }

    private function categoryById(mixed $id): ?Category
    {
        if (! $this->filled($id)) {
            return null;
        }
        $key = (string) $id;
        if (! array_key_exists($key, $this->categoriesById)) {
            $this->loadCategoriesByIds([$key => $id]);
        }

        return $this->categoriesById[$key] ?? null;
    }

    private function zoneById(mixed $id): ?Zone
    {
        if (! $this->filled($id)) {
            return null;
        }
        $key = (string) $id;
        if (! array_key_exists($key, $this->zonesById)) {
            $this->loadZonesByIds([$key => $id]);
        }

        return $this->zonesById[$key] ?? null;
    }

    private function areaById(mixed $id): ?CustomerLeadArea
    {
        if (! $this->filled($id)) {
            return null;
        }
        $key = (string) $id;
        if (! array_key_exists($key, $this->areasById)) {
            $this->loadAreasByIds([$key => $id]);
        }

        return $this->areasById[$key] ?? null;
    }

    private function serviceById(mixed $id): ?Service
    {
        if (! $this->filled($id)) {
            return null;
        }
        $key = (string) $id;
        if (! array_key_exists($key, $this->servicesById)) {
            $this->loadServicesByIds([$key => $id]);
        }

        return $this->servicesById[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    private function providerSubscribedSubcategoryIds(Provider $provider): array
    {
        return $provider->subscribed_services()
            ->where('is_subscribed', 1)
            ->pluck('sub_category_id')
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function providerZoneIds(Provider $provider): array
    {
        return collect([(string) $provider->zone_id])
            ->merge($provider->zones()->pluck('zones.id')->map(fn ($id) => (string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $subIds
     * @param  list<string>  $zoneIds
     */
    private function leadMatchesProvider(array $data, array $subIds, array $zoneIds): bool
    {
        $sub = (string) ($data['service_subcategory'] ?? '');
        $zone = (string) ($data['zone_id'] ?? '');
        if ($sub === '' || ! in_array($sub, $subIds, true)) {
            return false;
        }
        if ($zoneIds !== [] && $zone !== '' && ! in_array($zone, $zoneIds, true)) {
            return false;
        }

        return true;
    }

    private function isPublishedLeadVisibleToProvider(Lead $lead, Provider $provider): bool
    {
        $subIds = $this->providerSubscribedSubcategoryIds($provider);
        if ($subIds === []) {
            return false;
        }

        $dataByLead = $this->latestCustomerDataByLeadIds([(int) $lead->id]);
        $data = $dataByLead[(int) $lead->id] ?? [];

        return $this->leadMatchesProvider($data, $subIds, $this->providerZoneIds($provider));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function publicJobsForProvider(Provider $provider, int $limit = 20, int $offset = 1, array $filters = []): array
    {
        $limit = max(1, min(50, $limit));
        $offset = max(1, $offset);
        $emptyFilters = $this->emptyOpenRequestFilters();

        $subIds = $this->providerSubscribedSubcategoryIds($provider);

        if ($subIds === []) {
            return [
                'data' => [],
                'filters' => $emptyFilters,
                'current_page' => $offset,
                'last_page' => 1,
                'total' => 0,
                'per_page' => $limit,
                'pending_action_count' => 0,
            ];
        }

        $zoneIds = $this->providerZoneIds($provider);
        [$matched, $dataByLead] = $this->matchingPublishedLeadsForProvider($provider, $subIds, $zoneIds);
        $this->warmupPublicLookups($dataByLead);
        $pendingActionCount = $this->pendingActionCountFromMatched($provider, $matched);

        $rejectedLeadIds = LeadHuntingInterest::query()
            ->where('provider_id', $provider->id)
            ->where('status', LeadHuntingInterest::STATUS_REJECTED)
            ->pluck('lead_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $visible = [];
        foreach ($matched as $lead) {
            if (in_array((int) $lead->id, $rejectedLeadIds, true)) {
                continue;
            }
            $visible[] = $lead;
        }

        $filterOptions = $this->openRequestFilterOptions($provider, $visible, $dataByLead);
        $filtered = $this->applyOpenRequestFilters($visible, $dataByLead, $filters);
        $sorted = $this->sortOpenRequestLeads($filtered, $dataByLead, $provider, (string) ($filters['sort'] ?? 'date'));

        $total = count($sorted);
        $lastPage = max(1, (int) ceil($total / $limit));
        $slice = array_slice($sorted, ($offset - 1) * $limit, $limit);
        $interestedIds = [];
        if ($slice !== []) {
            $interestedIds = LeadHuntingInterest::query()
                ->where('provider_id', $provider->id)
                ->where('status', LeadHuntingInterest::STATUS_INTERESTED)
                ->whereIn('lead_id', array_map(fn ($l) => $l->id, $slice))
                ->pluck('lead_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $providerPoint = $this->providerLatLng($provider);
        $rows = [];
        foreach ($slice as $lead) {
            $data = $dataByLead[(int) $lead->id] ?? [];
            $public = $this->publicJobFields($data);
            $distanceKm = $this->distanceKmForLead($data, $providerPoint);
            $value = $public['estimated_value'];
            $rows[] = [
                'id' => (int) $lead->id,
                'job_text' => $public['job_text'],
                'service_name' => $public['service_name'],
                'category_id' => (string) ($data['service_category'] ?? ''),
                'category_name' => $public['category_name'],
                'subcategory_id' => (string) ($data['service_subcategory'] ?? ''),
                'subcategory_name' => $public['subcategory_name'],
                'area_id' => (string) ($data['area_id'] ?? ''),
                'area_name' => $public['area_name'],
                'zone_name' => $public['zone_name'],
                'estimated_at' => $public['estimated_at']?->toIso8601String(),
                'estimated_label' => $public['estimated_at']?->format('d M Y, h:i A'),
                'estimated_value' => $value,
                'estimated_value_label' => $value !== null ? with_currency_symbol($value) : null,
                'distance_km' => $distanceKm,
                'interested' => in_array((int) $lead->id, $interestedIds, true),
            ];
        }

        return [
            'data' => $rows,
            'filters' => $filterOptions,
            'current_page' => $offset,
            'last_page' => $lastPage,
            'total' => $total,
            'per_page' => $limit,
            'pending_action_count' => $pendingActionCount,
        ];
    }

    public function pendingActionCountForProvider(Provider $provider): int
    {
        $subIds = $this->providerSubscribedSubcategoryIds($provider);
        if ($subIds === []) {
            return 0;
        }

        [$matched] = $this->matchingPublishedLeadsForProvider($provider, $subIds, $this->providerZoneIds($provider));

        return $this->pendingActionCountFromMatched($provider, $matched);
    }

    /**
     * @param  list<string>  $subIds
     * @param  list<string>  $zoneIds
     * @return array{0: list<Lead>, 1: array<int, array<string, mixed>>}
     */
    private function matchingPublishedLeadsForProvider(Provider $provider, array $subIds, array $zoneIds): array
    {
        $leads = $this->publishedQuery()
            ->orderByDesc('hunting_started_at')
            ->orderByDesc('id')
            ->limit(1000)
            ->get(['id', 'hunting_started_at', 'hunting_status', 'lead_type']);
        $dataByLead = $this->latestCustomerDataByLeadIds(
            $leads->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        $matched = [];
        $matchedData = [];
        foreach ($leads as $lead) {
            $data = $dataByLead[(int) $lead->id] ?? [];
            if (! $this->leadMatchesProvider($data, $subIds, $zoneIds)) {
                continue;
            }
            $matched[] = $lead;
            $matchedData[(int) $lead->id] = $data;
        }

        return [$matched, $matchedData];
    }

    /**
     * @param  list<Lead>  $matched
     */
    private function pendingActionCountFromMatched(Provider $provider, array $matched): int
    {
        if ($matched === [] || ! Schema::hasTable('lead_hunting_interests')) {
            return count($matched);
        }

        $actedIds = LeadHuntingInterest::query()
            ->where('provider_id', $provider->id)
            ->whereIn('status', [
                LeadHuntingInterest::STATUS_INTERESTED,
                LeadHuntingInterest::STATUS_REJECTED,
            ])
            ->whereIn('lead_id', array_map(fn (Lead $lead) => $lead->id, $matched))
            ->pluck('lead_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $pending = 0;
        foreach ($matched as $lead) {
            if (! in_array((int) $lead->id, $actedIds, true)) {
                $pending++;
            }
        }

        return $pending;
    }

    /**
     * @return array{categories: list<array<string, string>>, subcategories: list<array<string, string>>, areas: list<array<string, string>>}
     */
    private function emptyOpenRequestFilters(): array
    {
        return [
            'categories' => [],
            'subcategories' => [],
            'areas' => [],
        ];
    }

    /**
     * @param  list<Lead>  $matched
     * @param  array<int, array<string, mixed>>  $dataByLead
     * @return array{categories: list<array<string, string>>, subcategories: list<array<string, string>>, areas: list<array<string, string>>}
     */
    private function openRequestFilterOptions(Provider $provider, array $matched, array $dataByLead): array
    {
        $categories = [];
        $subcategories = [];
        $subs = $provider->subscribed_services()
            ->where('is_subscribed', 1)
            ->with(['category', 'sub_category'])
            ->get();

        foreach ($subs as $sub) {
            $catId = (string) $sub->category_id;
            $subId = (string) $sub->sub_category_id;
            if ($catId !== '' && $sub->category && ! isset($categories[$catId])) {
                $categories[$catId] = [
                    'id' => $catId,
                    'name' => (string) $sub->category->name,
                ];
            }
            if ($subId !== '' && $sub->sub_category && ! isset($subcategories[$subId])) {
                $subcategories[$subId] = [
                    'id' => $subId,
                    'name' => (string) $sub->sub_category->name,
                    'category_id' => $catId,
                ];
            }
        }

        $areas = [];
        foreach ($matched as $lead) {
            $data = $dataByLead[(int) $lead->id] ?? [];
            $areaId = (string) ($data['area_id'] ?? '');
            if ($areaId === '' || isset($areas[$areaId])) {
                continue;
            }
            $area = $this->areaById($areaId);
            if ($area) {
                $areas[$areaId] = [
                    'id' => $areaId,
                    'name' => (string) $area->name,
                ];
            }
        }

        $sortByName = fn (array $a, array $b) => strcasecmp($a['name'], $b['name']);
        $categoryList = array_values($categories);
        $subcategoryList = array_values($subcategories);
        $areaList = array_values($areas);
        usort($categoryList, $sortByName);
        usort($subcategoryList, $sortByName);
        usort($areaList, $sortByName);

        return [
            'categories' => $categoryList,
            'subcategories' => $subcategoryList,
            'areas' => $areaList,
        ];
    }

    /**
     * @param  list<Lead>  $matched
     * @param  array<int, array<string, mixed>>  $dataByLead
     * @param  array<string, mixed>  $filters
     * @return list<Lead>
     */
    private function applyOpenRequestFilters(array $matched, array $dataByLead, array $filters): array
    {
        $dateRange = (string) ($filters['date_range'] ?? 'all');
        $categoryId = (string) ($filters['category_id'] ?? '');
        $subcategoryId = (string) ($filters['subcategory_id'] ?? '');
        $areaId = (string) ($filters['area_id'] ?? '');
        [$from, $to] = $this->dateRangeBounds($dateRange);

        $out = [];
        foreach ($matched as $lead) {
            $data = $dataByLead[(int) $lead->id] ?? [];
            if ($categoryId !== '' && (string) ($data['service_category'] ?? '') !== $categoryId) {
                continue;
            }
            if ($subcategoryId !== '' && (string) ($data['service_subcategory'] ?? '') !== $subcategoryId) {
                continue;
            }
            if ($areaId !== '' && (string) ($data['area_id'] ?? '') !== $areaId) {
                continue;
            }
            if ($from !== null && $to !== null) {
                $estimated = $this->parseEstimatedAt($data['estimated_service_at'] ?? null);
                if (! $estimated || $estimated->lt($from) || $estimated->gt($to)) {
                    continue;
                }
            }
            $out[] = $lead;
        }

        return $out;
    }

    /**
     * @param  list<Lead>  $leads
     * @param  array<int, array<string, mixed>>  $dataByLead
     * @return list<Lead>
     */
    private function sortOpenRequestLeads(array $leads, array $dataByLead, Provider $provider, string $sort): array
    {
        $sort = in_array($sort, ['date', 'price', 'distance'], true) ? $sort : 'date';
        $providerPoint = $this->providerLatLng($provider);

        usort($leads, function ($a, $b) use ($dataByLead, $sort, $providerPoint) {
            $dataA = $dataByLead[(int) $a->id] ?? [];
            $dataB = $dataByLead[(int) $b->id] ?? [];

            if ($sort === 'price') {
                $cmp = $this->nullsLastCompare(
                    $this->parseEstimatedServiceValue($dataA['estimated_service_value'] ?? null),
                    $this->parseEstimatedServiceValue($dataB['estimated_service_value'] ?? null),
                    true
                );
            } elseif ($sort === 'distance') {
                $cmp = $this->nullsLastCompare(
                    $this->distanceKmForLead($dataA, $providerPoint),
                    $this->distanceKmForLead($dataB, $providerPoint),
                    false
                );
            } else {
                $tsA = $this->parseEstimatedAt($dataA['estimated_service_at'] ?? null)?->timestamp;
                $tsB = $this->parseEstimatedAt($dataB['estimated_service_at'] ?? null)?->timestamp;
                $cmp = $this->nullsLastCompare($tsA, $tsB, false);
            }

            if ($cmp !== 0) {
                return $cmp;
            }

            return ((int) $b->id) <=> ((int) $a->id);
        });

        return $leads;
    }

    private function nullsLastCompare(int|float|null $a, int|float|null $b, bool $desc): int
    {
        if ($a === null && $b === null) {
            return 0;
        }
        if ($a === null) {
            return 1;
        }
        if ($b === null) {
            return -1;
        }

        return $desc ? $b <=> $a : $a <=> $b;
    }

    /**
     * @return array{0: ?\Carbon\Carbon, 1: ?\Carbon\Carbon}
     */
    private function dateRangeBounds(string $range): array
    {
        $now = \Carbon\Carbon::now();

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'next_week' => [
                $now->copy()->startOfWeek()->addWeek(),
                $now->copy()->startOfWeek()->addWeek()->endOfWeek(),
            ],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'next_month' => [
                $now->copy()->addMonthNoOverflow()->startOfMonth(),
                $now->copy()->addMonthNoOverflow()->endOfMonth(),
            ],
            default => [null, null],
        };
    }

    private function parseEstimatedAt(mixed $value): ?\Carbon\Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function parseEstimatedServiceValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;

        return $number < 0 ? null : round($number, 2);
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function providerLatLng(Provider $provider): ?array
    {
        $coords = $provider->coordinates;
        if (! is_array($coords)) {
            return null;
        }
        $lat = $coords['latitude'] ?? $coords['lat'] ?? null;
        $lng = $coords['longitude'] ?? $coords['lng'] ?? $coords['lon'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{0: float, 1: float}|null  $providerPoint
     */
    private function distanceKmForLead(array $data, ?array $providerPoint): ?float
    {
        if ($providerPoint === null) {
            return null;
        }
        $zoneId = (string) ($data['zone_id'] ?? '');
        if ($zoneId === '') {
            return null;
        }
        $centroid = $this->zoneCentroid($zoneId);
        if ($centroid === null) {
            return null;
        }

        return $this->haversineKm($providerPoint[0], $providerPoint[1], $centroid[0], $centroid[1]);
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function zoneCentroid(string $zoneId): ?array
    {
        if (array_key_exists($zoneId, $this->zoneCentroids)) {
            return $this->zoneCentroids[$zoneId];
        }

        try {
            $row = DB::selectOne(
                'SELECT ST_Y(ST_Centroid(coordinates)) as lat, ST_X(ST_Centroid(coordinates)) as lng
                 FROM zones WHERE id = ? AND coordinates IS NOT NULL',
                [$zoneId]
            );
            if ($row && is_numeric($row->lat ?? null) && is_numeric($row->lng ?? null)) {
                return $this->zoneCentroids[$zoneId] = [(float) $row->lat, (float) $row->lng];
            }
        } catch (\Throwable) {
        }

        return $this->zoneCentroids[$zoneId] = null;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    public function expressInterest(Lead $lead, Provider $provider, ?string $note = null): LeadHuntingInterest
    {
        if ($lead->hunting_status !== Lead::HUNTING_PUBLISHED) {
            throw new \RuntimeException(translate('Lead_is_not_on_the_hunting_board'));
        }

        if (! $this->isPublishedLeadVisibleToProvider($lead, $provider)) {
            throw new \RuntimeException(translate('Lead_is_not_available'));
        }

        $existing = LeadHuntingInterest::query()
            ->where('lead_id', $lead->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existing && $existing->status === LeadHuntingInterest::STATUS_REJECTED) {
            throw new \RuntimeException(translate('Lead_is_not_available'));
        }

        $alreadyInterested = $existing && $existing->status === LeadHuntingInterest::STATUS_INTERESTED;

        $interest = LeadHuntingInterest::query()->updateOrCreate(
            [
                'lead_id' => $lead->id,
                'provider_id' => $provider->id,
            ],
            [
                'status' => LeadHuntingInterest::STATUS_INTERESTED,
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]
        );

        if (! $alreadyInterested) {
            app(LeadHuntingNotificationService::class)->notifyAdminProviderInterest($lead, $provider, $interest);
        }

        return $interest;
    }

    public function withdrawInterest(Lead $lead, Provider $provider): LeadHuntingInterest
    {
        $interest = LeadHuntingInterest::query()
            ->where('lead_id', $lead->id)
            ->where('provider_id', $provider->id)
            ->first();

        if (! $interest || $interest->status !== LeadHuntingInterest::STATUS_INTERESTED) {
            throw new \RuntimeException(translate('No_interest_to_revoke'));
        }

        $interest->status = LeadHuntingInterest::STATUS_WITHDRAWN;
        $interest->save();

        app(LeadHuntingNotificationService::class)->notifyAdminProviderInterestRevoked($lead, $provider);

        return $interest;
    }

    public function rejectJob(Lead $lead, Provider $provider, string $reason): LeadHuntingInterest
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException(translate('Reject_reason_is_required'));
        }

        if ($lead->hunting_status !== Lead::HUNTING_PUBLISHED) {
            throw new \RuntimeException(translate('Lead_is_not_on_the_hunting_board'));
        }

        if (! $this->isPublishedLeadVisibleToProvider($lead, $provider)) {
            throw new \RuntimeException(translate('Lead_is_not_available'));
        }

        $existing = LeadHuntingInterest::query()
            ->where('lead_id', $lead->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existing && $existing->status === LeadHuntingInterest::STATUS_REJECTED) {
            throw new \RuntimeException(translate('Job_already_rejected'));
        }

        $interest = LeadHuntingInterest::query()->updateOrCreate(
            [
                'lead_id' => $lead->id,
                'provider_id' => $provider->id,
            ],
            [
                'status' => LeadHuntingInterest::STATUS_REJECTED,
                'note' => $reason,
            ]
        );

        app(LeadHuntingNotificationService::class)->notifyAdminProviderRejected($lead, $provider, $reason);

        return $interest;
    }
}
