<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\AdSource;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\District;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\ProviderCancellationReason;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\Source;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Seeds demo leads for lead management UI / reports (dummy +19980000xxxx phones).
 *
 * Run: php artisan db:seed --class=DummyLeadsSeeder
 */
class DummyLeadsSeeder extends Seeder
{
    private const PHONE_PREFIX = '+19980000';

    /** Total dummy rows (supports 0001–9999 via 4-digit suffix). */
    private const DEMO_LEAD_COUNT = 250;

    public function run(): void
    {
        if (!Schema::hasTable('leads')) {
            $this->command?->warn('Leads table is missing; skip dummy leads seed.');

            return;
        }

        $dummyPhones = [];
        for ($n = 1; $n <= self::DEMO_LEAD_COUNT; $n++) {
            $dummyPhones[] = self::PHONE_PREFIX . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        }

        $this->purgeDummyLeads($dummyPhones);

        $employeeIds = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->where('is_active', 1)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        if ($employeeIds === []) {
            $employeeIds = User::query()->orderBy('id')->limit(5)->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
        }

        $createdById = $employeeIds[0] ?? null;

        $sourceIds = Source::query()->where('is_active', true)->orderBy('id')->pluck('id')->all();
        $adSourceIds = AdSource::query()->where('is_active', true)->orderBy('id')->pluck('id')->all();
        $zoneIds = Zone::query()->ofStatus(1)->orderBy('name')->pluck('id')->all();
        $districtIds = District::query()->where('is_active', true)->orderBy('name')->pluck('id')->all();

        $customerStatuses = CustomerLeadStatus::query()->where('is_active', true)->orderBy('id')->get();
        $providerStatuses = ProviderLeadStatus::query()->where('is_active', true)->orderBy('id')->get();
        $invalidReasonIds = LeadInvalidReason::query()->where('is_active', true)->orderBy('id')->pluck('id')->all();
        $futureReasonIds = LeadFutureCustomerReason::query()->where('is_active', true)->orderBy('id')->pluck('id')->all();
        $customerTagIds = CustomerLeadTag::query()->where('is_active', true)->orderBy('id')->pluck('id')->all();
        $customerCancelReasonIds = LeadCancellationReason::query()->where('is_active', true)->orderBy('id')->pluck('id')->all();
        $providerCancelReasonIds = ProviderCancellationReason::query()->where('is_active', true)->orderBy('id')->pluck('id')->all();

        $parentCategoryIds = Category::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', '0')->orWhere('parent_id', '');
            })
            ->orderBy('name')
            ->pluck('id')
            ->all();

        $subCategoryByParent = $this->loadSubCategoriesByParent($parentCategoryIds);
        $serviceIds = Service::query()->where('is_active', 1)->orderBy('name')->limit(80)->pluck('id')->all();

        $typePlan = $this->buildTypePlan(self::DEMO_LEAD_COUNT);
        $handledByPool = $this->buildHandledByPool($employeeIds);
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();

        $customerVariantIndex = 0;
        $providerVariantIndex = 0;

        DB::transaction(function () use (
            $dummyPhones,
            $typePlan,
            $handledByPool,
            $employeeIds,
            $createdById,
            $sourceIds,
            $adSourceIds,
            $zoneIds,
            $districtIds,
            $customerStatuses,
            $providerStatuses,
            $invalidReasonIds,
            $futureReasonIds,
            $customerTagIds,
            $customerCancelReasonIds,
            $providerCancelReasonIds,
            $parentCategoryIds,
            $subCategoryByParent,
            $serviceIds,
            $now,
            $monthStart,
            &$customerVariantIndex,
            &$providerVariantIndex,
        ) {
            foreach ($dummyPhones as $index => $phone) {
                $leadType = $typePlan[$index];
                $handledBy = $handledByPool[$index % count($handledByPool)];
                $receivedAt = $this->receivedAtForIndex($index, $now, $monthStart);

                $hasFollowup = in_array($leadType, [Lead::TYPE_UNKNOWN, Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)
                    && ($index % 5 === 0);

                $leadId = DB::table('leads')->insertGetId([
                    'name' => $this->demoName($leadType, $index + 1),
                    'phone_number' => $phone,
                    'source_id' => $sourceIds !== [] ? $sourceIds[$index % count($sourceIds)] : null,
                    'lead_type' => $leadType,
                    'date_time_of_lead_received' => $receivedAt,
                    'ad_source_id' => $adSourceIds !== [] ? $adSourceIds[$index % count($adSourceIds)] : null,
                    'handled_by' => $handledBy,
                    'remarks' => 'Demo lead for testing (DummyLeadsSeeder).',
                    'next_followup_at' => $hasFollowup ? $now->copy()->addDays($index % 15)->startOfHour() : null,
                    'created_by' => $createdById,
                    'created_at' => $receivedAt,
                    'updated_at' => $receivedAt,
                ]);

                $historyData = match ($leadType) {
                    Lead::TYPE_CUSTOMER => $this->buildCustomerHistoryData(
                        $customerStatuses,
                        $zoneIds,
                        $parentCategoryIds,
                        $subCategoryByParent,
                        $serviceIds,
                        $customerCancelReasonIds,
                        $customerVariantIndex++
                    ),
                    Lead::TYPE_PROVIDER => $this->buildProviderHistoryData(
                        $providerStatuses,
                        $zoneIds,
                        $districtIds,
                        $parentCategoryIds,
                        $subCategoryByParent,
                        $providerCancelReasonIds,
                        $providerVariantIndex++
                    ),
                    Lead::TYPE_INVALID => $invalidReasonIds !== [] ? [
                        'invalid_reason_id' => $invalidReasonIds[$index % count($invalidReasonIds)],
                        'invalid_remarks' => 'Demo invalid lead.',
                    ] : null,
                    Lead::TYPE_FUTURE_CUSTOMER => $futureReasonIds !== [] ? [
                        'future_customer_reason_id' => $futureReasonIds[$index % count($futureReasonIds)],
                        'future_customer_remarks' => 'Demo future customer lead.',
                    ] : null,
                    default => null,
                };

                if ($historyData !== null && Schema::hasTable('lead_type_histories')) {
                    LeadTypeHistory::create([
                        'lead_id' => $leadId,
                        'type' => $leadType,
                        'data' => $historyData,
                        'created_by' => $createdById,
                    ]);
                }

                if ($leadType === Lead::TYPE_CUSTOMER && $customerTagIds !== []) {
                    $tagCount = 1 + ($index % 2);
                    for ($t = 0; $t < $tagCount; $t++) {
                        DB::table('lead_customer_tag')->insert([
                            'lead_id' => $leadId,
                            'customer_lead_tag_id' => $customerTagIds[($index + $t) % count($customerTagIds)],
                            'created_at' => $receivedAt,
                            'updated_at' => $receivedAt,
                        ]);
                    }
                }

                if ($hasFollowup && Schema::hasTable('lead_followups')) {
                    DB::table('lead_followups')->insert([
                        'lead_id' => $leadId,
                        'followup_at' => $receivedAt->copy()->addDay(),
                        'remarks' => 'Demo follow-up note.',
                        'next_followup_at' => $now->copy()->addDays(2 + ($index % 10)),
                        'created_by' => $employeeIds[$index % max(1, count($employeeIds))] ?? $createdById,
                        'created_at' => $receivedAt,
                        'updated_at' => $receivedAt,
                    ]);
                }
            }
        });

        $inMonth = Lead::query()
            ->where('phone_number', 'like', self::PHONE_PREFIX . '%')
            ->whereBetween('date_time_of_lead_received', [$monthStart, $now->copy()->endOfDay()])
            ->count();

        $this->command?->info(sprintf(
            'Seeded %d dummy leads (%sxxxx). %d fall in the current month (default report range).',
            self::DEMO_LEAD_COUNT,
            self::PHONE_PREFIX,
            $inMonth
        ));
    }

    /**
     * Weighted plan: more customer/provider rows for reporting matrices.
     *
     * @return array<int, string>
     */
    private function buildTypePlan(int $count): array
    {
        $weights = [
            Lead::TYPE_UNKNOWN => 20,
            Lead::TYPE_CUSTOMER => 100,
            Lead::TYPE_PROVIDER => 75,
            Lead::TYPE_INVALID => 30,
            Lead::TYPE_FUTURE_CUSTOMER => 25,
        ];

        $plan = [];
        foreach ($weights as $type => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $plan[] = $type;
            }
        }

        while (count($plan) < $count) {
            $plan[] = Lead::TYPE_CUSTOMER;
        }

        $buckets = [];
        foreach ($weights as $type => $weight) {
            $buckets[$type] = array_fill(0, min($weight, $count), $type);
        }

        $plan = [];
        while (count($plan) < $count) {
            foreach (array_keys($weights) as $type) {
                if ($buckets[$type] !== []) {
                    $plan[] = array_shift($buckets[$type]);
                    if (count($plan) >= $count) {
                        break 2;
                    }
                }
            }
        }

        return $plan;
    }

    /** All leads use date_time_of_lead_received within the current calendar month (through today). */
    private function receivedAtForIndex(int $index, Carbon $now, Carbon $monthStart): Carbon
    {
        $endOfRange = $now->copy()->endOfDay();
        $daysInRange = max(0, (int) $monthStart->copy()->startOfDay()->diffInDays($endOfRange));
        $dayOffset = $daysInRange > 0 ? ($index % ($daysInRange + 1)) : 0;

        $receivedAt = $monthStart->copy()
            ->addDays($dayOffset)
            ->setTime(8 + ($index % 12), ($index * 7) % 60, 0);

        if ($receivedAt->gt($endOfRange)) {
            $receivedAt = $endOfRange->copy()->setTime(
                8 + ($index % 12),
                ($index * 7) % 60,
                0
            );
        }

        return $receivedAt;
    }

    /**
     * @param  array<int, string>  $employeeIds
     * @return array<int, string|null>
     */
    private function buildHandledByPool(array $employeeIds): array
    {
        $pool = [];
        foreach ($employeeIds as $id) {
            for ($i = 0; $i < 8; $i++) {
                $pool[] = $id;
            }
        }
        for ($i = 0; $i < 12; $i++) {
            $pool[] = Lead::HANDLED_BY_AI;
        }
        for ($i = 0; $i < 10; $i++) {
            $pool[] = null;
        }

        return $pool;
    }

    /**
     * Cycle every active customer status × zone so reports show all buckets.
     *
     * @param  Collection<int, CustomerLeadStatus>  $customerStatuses
     * @param  array<int, string>  $zoneIds
     * @param  array<int, string>  $parentCategoryIds
     * @param  array<string, string|null>  $subCategoryByParent
     * @param  array<int, string>  $serviceIds
     * @param  array<int, int>  $cancelReasonIds
     * @return array<string, mixed>
     */
    private function buildCustomerHistoryData(
        Collection $customerStatuses,
        array $zoneIds,
        array $parentCategoryIds,
        array $subCategoryByParent,
        array $serviceIds,
        array $cancelReasonIds,
        int $variantIndex,
    ): array {
        $statusList = $customerStatuses->isNotEmpty()
            ? $customerStatuses->values()->all()
            : [null];

        $status = $statusList[$variantIndex % count($statusList)];
        $statusId = $status?->id ?? CustomerLeadStatus::defaultPendingStatusId();
        $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

        $zoneId = $zoneIds !== [] ? $zoneIds[$variantIndex % count($zoneIds)] : null;
        $parentId = $parentCategoryIds !== [] ? $parentCategoryIds[$variantIndex % count($parentCategoryIds)] : null;
        $subCategoryId = $parentId ? ($subCategoryByParent[$parentId] ?? null) : null;

        $bookingStatus = match ($baseType) {
            'completed' => 'booked',
            'cancel' => 'cancelled',
            default => 'pending',
        };

        $data = [
            'customer_lead_status_id' => $statusId,
            'booking_status' => $bookingStatus,
            'zone_id' => $zoneId,
            'service_category' => $parentId,
            'service_subcategory' => $subCategoryId,
            'service_name' => $serviceIds !== [] ? $serviceIds[$variantIndex % count($serviceIds)] : null,
            'service_description' => 'Demo customer lead — status ' . ($status?->name ?? 'pending') . ', zone variant ' . ($variantIndex + 1),
            'estimated_service_at' => Carbon::now()->startOfMonth()
                ->addDays($variantIndex % max(1, (int) Carbon::now()->day))
                ->setTime(10 + ($variantIndex % 8), 0)
                ->toDateTimeString(),
        ];

        if ($baseType === 'cancel' && $cancelReasonIds !== []) {
            $data['cancellation_reason_id'] = $cancelReasonIds[$variantIndex % count($cancelReasonIds)];
            $data['cancellation_remarks'] = 'Demo customer cancellation.';
        }

        return $data;
    }

    /**
     * @param  Collection<int, ProviderLeadStatus>  $providerStatuses
     * @param  array<int, string>  $zoneIds
     * @param  array<int, int>  $districtIds
     * @param  array<int, string>  $parentCategoryIds
     * @param  array<string, string|null>  $subCategoryByParent
     * @param  array<int, int>  $cancelReasonIds
     * @return array<string, mixed>
     */
    private function buildProviderHistoryData(
        Collection $providerStatuses,
        array $zoneIds,
        array $districtIds,
        array $parentCategoryIds,
        array $subCategoryByParent,
        array $cancelReasonIds,
        int $variantIndex,
    ): array {
        $statusList = $providerStatuses->isNotEmpty()
            ? $providerStatuses->values()->all()
            : [null];

        $status = $statusList[$variantIndex % count($statusList)];
        $statusId = $status?->id ?? ProviderLeadStatus::defaultPendingStatusId();
        $baseType = strtolower((string) ($status?->base_type ?? 'pending'));

        $zoneIdList = [];
        if ($zoneIds !== []) {
            $count = 1 + ($variantIndex % min(3, count($zoneIds)));
            for ($z = 0; $z < $count; $z++) {
                $zoneIdList[] = $zoneIds[($variantIndex + $z) % count($zoneIds)];
            }
            $zoneIdList = array_values(array_unique($zoneIdList));
        }

        $parentId = $parentCategoryIds !== [] ? $parentCategoryIds[$variantIndex % count($parentCategoryIds)] : null;
        $subCategoryId = $parentId ? ($subCategoryByParent[$parentId] ?? null) : null;

        $data = [
            'provider_lead_status_id' => $statusId,
            'district_id' => $districtIds !== [] ? $districtIds[$variantIndex % count($districtIds)] : null,
            'zone_ids' => $zoneIdList,
            'zone_id' => $zoneIdList[0] ?? null,
            'full_address' => 'Demo provider address #' . ($variantIndex + 1),
            'service_areas' => 'Demo coverage areas.',
            'provider_service_category' => $parentId,
            'provider_service_subcategory' => $subCategoryId,
            'provider_service_details' => 'Demo provider — status ' . ($status?->name ?? 'pending'),
        ];

        if ($baseType === 'cancel' && $cancelReasonIds !== []) {
            $data['provider_cancellation_reason_id'] = $cancelReasonIds[$variantIndex % count($cancelReasonIds)];
            $data['provider_cancellation_remarks'] = 'Demo provider cancellation.';
        }

        return $data;
    }

    /**
     * @param  array<int, string>  $parentCategoryIds
     * @return array<string, string|null>
     */
    private function loadSubCategoriesByParent(array $parentCategoryIds): array
    {
        $map = [];
        foreach ($parentCategoryIds as $parentId) {
            $map[$parentId] = Category::query()
                ->where('is_active', 1)
                ->where('parent_id', $parentId)
                ->orderBy('name')
                ->value('id');
        }

        return $map;
    }

    private function demoName(string $leadType, int $num): string
    {
        $labels = [
            Lead::TYPE_UNKNOWN => 'Demo Unknown',
            Lead::TYPE_CUSTOMER => 'Demo Customer',
            Lead::TYPE_PROVIDER => 'Demo Provider',
            Lead::TYPE_INVALID => 'Demo Invalid',
            Lead::TYPE_FUTURE_CUSTOMER => 'Demo Future Customer',
        ];

        return ($labels[$leadType] ?? 'Demo Lead') . ' #' . $num;
    }

    /**
     * @param  array<int, string>  $phones
     */
    private function purgeDummyLeads(array $phones): void
    {
        $existingIds = DB::table('leads')
            ->where('phone_number', 'like', self::PHONE_PREFIX . '%')
            ->pluck('id')
            ->all();

        if ($existingIds === []) {
            return;
        }

        if (Schema::hasTable('lead_customer_tag')) {
            DB::table('lead_customer_tag')->whereIn('lead_id', $existingIds)->delete();
        }
        if (Schema::hasTable('lead_followups')) {
            DB::table('lead_followups')->whereIn('lead_id', $existingIds)->delete();
        }
        if (Schema::hasTable('lead_type_histories')) {
            DB::table('lead_type_histories')->whereIn('lead_id', $existingIds)->delete();
        }
        if (Schema::hasTable('lead_change_logs')) {
            DB::table('lead_change_logs')->whereIn('lead_id', $existingIds)->delete();
        }
        if (Schema::hasTable('lead_provider_checklist')) {
            DB::table('lead_provider_checklist')->whereIn('lead_id', $existingIds)->delete();
        }

        DB::table('leads')->whereIn('id', $existingIds)->delete();

        $this->command?->info('Removed ' . count($existingIds) . ' existing dummy leads before re-seeding.');
    }
}
