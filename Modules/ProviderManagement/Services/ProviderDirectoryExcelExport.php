<?php

namespace Modules\ProviderManagement\Services;

use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderExpertise;
use Modules\ProviderManagement\Entities\ProviderQuestionnaire;
use Modules\ProviderManagement\Support\ProviderOnboardingQuestionnaire;

class ProviderDirectoryExcelExport
{
    public const PREVIEW_LIMIT = 20;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $questions = null;

    private bool $onboarding = false;

    public function forOnboarding(): self
    {
        $this->onboarding = true;

        return $this;
    }

    /**
     * @return list<array{key: string, label: string, columns: array<string, string>}>
     */
    public function groups(): array
    {
        if ($this->onboarding) {
            return $this->onboardingGroups();
        }

        $groups = [
            [
                'key' => 'profile',
                'label' => translate('Provider_Info'),
                'columns' => [
                    'profile_link' => translate('Profile_link'),
                    'company_name' => translate('company_name'),
                    'provider_type' => translate('Provider_Type'),
                    'contact_person_name' => translate('contact_person_name'),
                    'phone' => translate('phone'),
                    'company_phone' => translate('company_phone'),
                    'contact_person_phone' => translate('contact_person_phone'),
                    'company_email' => translate('company_email'),
                    'contact_person_email' => translate('contact_person_email'),
                ],
            ],
            [
                'key' => 'address',
                'label' => translate('Address_Information'),
                'columns' => [
                    'address' => translate('company_address'),
                    'street' => translate('street'),
                    'city' => translate('city'),
                    'pincode' => translate('pincode'),
                    'zones' => translate('Zones'),
                    'area' => translate('Area'),
                    'latitude' => translate('latitude'),
                    'longitude' => translate('longitude'),
                ],
            ],
            [
                'key' => 'photos',
                'label' => translate('Photos'),
                'columns' => [
                    'logo' => translate('logo'),
                    'cover_image' => translate('cover_image'),
                    'contact_person_photo' => translate('Contact_Person_Photo'),
                ],
            ],
            [
                'key' => 'contact_identity',
                'label' => translate('Contact_Person_Identity'),
                'columns' => [
                    'identity_type' => translate('Identity_Type'),
                    'identity_number' => translate('Identity_Number'),
                    'identity_images' => translate('Identity_images'),
                ],
            ],
            [
                'key' => 'company_identity',
                'label' => translate('Company_Information'),
                'columns' => [
                    'company_identity_type' => translate('Company_identity_type'),
                    'company_identity_number' => translate('Company_identity_number'),
                    'company_identity_images' => translate('Company_identity_images'),
                ],
            ],
            [
                'key' => 'services',
                'label' => translate('Subscribed_Services'),
                'columns' => [
                    'categories' => translate('Category'),
                    'subscribed_services' => translate('Subscribed_service_detail'),
                    'expertise' => translate('Expertise'),
                    'bookings' => translate('Total_Bookings'),
                    'commission' => translate('Commission'),
                ],
            ],
            [
                'key' => 'questionnaire',
                'label' => translate('Questionnaire'),
                'columns' => $this->questionnaireColumns(),
            ],
            [
                'key' => 'documents',
                'label' => translate('Additional_Documents'),
                'columns' => [
                    'additional_documents' => translate('Additional_Documents'),
                ],
            ],
            [
                'key' => 'account',
                'label' => translate('Account'),
                'columns' => [
                    'rating' => translate('Rating'),
                    'rating_count' => translate('ratings'),
                    'app' => translate('App'),
                    'joined' => translate('Joined'),
                    'performance' => translate('Performance_Status'),
                    'service_availability' => translate('Service_Availability'),
                    'account_status' => translate('status'),
                    'app_availability' => translate('App_Availability'),
                ],
            ],
        ];

        return $groups;
    }

