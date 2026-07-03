<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderChangeRequest;
use Modules\ProviderManagement\Services\ProviderProfileChangeRequestService;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class ProfileChangeReviewServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    public function test_single_approve_applies_one_branding_field_and_keeps_other_pending(): void
    {
        [$provider, $request, $adminId] = $this->seedBrandingChangeRequest();

        $service = app(ProviderProfileChangeRequestService::class);
        $result = $service->reviewSingleField($request, 'logo', true, $adminId);

        $request->refresh();
        $provider->refresh();

        $this->assertFalse($result['request_closed']);
        $this->assertSame(1, $result['remaining_count']);
        $this->assertSame('logo', $result['field_key']);
        $this->assertSame('new-logo.png', $provider->logo);
        $this->assertSame(ProviderChangeRequest::STATUS_PENDING, $request->status);
        $this->assertSame(1, $service->pendingReviewCount($request));

        $display = $service->buildReviewDisplayChanges($request);
        $this->assertCount(2, $display);
        $this->assertSame('approved', $display[0]['review_status']);
        $this->assertSame('pending', $display[1]['review_status']);
    }

    public function test_single_deny_then_accept_closes_request_as_approved(): void
    {
        [$provider, $request, $adminId] = $this->seedBrandingChangeRequest();
        $service = app(ProviderProfileChangeRequestService::class);

        $service->reviewSingleField($request, 'logo', false, $adminId);
        $request->refresh();

        $result = $service->reviewSingleField($request, 'cover_image', true, $adminId);
        $request->refresh();
        $provider->refresh();

        $this->assertTrue($result['request_closed']);
        $this->assertSame(ProviderChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('old-logo.png', $provider->logo);
        $this->assertSame('new-cover.png', $provider->cover_image);
        $this->assertStringContainsString('Logo', (string) $request->admin_note);
    }

    public function test_accept_all_pending_fields_closes_request_as_approved(): void
    {
        [$provider, $request, $adminId] = $this->seedBrandingChangeRequest();
        $service = app(ProviderProfileChangeRequestService::class);
        $pendingKeys = $service->pendingFieldChangesForRequest($request);

        foreach ($pendingKeys as $fieldKey) {
            $service->reviewSingleField($request, $fieldKey, true, $adminId);
            $request->refresh();
        }

        $provider->refresh();

        $this->assertSame(ProviderChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('new-logo.png', $provider->logo);
        $this->assertSame('new-cover.png', $provider->cover_image);
        $this->assertSame(0, $service->pendingReviewCount($request));
    }

    public function test_deny_all_pending_fields_closes_request_as_denied(): void
    {
        [, $request, $adminId] = $this->seedBrandingChangeRequest();
        $service = app(ProviderProfileChangeRequestService::class);
        $pendingKeys = $service->pendingFieldChangesForRequest($request);

        foreach ($pendingKeys as $fieldKey) {
            $service->reviewSingleField($request, $fieldKey, false, $adminId);
            $request->refresh();
        }

        $this->assertSame(ProviderChangeRequest::STATUS_DENIED, $request->status);
        $this->assertSame(0, $service->pendingReviewCount($request));
    }

    /**
     * @return array{0: Provider, 1: ProviderChangeRequest, 2: string}
     */
    private function seedBrandingChangeRequest(): array
    {
        $adminId = (string) Str::uuid();
        User::query()->create([
            'id' => $adminId,
            'first_name' => 'Review',
            'last_name' => 'Admin',
            'email' => 'review-admin-'.Str::random(6).'@test.local',
            'phone' => '9'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'user_type' => 'super-admin',
            'is_active' => 1,
        ]);

        $ownerId = (string) Str::uuid();
        User::query()->create([
            'id' => $ownerId,
            'first_name' => 'Provider',
            'last_name' => 'Owner',
            'email' => 'provider-owner-'.Str::random(6).'@test.local',
            'phone' => '8'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'user_type' => 'provider-admin',
            'is_active' => 1,
        ]);

        $provider = Provider::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $ownerId,
            'company_name' => 'Review Test Provider',
            'company_phone' => '7000000001',
            'company_email' => 'provider-'.Str::random(6).'@test.local',
            'logo' => 'old-logo.png',
            'cover_image' => null,
            'is_active' => 1,
            'is_approved' => 1,
        ]);

        $request = ProviderChangeRequest::query()->create([
            'provider_id' => $provider->id,
            'change_type' => 'branding',
            'status' => ProviderChangeRequest::STATUS_PENDING,
            'payload' => [
                'logo' => 'new-logo.png',
                'cover_image' => 'new-cover.png',
            ],
        ]);

        return [$provider, $request, $adminId];
    }
}
