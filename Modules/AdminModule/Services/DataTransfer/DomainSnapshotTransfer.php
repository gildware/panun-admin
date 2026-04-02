<?php

namespace Modules\AdminModule\Services\DataTransfer;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\DataSetting;

class DomainSnapshotTransfer
{
    public const DOMAIN_CUSTOMERS = 'customers';

    public const DOMAIN_PROVIDERS = 'providers';

    public const DOMAIN_LEADS = 'leads';

    public const DOMAIN_BOOKINGS = 'bookings';

    public const DOMAIN_CONFIGURATION = 'configuration';

    /** @var array<string, string> */
    private const PAYLOAD_TYPES = [
        self::DOMAIN_CUSTOMERS => 'customers_v1',
        self::DOMAIN_PROVIDERS => 'providers_v1',
        self::DOMAIN_LEADS => 'leads_v1',
        self::DOMAIN_BOOKINGS => 'bookings_v1',
        self::DOMAIN_CONFIGURATION => 'configuration_v1',
    ];

    /** @var array<string, list<string>> */
    private const IMPORT_TABLE_ORDER = [
        self::DOMAIN_CUSTOMERS => [
            'users',
            'accounts',
            'user_addresses',
            'user_zones',
            'user_verifications',
            'loyalty_point_transactions',
            'carts',
            'added_to_carts',
            'favorite_services',
            'favorite_providers',
            'coupon_customers',
            'searched_data',
            'visited_services',
            'recent_views',
            'recent_searches',
            'service_requests',
            'customer_incidents',
        ],
        self::DOMAIN_PROVIDERS => ['users', 'providers', 'bank_details'],
        self::DOMAIN_LEADS => [
            'sources', 'adsources', 'customer_lead_tags', 'leads', 'lead_followups', 'lead_customer_tag',
        ],
        self::DOMAIN_BOOKINGS => [
            'bookings', 'booking_details', 'booking_details_amounts', 'booking_extra_services',
            'booking_followups', 'booking_status_histories', 'booking_schedule_histories',
            'booking_partial_payments', 'booking_offline_payments', 'booking_repeats',
            'booking_repeat_details', 'booking_repeat_histories', 'subscription_subscriber_bookings',
        ],
        self::DOMAIN_CONFIGURATION => ['business_settings', 'data_settings'],
    ];

    public function export(string $domain): array
    {
        return match ($domain) {
            self::DOMAIN_CUSTOMERS => $this->exportCustomers(),
            self::DOMAIN_PROVIDERS => $this->exportProviders(),
            self::DOMAIN_LEADS => $this->exportLeads(),
            self::DOMAIN_BOOKINGS => $this->exportBookings(),
            self::DOMAIN_CONFIGURATION => $this->exportConfiguration(),
            default => throw new InvalidArgumentException('Unknown export domain.'),
        };
    }

    public function assertValidPayload(string $domain, array $payload): void
    {
        $expected = self::PAYLOAD_TYPES[$domain] ?? null;
        if (! $expected) {
            throw new InvalidArgumentException('Unknown domain.');
        }
        if (($payload['meta']['payload_type'] ?? null) !== $expected) {
            throw new InvalidArgumentException('Invalid export file for this tab (wrong payload_type).');
        }
        if (! isset($payload['tables']) || ! is_array($payload['tables'])) {
            throw new InvalidArgumentException('Invalid export file (missing tables).');
        }
    }

    public function preview(string $domain, array $payload): array
    {
        $this->assertValidPayload($domain, $payload);
        $tables = $payload['tables'] ?? [];
        $summary = [];
        foreach ($tables as $name => $rows) {
            if (! is_array($rows)) {
                continue;
            }
            $maxCols = ($domain === self::DOMAIN_CUSTOMERS && $name === 'users') ? 120 : 20;
            $summary[$name] = [
                'count' => count($rows),
                'sample' => $this->sampleRows($rows, 12, $maxCols),
            ];
        }

        return ['tables' => $summary, 'exported_at' => $payload['meta']['exported_at'] ?? null];
    }

