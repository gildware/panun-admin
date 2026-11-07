<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
use Modules\LeadManagement\Entities\LeadFutureProviderReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadProviderChecklist;
use Modules\LeadManagement\Entities\LeadReasonFollowupOption;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderChecklistItem;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class LeadListExcelExport
{
    public const PREVIEW_LIMIT = 20;

    /**
     * @return array<string, string>
     */
    public function scopes(): array
    {
        return [
            'all' => translate('All_Leads'),
            Lead::TYPE_UNKNOWN => translate('Unknown_Leads'),
            Lead::TYPE_CUSTOMER => translate('Customer_Lead'),
            Lead::TYPE_FUTURE_CUSTOMER => translate('Future_Customer_Lead'),
            Lead::TYPE_FUTURE_PROVIDER => translate('Future_Provider_Lead'),
            Lead::TYPE_PROVIDER => translate('Provider_Leads'),
            Lead::TYPE_INVALID => translate('Invalid_Leads'),
        ];
    }

    public function resolveScope(?string $scope): string
    {
        $scope = (string) $scope;

        return array_key_exists($scope, $this->scopes()) ? $scope : 'all';
    }

    public function showsFollowup(string $scope): bool
    {
        return in_array($this->resolveScope($scope), ['all', Lead::TYPE_UNKNOWN, Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true);
    }

    public function showsTemperature(string $scope): bool
    {
        return in_array($this->resolveScope($scope), [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true);
    }

    /**
     * @return list<array{key: string, label: string, columns: array<string, string>}>
     */
    public function groups(string $scope): array
    {
        $scope = $this->resolveScope($scope);
        $groups = [
            [
                'key' => 'lead',
                'label' => translate('Lead_Details'),
                'columns' => $this->leadColumns($scope),
            ],
        ];

        if ($this->showsFollowup($scope)) {
            $groups[] = [
                'key' => 'followup',
                'label' => translate('Follow_ups'),
                'columns' => [
                    'next_followup' => translate('Followup_On'),
                    'followup_count' => translate('Follow_ups'),
                    'non_responsive' => translate('Non_Responsive'),
                    'non_responsive_remarks' => translate('Non_Responsive').' '.translate('Remarks'),
                ],
            ];
        }

        if (in_array($scope, ['all', Lead::TYPE_CUSTOMER], true)) {
            $customerPrefix = $scope === 'all' ? translate('Customer_Lead').' ' : '';
            $groups[] = [
                'key' => 'customer',
                'label' => translate('Customer_Lead'),
                'columns' => [
                    'customer_status' => $customerPrefix.translate('Status'),
                    'booking' => translate('Booking_ID'),
                    'cancellation_reason' => translate('Customer_cancellation_reasons'),
                    'cancellation_remarks' => $customerPrefix.translate('Cancellation_Remarks'),
                    'customer_zone' => $customerPrefix.translate('Zone'),
                    'customer_area' => $customerPrefix.translate('Area'),
                    'customer_category' => $customerPrefix.translate('Category'),
                    'customer_subcategory' => $customerPrefix.translate('Sub_Category'),
                    'customer_service' => translate('Service'),
                    'customer_variant' => translate('variant'),
                    'customer_description' => translate('Description'),
                    'estimated_service_at' => translate('Estimated_Date_Time_of_Service'),
                    'estimated_service_value' => translate('Estimated_Service_Value'),
                    'tags' => translate('Tags'),
                ],
            ];
        }

        if (in_array($scope, ['all', Lead::TYPE_PROVIDER], true)) {
            $providerPrefix = $scope === 'all' ? translate('Provider_Leads').' ' : '';
            $groups[] = [
                'key' => 'provider',
                'label' => translate('Provider_Leads'),
                'columns' => [
                    'provider_status' => $providerPrefix.translate('Status'),
                    'panel_provider' => translate('Is_Added_in_Panel'),
                    'provider_cancellation_reason' => translate('Provider_cancellation_reasons'),
                    'provider_cancellation_remarks' => $providerPrefix.translate('Cancellation_Remarks'),
                    'provider_district' => translate('District'),
                    'provider_zones' => translate('Zones'),
                    'provider_area' => $providerPrefix.translate('Area'),
                    'provider_category' => translate('Service_Category'),
                    'provider_subcategory' => $providerPrefix.translate('Sub_Category'),
                    'provider_service_details' => translate('Service_Details'),
                    'provider_service_areas' => translate('Service_Areas'),
                    'provider_address' => translate('Full_Address'),
                    'checklist' => translate('Checklist_Done_Items'),
                ],
            ];
        }

        if (in_array($scope, ['all', Lead::TYPE_FUTURE_CUSTOMER], true)) {
            $futureCustomerPrefix = $scope === 'all' ? translate('Future_Customer_Lead').' ' : '';
            $groups[] = [
                'key' => 'future_customer',
                'label' => translate('Future_Customer_Lead'),
                'columns' => [
                    'future_customer_reason' => $futureCustomerPrefix.translate('Reason'),
                    'future_customer_option' => $futureCustomerPrefix.translate('Follow_up'),
                    'future_customer_remarks' => $futureCustomerPrefix.translate('Remarks'),
                    'outbound_enquiries' => translate('Outbound_Enquiries'),
                ],
            ];
        }

        if (in_array($scope, ['all', Lead::TYPE_FUTURE_PROVIDER], true)) {
            $futureProviderPrefix = $scope === 'all' ? translate('Future_Provider_Lead').' ' : '';
            $groups[] = [
                'key' => 'future_provider',
                'label' => translate('Future_Provider_Lead'),
                'columns' => [
                    'future_provider_reason' => $futureProviderPrefix.translate('Reason'),
                    'future_provider_option' => $futureProviderPrefix.translate('Follow_up'),
                    'future_provider_remarks' => $futureProviderPrefix.translate('Remarks'),
                    'future_provider_area' => $futureProviderPrefix.translate('Area'),
                ],
            ];
        }

        if (in_array($scope, ['all', Lead::TYPE_INVALID], true)) {
            $invalidPrefix = $scope === 'all' ? translate('Invalid_Leads').' ' : '';
            $groups[] = [
                'key' => 'invalid',
                'label' => translate('Invalid_Leads'),
                'columns' => [
                    'invalid_reason' => $invalidPrefix.translate('Reason'),
                    'invalid_option' => $invalidPrefix.translate('Follow_up'),
                    'invalid_remarks' => $invalidPrefix.translate('Remarks'),
                    'invalid_area' => $invalidPrefix.translate('Area'),
                ],
            ];
        }

        return $groups;
    }

    /**
     * @return array<string, string>
     */
    public function flatColumns(string $scope): array
    {
        $columns = [];
        foreach ($this->groups($scope) as $group) {
            foreach ($group['columns'] as $key => $label) {
                $columns[$key] = $label;
            }
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    public function defaultKeys(string $scope): array
    {
        $scope = $this->resolveScope($scope);
        $preferred = match ($scope) {
            Lead::TYPE_CUSTOMER => [
                'lead_link', 'id', 'name', 'phone', 'temperature', 'customer_status', 'booking',
                'cancellation_reason', 'customer_zone', 'customer_category', 'customer_subcategory',
                'estimated_service_at', 'source', 'received_at', 'next_followup', 'handled_by', 'open_status', 'tags',
            ],
            Lead::TYPE_PROVIDER => [
                'lead_link', 'id', 'name', 'phone', 'temperature', 'provider_status', 'panel_provider',
                'provider_cancellation_reason', 'provider_district', 'provider_zones', 'provider_category',
                'checklist', 'source', 'received_at', 'next_followup', 'handled_by', 'open_status',
            ],
            Lead::TYPE_FUTURE_CUSTOMER => [
                'lead_link', 'id', 'name', 'phone', 'future_customer_reason', 'future_customer_option',
                'outbound_enquiries', 'source', 'received_at', 'handled_by', 'open_status',
            ],
            Lead::TYPE_FUTURE_PROVIDER => [
                'lead_link', 'id', 'name', 'phone', 'future_provider_reason', 'future_provider_option',
                'future_provider_area', 'source', 'received_at', 'handled_by', 'open_status',
            ],
            Lead::TYPE_INVALID => [
                'lead_link', 'id', 'name', 'phone', 'invalid_reason', 'invalid_option', 'invalid_area',
                'source', 'received_at', 'handled_by', 'open_status',
            ],
            Lead::TYPE_UNKNOWN => [
                'lead_link', 'id', 'name', 'phone', 'source', 'received_at', 'next_followup', 'handled_by', 'open_status',
            ],
            default => [
                'lead_link', 'id', 'name', 'phone', 'lead_type', 'temperature', 'source', 'received_at',
                'next_followup', 'handled_by', 'open_status',
            ],
        };

        return $this->ensureLeadLink(array_values(array_intersect($preferred, array_keys($this->flatColumns($scope)))));
    }

    /**
     * @return list<string>
     */
    public function resolveKeys(string $scope, Request $request): array
    {
        $known = array_keys($this->flatColumns($scope));
        if (! $request->boolean('column_selection')) {
            return $this->defaultKeys($scope);
        }

        $requested = array_values(array_filter(array_map('strval', (array) $request->input('columns', []))));

        return $this->ensureLeadLink(array_values(array_intersect($known, $requested)));
    }

    public function query(Request $request): Builder
    {
        $scope = $this->resolveScope($request->input('scope'));
        $query = Lead::query()->ofType($scope === 'all' ? null : $scope);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $relatedIds = app(PartyPhoneDirectory::class)->relatedLeadIdsForSearch($search);
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($inner) use ($like, $search, $relatedIds) {
                $inner->where('name', 'like', $like)
                    ->orWhere('phone_number', 'like', $like);
                if (ctype_digit($search)) {
                    $inner->orWhere('id', (int) $search);
                }
                if ($relatedIds !== []) {
                    $inner->orWhereIn('id', $relatedIds);
                }
            });
        }

        $sourceIds = $this->listInput($request, 'source_id');
        if ($sourceIds !== []) {
            $query->whereIn('source_id', $sourceIds);
        }

        $adSourceIds = $this->listInput($request, 'ad_source_id');
        if ($adSourceIds !== []) {
            $query->whereIn('ad_source_id', $adSourceIds);
        }

        $this->applyHandledBy($query, $this->listInput($request, 'handled_by'));
        $this->applyReceivedRange($query, $request);
        if ($this->showsFollowup($scope)) {
            $this->applyFollowupRange($query, $request);
        }
        if ($this->showsTemperature($scope)) {
            $temperatures = array_values(array_intersect($this->listInput($request, 'lead_temperature'), Lead::TEMPERATURES));
            if ($temperatures !== []) {
                $query->whereIn('lead_temperature', $temperatures);
            }
        }

        $this->applyTypeFilters($query, $scope, $request);

        $leadStatus = (string) $request->input('lead_status', 'all');
        if ($leadStatus === 'open') {
            app(LeadOpenStatusService::class)->restrictQueryToOpenLeads($query);
        } elseif ($leadStatus === 'closed') {
            $openQuery = clone $query;
            app(LeadOpenStatusService::class)->restrictQueryToOpenLeads($openQuery);
            $openIds = $openQuery->pluck('id');
            if ($openIds->isNotEmpty()) {
                $query->whereNotIn('id', $openIds->all());
            }
        }

        match ((string) $request->input('sort', 'latest')) {
            'oldest' => $query->orderBy('date_time_of_lead_received')->orderBy('id'),
            'name_asc' => $query->orderBy('name')->orderByDesc('id'),
            'name_desc' => $query->orderByDesc('name')->orderByDesc('id'),
            default => $query->orderByDesc('date_time_of_lead_received')->orderByDesc('id'),
        };

        return $query;
    }

    /**
     * @param  list<string>  $keys
     */
    public function present(Builder $query, array $keys): Builder
    {
        $with = ['source', 'adSource', 'createdBy'];
        if ($this->wants($keys, ['tags'])) {
            $with[] = 'customerLeadTags';
        }
        $query->with($with);

        $counts = [];
        if ($this->wants($keys, ['followup_count'])) {
            $counts[] = 'followups';
        }
        if ($this->wants($keys, ['outbound_enquiries'])) {
            $counts[] = 'outboundEnquiries';
        }
        if ($counts !== []) {
            $query->withCount($counts);
        }

        return $query;
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, string>>
     */
    public function valueRows(Collection $leads, array $keys, string $scope): array
    {
        if ($leads->isEmpty() || $keys === []) {
            return [];
        }

        $context = $this->buildContext($leads, $keys, $this->resolveScope($scope));
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
     * @param  array<string, string>  $labels
     */
    public function streamLabeledRows(Builder $query, array $keys, string $scope, int $limit, array $labels): Generator
    {
        $headers = $this->uniqueHeaderLabels($scope, $keys, $labels);
        $offset = 0;
        $yielded = 0;

        while (true) {
            $take = 200;
            if ($limit > 0) {
                $remaining = $limit - $yielded;
                if ($remaining <= 0) {
                    return;
                }
                $take = min($take, $remaining);
            }

            $batch = (clone $query)->offset($offset)->limit($take)->get();
            if ($batch->isEmpty()) {
                return;
            }

            foreach ($this->valueRows($batch, $keys, $scope) as $row) {
                $labeled = [];
                foreach ($keys as $key) {
                    $labeled[$headers[$key]] = $row[$key] ?? '';
                }
                yield $labeled;
                $yielded++;
            }

            if ($batch->count() < $take) {
                return;
            }
            $offset += $batch->count();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sharedQuery(Request $request): array
    {
        $scope = $this->resolveScope($request->input('scope'));
        $shared = ['scope' => $scope];

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $shared['search'] = $search;
        }

        foreach (['source_id', 'ad_source_id', 'handled_by'] as $key) {
            $values = $this->listInput($request, $key);
            if ($values !== []) {
                $shared[$key] = $values;
            }
        }

        $leadStatus = (string) $request->input('lead_status', 'all');
        if (in_array($leadStatus, ['open', 'closed'], true)) {
            $shared['lead_status'] = $leadStatus;
        }

        foreach (['date_from', 'date_to'] as $key) {
            $value = trim((string) $request->input($key, ''));
            if ($value !== '') {
                $shared[$key] = $value;
            }
        }

        if ($this->showsFollowup($scope)) {
            foreach (['followup_from', 'followup_to'] as $key) {
                $value = trim((string) $request->input($key, ''));
                if ($value !== '') {
                    $shared[$key] = $value;
                }
            }
        }

        if ($this->showsTemperature($scope)) {
            $temperatures = array_values(array_intersect($this->listInput($request, 'lead_temperature'), Lead::TEMPERATURES));
            if ($temperatures !== []) {
                $shared['lead_temperature'] = $temperatures;
            }
        }

        $typeKeys = match ($scope) {
            Lead::TYPE_CUSTOMER => ['customer_status_id', 'customer_zone_id', 'customer_category_id', 'customer_sub_category_id'],
            Lead::TYPE_PROVIDER => ['status_id', 'district_id', 'zone_id', 'category_id'],
            Lead::TYPE_FUTURE_CUSTOMER => ['future_customer_reason_id'],
            Lead::TYPE_FUTURE_PROVIDER => ['future_provider_reason_id'],
            Lead::TYPE_INVALID => ['invalid_reason_id'],
            default => [],
        };
        foreach ($typeKeys as $key) {
            $values = $this->listInput($request, $key);
            if ($values !== []) {
                $shared[$key] = $values;
            }
        }

        if ($scope === Lead::TYPE_CUSTOMER) {
            foreach (['estimated_date_from', 'estimated_date_to'] as $key) {
                $value = trim((string) $request->input($key, ''));
                if ($value !== '') {
                    $shared[$key] = $value;
                }
            }
            $booking = (string) $request->input('customer_has_booking', 'all');
            if (in_array($booking, ['yes', 'no'], true)) {
                $shared['customer_has_booking'] = $booking;
            }
        }

        if ($scope === Lead::TYPE_FUTURE_CUSTOMER) {
            $outbound = (string) $request->input('outbound_enquiry_filter', 'all');
            if (in_array($outbound, ['none', 'exact', 'min'], true)) {
                $shared['outbound_enquiry_filter'] = $outbound;
                if (in_array($outbound, ['exact', 'min'], true)) {
                    $shared['outbound_enquiry_count'] = max(0, (int) $request->input('outbound_enquiry_count'));
                }
            }
        }

        $sort = (string) $request->input('sort', 'latest');
        if (in_array($sort, ['oldest', 'name_asc', 'name_desc'], true)) {
            $shared['sort'] = $sort;
        }

        $limit = (int) $request->input('limit');
        if ($limit > 0) {
            $shared['limit'] = $limit;
        }

        return $shared;
    }

    /**
     * @return array<string, string>
     */
    public function customLabels(string $scope, Request $request): array
    {
        $known = $this->flatColumns($scope);
        $input = $request->input('labels', []);
        if (! is_array($input)) {
            return [];
        }

        $custom = [];
        foreach ($input as $key => $value) {
            $key = (string) $key;
            if (! array_key_exists($key, $known) || ! is_scalar($value)) {
                continue;
            }
            $label = $this->cleanHeaderLabel((string) $value);
            if ($label !== '' && $label !== $known[$key]) {
                $custom[$key] = $label;
            }
        }

        return $custom;
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    public function labelsFor(string $scope, Request $request, array $keys): array
    {
        $custom = $this->customLabels($scope, $request);
        $defaults = $this->flatColumns($scope);
        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = $custom[$key] ?? ($defaults[$key] ?? (string) $key);
        }

        return $labels;
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(string $scope): array
    {
        $scope = $this->resolveScope($scope);
        $options = [
            'sources' => Source::active()->orderBy('name')->get(['id', 'name']),
            'adSources' => AdSource::active()->orderBy('name')->get(['id', 'name']),
            'employees' => User::query()
                ->whereIn('user_type', ['super-admin', 'admin-employee'])
                ->ofStatus(1)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'email']),
        ];

        if ($scope === Lead::TYPE_CUSTOMER) {
            $options['customerStatuses'] = CustomerLeadStatus::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $options['zones'] = Zone::ofStatus(1)->orderBy('name')->get(['id', 'name']);
            $options['categories'] = Category::withoutGlobalScopes()->ofType('main')->orderBy('name')->get(['id', 'name']);
            $options['subcategories'] = Category::withoutGlobalScopes()->ofType('sub')->orderBy('name')->get(['id', 'name']);
        }

        if ($scope === Lead::TYPE_PROVIDER) {
            $options['providerStatuses'] = ProviderLeadStatus::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $options['districts'] = District::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $options['zones'] = Zone::ofStatus(1)->orderBy('name')->get(['id', 'name']);
            $options['categories'] = Category::withoutGlobalScopes()->ofType('main')->orderBy('name')->get(['id', 'name']);
        }

        if ($scope === Lead::TYPE_FUTURE_CUSTOMER) {
            $options['futureCustomerReasons'] = LeadFutureCustomerReason::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
        if ($scope === Lead::TYPE_FUTURE_PROVIDER) {
            $options['futureProviderReasons'] = LeadFutureProviderReason::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
        if ($scope === Lead::TYPE_INVALID) {
            $options['invalidReasons'] = LeadInvalidReason::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function leadColumns(string $scope): array
    {
        $columns = [
            'lead_link' => translate('Lead_Details'),
            'id' => translate('ID'),
            'name' => translate('Name'),
            'phone' => translate('Phone'),
            'other_phones' => translate('Other_phones'),
        ];

        if ($scope === 'all') {
            $columns['lead_type'] = translate('Lead_Type');
        }
        if ($scope === 'all' || $this->showsTemperature($scope)) {
            $columns['temperature'] = translate('Lead_Temperature');
        }

        $columns['source'] = translate('Lead_Source');
        $columns['ad_source'] = translate('Ad_Source');
        $columns['received_at'] = translate('Recieved_On');
        $columns['handled_by'] = translate('Handled_By');
        $columns['created_by'] = translate('Created_By');
        $columns['remarks'] = translate('Initial_Remarks');
        $columns['open_status'] = translate('Lead_Status');
        $columns['created_at'] = translate('Created_at');

        return $columns;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function ensureLeadLink(array $keys): array
    {
        $keys = array_values(array_filter($keys, fn ($key) => $key !== 'lead_link'));
        array_unshift($keys, 'lead_link');

        return $keys;
    }

    /**
     * @param  list<string>  $keys
     * @param  list<string>  $wanted
     */
    private function wants(array $keys, array $wanted): bool
    {
        return array_intersect($keys, $wanted) !== [];
    }

    /**
     * @return list<string>
     */
    private function listInput(Request $request, string $key): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            (array) $request->input($key, [])
        ), static fn ($value) => $value !== '')));
    }

    private function applyHandledBy(Builder $query, array $values): void
    {
        if ($values === []) {
            return;
        }

        $unassigned = in_array(Lead::FILTER_UNASSIGNED_VALUE, $values, true);
        $ids = array_values(array_filter($values, fn ($value) => $value !== Lead::FILTER_UNASSIGNED_VALUE));

        $query->where(function ($inner) use ($unassigned, $ids) {
            if ($unassigned) {
                $inner->where(function ($blank) {
                    $blank->whereNull('handled_by')
                        ->orWhere('handled_by', '')
                        ->orWhere('handled_by', Lead::HANDLED_BY_AI);
                });
            }
            if ($ids !== []) {
                $method = $unassigned ? 'orWhereIn' : 'whereIn';
                $inner->{$method}('handled_by', $ids);
            }
        });
    }

    private function applyReceivedRange(Builder $query, Request $request): void
    {
        $from = trim((string) $request->input('date_from', ''));
        $to = trim((string) $request->input('date_to', ''));
        if ($from === '' && $to === '') {
            return;
        }

        try {
            if ($from !== '' && $to !== '') {
                $query->whereBetween('date_time_of_lead_received', [
                    Carbon::parse($from)->startOfDay(),
                    Carbon::parse($to)->endOfDay(),
                ]);
            } elseif ($from !== '') {
                $query->where('date_time_of_lead_received', '>=', Carbon::parse($from)->startOfDay());
            } else {
                $query->where('date_time_of_lead_received', '<=', Carbon::parse($to)->endOfDay());
            }
        } catch (\Throwable) {
            // Ignore a date the browser did not send as a real date.
        }
    }

    private function applyFollowupRange(Builder $query, Request $request): void
    {
        $from = trim((string) $request->input('followup_from', ''));
        $to = trim((string) $request->input('followup_to', ''));
        if ($from === '' && $to === '') {
            return;
        }

        try {
            $query->whereNotNull('next_followup_at');
            if ($from !== '') {
                $query->where('next_followup_at', '>=', Carbon::parse(str_replace('T', ' ', $from)));
            }
            if ($to !== '') {
                $query->where('next_followup_at', '<=', Carbon::parse(str_replace('T', ' ', $to)));
            }
        } catch (\Throwable) {
            // Ignore an unparseable follow-up datetime.
        }
    }

    private function applyTypeFilters(Builder $query, string $scope, Request $request): void
    {
        if ($scope === Lead::TYPE_FUTURE_CUSTOMER) {
            $outbound = (string) $request->input('outbound_enquiry_filter', 'all');
            $count = max(0, (int) $request->input('outbound_enquiry_count'));
            match ($outbound) {
                'none' => $query->whereDoesntHave('outboundEnquiries'),
                'exact' => $query->has('outboundEnquiries', '=', $count),
                'min' => $query->has('outboundEnquiries', '>=', max(1, $count)),
                default => null,
            };
        }

        $rules = [];
        $push = function (array $keys, array $wanted, array $listKeys = []) use (&$rules): void {
            $wanted = array_values(array_unique(array_map('strval', $wanted)));
            if ($wanted === []) {
                return;
            }
            $rules[] = ['keys' => $keys, 'list' => $listKeys, 'wanted' => $wanted];
        };

        if ($scope === Lead::TYPE_CUSTOMER) {
            $push(['customer_lead_status_id'], $this->listInput($request, 'customer_status_id'));
            $push(['zone_id'], $this->listInput($request, 'customer_zone_id'));
            $push(['service_category'], $this->listInput($request, 'customer_category_id'));
            $push(['service_subcategory'], $this->listInput($request, 'customer_sub_category_id'));
        }
        if ($scope === Lead::TYPE_PROVIDER) {
            $push(['provider_lead_status_id'], $this->listInput($request, 'status_id'));
            $push(['district_id'], $this->listInput($request, 'district_id'));
            $push(['zone_id'], $this->listInput($request, 'zone_id'), ['zone_ids']);
            $push(['provider_service_category'], $this->listInput($request, 'category_id'));
        }
        if ($scope === Lead::TYPE_FUTURE_CUSTOMER) {
            $push(['future_customer_reason_id'], $this->listInput($request, 'future_customer_reason_id'));
        }
        if ($scope === Lead::TYPE_FUTURE_PROVIDER) {
            $push(['future_provider_reason_id'], $this->listInput($request, 'future_provider_reason_id'));
        }
        if ($scope === Lead::TYPE_INVALID) {
            $push(['invalid_reason_id'], $this->listInput($request, 'invalid_reason_id'));
        }

        $estimatedFrom = trim((string) $request->input('estimated_date_from', ''));
        $estimatedTo = trim((string) $request->input('estimated_date_to', ''));
        $hasEstimate = $scope === Lead::TYPE_CUSTOMER && $estimatedFrom !== '' && $estimatedTo !== '';
        $booking = $scope === Lead::TYPE_CUSTOMER ? (string) $request->input('customer_has_booking', 'all') : 'all';
        if ($rules === [] && ! $hasEstimate && ! in_array($booking, ['yes', 'no'], true)) {
            return;
        }

        $leadIds = (clone $query)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($leadIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $type = $scope === 'all' ? null : $scope;
        $histories = LeadTypeHistory::query()
            ->whereIn('lead_id', $leadIds)
            ->when($type !== null, fn ($builder) => $builder->where('type', $type))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['lead_id', 'type', 'data']);

        $latest = [];
        foreach ($histories as $history) {
            $leadId = (int) $history->lead_id;
            if (! array_key_exists($leadId, $latest)) {
                $latest[$leadId] = is_array($history->data) ? $history->data : [];
            }
        }

        $bookingLeadIds = [];
        if (in_array($booking, ['yes', 'no'], true)) {
            $bookingLeadIds = Booking::query()
                ->whereIn('lead_id', $leadIds)
                ->pluck('lead_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->all();
        }

        $matched = [];
        foreach ($leadIds as $leadId) {
            $data = $latest[$leadId] ?? [];
            if ($rules !== [] && ! $this->historyMatches(is_array($data) ? $data : [], $rules)) {
                continue;
            }
            if ($hasEstimate && ! $this->estimateInRange($data['estimated_service_at'] ?? null, $estimatedFrom, $estimatedTo)) {
                continue;
            }
            if (in_array($booking, ['yes', 'no'], true)) {
                $hasBooking = (is_array($data) && ! empty($data['booking_id'])) || in_array($leadId, $bookingLeadIds, true);
                if ($booking === 'yes' && ! $hasBooking) {
                    continue;
                }
                if ($booking === 'no' && $hasBooking) {
                    continue;
                }
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
     * @param  list<array{keys: list<string>, list: list<string>, wanted: list<string>}>  $rules
     */
    private function historyMatches(array $data, array $rules): bool
    {
        foreach ($rules as $rule) {
            $hit = false;
            foreach ($rule['keys'] as $key) {
                $value = isset($data[$key]) ? (string) $data[$key] : '';
                if ($value !== '' && in_array($value, $rule['wanted'], true)) {
                    $hit = true;
                    break;
                }
            }
            if (! $hit) {
                foreach ($rule['list'] as $key) {
                    foreach ((array) ($data[$key] ?? []) as $item) {
                        if (in_array((string) $item, $rule['wanted'], true)) {
                            $hit = true;
                            break 2;
                        }
                    }
                }
            }
            if (! $hit) {
                return false;
            }
        }

        return true;
    }

    private function estimateInRange(mixed $value, string $from, string $to): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        try {
            $at = Carbon::parse((string) $value);

            return $at->betweenIncluded(Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay());
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function buildContext(Collection $leads, array $keys, string $scope): array
    {
        $ids = $leads->pluck('id')->map(fn ($id) => (int) $id)->all();
        $types = match ($scope) {
            Lead::TYPE_CUSTOMER => [Lead::TYPE_CUSTOMER],
            Lead::TYPE_PROVIDER => [Lead::TYPE_PROVIDER],
            Lead::TYPE_FUTURE_CUSTOMER => [Lead::TYPE_FUTURE_CUSTOMER],
            Lead::TYPE_FUTURE_PROVIDER => [Lead::TYPE_FUTURE_PROVIDER],
            Lead::TYPE_INVALID => [Lead::TYPE_INVALID],
            Lead::TYPE_UNKNOWN => [],
            default => [
                Lead::TYPE_CUSTOMER,
                Lead::TYPE_PROVIDER,
                Lead::TYPE_FUTURE_CUSTOMER,
                Lead::TYPE_FUTURE_PROVIDER,
                Lead::TYPE_INVALID,
            ],
        };

        $latest = [];
        $buckets = [
            'status' => [],
            'providerStatus' => [],
            'zone' => [],
            'area' => [],
            'category' => [],
            'service' => [],
            'district' => [],
            'cancel' => [],
            'providerCancel' => [],
            'futureReason' => [],
            'futureOption' => [],
            'futureProviderReason' => [],
            'futureProviderOption' => [],
            'invalidReason' => [],
            'invalidOption' => [],
        ];

        if ($types !== []) {
            $histories = LeadTypeHistory::query()
                ->whereIn('lead_id', $ids)
                ->whereIn('type', $types)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['lead_id', 'type', 'data']);

            foreach ($histories as $history) {
                $key = $history->lead_id.'|'.$history->type;
                if (isset($latest[$key])) {
                    continue;
                }
                $data = is_array($history->data) ? $history->data : [];
                $latest[$key] = $data;
                $this->collectId($buckets['status'], $data['customer_lead_status_id'] ?? null);
                $this->collectId($buckets['providerStatus'], $data['provider_lead_status_id'] ?? null);
                $this->collectId($buckets['zone'], $data['zone_id'] ?? null);
                foreach ((array) ($data['zone_ids'] ?? []) as $zoneId) {
                    $this->collectId($buckets['zone'], $zoneId);
                }
                $this->collectId($buckets['area'], $data['area_id'] ?? null);
                $this->collectId($buckets['category'], $data['service_category'] ?? null);
                $this->collectId($buckets['category'], $data['service_subcategory'] ?? null);
                $this->collectId($buckets['category'], $data['provider_service_category'] ?? null);
                $this->collectId($buckets['category'], $data['provider_service_subcategory'] ?? null);
                $this->collectId($buckets['service'], $data['service_name'] ?? null);
                $this->collectId($buckets['district'], $data['district_id'] ?? null);
                $this->collectId($buckets['cancel'], $data['cancellation_reason_id'] ?? null);
                $this->collectId($buckets['providerCancel'], $data['provider_cancellation_reason_id'] ?? null);
                $this->collectId($buckets['futureReason'], $data['future_customer_reason_id'] ?? null);
                $this->collectId($buckets['futureOption'], $data['future_customer_reason_option_id'] ?? null);
                $this->collectId($buckets['futureProviderReason'], $data['future_provider_reason_id'] ?? null);
                $this->collectId($buckets['futureProviderOption'], $data['future_provider_reason_option_id'] ?? null);
                $this->collectId($buckets['invalidReason'], $data['invalid_reason_id'] ?? null);
                $this->collectId($buckets['invalidOption'], $data['invalid_reason_option_id'] ?? null);
            }
        }

        $userIds = [];
        foreach ($leads as $lead) {
            if (Lead::assigneeIsHuman($lead->handled_by)) {
                $userIds[] = (string) $lead->handled_by;
            }
        }

        $bookings = $this->wants($keys, ['booking'])
            ? Booking::query()->whereIn('lead_id', $ids)->orderByDesc('created_at')->get(['id', 'lead_id', 'readable_id'])
            : collect();

        $otherPhones = $this->wants($keys, ['other_phones'])
            ? app(PartyPhoneDirectory::class)->otherPhoneDisplaysForLeadIds($ids)
            : [];

        $checklist = [];
        $checklistTotal = 0;
        if ($this->wants($keys, ['checklist'])) {
            $checklist = LeadProviderChecklist::query()
                ->whereIn('lead_id', $ids)
                ->where('is_done', true)
                ->selectRaw('lead_id, count(*) as cnt')
                ->groupBy('lead_id')
                ->pluck('cnt', 'lead_id')
                ->all();
            $checklistTotal = (int) ProviderChecklistItem::active()->count();
        }

        $panel = [];
        if ($this->wants($keys, ['panel_provider'])) {
            $providerLeads = $leads->filter(fn (Lead $lead) => $lead->lead_type === Lead::TYPE_PROVIDER);
            $panel = app(ProviderLeadPanelMatchService::class)->matchForLeads($providerLeads);
        }

        return [
            'latest' => $latest,
            'open' => app(LeadOpenStatusService::class)->buildLeadStatusMeta($leads),
            'users' => $userIds === []
                ? collect()
                : User::query()->whereIn('id', array_unique($userIds))->get(['id', 'first_name', 'last_name', 'email'])->keyBy(fn ($user) => (string) $user->id),
            'bookingsByLead' => $bookings instanceof Collection
                ? $bookings->groupBy(fn ($booking) => (string) $booking->lead_id)->map(fn ($group) => $group->first())
                : collect(),
            'bookingsById' => $bookings instanceof Collection
                ? $bookings->keyBy(fn ($booking) => (string) $booking->id)
                : collect(),
            'otherPhones' => $otherPhones,
            'checklist' => $checklist,
            'checklistTotal' => $checklistTotal,
            'panel' => $panel,
            'statuses' => $this->keyedModels(CustomerLeadStatus::class, $buckets['status']),
            'providerStatuses' => $this->keyedModels(ProviderLeadStatus::class, $buckets['providerStatus']),
            'zones' => $buckets['zone'] === [] ? collect() : Zone::withoutGlobalScopes()->whereIn('id', array_unique($buckets['zone']))->get()->keyBy(fn ($row) => (string) $row->id),
            'areas' => $this->keyedModels(CustomerLeadArea::class, $buckets['area']),
            'categories' => $buckets['category'] === [] ? collect() : Category::withoutGlobalScopes()->whereIn('id', array_unique($buckets['category']))->get()->keyBy(fn ($row) => (string) $row->id),
            'services' => $buckets['service'] === [] ? collect() : Service::withTrashed()->whereIn('id', array_unique($buckets['service']))->get()->keyBy(fn ($row) => (string) $row->id),
            'districts' => $this->keyedModels(District::class, $buckets['district']),
            'cancelReasons' => $this->keyedModels(LeadCancellationReason::class, $buckets['cancel']),
            'providerCancelReasons' => $this->keyedModels(ProviderCancellationReason::class, $buckets['providerCancel']),
            'futureReasons' => $this->keyedModels(LeadFutureCustomerReason::class, $buckets['futureReason']),
            'futureOptions' => $this->keyedModels(LeadFutureCustomerReasonOption::class, $buckets['futureOption']),
            'futureProviderReasons' => $this->keyedModels(LeadFutureProviderReason::class, $buckets['futureProviderReason']),
            'futureProviderOptions' => $buckets['futureProviderOption'] === []
                ? collect()
                : LeadReasonFollowupOption::query()->where('reason_type', 'future_provider_reason')->whereIn('id', array_unique($buckets['futureProviderOption']))->get()->keyBy(fn ($row) => (string) $row->id),
            'invalidReasons' => $this->keyedModels(LeadInvalidReason::class, $buckets['invalidReason']),
            'invalidOptions' => $buckets['invalidOption'] === []
                ? collect()
                : LeadReasonFollowupOption::query()->where('reason_type', 'invalid_reason')->whereIn('id', array_unique($buckets['invalidOption']))->get()->keyBy(fn ($row) => (string) $row->id),
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
        $futureProvider = $context['latest'][$lead->id.'|'.Lead::TYPE_FUTURE_PROVIDER] ?? [];
        $invalid = $context['latest'][$lead->id.'|'.Lead::TYPE_INVALID] ?? [];

        if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
            $customer = [];
        }
        if ($lead->lead_type !== Lead::TYPE_PROVIDER) {
            $provider = [];
        }
        if ($lead->lead_type !== Lead::TYPE_FUTURE_CUSTOMER) {
            $future = [];
        }
        if ($lead->lead_type !== Lead::TYPE_FUTURE_PROVIDER) {
            $futureProvider = [];
        }
        if ($lead->lead_type !== Lead::TYPE_INVALID) {
            $invalid = [];
        }

        return match ($key) {
            'lead_link' => route('admin.lead.show', $lead->id),
            'id' => (string) $lead->id,
            'name' => trim((string) ($lead->name ?? '')),
            'phone' => (string) ($lead->phone_number ?? ''),
            'other_phones' => implode(', ', $context['otherPhones'][(int) $lead->id] ?? []),
            'lead_type' => Lead::leadTypes()[$lead->lead_type] ?? (string) $lead->lead_type,
            'temperature' => in_array($lead->lead_type, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)
                ? $this->plainTemperature($lead->lead_temperature)
                : '',
            'source' => (string) ($lead->source?->name ?? ''),
            'ad_source' => (string) ($lead->adSource?->name ?? ''),
            'received_at' => $this->dateText($lead->date_time_of_lead_received),
            'handled_by' => $this->handledByLabel($lead->handled_by, $context['users']),
            'created_by' => $this->personName($lead->createdBy),
            'remarks' => trim((string) ($lead->remarks ?? '')),
            'open_status' => (string) ($context['open'][(int) $lead->id]['label'] ?? ''),
            'created_at' => $this->dateText($lead->created_at),
            'next_followup' => $this->dateText($lead->next_followup_at),
            'followup_count' => (string) ($lead->followups_count ?? 0),
            'non_responsive' => $lead->supportsNonResponsiveClose() ? ($lead->isNonResponsive() ? translate('Yes') : translate('No')) : '',
            'non_responsive_remarks' => trim((string) ($lead->non_responsive_remarks ?? '')),
            'customer_status' => $this->modelName($context['statuses'], $customer['customer_lead_status_id'] ?? null),
            'booking' => $this->bookingLabel($customer, $context, $lead),
            'cancellation_reason' => $this->modelName($context['cancelReasons'], $customer['cancellation_reason_id'] ?? null),
            'cancellation_remarks' => trim((string) ($customer['cancellation_remarks'] ?? '')),
            'customer_zone' => $this->modelName($context['zones'], $customer['zone_id'] ?? null),
            'customer_area' => $this->modelName($context['areas'], $customer['area_id'] ?? null),
            'customer_category' => $this->modelName($context['categories'], $customer['service_category'] ?? null),
            'customer_subcategory' => $this->modelName($context['categories'], $customer['service_subcategory'] ?? null),
            'customer_service' => $this->modelName($context['services'], $customer['service_name'] ?? null),
            'customer_variant' => trim((string) ($customer['variant_key'] ?? '')),
            'customer_description' => trim((string) ($customer['service_description'] ?? '')),
            'estimated_service_at' => $this->looseDateText($customer['estimated_service_at'] ?? null),
            'estimated_service_value' => $this->moneyText($customer['estimated_service_value'] ?? null),
            'tags' => $lead->lead_type === Lead::TYPE_CUSTOMER
                ? $lead->customerLeadTags->pluck('name')->filter()->implode(', ')
                : '',
            'provider_status' => $this->modelName($context['providerStatuses'], $provider['provider_lead_status_id'] ?? null),
            'panel_provider' => (string) ($context['panel'][(int) $lead->id]['name'] ?? ''),
            'provider_cancellation_reason' => $this->modelName($context['providerCancelReasons'], $provider['provider_cancellation_reason_id'] ?? null),
            'provider_cancellation_remarks' => trim((string) ($provider['provider_cancellation_remarks'] ?? '')),
            'provider_district' => $this->modelName($context['districts'], $provider['district_id'] ?? null),
            'provider_zones' => $this->zoneList($provider, $context['zones']),
            'provider_area' => $this->modelName($context['areas'], $provider['area_id'] ?? null),
            'provider_category' => $this->modelName($context['categories'], $provider['provider_service_category'] ?? null),
            'provider_subcategory' => $this->modelName($context['categories'], $provider['provider_service_subcategory'] ?? null),
            'provider_service_details' => trim((string) ($provider['provider_service_details'] ?? '')),
            'provider_service_areas' => trim((string) ($provider['service_areas'] ?? '')),
            'provider_address' => trim((string) ($provider['full_address'] ?? '')),
            'checklist' => $lead->lead_type === Lead::TYPE_PROVIDER
                ? ((int) ($context['checklist'][(int) $lead->id] ?? 0)).'/'.((int) $context['checklistTotal'])
                : '',
            'future_customer_reason' => $this->modelName($context['futureReasons'], $future['future_customer_reason_id'] ?? null),
            'future_customer_option' => $this->modelName($context['futureOptions'], $future['future_customer_reason_option_id'] ?? null),
            'future_customer_remarks' => trim((string) ($future['future_customer_remarks'] ?? '')),
            'outbound_enquiries' => $lead->lead_type === Lead::TYPE_FUTURE_CUSTOMER
                ? (string) ($lead->outbound_enquiries_count ?? 0)
                : '',
            'future_provider_reason' => $this->modelName($context['futureProviderReasons'], $futureProvider['future_provider_reason_id'] ?? null),
            'future_provider_option' => $this->modelName($context['futureProviderOptions'], $futureProvider['future_provider_reason_option_id'] ?? null),
            'future_provider_remarks' => trim((string) ($futureProvider['future_provider_remarks'] ?? '')),
            'future_provider_area' => $this->modelName($context['areas'], $futureProvider['area_id'] ?? null),
            'invalid_reason' => $this->modelName($context['invalidReasons'], $invalid['invalid_reason_id'] ?? null),
            'invalid_option' => $this->modelName($context['invalidOptions'], $invalid['invalid_reason_option_id'] ?? null),
            'invalid_remarks' => trim((string) ($invalid['invalid_remarks'] ?? '')),
            'invalid_area' => $this->modelName($context['areas'], $invalid['area_id'] ?? null),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $context
     */
    private function bookingLabel(array $customer, array $context, Lead $lead): string
    {
        $booking = null;
        if (! empty($customer['booking_id'])) {
            $booking = $context['bookingsById']->get((string) $customer['booking_id']);
        }
        if (! $booking) {
            $booking = $context['bookingsByLead']->get((string) $lead->id);
        }
        if (! $booking) {
            return '';
        }

        $readable = trim((string) ($booking->readable_id ?? ''));

        return $readable !== '' ? $readable : (string) $booking->id;
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
            return translate('Unassigned');
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

    private function dateText(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);

            return $date->format('d M Y, h:i a');
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
            return Carbon::parse($text)->format('d M Y, h:i a');
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

    /**
     * @param  list<string>  $keys
     * @param  array<string, string>  $labels
     * @return array<string, string>
     */
    private function uniqueHeaderLabels(string $scope, array $keys, array $labels): array
    {
        $defaults = $this->flatColumns($scope);
        $used = [];
        $unique = [];
        foreach ($keys as $key) {
            $label = $this->cleanHeaderLabel((string) ($labels[$key] ?? $defaults[$key] ?? $key));
            if ($label === '') {
                $label = (string) ($defaults[$key] ?? $key);
            }
            $candidate = $label;
            $n = 2;
            while (isset($used[mb_strtolower($candidate)])) {
                $candidate = $label.' ('.$n.')';
                $n++;
            }
            $used[mb_strtolower($candidate)] = true;
            $unique[$key] = $candidate;
        }

        return $unique;
    }

    private function cleanHeaderLabel(string $value): string
    {
        $value = Str::of(strip_tags($value))->squish()->trim()->toString();
        $value = ltrim($value, "=+-@");
        if (mb_strlen($value) > 80) {
            $value = rtrim(mb_substr($value, 0, 80));
        }

        return $value;
    }
}