    /**
     * Fields the provider fills on the onboarding form.
     *
     * @return list<array{key: string, label: string, columns: array<string, string>}>
     */
    private function onboardingGroups(): array
    {
        return [
            [
                'key' => 'profile',
                'label' => translate('Provider_Info'),
                'columns' => [
                    'profile_link' => translate('Profile_link'),
                    'company_name' => translate('company_name'),
                    'provider_type' => translate('Provider_Type'),
                    'contact_person_name' => translate('contact_person_name'),
                    'company_phone' => translate('company_phone'),
                    'contact_person_phone' => translate('contact_person_phone'),
                    'company_email' => translate('company_email'),
                    'contact_person_email' => translate('contact_person_email'),
                ],
            ],
            [
                'key' => 'address',
                'label' => translate('Address_Information'),
                'columns' => [
                    'address' => translate('company_address'),
                    'street' => translate('street'),
                    'city' => translate('city'),
                    'pincode' => translate('pincode'),
                    'zones' => translate('Zones'),
                    'area' => translate('Area'),
                    'latitude' => translate('latitude'),
                    'longitude' => translate('longitude'),
                ],
            ],
            [
                'key' => 'photos',
                'label' => translate('Photos'),
                'columns' => [
                    'logo' => translate('logo'),
                    'contact_person_photo' => translate('Contact_Person_Photo'),
                ],
            ],
            [
                'key' => 'contact_identity',
                'label' => translate('Contact_Person_Identity'),
                'columns' => [
                    'identity_type' => translate('Identity_Type'),
                    'identity_number' => translate('Identity_Number'),
                    'identity_images' => translate('Identity_images'),
                ],
            ],
            [
                'key' => 'company_identity',
                'label' => translate('Company_Information'),
                'columns' => [
                    'company_identity_type' => translate('Company_identity_type'),
                    'company_identity_number' => translate('Company_identity_number'),
                    'company_identity_images' => translate('Company_identity_images'),
                ],
            ],
            [
                'key' => 'services',
                'label' => translate('Services'),
                'columns' => [
                    'categories' => translate('Category'),
                    'subscribed_services' => translate('Subscribed_service_detail'),
                ],
            ],
            [
                'key' => 'documents',
                'label' => translate('Additional_Documents'),
                'columns' => [
                    'additional_documents' => translate('Additional_Documents'),
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function flatColumns(): array
    {
        $columns = [];
        foreach ($this->groups() as $group) {
            foreach ($group['columns'] as $key => $label) {
                $columns[$key] = $label;
            }
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    public function allKeys(): array
    {
        return array_keys($this->flatColumns());
    }

    /**
     * Columns whose preview cells are pictures, not text links.
     *
     * @return list<string>
     */
    public function imageKeys(): array
    {
        return [
            'logo',
            'cover_image',
            'contact_person_photo',
            'identity_images',
            'company_identity_images',
        ];
    }

    /**
     * Columns that match the provider list.
     *
     * @return list<string>
     */
    public function defaultKeys(): array
    {
        if ($this->onboarding) {
            return [
                'profile_link',
                'company_name',
                'provider_type',
                'contact_person_name',
                'contact_person_phone',
                'zones',
                'address',
                'subscribed_services',
            ];
        }

        return [
            'profile_link',
            'company_name',
            'categories',
            'zones',
            'rating',
            'phone',
            'bookings',
            'app',
            'joined',
            'performance',
            'service_availability',
            'account_status',
            'app_availability',
        ];
    }

    /**
     * @return list<string>
     */
    public function resolveKeys(Request $request): array
    {
        $known = $this->allKeys();
        if (! $request->boolean('column_selection')) {
            $keys = array_values(array_intersect($this->defaultKeys(), $known));
        } else {
            $requested = array_values(array_filter(array_map('strval', (array) $request->input('columns', []))));
            $keys = array_values(array_intersect($known, $requested));
        }

        return $this->ensureProfileLink($keys);
    }

    /**
     * The admin profile URL is always the first column.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function ensureProfileLink(array $keys): array
    {
        $keys = array_values(array_filter($keys, fn ($key) => $key !== 'profile_link'));
        array_unshift($keys, 'profile_link');

        return $keys;
    }

    /**
     * @param  list<string>  $keys
     * @return array<int|string, mixed>
     */
    public function eagerWith(array $keys = []): array
    {
        $needsAppCounts = $keys === [] || $this->wants($keys, ['app']);
        $needsOwner = $keys === [] || $this->wants($keys, [
            'app',
            'account_status',
            'identity_type',
            'identity_number',
            'identity_images',
            'phone',
            'company_phone',
            'contact_person_phone',
        ]);

        $with = [
            'zone:id,name',
            'zones' => function ($zoneQuery) {
                $zoneQuery->select('zones.id', 'zones.name')->orderBy('zones.name');
            },
        ];

        if ($needsOwner) {
            $with['owner'] = function ($ownerQuery) use ($needsAppCounts) {
                if (! $needsAppCounts) {
                    return;
                }
                $ownerQuery->withCount([
                    'fcmDevices as fcm_devices_count',
                    'tokens as app_login_sessions_count',
                    'tokens as active_app_sessions_count' => function ($tokenQuery) {
                        $tokenQuery->where('revoked', false)
                            ->where(function ($inner) {
                                $inner->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            });
                    },
                ]);
            };
        }

        if ($keys === [] || $this->wants($keys, ['categories', 'subscribed_services'])) {
            $with['subscribed_services.category'] = function ($query) {
                $query->select('id', 'name');
            };
        }
        if ($this->wants($keys, ['subscribed_services'])) {
            $with['subscribed_services.sub_category'] = function ($query) {
                $query->select('id', 'name');
            };
        }
        if ($this->wants($keys, ['area'])) {
            $with[] = 'areas';
        }
        if ($this->wants($keys, ['logo', 'cover_image'])) {
            $with[] = 'storage';
        }
        if ($this->wants($keys, ['denied_by'])) {
            $with[] = 'deniedBy';
        }
        if ($this->wants($keys, ['accepted_by'])) {
            $with[] = 'approvedBy';
        }

        return $with;
    }

    /**
     * @return array<string, mixed>
     */
    public function sharedQuery(Request $request): array
    {
        $shared = [];
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $shared['search'] = $search;
        }

        $status = (string) $request->input('status', 'all');
        if ($status !== '' && $status !== 'all') {
            $shared['status'] = $status;
        }

        foreach ([
            'app_filter' => 'all',
            'rating_filter' => 'all',
            'performance_filter' => 'all',
            'provider_type' => 'all',
            'service_availability' => 'all',
            'app_availability' => 'all',
            'photo' => 'all',
            'identity_type' => 'all',
            'commission' => 'all',
            'suspended' => 'all',
            'has_expertise' => 'all',
            'has_questionnaire' => 'all',
        ] as $key => $default) {
            $value = (string) $request->input($key, $default);
            if ($value !== '' && $value !== $default) {
                $shared[$key] = $value;
            }
        }

        foreach (['category_id', 'zone_id', 'from', 'to', 'city', 'pincode'] as $key) {
            $value = trim((string) $request->input($key, ''));
            if ($value !== '') {
                $shared[$key] = $value;
            }
        }

        $minBookings = (int) $request->input('min_bookings');
        if ($minBookings > 0) {
            $shared['min_bookings'] = $minBookings;
        }

        $sort = (string) $request->input('sort', 'latest');
        if ($sort !== '' && $sort !== 'latest') {
            $shared['sort'] = $sort;
        }

        $limit = (int) $request->input('limit');
        if ($limit > 0) {
            $shared['limit'] = $limit;
        }

        return $shared;
    }

    /**
     * @param  Collection<int, Provider>  $providers
     * @param  list<string>  $keys
     * @return list<array<string, string>>
     */
    public function valueRows(Collection $providers, array $keys): array
    {
        if ($providers->isEmpty() || $keys === []) {
            return [];
        }

        $context = $this->contextFor($providers, $keys);
        $rows = [];
        foreach ($providers as $provider) {
            $row = [];
            foreach ($keys as $key) {
                $row[$key] = $this->cell($provider, $key, $context);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Custom column titles from the export wizard. Empty and unchanged names are dropped.
     *
     * @return array<string, string>
     */
    public function customLabels(Request $request): array
    {
        $known = $this->flatColumns();
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
     * Header text for the selected columns, with custom titles applied.
     *
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    public function labelsFor(Request $request, array $keys): array
    {
        $custom = $this->customLabels($request);
        $defaults = $this->flatColumns();
        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = $custom[$key] ?? ($defaults[$key] ?? (string) $key);
        }

        return $labels;
    }

    /**
     * @param  Collection<int, Provider>  $providers
     * @param  list<string>  $keys
     * @param  array<string, string>|null  $labels
     * @return list<array<string, string>>
     */
    public function labeledRows(Collection $providers, array $keys, ?array $labels = null): array
    {
        $headers = $this->uniqueHeaderLabels($keys, $labels);
        $rows = [];
        foreach ($this->valueRows($providers, $keys) as $row) {
            $labeled = [];
            foreach ($keys as $key) {
                $labeled[$headers[$key]] = $row[$key] ?? '';
            }
            $rows[] = $labeled;
        }

        return $rows;
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, string>|null  $labels
     */
    /**
     * @param  list<string>  $keys
     * @param  array<string, string>|null  $labels
     * @return array<string, string>
     */
    public function headerLabels(array $keys, ?array $labels = null): array
    {
        return $this->uniqueHeaderLabels($keys, $labels);
    }

    /**
     * @param  list<string>  $keys
     * @return Generator<int, array<string, string>>
     */
    public function streamValueRows(Builder $query, array $keys, int $limit = 0): Generator
    {
        $page = 1;
        $size = 200;
        $yielded = 0;
        $cap = $limit > 0 ? $limit : null;

        while (true) {
            $take = $cap === null ? $size : min($size, $cap - $yielded);
            if ($take <= 0) {
                return;
            }

            $batch = (clone $query)->forPage($page, $take)->get();
            if ($batch->isEmpty()) {
                return;
            }

            foreach ($this->valueRows($batch, $keys) as $row) {
                yield $row;
                $yielded++;
            }

            if ($batch->count() < $take) {
                return;
            }

            $page++;
        }
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, string>|null  $labels
     */
    public function streamLabeledRows(Builder $query, array $keys, int $limit = 0, ?array $labels = null): Generator
    {
        $headers = $this->uniqueHeaderLabels($keys, $labels);
        foreach ($this->streamValueRows($query, $keys, $limit) as $row) {
            $labeled = [];
            foreach ($keys as $key) {
                $labeled[$headers[$key]] = $row[$key] ?? '';
            }
            yield $labeled;
        }
    }

    /**
     * Spreadsheet headers must be unique. A repeated title gets a numeric suffix.
     *
     * @param  list<string>  $keys
     * @param  array<string, string>|null  $labels
     * @return array<string, string>
     */
    private function uniqueHeaderLabels(array $keys, ?array $labels = null): array
    {
        $defaults = $this->flatColumns();
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

    /**
     * @return array<string, string>
     */
    private function questionnaireColumns(): array
    {
        $labels = [
            'expertise' => translate('Export_q_expertise'),
            'main_working_area' => translate('Export_q_main_working_area'),
            'other_working_areas' => translate('Export_q_other_working_areas'),
            'days_available' => translate('Export_q_days_available'),
            'max_travel' => translate('Export_q_max_travel'),
            'working_hours' => translate('Export_q_working_hours'),
            'emergency_off_day' => translate('Export_q_emergency_off_day'),
            'years_experience' => translate('Export_q_years_experience'),
            'job_types' => translate('Export_q_job_types'),
            'travel_mode' => translate('Export_q_travel_mode'),
            'current_earnings' => translate('Export_q_current_earnings'),
            'min_job_value' => translate('Export_q_min_job_value'),
            'problems' => translate('Export_q_problems'),
            'expectations' => translate('Export_q_expectations'),
        ];

        $columns = [];
        foreach ($this->questions() as $id => $question) {
            $columns['q_'.$id] = $labels[$id] ?? (string) ($question['label'] ?? $id);
        }

        return $columns;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function questions(): array
    {
        if ($this->questions === null) {
            $this->questions = [];
            foreach (ProviderOnboardingQuestionnaire::allQuestions() as $question) {
                $id = (string) ($question['id'] ?? '');
                if ($id !== '') {
                    $this->questions[$id] = $question;
                }
            }
        }

        return $this->questions;
    }

    /**
     * @param  list<string>  $keys
     * @param  list<string>  $needles
     */
    private function wants(array $keys, array $needles): bool
    {
        return array_intersect($keys, $needles) !== [];
    }

    /**
     * @param  Collection<int, Provider>  $providers
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function contextFor(Collection $providers, array $keys): array
    {
        $ids = $providers->pluck('id')->filter()->values();
        $context = [
            'questionnaires' => collect(),
            'expertise' => collect(),
            'documents' => collect(),
            'document_files' => collect(),
        ];
        if ($ids->isEmpty()) {
            return $context;
        }

        if (array_filter($keys, fn ($key) => str_starts_with((string) $key, 'q_')) !== []) {
            $context['questionnaires'] = ProviderQuestionnaire::query()
                ->whereIn('provider_id', $ids)
                ->get()
                ->keyBy('provider_id');
        }

        if (in_array('expertise', $keys, true)) {
            $context['expertise'] = ProviderExpertise::query()
                ->with(['category:id,name', 'sub_category:id,name', 'service:id,name'])
                ->whereIn('provider_id', $ids)
                ->get()
                ->groupBy('provider_id');
        }

        if (in_array('additional_documents', $keys, true)) {
            $documents = DB::table('providers_additional_documents')
                ->whereIn('provider_id', $ids)
                ->orderBy('document_name')
                ->get();
            $context['documents'] = $documents->groupBy('provider_id');
            $context['document_files'] = $documents->isEmpty()
                ? collect()
                : DB::table('providers_additional_document_files')
                    ->whereIn('document_id', $documents->pluck('id'))
                    ->get()
                    ->groupBy('document_id');
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function cell(Provider $provider, string $key, array $context): string
    {
        if (str_starts_with($key, 'q_')) {
            return $this->questionnaireAnswer($provider, substr($key, 2), $context);
        }

        return match ($key) {
            'profile_link' => $this->onboarding
                ? route('admin.provider.onboarding_details', [$provider->id])
                : route('admin.provider.details', [$provider->id, 'web_page' => 'overview']),
            'request_status' => $this->requestStatusLabel($provider),
            'filled_on' => $provider->created_at ? $provider->created_at->format('d M Y, h:i A') : '',
            'denied_by' => $this->personName($provider->deniedBy),
            'denial_reason' => trim((string) $provider->denial_reason),
            'accepted_on' => $provider->approved_at ? $provider->approved_at->format('d M Y, h:i A') : '',
            'accepted_by' => $this->personName($provider->approvedBy),
            'company_name' => trim((string) $provider->company_name),
            'provider_type' => $this->providerType($provider),
            'contact_person_name' => trim((string) $provider->contact_person_name),
            'phone' => $this->sensitive((string) ($provider->company_phone ?: $provider->contact_person_phone)),
            'company_phone' => $this->sensitive((string) $provider->company_phone),
            'contact_person_phone' => $this->sensitive((string) $provider->contact_person_phone),
            'company_email' => $this->sensitive((string) $provider->company_email),
            'contact_person_email' => $this->sensitive((string) $provider->contact_person_email),
            'address' => trim((string) $provider->company_address),
            'street' => trim((string) $provider->street),
            'city' => trim((string) $provider->city),
            'pincode' => trim((string) $provider->pincode),
            'zones' => $this->zoneNames($provider),
            'area' => $this->areaNames($provider),
            'latitude' => trim((string) data_get($provider->coordinates, 'latitude', '')),
            'longitude' => trim((string) data_get($provider->coordinates, 'longitude', '')),
            'logo' => $this->photoUrl($provider->logo, MediaStoragePath::legacyPrefixForProviderLogo(), $provider->storage?->storage_type),
            'cover_image' => $this->photoUrl($provider->cover_image, MediaStoragePath::legacyPrefixForProviderCover(), $provider->storage?->storage_type),
            'contact_person_photo' => $this->photoUrl($provider->contact_person_photo, 'provider/contact_person_photo/', null),
            'identity_type' => $this->identityLabel($provider->owner?->identification_type),
            'identity_number' => $this->sensitive((string) ($provider->owner?->identification_number ?? '')),
            'identity_images' => $this->joinLines($this->withoutPlaceholders((array) ($provider->owner?->identification_image_full_path ?? []))),
            'company_identity_type' => $this->identityLabel($provider->company_identity_type),
            'company_identity_number' => $this->sensitive((string) $provider->company_identity_number),
            'company_identity_images' => $this->joinLines((array) ($provider->company_identity_images_full_path ?? [])),
            'categories' => $this->categoryNames($provider),
            'subscribed_services' => $this->subscribedServices($provider),
            'expertise' => $this->expertiseSummary($provider, $context),
            'additional_documents' => $this->additionalDocuments($provider, $context),
            'rating' => number_format((float) ($provider->avg_rating ?? 0), 1, '.', ''),
            'rating_count' => (string) (int) ($provider->rating_count ?? 0),
            'bookings' => (string) (int) ($provider->bookings_count ?? 0),
            'app' => $this->appLabel($provider),
            'joined' => $provider->created_at ? $provider->created_at->format('Y-m-d') : '',
            'performance' => (string) (ProviderManualPerformanceEnforcement::providerListPerformance($provider)['label'] ?? ''),
            'service_availability' => $this->onOff((bool) $provider->service_availability),
            'account_status' => $this->onOff((bool) ($provider->owner?->is_active)),
            'app_availability' => $this->onOff((bool) $provider->app_availability),
            'commission' => (int) ($provider->commission_status ?? 0) === 1
                ? rtrim(rtrim(number_format((float) $provider->commission_percentage, 2, '.', ''), '0'), '.')
                : '',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function questionnaireAnswer(Provider $provider, string $questionId, array $context): string
    {
        $question = $this->questions()[$questionId] ?? null;
        if (! is_array($question)) {
            return '';
        }

        $row = $context['questionnaires']->get($provider->id);
        $answers = is_array($row?->answers) ? $row->answers : [];

        return (string) (ProviderOnboardingQuestionnaire::formatAnswer($question, $answers) ?? '');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function expertiseSummary(Provider $provider, array $context): string
    {
        $rows = $context['expertise']->get($provider->id, collect());
        if (! $rows instanceof Collection || $rows->isEmpty()) {
            return '';
        }

        $lines = [];
        foreach ($rows->groupBy(fn ($row) => (string) $row->sub_category_id) as $items) {
            $first = $items->first();
            $category = trim((string) ($first->category->name ?? ''));
            $subcategory = trim((string) ($first->sub_category->name ?? ''));
            $heading = $category !== '' && $subcategory !== ''
                ? $category.' › '.$subcategory
                : ($subcategory !== '' ? $subcategory : $category);
            if ($heading === '') {
                continue;
            }

            if ($items->contains(fn ($item) => $item->scope === 'sub_category')) {
                $lines[] = $heading.': '.translate('All');
                continue;
            }

            $services = $items
                ->map(fn ($item) => trim((string) ($item->service->name ?? '')))
                ->filter()
                ->unique()
                ->values();
            if ($services->isNotEmpty()) {
                $lines[] = $heading.': '.$services->implode(', ');
            }
        }

        return implode("\n", $lines);
    }

    private function subscribedServices(Provider $provider): string
    {
        if (! $provider->relationLoaded('subscribed_services')) {
            return '';
        }

        $lines = [];
        $grouped = $provider->subscribed_services->groupBy(fn ($row) => (string) ($row->category->name ?? ''));
        foreach ($grouped as $category => $rows) {
            $subs = $rows
                ->map(fn ($row) => trim((string) ($row->sub_category->name ?? '')))
                ->filter()
                ->unique()
                ->values();
            $category = trim((string) $category);
            if ($category === '' && $subs->isEmpty()) {
                continue;
            }
            $lines[] = $subs->isEmpty() ? $category : $category.': '.$subs->implode(', ');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function additionalDocuments(Provider $provider, array $context): string
    {
        $documents = $context['documents']->get($provider->id, collect());
        if (! $documents instanceof Collection || $documents->isEmpty()) {
            return '';
        }

        $blocks = [];
        foreach ($documents as $document) {
            $name = trim((string) ($document->document_name ?? ''));
            $description = trim((string) ($document->document_description ?? ''));
            $heading = $name !== '' ? $name : translate('Additional_Documents');
            if ($description !== '') {
                $heading .= ' — '.$description;
            }
            $links = [];
            foreach ($context['document_files']->get($document->id, collect()) as $file) {
                $url = $this->documentFileUrl($file, (string) $document->id);
                if ($url !== '') {
                    $links[] = $url;
                }
            }
            $blocks[] = $links === [] ? $heading : $heading."\n".implode("\n", $links);
        }

        return implode("\n\n", $blocks);
    }

    private function documentFileUrl(object $file, string $documentId): string
    {
        $filePath = trim((string) ($file->file_path ?? ''));
        if ($filePath === '') {
            return '';
        }
        if (Str::startsWith($filePath, ['http://', 'https://'])) {
            return $filePath;
        }

        $disk = (string) ($file->storage ?? 'public');
        $normalized = str_contains($filePath, '/')
            ? $filePath
            : 'provider/additional-documents/'.$documentId.'/'.$filePath;

        if ($disk === 's3') {
            try {
                return Storage::disk('s3')->temporaryUrl(
                    StoragePathPrefix::apply(StoragePathPrefix::strip($normalized)),
                    now()->addMinutes(60)
                );
            } catch (\Throwable) {
                // Fall through to the shared media resolver.
            }
        }

        $url = resolve_media_storage_url($normalized, '', $disk, null, false);

        return is_string($url) ? $url : '';
    }

    private function requestStatusLabel(Provider $provider): string
    {
        return match ((int) $provider->is_approved) {
            0 => translate('Denied'),
            1 => translate('Accepted'),
            2 => translate('Onboarding_Request'),
            default => '',
        };
    }

    private function personName(?object $user): string
    {
        if ($user === null) {
            return '';
        }

        return trim(trim((string) ($user->first_name ?? '')).' '.trim((string) ($user->last_name ?? '')));
    }

    private function providerType(Provider $provider): string
    {
        return match (strtolower((string) ($provider->provider_type ?? 'individual'))) {
            'company' => translate('Company'),
            default => translate('Individual'),
        };
    }

    private function identityLabel(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return ucfirst(str_replace('_', ' ', $value));
    }

    private function photoUrl(mixed $filename, string $prefix, ?string $disk): string
    {
        if (! is_string($filename) || strlen($filename) < 2 || $filename === 'def.png') {
            return '';
        }

        $url = resolve_media_storage_url(
            resolve_stored_media_key($filename, $prefix),
            '',
            $disk,
            null,
            false
        );

        return is_string($url) ? $url : '';
    }

    /**
     * @param  list<string>  $urls
     * @return list<string>
     */
    private function withoutPlaceholders(array $urls): array
    {
        $placeholders = [
            asset('assets/admin-module/img/media/provider-id.png'),
            asset('assets/admin-module/img/placeholder.png'),
            asset('assets/provider-module/img/user2x.png'),
        ];

        return array_values(array_filter($urls, function ($url) use ($placeholders) {
            return is_string($url) && $url !== '' && ! in_array($url, $placeholders, true);
        }));
    }

    /**
     * @param  list<string>  $lines
     */
    private function joinLines(array $lines): string
    {
        return implode("\n", array_values(array_filter(array_map(
            fn ($line) => trim((string) $line),
            $lines
        ), fn ($line) => $line !== '')));
    }

    private function categoryNames(Provider $provider): string
    {
        if (! $provider->relationLoaded('subscribed_services')) {
            return '';
        }

        return $provider->subscribed_services
            ->pluck('category.name')
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');
    }

    private function areaNames(Provider $provider): string
    {
        if (! $provider->relationLoaded('areas')) {
            return '';
        }

        return $provider->areas->pluck('name')->filter()->unique()->values()->implode(', ');
    }

    private function zoneNames(Provider $provider): string
    {
        $names = $provider->relationLoaded('zones')
            ? $provider->zones->pluck('name')->filter()->unique()->values()
            : collect();

        if ($names->isEmpty() && $provider->zone?->name) {
            $names = collect([$provider->zone->name]);
        }

        return $names->implode(', ');
    }

    private function appLabel(Provider $provider): string
    {
        $owner = $provider->owner;
        $hasAppDevice = $owner && (
            ((int) ($owner->fcm_devices_count ?? 0)) > 0
            || (function_exists('is_valid_fcm_token') && is_valid_fcm_token($owner->fcm_token ?? null))
        );
        $hasActiveSession = $owner && ((int) ($owner->active_app_sessions_count ?? 0)) > 0;
        $hasLoggedInBefore = $hasAppDevice || ($owner && ((int) ($owner->app_login_sessions_count ?? 0)) > 0);

        if ($hasActiveSession) {
            return translate('customer_app_status_active');
        }
        if ($hasLoggedInBefore) {
            return translate('customer_app_status_registered');
        }

        return translate('customer_app_status_not_in_app');
    }

    private function onOff(bool $on): string
    {
        return $on ? translate('active') : translate('inactive');
    }

    private function sensitive(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (env('APP_ENV') === 'demo') {
            return translate('protected');
        }

        return $value;
    }
}