    /**
     * @return array{imported: array<string, int>, warnings: array<int, string>}
     */
    public function import(string $domain, array $payload): array
    {
        $this->assertValidPayload($domain, $payload);
        $warnings = [];
        $imported = [];

        $order = self::IMPORT_TABLE_ORDER[$domain] ?? [];
        DB::beginTransaction();
        try {
            foreach ($order as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $rows = $payload['tables'][$table] ?? [];
                if (! is_array($rows) || $rows === []) {
                    $imported[$table] = 0;

                    continue;
                }
                $imported[$table] = $this->upsertTableRows($table, $rows);
            }

            if ($domain === self::DOMAIN_CONFIGURATION) {
                $this->importConfigurationTranslations($payload, $imported, $warnings);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['imported' => $imported, 'warnings' => $warnings];
    }

    private function exportCustomers(): array
    {
        $userIds = DB::table('users')
            ->inCustomerDirectory()
            ->pluck('id')
            ->all();

        $tables = ['users' => []];

        if ($userIds === []) {
            return $this->wrapMeta(self::DOMAIN_CUSTOMERS, $tables);
        }

        $tables['users'] = DB::table('users')
            ->whereIn('id', $userIds)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->values()
            ->all();

        if (Schema::hasTable('user_addresses')) {
            $tables['user_addresses'] = $this->exportRowsForUserIds('user_addresses', 'user_id', $userIds);
        }
        if (Schema::hasTable('user_zones')) {
            $tables['user_zones'] = $this->exportRowsForUserIds('user_zones', 'user_id', $userIds);
        }

        foreach ($this->customerRelatedTableSpecs() as [$table, $column]) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $rows = $this->exportRowsForUserIds($table, $column, $userIds);
            if ($rows !== []) {
                $tables[$table] = $rows;
            }
        }

        return $this->wrapMeta(self::DOMAIN_CUSTOMERS, $tables);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function customerRelatedTableSpecs(): array
    {
        return [
            ['accounts', 'user_id'],
            ['user_verifications', 'user_id'],
            ['loyalty_point_transactions', 'user_id'],
            ['carts', 'customer_id'],
            ['added_to_carts', 'user_id'],
            ['favorite_services', 'customer_user_id'],
            ['favorite_providers', 'customer_user_id'],
            ['coupon_customers', 'customer_user_id'],
            ['searched_data', 'user_id'],
            ['visited_services', 'user_id'],
            ['recent_views', 'user_id'],
            ['recent_searches', 'user_id'],
            ['service_requests', 'user_id'],
            ['customer_incidents', 'customer_id'],
        ];
    }

    /**
     * @param  list<string>  $userIds
     * @return list<array<string, mixed>>
     */
    private function exportRowsForUserIds(string $table, string $column, array $userIds): array
    {
        if ($userIds === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)
            ->whereIn($column, $userIds)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->values()
            ->all();
    }

    private function exportProviders(): array
    {
        if (! Schema::hasTable('providers')) {
            return $this->wrapMeta(self::DOMAIN_PROVIDERS, []);
        }
        $providers = DB::table('providers')->get()->map(fn ($r) => (array) $r)->values()->all();
        $userIds = array_values(array_unique(array_filter(array_column($providers, 'user_id'))));
        $tables = [
            'providers' => $providers,
        ];
        if ($userIds !== []) {
            $tables['users'] = DB::table('users')->whereIn('id', $userIds)->get()->map(fn ($r) => (array) $r)->values()->all();
        } else {
            $tables['users'] = [];
        }
        if (Schema::hasTable('bank_details') && $providers !== []) {
            $pids = array_column($providers, 'id');
            $tables['bank_details'] = DB::table('bank_details')->whereIn('provider_id', $pids)->get()->map(fn ($r) => (array) $r)->values()->all();
        }

        return $this->wrapMeta(self::DOMAIN_PROVIDERS, $tables);
    }

    private function exportLeads(): array
    {
        $tables = [];
        if (Schema::hasTable('sources')) {
            $tables['sources'] = DB::table('sources')->get()->map(fn ($r) => (array) $r)->values()->all();
        }
        if (Schema::hasTable('adsources')) {
            $tables['adsources'] = DB::table('adsources')->get()->map(fn ($r) => (array) $r)->values()->all();
        }
        if (Schema::hasTable('customer_lead_tags')) {
            $tables['customer_lead_tags'] = DB::table('customer_lead_tags')->get()->map(fn ($r) => (array) $r)->values()->all();
        }
        if (Schema::hasTable('leads')) {
            $tables['leads'] = DB::table('leads')->get()->map(fn ($r) => (array) $r)->values()->all();
            $leadIds = array_column($tables['leads'], 'id');
            if ($leadIds !== [] && Schema::hasTable('lead_followups')) {
                $tables['lead_followups'] = DB::table('lead_followups')->whereIn('lead_id', $leadIds)->get()->map(fn ($r) => (array) $r)->values()->all();
            }
            if ($leadIds !== [] && Schema::hasTable('lead_customer_tag')) {
                $tables['lead_customer_tag'] = DB::table('lead_customer_tag')->whereIn('lead_id', $leadIds)->get()->map(fn ($r) => (array) $r)->values()->all();
            }
        }

        return $this->wrapMeta(self::DOMAIN_LEADS, $tables);
    }

    private function exportBookings(): array
    {
        $tables = [];
        foreach (self::IMPORT_TABLE_ORDER[self::DOMAIN_BOOKINGS] as $table) {
            if (Schema::hasTable($table)) {
                $tables[$table] = DB::table($table)->get()->map(fn ($r) => (array) $r)->values()->all();
            }
        }

        return $this->wrapMeta(self::DOMAIN_BOOKINGS, $tables);
    }

    private function exportConfiguration(): array
    {
        $tables = [];
        if (Schema::hasTable('business_settings')) {
            $tables['business_settings'] = DB::table('business_settings')->get()->map(fn ($r) => (array) $r)->values()->all();
        }
        if (Schema::hasTable('data_settings')) {
            $tables['data_settings'] = DB::table('data_settings')->get()->map(fn ($r) => (array) $r)->values()->all();
        }
        $ids = [];
        foreach ($tables['business_settings'] ?? [] as $r) {
            $r = (array) $r;
            if (! empty($r['id'])) {
                $ids[] = (string) $r['id'];
            }
        }
        foreach ($tables['data_settings'] ?? [] as $r) {
            $r = (array) $r;
            if (! empty($r['id'])) {
                $ids[] = (string) $r['id'];
            }
        }
        $translations = [];
        if ($ids !== [] && Schema::hasTable('translations')) {
            $translations = DB::table('translations')
                ->whereIn('translationable_type', [BusinessSettings::class, DataSetting::class])
                ->whereIn('translationable_id', $ids)
                ->get()->map(fn ($r) => (array) $r)->values()->all();
        }
        $tables['translations'] = $translations;

        return $this->wrapMeta(self::DOMAIN_CONFIGURATION, $tables);
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $tables
     */
    private function wrapMeta(string $domain, array $tables): array
    {
        return [
            'meta' => [
                'domain' => $domain,
                'payload_type' => self::PAYLOAD_TYPES[$domain],
                'version' => 1,
                'exported_at' => Carbon::now()->toIso8601String(),
            ],
            'tables' => $tables,
        ];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function sampleRows(array $rows, int $n, int $maxColumns = 12): array
    {
        $out = [];
        foreach (array_slice($rows, 0, $n) as $r) {
            $r = (array) $r;
            $out[] = array_slice($r, 0, $maxColumns, true);
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>|object>  $rows
     */
    private function upsertTableRows(string $table, array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            $row = (array) $row;
            if (array_key_exists('id', $row)) {
                $id = $row['id'];
                unset($row['id']);
                DB::table($table)->updateOrInsert(['id' => $id], array_merge($row, ['id' => $id]));
            } else {
                DB::table($table)->insert($row);
            }
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $imported
     * @param  array<int, string>  $warnings
     */
    private function importConfigurationTranslations(array $payload, array &$imported, array &$warnings): void
    {
        $rows = $payload['tables']['translations'] ?? [];
        if (! is_array($rows) || $rows === [] || ! Schema::hasTable('translations')) {
            $imported['translations'] = 0;

            return;
        }
        $bsIds = [];
        $dsIds = [];
        foreach ($payload['tables']['business_settings'] ?? [] as $r) {
            $r = (array) $r;
            if (! empty($r['id'])) {
                $bsIds[] = (string) $r['id'];
            }
        }
        foreach ($payload['tables']['data_settings'] ?? [] as $r) {
            $r = (array) $r;
            if (! empty($r['id'])) {
                $dsIds[] = (string) $r['id'];
            }
        }
        $targetIds = array_merge($bsIds, $dsIds);
        if ($targetIds === []) {
            $imported['translations'] = 0;

            return;
        }
        DB::table('translations')
            ->whereIn('translationable_type', [BusinessSettings::class, DataSetting::class])
            ->whereIn('translationable_id', $targetIds)
            ->delete();
        foreach ($rows as $row) {
            $row = (array) $row;
            unset($row['id']);
            DB::table('translations')->insert($row);
        }
        $imported['translations'] = count($rows);
    }
}
