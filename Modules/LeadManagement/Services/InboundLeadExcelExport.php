<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\AdSource;
use Modules\LeadManagement\Entities\CustomerLeadArea;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\District;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadFutureCustomerReasonOption;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadReasonFollowupOption;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class InboundLeadExcelExport
{
    public const REPORTS = [
        'general',
        'customer',
        'provider',
        'future_customer',
        'invalid',
    ];

    public const PREVIEW_LIMIT = 20;

    public function resolveReport(?string $report): string
    {
        $report = (string) $report;

        return in_array($report, self::REPORTS, true) ? $report : 'general';
    }

    /**
     * @return array<string, string>
     */
    public function reportLabels(): array
    {
        return [
            'general' => translate('General_Reports'),
            'customer' => translate('Customer_Lead_Reports'),
            'provider' => translate('Provider_Lead_Reports'),
            'future_customer' => translate('Future_Customer_Lead_Reports'),
            'invalid' => translate('Invalid_Lead_Reports'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return [
            'received_at' => translate('Recieved_On'),
            'name' => translate('Name'),
            'phone' => translate('Phone'),
            'next_followup' => translate('Followup_On'),
            'lead_type' => translate('Lead_Type'),
            'temperature' => translate('Lead_Temperature'),
            'created_at' => translate('Created_at'),
            'id' => translate('ID'),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function resolvedSort(Request $request): array
    {
        $sort = (string) $request->input('sort', 'received_at');
        $direction = strtolower((string) $request->input('direction', 'desc'));
        if (! array_key_exists($sort, $this->sortColumns())) {
            $sort = 'received_at';
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return [$sort, $direction];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function groups(string $report): array
    {
        $report = $this->resolveReport($report);
        $catalog = $this->catalog();
        $map = function (array $keys) use ($catalog): array {
            $out = [];
            foreach ($keys as $key) {
                $out[$key] = $catalog[$key];
            }

            return $out;
        };

        $leadKeys = ['id', 'phone'];
        if ($report === 'general') {
            $leadKeys[] = 'lead_type';
        }
        if (in_array($report, ['general', 'customer', 'provider'], true)) {
            $leadKeys[] = 'temperature';
        }
        array_push(
            $leadKeys,
            'source',
            'ad_source',
            'received_at',
            'next_followup',
            'handled_by',
            'created_by',
            'remarks',
            'open_status',
            'non_responsive',
            'non_responsive_remarks',
            'followup_count',
            'created_at'
        );
        if (in_array($report, ['general', 'customer'], true)) {
            $leadKeys[] = 'tags';
        }

        $groups = [
            'Details' => $map([
                'name',
                'status',
                'cancellation_reason',
                'area',
                'district',
                'category_name',
                'subcategory_name',
            ]),
            'Lead' => $map($leadKeys),
        ];

        if (in_array($report, ['general', 'customer'], true)) {
            $groups['Customer'] = $map([
                'customer_zone',
                'customer_service',
                'estimated_service_at',
                'estimated_service_value',
                'cancellation_remarks',
                'booking',
            ]);
        }

        if (in_array($report, ['general', 'provider'], true)) {
            $groups['Provider'] = $map([
                'provider_zones',
                'provider_service_details',
                'provider_service_areas',
                'provider_address',
                'provider_cancellation_remarks',
            ]);
        }

        if (in_array($report, ['general', 'future_customer'], true)) {
            $groups['Future customer'] = $map([
                'future_customer_reason',
                'future_customer_option',
                'future_customer_remarks',
            ]);
        }

        if (in_array($report, ['general', 'invalid'], true)) {
            $groups['Invalid'] = $map([
                'invalid_reason',
                'invalid_option',
                'invalid_remarks',
            ]);
        }

        return $groups;
    }

    /**
     * @return array<string, string>
     */
    public function flatColumns(string $report): array
    {
        $columns = [];
        foreach ($this->groups($report) as $group) {
            foreach ($group as $key => $label) {
                $columns[$key] = $label;
            }
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    public function defaultKeys(string $report): array
    {
        $report = $this->resolveReport($report);
        $core = [
            'name', 'status', 'cancellation_reason', 'area', 'district', 'category_name', 'subcategory_name',
            'phone', 'id', 'source', 'received_at', 'handled_by', 'remarks',
        ];
        $preferred = match ($report) {
            'customer' => array_merge($core, ['temperature', 'customer_zone', 'customer_service', 'booking']),
            'provider' => array_merge($core, ['temperature', 'provider_zones', 'provider_address']),
            'future_customer' => array_merge($core, ['future_customer_reason', 'future_customer_option', 'future_customer_remarks']),
            'invalid' => array_merge($core, ['invalid_reason', 'invalid_option', 'invalid_remarks']),
            default => array_merge($core, ['lead_type', 'open_status', 'future_customer_reason', 'invalid_reason']),
        };

        return array_values(array_intersect($preferred, array_keys($this->flatColumns($report))));
    }

    /**
     * @return list<string>
     */
    public function resolveKeys(string $report, Request $request): array
    {
        $known = array_keys($this->flatColumns($report));
        if (! $request->boolean('column_selection')) {
            return $this->defaultKeys($report);
        }

        $requested = array_values(array_filter(array_map('strval', (array) $request->input('columns', []))));

        return array_values(array_intersect($known, $requested));
    }

    /**
     * @return list<string>
     */
    public function relations(): array
    {
        return ['source', 'adSource', 'createdBy', 'customerLeadTags'];
    }

    public function query(Request $request, Carbon $from, Carbon $to, string $report): Builder
    {
        $report = $this->resolveReport($report);
        [$sort, $direction] = $this->resolvedSort($request);
        $sortColumn = $this->sortColumns()[$sort];

        $query = Lead::query()
            ->whereBetween('date_time_of_lead_received', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ]);

        $leadType = $this->leadTypeForReport($report);
        if ($leadType !== null) {
            $query->where('lead_type', $leadType);
        }

        $handledByIds = $this->listInput($request, 'handled_by_ids');
        if ($handledByIds !== []) {
            $query->whereIn('handled_by', $handledByIds);
        }

        $sourceIds = $this->listInput($request, 'source_ids');
        if ($sourceIds !== []) {
            $query->whereIn('source_id', $sourceIds);
        }

        $adSourceIds = $this->listInput($request, 'ad_source_ids');
        if ($adSourceIds !== []) {
            $query->whereIn('ad_source_id', $adSourceIds);
        }

        if (in_array($report, ['general', 'customer', 'provider'], true)) {
            $temperatures = array_values(array_intersect(
                $this->listInput($request, 'lead_temperature'),
                Lead::TEMPERATURES
            ));
            if ($temperatures !== []) {
                $query->whereIn('lead_temperature', $temperatures);
            }
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($inner) use ($like, $search) {
                $inner->where('name', 'like', $like)
                    ->orWhere('phone_number', 'like', $like);
                if (ctype_digit($search)) {
                    $inner->orWhere('id', (int) $search);
                }
            });
        }

        $this->applyHistoryFilters($query, $report, $request);

        $openStatus = (string) $request->input('open_status', 'all');
        if ($openStatus === 'open') {
            app(LeadOpenStatusService::class)->restrictQueryToOpenLeads($query);
        } elseif ($openStatus === 'closed') {
            $openQuery = clone $query;
            app(LeadOpenStatusService::class)->restrictQueryToOpenLeads($openQuery);
            $openIds = $openQuery->pluck('id');
            if ($openIds->isNotEmpty()) {
                $query->whereNotIn('id', $openIds->all());
            }
        }

        $query->orderBy($sortColumn, $direction);
        if ($sortColumn !== 'id') {
            $query->orderByDesc('id');
        }

        return $query;
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, string>>
     */
    public function valueRows(Collection $leads, array $keys): array
    {
        if ($leads->isEmpty() || $keys === []) {
            return [];
        }

        $context = $this->buildContext($leads);
        $rows = [];
        foreach ($leads as $lead) {
            $row = [];
            foreach ($keys as $key) {
                $row[$key] = $this->cell($lead, $key, $context);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, string>>
     */
    public function labeledRows(Collection $leads, array $keys): array
    {
        $labels = $this->catalog();
        $rows = [];
        foreach ($this->valueRows($leads, $keys) as $row) {
            $labeled = [];
            foreach ($keys as $key) {
                $labeled[$labels[$key] ?? $key] = $row[$key] ?? '';
            }
            $rows[] = $labeled;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(string $report): array
    {
        $report = $this->resolveReport($report);

        return [
            'sources' => Source::active()->orderBy('name')->get(['id', 'name']),
            'adSources' => AdSource::active()->orderBy('name')->get(['id', 'name']),
            'employees' => User::query()
                ->whereIn('user_type', ['super-admin', 'admin-employee'])
                ->ofStatus(1)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'email']),
            'customerStatuses' => CustomerLeadStatus::query()->orderBy('name')->get(['id', 'name']),
            'providerStatuses' => ProviderLeadStatus::query()->orderBy('name')->get(['id', 'name']),
            'futureReasons' => LeadFutureCustomerReason::query()->orderBy('name')->get(['id', 'name']),
            'invalidReasons' => LeadInvalidReason::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::withoutGlobalScope('zone_wise_data')
                ->ofType('main')
                ->ordered()
                ->get(['id', 'name', 'parent_id', 'position']),
            'subcategories' => Category::withoutGlobalScope('zone_wise_data')
                ->with(['parent' => function ($parent) {
                    $parent->withoutGlobalScope('zone_wise_data')->with('translations');
                }])
                ->ofType('sub')
                ->ordered()
                ->get(['id', 'name', 'parent_id', 'position']),
            'zones' => Zone::query()->forAdminList()->orderBy('name')->get(),
            'areas' => CustomerLeadArea::query()->orderBy('name')->get(['id', 'name']),
            'cancelReasons' => LeadCancellationReason::query()->orderBy('name')->get(['id', 'name']),
            'providerCancelReasons' => ProviderCancellationReason::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Filters that stay useful when switching between inbound report types.
     *
     * @return array<string, mixed>
     */
    public function sharedQuery(Request $request, Carbon $from, Carbon $to): array
    {
        [$sort, $direction] = $this->resolvedSort($request);
        $shared = [
            'inbound_report' => $this->resolveReport($request->input('inbound_report')),
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'sort' => $sort,
            'direction' => $direction,
            'open_status' => in_array($request->input('open_status'), ['open', 'closed'], true) ? $request->input('open_status') : 'all',
        ];

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $shared['search'] = $search;
        }

        foreach (['handled_by_ids', 'source_ids', 'ad_source_ids', 'customer_status_ids', 'provider_status_ids', 'future_customer_reason_ids', 'invalid_reason_ids', 'category_ids', 'subcategory_ids', 'zone_ids', 'area_ids', 'cancellation_reason_ids'] as $key) {
            $values = $this->listInput($request, $key);
            if ($values !== []) {
                $shared[$key] = $values;
            }
        }

        $temperatures = array_values(array_intersect($this->listInput($request, 'lead_temperature'), Lead::TEMPERATURES));
        if ($temperatures !== []) {
            $shared['lead_temperature'] = $temperatures;
        }

        return $shared;
    }

    /**
     * @return array<string, string>
     */
    private function catalog(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'status' => 'Status',
            'area' => 'Area',
            'district' => 'District',
            'category_name' => 'Category name',
            'subcategory_name' => 'Sub category name',
            'phone' => 'Phone',
            'lead_type' => 'Lead type',
            'temperature' => 'Lead temperature',
            'source' => 'Source',
            'ad_source' => 'Ad source',
            'received_at' => 'Received on',
            'next_followup' => 'Follow-up on',
            'handled_by' => 'Handled by',
            'created_by' => 'Created by',
            'remarks' => 'Remarks',
            'open_status' => 'Open or closed',
            'non_responsive' => 'Non responsive',
            'non_responsive_remarks' => 'Non responsive remarks',
            'followup_count' => 'Follow-ups',
            'created_at' => 'Created at',
            'tags' => 'Tags',
            'customer_status' => 'Customer status',
            'customer_zone' => 'Customer zone',
            'customer_area' => 'Customer area',
            'customer_category' => 'Customer category',
            'customer_subcategory' => 'Customer sub category',
            'customer_service' => 'Customer service',
            'estimated_service_at' => 'Estimated service time',
            'estimated_service_value' => 'Estimated service value',
            'cancellation_reason' => 'Cancellation reason',
            'cancellation_remarks' => 'Cancellation remarks',
            'booking' => 'Booking',
            'provider_status' => 'Provider status',
            'provider_district' => 'District',
            'provider_zones' => 'Provider zones',
            'provider_area' => 'Provider area',
            'provider_category' => 'Provider category',
            'provider_subcategory' => 'Provider sub category',
            'provider_service_details' => 'Service details',
            'provider_service_areas' => 'Service areas',
            'provider_address' => 'Address',
            'provider_cancellation_reason' => 'Provider cancellation reason',
            'provider_cancellation_remarks' => 'Provider cancellation remarks',
            'future_customer_reason' => 'Future customer reason',
            'future_customer_option' => 'Future customer option',
            'future_customer_remarks' => 'Future customer remarks',
            'invalid_reason' => 'Invalid reason',
            'invalid_option' => 'Invalid option',
            'invalid_remarks' => 'Invalid remarks',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sortColumns(): array
    {
        return [
            'received_at' => 'date_time_of_lead_received',
            'name' => 'name',
            'phone' => 'phone_number',
            'next_followup' => 'next_followup_at',
            'lead_type' => 'lead_type',
            'temperature' => 'lead_temperature',
            'created_at' => 'created_at',
            'id' => 'id',
        ];
    }

    private function leadTypeForReport(string $report): ?string
    {
        return match ($report) {
            'customer' => Lead::TYPE_CUSTOMER,
            'provider' => Lead::TYPE_PROVIDER,
            'future_customer' => Lead::TYPE_FUTURE_CUSTOMER,
            'invalid' => Lead::TYPE_INVALID,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function listInput(Request $request, string $key): array
    {
        return array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            (array) $request->input($key, [])
        ), static fn ($value) => $value !== ''));
    }

    private function applyHistoryFilters(Builder $query, string $report, Request $request): void
    {
        $rules = [];
        $push = function (array $keys, array $wanted, array $listKeys = []) use (&$rules): void {
            $wanted = array_values(array_unique(array_map('strval', $wanted)));
            if ($wanted === []) {
                return;
            }
            $rules[] = ['keys' => $keys, 'list' => $listKeys, 'wanted' => $wanted];
        };

        if ($report === 'customer') {
            $push(['customer_lead_status_id'], $this->listInput($request, 'customer_status_ids'));
        }
        if ($report === 'provider') {
            $push(['provider_lead_status_id'], $this->listInput($request, 'provider_status_ids'));
        }
        if ($report === 'future_customer') {
            $push(['future_customer_reason_id'], $this->listInput($request, 'future_customer_reason_ids'));
        }
        if ($report === 'invalid') {
            $push(['invalid_reason_id'], $this->listInput($request, 'invalid_reason_ids'));
        }

        $push(['service_category', 'provider_service_category'], $this->listInput($request, 'category_ids'));
        $push(['service_subcategory', 'provider_service_subcategory'], $this->listInput($request, 'subcategory_ids'));
        $push(['zone_id'], $this->listInput($request, 'zone_ids'), ['zone_ids']);
        $push(['area_id'], $this->listInput($request, 'area_ids'));

        [$customerCancel, $providerCancel] = $this->splitCancellationTokens($this->listInput($request, 'cancellation_reason_ids'));
        if ($customerCancel !== [] || $providerCancel !== []) {
            $rules[] = ['cancel' => true, 'customer' => $customerCancel, 'provider' => $providerCancel];
        }

        if ($rules === []) {
            return;
        }

        $leads = (clone $query)->get(['id', 'lead_type']);
        if ($leads->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $type = $this->leadTypeForReport($report);
        $leadTypes = [];
        foreach ($leads as $lead) {
            $leadTypes[(int) $lead->id] = (string) $lead->lead_type;
        }

        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', array_keys($leadTypes))
            ->when($type !== null, fn ($builder) => $builder->where('type', $type))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['lead_id', 'type', 'data']);

        $latest = [];
        foreach ($histories as $history) {
            $leadId = (int) $history->lead_id;
            if ($type === null && $history->type !== ($leadTypes[$leadId] ?? '')) {
                continue;
            }
            if (! array_key_exists($leadId, $latest)) {
                $latest[$leadId] = is_array($history->data) ? $history->data : [];
            }
        }

        $matched = [];
        foreach (array_keys($leadTypes) as $leadId) {
            $data = $latest[$leadId] ?? null;
            if (! is_array($data) || ! $this->historyMatches($data, $rules)) {
                continue;
            }
            $matched[] = $leadId;
        }

        if ($matched === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('id', $matched);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $rules
     */
    private function historyMatches(array $data, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (! empty($rule['cancel'])) {
                $customerId = isset($data['cancellation_reason_id']) ? (string) $data['cancellation_reason_id'] : '';
                $providerId = isset($data['provider_cancellation_reason_id']) ? (string) $data['provider_cancellation_reason_id'] : '';
                $hit = ($customerId !== '' && in_array($customerId, $rule['customer'], true))
                    || ($providerId !== '' && in_array($providerId, $rule['provider'], true));
                if (! $hit) {
                    return false;
                }

                continue;
            }

            if (! $this->historyValueMatches($data, $rule['keys'], $rule['wanted'], $rule['list'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @param  list<string>  $wanted
     * @param  list<string>  $listKeys
     */
    private function historyValueMatches(array $data, array $keys, array $wanted, array $listKeys): bool
    {
        foreach ($keys as $key) {
            $value = isset($data[$key]) ? (string) $data[$key] : '';
            if ($value !== '' && in_array($value, $wanted, true)) {
                return true;
            }
        }

        foreach ($listKeys as $key) {
            foreach ((array) ($data[$key] ?? []) as $item) {
                if (in_array((string) $item, $wanted, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $tokens
     * @return array{0: list<string>, 1: list<string>}
     */
    private function splitCancellationTokens(array $tokens): array
    {
        $customer = [];
        $provider = [];
        foreach ($tokens as $token) {
            if (str_starts_with($token, 'c:')) {
                $id = substr($token, 2);
                if ($id !== '') {
                    $customer[] = $id;
                }
            } elseif (str_starts_with($token, 'p:')) {
                $id = substr($token, 2);
                if ($id !== '') {
                    $provider[] = $id;
                }
            }
        }

        return [array_values(array_unique($customer)), array_values(array_unique($provider))];
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<string, mixed>
     */
    private function buildContext(Collection $leads): array
    {
        $ids = $leads->pluck('id')->all();
        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $ids)
            ->whereIn('type', [
                Lead::TYPE_CUSTOMER,
                Lead::TYPE_PROVIDER,
                Lead::TYPE_INVALID,
                Lead::TYPE_FUTURE_CUSTOMER,
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['lead_id', 'type', 'data']);

        $latest = [];
        $statusIds = [];
        $providerStatusIds = [];
        $zoneIds = [];
        $areaIds = [];
        $categoryIds = [];
        $serviceIds = [];
        $districtIds = [];
        $cancelReasonIds = [];
        $providerCancelReasonIds = [];
        $futureReasonIds = [];
        $futureOptionIds = [];
        $invalidReasonIds = [];
        $invalidOptionIds = [];

        foreach ($histories as $history) {
            $key = $history->lead_id.'|'.$history->type;
            if (isset($latest[$key])) {
                continue;
            }
            $data = is_array($history->data) ? $history->data : [];
            $latest[$key] = $data;

            $this->collectId($statusIds, $data['customer_lead_status_id'] ?? null);
            $this->collectId($providerStatusIds, $data['provider_lead_status_id'] ?? null);
            $this->collectId($zoneIds, $data['zone_id'] ?? null);
            foreach ((array) ($data['zone_ids'] ?? []) as $zoneId) {
                $this->collectId($zoneIds, $zoneId);
            }
            $this->collectId($areaIds, $data['area_id'] ?? null);
            $this->collectId($categoryIds, $data['service_category'] ?? null);
            $this->collectId($categoryIds, $data['service_subcategory'] ?? null);
            $this->collectId($categoryIds, $data['provider_service_category'] ?? null);
            $this->collectId($categoryIds, $data['provider_service_subcategory'] ?? null);
            $this->collectId($serviceIds, $data['service_name'] ?? null);
            $this->collectId($districtIds, $data['district_id'] ?? null);
            $this->collectId($cancelReasonIds, $data['cancellation_reason_id'] ?? null);
            $this->collectId($providerCancelReasonIds, $data['provider_cancellation_reason_id'] ?? null);
            $this->collectId($futureReasonIds, $data['future_customer_reason_id'] ?? null);
            $this->collectId($futureOptionIds, $data['future_customer_reason_option_id'] ?? null);
            $this->collectId($invalidReasonIds, $data['invalid_reason_id'] ?? null);
            $this->collectId($invalidOptionIds, $data['invalid_reason_option_id'] ?? null);
        }

        $handledByIds = [];
        foreach ($leads as $lead) {
            if (Lead::assigneeIsHuman($lead->handled_by)) {
                $handledByIds[] = (string) $lead->handled_by;
            }
        }

        $users = $handledByIds !== []
            ? User::query()->whereIn('id', array_unique($handledByIds))->get(['id', 'first_name', 'last_name', 'email'])->keyBy(fn ($user) => (string) $user->id)
            : collect();

        $bookings = Booking::query()
            ->whereIn('lead_id', $ids)
            ->orderByDesc('created_at')
            ->get(['id', 'lead_id', 'readable_id'])
            ->groupBy(fn ($booking) => (string) $booking->lead_id)
            ->map(fn ($group) => $group->first());

        return [
            'latest' => $latest,
            'open' => app(LeadOpenStatusService::class)->buildLeadStatusMeta($leads),
            'users' => $users,
            'bookings' => $bookings,
            'statuses' => $this->keyedModels(CustomerLeadStatus::class, $statusIds),
            'providerStatuses' => $this->keyedModels(ProviderLeadStatus::class, $providerStatusIds),
            'zones' => $zoneIds === [] ? collect() : Zone::withoutGlobalScopes()->with('translations')->whereIn('id', array_unique($zoneIds))->get()->keyBy(fn ($row) => (string) $row->id),
            'areas' => $this->keyedModels(CustomerLeadArea::class, $areaIds),
            'categories' => $categoryIds === [] ? collect() : Category::withoutGlobalScopes()->with('translations')->whereIn('id', array_unique($categoryIds))->get()->keyBy(fn ($row) => (string) $row->id),
            'services' => $serviceIds === [] ? collect() : Service::withTrashed()->with('translations')->whereIn('id', array_unique($serviceIds))->get()->keyBy(fn ($row) => (string) $row->id),
            'districts' => $this->keyedModels(District::class, $districtIds),
            'cancelReasons' => $this->keyedModels(LeadCancellationReason::class, $cancelReasonIds),
            'providerCancelReasons' => $this->keyedModels(ProviderCancellationReason::class, $providerCancelReasonIds),
            'futureReasons' => $this->keyedModels(LeadFutureCustomerReason::class, $futureReasonIds),
            'futureOptions' => $this->keyedModels(LeadFutureCustomerReasonOption::class, $futureOptionIds),
            'invalidReasons' => $this->keyedModels(LeadInvalidReason::class, $invalidReasonIds),
            'invalidOptions' => $invalidOptionIds === []
                ? collect()
                : LeadReasonFollowupOption::query()
                    ->where('reason_type', 'invalid_reason')
                    ->whereIn('id', array_unique($invalidOptionIds))
                    ->get()
                    ->keyBy(fn ($row) => (string) $row->id),
        ];
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function keyedModels(string $model, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return $model::query()->whereIn('id', array_unique($ids))->get()->keyBy(fn ($row) => (string) $row->id);
    }

    /**
     * @param  list<int|string>  $bucket
     */
    private function collectId(array &$bucket, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $bucket[] = $value;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function cell(Lead $lead, string $key, array $context): string
    {
        $customer = $context['latest'][$lead->id.'|'.Lead::TYPE_CUSTOMER] ?? [];
        $provider = $context['latest'][$lead->id.'|'.Lead::TYPE_PROVIDER] ?? [];
        $future = $context['latest'][$lead->id.'|'.Lead::TYPE_FUTURE_CUSTOMER] ?? [];
        $invalid = $context['latest'][$lead->id.'|'.Lead::TYPE_INVALID] ?? [];

        return match ($key) {
            'id' => (string) $lead->id,
            'name' => trim((string) ($lead->name ?? '')),
            'status' => $this->statusText($lead, $customer, $provider, $future, $invalid, $context),
            'area' => $this->modelName($context['areas'], $this->historyFor($lead, $customer, $provider, $future, $invalid)['area_id'] ?? null),
            'district' => $this->modelName($context['districts'], $this->historyFor($lead, $customer, $provider, $future, $invalid)['district_id'] ?? null),
            'category_name' => $this->categoryText($lead, $customer, $provider, $context, false),
            'subcategory_name' => $this->categoryText($lead, $customer, $provider, $context, true),
            'phone' => (string) ($lead->phone_number ?? ''),
            'lead_type' => Lead::leadTypes()[$lead->lead_type] ?? (string) $lead->lead_type,
            'temperature' => in_array($lead->lead_type, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)
                ? $this->plainTemperature($lead->lead_temperature)
                : '',
            'source' => (string) ($lead->source?->name ?? ''),
            'ad_source' => (string) ($lead->adSource?->name ?? ''),
            'received_at' => $this->dateText($lead->date_time_of_lead_received),
            'next_followup' => $this->dateText($lead->next_followup_at),
            'handled_by' => $this->handledByLabel($lead->handled_by, $context['users']),
            'created_by' => $this->personName($lead->createdBy),
            'remarks' => trim((string) ($lead->remarks ?? '')),
            'open_status' => (string) ($context['open'][(int) $lead->id]['label'] ?? ''),
            'non_responsive' => $lead->isNonResponsive() ? 'Yes' : 'No',
            'non_responsive_remarks' => trim((string) ($lead->non_responsive_remarks ?? '')),
            'followup_count' => (string) ($lead->followups_count ?? 0),
            'created_at' => $this->dateText($lead->created_at),
            'tags' => $lead->customerLeadTags->pluck('name')->filter()->implode(', '),
            'customer_status' => $this->modelName($context['statuses'], $customer['customer_lead_status_id'] ?? null),
            'customer_zone' => $this->modelName($context['zones'], $customer['zone_id'] ?? null),
            'customer_area' => $this->modelName($context['areas'], $customer['area_id'] ?? null),
            'customer_category' => $this->modelName($context['categories'], $customer['service_category'] ?? null),
            'customer_subcategory' => $this->modelName($context['categories'], $customer['service_subcategory'] ?? null),
            'customer_service' => $this->modelName($context['services'], $customer['service_name'] ?? null),
            'estimated_service_at' => $this->looseDateText($customer['estimated_service_at'] ?? null),
            'estimated_service_value' => $this->moneyText($customer['estimated_service_value'] ?? null),
            'cancellation_reason' => $this->cancellationText($lead, $customer, $provider, $context),
            'cancellation_remarks' => trim((string) ($customer['cancellation_remarks'] ?? '')),
            'booking' => $this->bookingLabel($context['bookings']->get((string) $lead->id)),
            'provider_status' => $this->modelName($context['providerStatuses'], $provider['provider_lead_status_id'] ?? null),
            'provider_district' => $this->modelName($context['districts'], $provider['district_id'] ?? null),
            'provider_zones' => $this->zoneList($provider, $context['zones']),
            'provider_area' => $this->modelName($context['areas'], $provider['area_id'] ?? null),
            'provider_category' => $this->modelName($context['categories'], $provider['provider_service_category'] ?? null),
            'provider_subcategory' => $this->modelName($context['categories'], $provider['provider_service_subcategory'] ?? null),
            'provider_service_details' => trim((string) ($provider['provider_service_details'] ?? '')),
            'provider_service_areas' => trim((string) ($provider['service_areas'] ?? '')),
            'provider_address' => trim((string) ($provider['full_address'] ?? '')),
            'provider_cancellation_reason' => $this->modelName($context['providerCancelReasons'], $provider['provider_cancellation_reason_id'] ?? null),
            'provider_cancellation_remarks' => trim((string) ($provider['provider_cancellation_remarks'] ?? '')),
            'future_customer_reason' => $this->modelName($context['futureReasons'], $future['future_customer_reason_id'] ?? null),
            'future_customer_option' => $this->modelName($context['futureOptions'], $future['future_customer_reason_option_id'] ?? null),
            'future_customer_remarks' => trim((string) ($future['future_customer_remarks'] ?? '')),
            'invalid_reason' => $this->modelName($context['invalidReasons'], $invalid['invalid_reason_id'] ?? null),
            'invalid_option' => $this->modelName($context['invalidOptions'], $invalid['invalid_reason_option_id'] ?? null),
            'invalid_remarks' => trim((string) ($invalid['invalid_remarks'] ?? '')),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $provider
     * @param  array<string, mixed>  $future
     * @param  array<string, mixed>  $invalid
     * @return array<string, mixed>
     */
    private function historyFor(Lead $lead, array $customer, array $provider, array $future, array $invalid): array
    {
        return match ($lead->lead_type) {
            Lead::TYPE_CUSTOMER => $customer,
            Lead::TYPE_PROVIDER => $provider,
            Lead::TYPE_FUTURE_CUSTOMER => $future,
            Lead::TYPE_INVALID => $invalid,
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $provider
     * @param  array<string, mixed>  $future
     * @param  array<string, mixed>  $invalid
     * @param  array<string, mixed>  $context
     */
    private function statusText(Lead $lead, array $customer, array $provider, array $future, array $invalid, array $context): string
    {
        return match ($lead->lead_type) {
            Lead::TYPE_CUSTOMER => $this->modelName($context['statuses'], $customer['customer_lead_status_id'] ?? null),
            Lead::TYPE_PROVIDER => $this->modelName($context['providerStatuses'], $provider['provider_lead_status_id'] ?? null),
            Lead::TYPE_FUTURE_CUSTOMER => $this->modelName($context['futureReasons'], $future['future_customer_reason_id'] ?? null),
            Lead::TYPE_INVALID => $this->modelName($context['invalidReasons'], $invalid['invalid_reason_id'] ?? null),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $provider
     * @param  array<string, mixed>  $context
     */
    private function cancellationText(Lead $lead, array $customer, array $provider, array $context): string
    {
        return match ($lead->lead_type) {
            Lead::TYPE_CUSTOMER => $this->modelName($context['cancelReasons'], $customer['cancellation_reason_id'] ?? null),
            Lead::TYPE_PROVIDER => $this->modelName($context['providerCancelReasons'], $provider['provider_cancellation_reason_id'] ?? null),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $provider
     * @param  array<string, mixed>  $context
     */
    private function categoryText(Lead $lead, array $customer, array $provider, array $context, bool $sub): string
    {
        $data = match ($lead->lead_type) {
            Lead::TYPE_CUSTOMER => $customer,
            Lead::TYPE_PROVIDER => $provider,
            default => [],
        };
        $key = match (true) {
            $lead->lead_type === Lead::TYPE_PROVIDER && $sub => 'provider_service_subcategory',
            $lead->lead_type === Lead::TYPE_PROVIDER => 'provider_service_category',
            $sub => 'service_subcategory',
            default => 'service_category',
        };

        return $this->modelName($context['categories'], $data[$key] ?? null);
    }

    private function plainTemperature(?string $temperature): string
    {
        return match ($temperature) {
            Lead::TEMPERATURE_HOTTER => 'Hotter',
            Lead::TEMPERATURE_HOT => 'Hot',
            Lead::TEMPERATURE_WARM => 'Warm',
            Lead::TEMPERATURE_COLD => 'Cold',
            default => '',
        };
    }

    private function handledByLabel(?string $handledBy, Collection $users): string
    {
        if (! Lead::assigneeIsHuman($handledBy)) {
            return 'Unassigned';
        }

        return $this->personName($users->get((string) $handledBy));
    }

    private function personName(mixed $user): string
    {
        if (! $user) {
            return '';
        }
        $name = trim(((string) ($user->first_name ?? '')).' '.((string) ($user->last_name ?? '')));

        return $name !== '' ? $name : (string) ($user->email ?? '');
    }

    private function modelName(Collection $models, mixed $id): string
    {
        if ($id === null || $id === '') {
            return '';
        }
        $model = $models->get((string) $id);
        if (! $model) {
            return '';
        }
        $name = $model->name ?? '';
        if (is_array($name)) {
            $name = $name['en'] ?? reset($name) ?: '';
        }

        return trim((string) $name);
    }

    /**
     * @param  array<string, mixed>  $provider
     */
    private function zoneList(array $provider, Collection $zones): string
    {
        $ids = array_values(array_filter((array) ($provider['zone_ids'] ?? [])));
        if ($ids === [] && ! empty($provider['zone_id'])) {
            $ids = [$provider['zone_id']];
        }
        $names = [];
        foreach ($ids as $id) {
            $name = $this->modelName($zones, $id);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return implode(', ', array_unique($names));
    }

    private function bookingLabel(mixed $booking): string
    {
        if (! $booking) {
            return '';
        }

        $readable = trim((string) ($booking->readable_id ?? ''));

        return $readable !== '' ? $readable : (string) $booking->id;
    }

    private function dateText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i');
        }
        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function looseDateText(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }
        try {
            return Carbon::parse($text)->format('Y-m-d H:i');
        } catch (\Throwable) {
            return $text;
        }
    }

    private function moneyText(mixed $value): string
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
