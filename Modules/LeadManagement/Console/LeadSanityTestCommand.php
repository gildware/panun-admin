<?php

namespace Modules\LeadManagement\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\CategoryManagement\Entities\Category;
use Modules\LeadManagement\Entities\CustomerLeadTag;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadFutureCustomerReason;
use Modules\LeadManagement\Entities\LeadInvalidReason;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Http\Controllers\Web\Admin\LeadController;
use Modules\LeadManagement\Entities\District;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;
use Symfony\Component\HttpFoundation\Response;

class LeadSanityTestCommand extends Command
{
    protected $signature = 'lead:sanity-test {--keep-data : Leave test rows in the database (default: rollback transaction)}';

    protected $description = 'Sanity test all lead types, conversions, updates, tags, temp provider, follow-ups, and delete';

    private string $tag;

    /** @var array<int, int> */
    private array $leadIds = [];

    private ?User $admin = null;

    private ?string $zoneId = null;

    private ?string $subCategoryId = null;

    private ?string $parentCategoryId = null;

    private ?int $invalidReasonId = null;

    private ?int $futureReasonId = null;

    private ?int $districtId = null;

    private ?string $providerId = null;

    private ?int $customerTagId = null;

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to run on production.');

            return self::FAILURE;
        }

        $this->tag = 'lead-sanity-'.Str::lower(Str::random(6));
        $this->info("Lead sanity test [{$this->tag}]");

        $shouldKeep = (bool) $this->option('keep-data');
        $passed = 0;
        $failed = 0;

        if (! $shouldKeep) {
            DB::beginTransaction();
        }

        try {
            $this->bootstrapFixtures();

            $scenarios = [
                'fixtures_loaded' => fn () => $this->testFixturesLoaded(),
                'create_unknown_lead' => fn () => $this->testCreateLead(Lead::TYPE_UNKNOWN),
                'create_customer_lead' => fn () => $this->testCreateLead(Lead::TYPE_CUSTOMER),
                'create_provider_lead' => fn () => $this->testCreateLead(Lead::TYPE_PROVIDER),
                'create_invalid_lead' => fn () => $this->testCreateInvalidLead(),
                'create_future_customer_lead' => fn () => $this->testCreateFutureCustomerLead(),
                'unknown_to_customer' => fn () => $this->testUnknownToCustomer(),
                'unknown_to_provider' => fn () => $this->testUnknownToProvider(),
                'unknown_to_invalid' => fn () => $this->testUnknownToInvalid(),
                'unknown_to_future_customer' => fn () => $this->testUnknownToFutureCustomer(),
                'update_lead_core_fields' => fn () => $this->testUpdateLeadCoreFields(),
                'update_customer_info' => fn () => $this->testUpdateCustomerInfo(),
                'update_provider_info' => fn () => $this->testUpdateProviderInfo(),
                'customer_tags_update' => fn () => $this->testCustomerTagsUpdate(),
                'temporary_provider_assign_clear' => fn () => $this->testTemporaryProviderAssignClear(),
                'search_providers_endpoint' => fn () => $this->testSearchProvidersEndpoint(),
                'store_followup' => fn () => $this->testStoreFollowup(),
                'store_call_log_customer' => fn () => $this->testStoreCallLogCustomer(),
                'store_call_log_provider' => fn () => $this->testStoreCallLogProvider(),
                'store_call_log_other' => fn () => $this->testStoreCallLogOther(),
                'store_call_log_with_recording' => fn () => $this->testStoreCallLogWithRecording(),
                'update_call_log' => fn () => $this->testUpdateCallLog(),
                'delete_call_log' => fn () => $this->testDeleteCallLog(),
                'show_pages_render' => fn () => $this->testShowPagesRender(),
                'lead_index_renders' => fn () => $this->testLeadIndexRenders(),
                'delete_leads' => fn () => $this->testDeleteLeads(),
            ];

            foreach ($scenarios as $name => $runner) {
                $this->line('');
                $this->info($name);
                try {
                    $detail = $runner();
                    $this->line("  <fg=green>PASS</> {$detail}");
                    $passed++;
                } catch (\Throwable $e) {
                    $this->line("  <fg=red>FAIL</> {$e->getMessage()}");
                    $failed++;
                }
            }

            if (! $shouldKeep) {
                DB::rollBack();
                $this->line('');
                $this->info('Sanity test transaction rolled back.');
            } else {
                DB::commit();
                $this->line('');
                $this->warn('Test data kept in database (--keep-data). Lead IDs: '.implode(', ', $this->leadIds));
            }

            $this->line('');
            $this->info("Passed: {$passed} | Failed: {$failed}");

            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            if (! $shouldKeep) {
                DB::rollBack();
            }
            $this->error('Sanity test aborted: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }

    private function bootstrapFixtures(): void
    {
        $this->admin = User::query()
            ->whereIn('user_type', ['super-admin', 'admin-employee'])
            ->where('is_active', 1)
            ->first();

        if (! $this->admin) {
            throw new \RuntimeException('No active admin user found.');
        }

        Auth::login($this->admin);

        $this->zoneId = Zone::query()->ofStatus(1)->value('id');
        $subCategory = Category::query()->whereNotNull('parent_id')->where('is_active', 1)->first();
        $this->subCategoryId = $subCategory?->id;
        $this->parentCategoryId = $subCategory && ! empty($subCategory->parent_id) && $subCategory->parent_id !== '0'
            ? (string) $subCategory->parent_id
            : Category::query()->whereNull('parent_id')->where('is_active', 1)->value('id');

        $this->invalidReasonId = LeadInvalidReason::query()->where('is_active', true)->value('id');
        $this->futureReasonId = LeadFutureCustomerReason::query()->where('is_active', true)->value('id');
        $this->districtId = District::query()->where('is_active', true)->value('id');
        $this->providerId = Provider::query()->ofStatus(1)->ofApproval(1)->value('id');
        $this->customerTagId = CustomerLeadTag::query()->where('is_active', true)->value('id');
    }

    private function testFixturesLoaded(): string
    {
        if (! $this->admin || ! $this->invalidReasonId || ! $this->futureReasonId) {
            throw new \RuntimeException('Missing admin or reason fixtures.');
        }

        return 'admin='.$this->admin->email;
    }

    private function testCreateLead(string $type): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} {$type}",
            'phone_number' => $this->randomPhone(),
            'lead_type' => $type,
            'remarks' => "Sanity create {$type}",
        ]);

        if ($lead->lead_type !== $type) {
            throw new \RuntimeException("Expected type {$type}, got {$lead->lead_type}");
        }

        if (in_array($type, [Lead::TYPE_CUSTOMER, Lead::TYPE_PROVIDER], true)) {
            $history = LeadTypeHistory::query()
                ->where('lead_id', $lead->id)
                ->where('type', $type)
                ->exists();
            if (! $history) {
                throw new \RuntimeException("Missing type history for {$type}");
            }
        }

        return "lead #{$lead->id} type={$type}";
    }

    private function testCreateInvalidLead(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} invalid",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_INVALID,
            'invalid_reason_id' => $this->invalidReasonId,
            'invalid_remarks' => 'Sanity invalid',
        ]);

        if ($lead->lead_type !== Lead::TYPE_INVALID) {
            throw new \RuntimeException('Invalid lead type mismatch.');
        }

        return "lead #{$lead->id}";
    }

    private function testCreateFutureCustomerLead(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} future",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_FUTURE_CUSTOMER,
            'future_customer_reason_id' => $this->futureReasonId,
            'future_customer_remarks' => 'Sanity future customer',
        ]);

        if ($lead->lead_type !== Lead::TYPE_FUTURE_CUSTOMER) {
            throw new \RuntimeException('Future customer lead type mismatch.');
        }

        return "lead #{$lead->id}";
    }

    private function testUnknownToCustomer(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} unknown->customer",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_UNKNOWN,
        ]);

        $payload = ['lead_type' => Lead::TYPE_CUSTOMER];
        if ($this->zoneId) {
            $payload['zone_id'] = $this->zoneId;
        }
        if ($this->parentCategoryId) {
            $payload['service_category'] = $this->parentCategoryId;
        }
        if ($this->subCategoryId) {
            $payload['service_subcategory'] = $this->subCategoryId;
        }

        $this->callUpdateType($lead->id, $payload);
        $lead->refresh();

        if ($lead->lead_type !== Lead::TYPE_CUSTOMER) {
            throw new \RuntimeException('Conversion to customer failed.');
        }

        return "lead #{$lead->id}";
    }

    private function testUnknownToProvider(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} unknown->provider",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_UNKNOWN,
        ]);

        $payload = [
            'lead_type' => Lead::TYPE_PROVIDER,
            'full_address' => 'Sanity provider address',
            'service_areas' => 'Area A',
        ];
        if ($this->districtId) {
            $payload['district_id'] = $this->districtId;
        }
        if ($this->zoneId) {
            $payload['zone_ids'] = [$this->zoneId];
        }

        $this->callUpdateType($lead->id, $payload);
        $lead->refresh();

        if ($lead->lead_type !== Lead::TYPE_PROVIDER) {
            throw new \RuntimeException('Conversion to provider failed.');
        }

        return "lead #{$lead->id}";
    }

    private function testUnknownToInvalid(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} unknown->invalid",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_UNKNOWN,
        ]);

        $this->callUpdateType($lead->id, [
            'lead_type' => Lead::TYPE_INVALID,
            'invalid_reason_id' => $this->invalidReasonId,
            'invalid_remarks' => 'Converted invalid',
        ]);
        $lead->refresh();

        if ($lead->lead_type !== Lead::TYPE_INVALID) {
            throw new \RuntimeException('Conversion to invalid failed.');
        }

        return "lead #{$lead->id}";
    }

    private function testUnknownToFutureCustomer(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} unknown->future",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_UNKNOWN,
        ]);

        $this->callUpdateType($lead->id, [
            'lead_type' => Lead::TYPE_FUTURE_CUSTOMER,
            'future_customer_reason_id' => $this->futureReasonId,
            'future_customer_remarks' => 'Converted future',
        ]);
        $lead->refresh();

        if ($lead->lead_type !== Lead::TYPE_FUTURE_CUSTOMER) {
            throw new \RuntimeException('Conversion to future customer failed.');
        }

        return "lead #{$lead->id}";
    }

    private function testUpdateLeadCoreFields(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} update-core",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_UNKNOWN,
        ]);

        $newPhone = $this->randomPhone();
        $this->callUpdate($lead->id, [
            'name' => "{$this->tag} updated name",
            'phone_number' => $newPhone,
            'remarks' => 'Updated remarks',
            'next_followup_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]);

        $lead->refresh();
        if ($lead->name !== "{$this->tag} updated name" || $lead->phone_number !== $newPhone) {
            throw new \RuntimeException('Core field update did not persist.');
        }

        return "lead #{$lead->id}";
    }

    private function testUpdateCustomerInfo(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} customer-edit",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        $payload = [
            'lead_type' => Lead::TYPE_CUSTOMER,
            'update_customer' => '1',
            'service_description' => 'Updated service description',
        ];
        if ($this->zoneId) {
            $payload['zone_id'] = $this->zoneId;
        }

        $this->callUpdateType($lead->id, $payload);

        $history = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->where('type', Lead::TYPE_CUSTOMER)
            ->latest()
            ->first();

        if (! $history || ($history->data['service_description'] ?? '') !== 'Updated service description') {
            throw new \RuntimeException('Customer info update did not persist.');
        }

        return "lead #{$lead->id}";
    }

    private function testUpdateProviderInfo(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} provider-edit",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_PROVIDER,
        ]);

        $this->callUpdateType($lead->id, [
            'lead_type' => Lead::TYPE_PROVIDER,
            'update_provider' => '1',
            'full_address' => 'Updated provider address',
            'provider_service_details' => 'Updated services',
        ]);

        $history = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->where('type', Lead::TYPE_PROVIDER)
            ->latest()
            ->first();

        if (! $history || ($history->data['full_address'] ?? '') !== 'Updated provider address') {
            throw new \RuntimeException('Provider info update did not persist.');
        }

        return "lead #{$lead->id}";
    }

    private function testCustomerTagsUpdate(): string
    {
        if (! $this->customerTagId) {
            return 'skipped (no tags configured)';
        }

        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} tags",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        $response = app(LeadController::class)->updateCustomerTags(
            Request::create('/', 'PUT', ['tag_ids' => [(int) $this->customerTagId]]),
            $lead->id
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Tags update HTTP '.$response->getStatusCode());
        }

        $payload = json_decode($response->getContent(), true);
        if (! ($payload['success'] ?? false)) {
            throw new \RuntimeException('Tags update JSON success=false');
        }

        $lead->load('customerLeadTags');
        if ($lead->customerLeadTags->isEmpty()) {
            throw new \RuntimeException('Tag was not attached.');
        }

        return "lead #{$lead->id}, tag={$this->customerTagId}";
    }

    private function testTemporaryProviderAssignClear(): string
    {
        if (! $this->providerId) {
            return 'skipped (no providers)';
        }

        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} temp-provider",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        app(LeadController::class)->updateTemporaryProvider(
            Request::create('/', 'PUT', ['temporary_provider_id' => $this->providerId]),
            $lead->id
        );

        $history = LeadTypeHistory::query()
            ->where('lead_id', $lead->id)
            ->where('type', Lead::TYPE_CUSTOMER)
            ->latest()
            ->first();

        if (($history->data['temporary_provider_id'] ?? null) !== $this->providerId) {
            throw new \RuntimeException('Temporary provider was not assigned.');
        }

        app(LeadController::class)->updateTemporaryProvider(
            Request::create('/', 'PUT', ['temporary_provider_id' => null]),
            $lead->id
        );

        $history->refresh();
        if (! empty($history->data['temporary_provider_id'])) {
            throw new \RuntimeException('Temporary provider was not cleared.');
        }

        return "lead #{$lead->id}, provider={$this->providerId}";
    }

    private function testSearchProvidersEndpoint(): string
    {
        $response = app(LeadController::class)->searchProvidersForLead(Request::create('/', 'GET'));
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Search providers HTTP '.$response->getStatusCode());
        }

        $payload = json_decode($response->getContent(), true);
        $count = count($payload['results'] ?? []);
        if ($count < 1) {
            throw new \RuntimeException('Search providers returned zero results.');
        }

        return "results={$count}";
    }

    private function testStoreFollowup(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} followup",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        app(LeadController::class)->storeFollowup(
            Request::create('/', 'POST', [
                'followup_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'remarks' => 'Sanity follow-up',
                'contact_channel' => 'call',
                'urgency' => 'medium',
                'next_followup_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            ]),
            $lead->id
        );

        $lead->refresh();
        $count = $lead->followups()->count();
        if ($count < 1) {
            throw new \RuntimeException('Follow-up was not stored.');
        }
        if (! $lead->next_followup_at) {
            throw new \RuntimeException('Lead next_followup_at was not updated after follow-up save.');
        }

        return "lead #{$lead->id}, followups={$count}, next={$lead->next_followup_at->format('Y-m-d H:i')}";
    }

    private function testStoreCallLogCustomer(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} call-log-customer",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        $this->callStoreCallLog($lead->id, [
            'called_party_type' => LeadFollowup::CALLED_PARTY_CUSTOMER,
            'called_at' => now()->subMinutes(10)->format('Y-m-d H:i:s'),
            'remarks' => 'Sanity customer call log',
        ]);

        $followup = $this->assertCallLogStored($lead, LeadFollowup::CALLED_PARTY_CUSTOMER);
        if ($followup->called_name !== $lead->name || $followup->called_number !== $lead->phone_number) {
            throw new \RuntimeException('Customer call log did not capture lead contact details.');
        }

        return "lead #{$lead->id}, followup={$followup->id}";
    }

    private function testStoreCallLogProvider(): string
    {
        if (! $this->providerId) {
            return 'skipped (no providers)';
        }

        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} call-log-provider",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        $this->callStoreCallLog($lead->id, [
            'called_party_type' => LeadFollowup::CALLED_PARTY_PROVIDER,
            'called_provider_id' => $this->providerId,
            'called_at' => now()->subMinutes(20)->format('Y-m-d H:i:s'),
            'remarks' => 'Sanity provider call log',
        ]);

        $followup = $this->assertCallLogStored($lead, LeadFollowup::CALLED_PARTY_PROVIDER);
        if ($followup->called_provider_id !== $this->providerId) {
            throw new \RuntimeException('Provider call log did not store provider id.');
        }
        if (empty($followup->called_name) || empty($followup->called_number)) {
            throw new \RuntimeException('Provider call log missing resolved name or phone.');
        }

        return "lead #{$lead->id}, followup={$followup->id}, provider={$this->providerId}";
    }

    private function testStoreCallLogOther(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} call-log-other",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        $otherName = "{$this->tag} other contact";
        $otherPhone = $this->randomPhone();

        $this->callStoreCallLog($lead->id, [
            'called_party_type' => LeadFollowup::CALLED_PARTY_OTHER,
            'called_name' => $otherName,
            'called_number' => $otherPhone,
            'called_at' => now()->subMinutes(30)->format('Y-m-d H:i:s'),
            'remarks' => 'Sanity other call log',
        ]);

        $followup = $this->assertCallLogStored($lead, LeadFollowup::CALLED_PARTY_OTHER);
        if ($followup->called_name !== $otherName || $followup->called_number !== $otherPhone) {
            throw new \RuntimeException('Other call log did not store custom contact details.');
        }

        return "lead #{$lead->id}, followup={$followup->id}";
    }

    private function testStoreCallLogWithRecording(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} call-log-recording",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        $recording = UploadedFile::fake()->create('sanity-call-log.wav', 64, 'audio/wav');

        $this->callStoreCallLog($lead->id, [
            'called_party_type' => LeadFollowup::CALLED_PARTY_CUSTOMER,
            'called_at' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'remarks' => 'Sanity call log with recording',
        ], [
            'recording' => $recording,
        ]);

        $followup = $this->assertCallLogStored($lead, LeadFollowup::CALLED_PARTY_CUSTOMER);
        if (! $followup->hasRecording() || empty($followup->recording_path)) {
            throw new \RuntimeException('Call log recording was not stored.');
        }

        return "lead #{$lead->id}, followup={$followup->id}, recording={$followup->recording_path}";
    }

    private function testUpdateCallLog(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} call-log-update",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        $this->callStoreCallLog($lead->id, [
            'called_party_type' => LeadFollowup::CALLED_PARTY_OTHER,
            'called_name' => "{$this->tag} before edit",
            'called_number' => $this->randomPhone(),
            'called_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'remarks' => 'Before edit',
        ]);

        $followup = $this->assertCallLogStored($lead, LeadFollowup::CALLED_PARTY_OTHER);
        $updatedName = "{$this->tag} after edit";
        $updatedPhone = $this->randomPhone();

        $response = app(LeadController::class)->updateCallLog(
            Request::create('/', 'PUT', [
                'called_party_type' => LeadFollowup::CALLED_PARTY_OTHER,
                'called_name' => $updatedName,
                'called_number' => $updatedPhone,
                'called_at' => now()->subMinutes(15)->format('Y-m-d H:i:s'),
                'remarks' => 'After edit',
            ]),
            $lead->id,
            $followup->id
        );

        if ($response->getStatusCode() !== 302) {
            throw new \RuntimeException('Update call log HTTP '.$response->getStatusCode());
        }

        $followup->refresh();
        if ($followup->called_name !== $updatedName || $followup->called_number !== $updatedPhone || $followup->remarks !== 'After edit') {
            throw new \RuntimeException('Call log update did not persist changes.');
        }

        return "lead #{$lead->id}, followup={$followup->id}";
    }

    private function testDeleteCallLog(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} call-log-delete",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_CUSTOMER,
        ]);

        $this->callStoreCallLog($lead->id, [
            'called_party_type' => LeadFollowup::CALLED_PARTY_CUSTOMER,
            'called_at' => now()->subMinutes(10)->format('Y-m-d H:i:s'),
            'remarks' => 'To delete',
        ]);

        $followup = $this->assertCallLogStored($lead, LeadFollowup::CALLED_PARTY_CUSTOMER);
        $followupId = $followup->id;

        $response = app(LeadController::class)->destroyCallLog(
            Request::create('/', 'DELETE'),
            $lead->id,
            $followupId
        );

        if ($response->getStatusCode() !== 302) {
            throw new \RuntimeException('Delete call log HTTP '.$response->getStatusCode());
        }

        if (LeadFollowup::query()->whereKey($followupId)->exists()) {
            throw new \RuntimeException('Call log was not deleted.');
        }

        return "lead #{$lead->id}, deleted followup={$followupId}";
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, UploadedFile>  $files
     */
    private function callStoreCallLog(int $leadId, array $payload, array $files = []): void
    {
        $response = app(LeadController::class)->storeCallLog(
            Request::create('/', 'POST', $payload, [], $files),
            $leadId
        );

        if ($response->getStatusCode() !== 302) {
            throw new \RuntimeException('Store call log HTTP '.$response->getStatusCode());
        }
    }

    private function assertCallLogStored(Lead $lead, string $partyType): LeadFollowup
    {
        $followup = $lead->followups()
            ->where('contact_channel', LeadFollowup::CHANNEL_CALL)
            ->where('called_party_type', $partyType)
            ->latest('id')
            ->first();

        if (! $followup) {
            throw new \RuntimeException("Call log for {$partyType} was not stored.");
        }

        if ($followup->followup_status !== LeadFollowup::STATUS_TAKEN) {
            throw new \RuntimeException('Call log followup status is not taken.');
        }

        return $followup;
    }

    private function testShowPagesRender(): string
    {
        $types = [
            Lead::TYPE_UNKNOWN,
            Lead::TYPE_CUSTOMER,
            Lead::TYPE_PROVIDER,
            Lead::TYPE_INVALID,
            Lead::TYPE_FUTURE_CUSTOMER,
        ];

        $controller = app(LeadController::class);
        foreach ($types as $type) {
            $lead = $this->createLeadViaStore([
                'name' => "{$this->tag} show {$type}",
                'phone_number' => $this->randomPhone(),
                'lead_type' => $type,
                ...( $type === Lead::TYPE_INVALID ? ['invalid_reason_id' => $this->invalidReasonId] : []),
                ...( $type === Lead::TYPE_FUTURE_CUSTOMER ? ['future_customer_reason_id' => $this->futureReasonId] : []),
            ]);

            $view = $controller->show($lead->id);
            if (! $view instanceof View) {
                throw new \RuntimeException("Show page for {$type} did not return a view.");
            }
        }

        return 'all 5 lead types render show view';
    }

    private function testLeadIndexRenders(): string
    {
        $response = $this->dispatchWeb('GET', route('admin.lead.index'));
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Lead index HTTP '.$response->getStatusCode());
        }

        return 'HTTP 200';
    }

    private function testDeleteLeads(): string
    {
        $lead = $this->createLeadViaStore([
            'name' => "{$this->tag} delete",
            'phone_number' => $this->randomPhone(),
            'lead_type' => Lead::TYPE_UNKNOWN,
        ]);
        $id = $lead->id;

        app(LeadController::class)->destroy($id);

        if (Lead::query()->whereKey($id)->exists()) {
            throw new \RuntimeException('Lead was not deleted.');
        }

        $this->leadIds = array_values(array_filter($this->leadIds, fn ($x) => $x !== $id));

        return "deleted lead #{$id}";
    }

    private function createLeadViaStore(array $payload): Lead
    {
        app(LeadController::class)->store(Request::create('/', 'POST', $payload));

        $lead = Lead::query()
            ->where('phone_number', $payload['phone_number'])
            ->latest('id')
            ->first();

        if (! $lead) {
            throw new \RuntimeException('Lead was not created for phone '.$payload['phone_number']);
        }

        $this->leadIds[] = $lead->id;

        return $lead;
    }

    private function callUpdateType(int $leadId, array $payload): void
    {
        $payload['workflow_confirmed'] = $payload['workflow_confirmed'] ?? '1';
        app(LeadController::class)->updateType(Request::create('/', 'POST', $payload), $leadId);
    }

    private function callUpdate(int $leadId, array $payload): void
    {
        app(LeadController::class)->update(Request::create('/', 'PUT', $payload), $leadId);
    }

    private function dispatchWeb(string $method, string $uri): Response
    {
        $request = Request::create($uri, $method);
        $request->setUserResolver(fn () => $this->admin);

        return app(Kernel::class)->handle($request);
    }

    private function randomPhone(): string
    {
        return '7'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    }
}
