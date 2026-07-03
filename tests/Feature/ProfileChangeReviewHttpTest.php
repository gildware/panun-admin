<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderChangeRequest;
use Modules\UserManagement\Entities\User;
use Tests\TestCase;

class ProfileChangeReviewHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    public function test_review_single_field_endpoint_returns_content_and_updates_row_state(): void
    {
        [$admin, $request] = $this->seedPendingBrandingRequest();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.provider.profile_change_review_field', ['id' => $request->id]), [
                'field_key' => 'logo',
                'approved' => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('content.field_key', 'logo')
            ->assertJsonPath('content.approved', true)
            ->assertJsonPath('content.request_closed', false)
            ->assertJsonPath('content.remaining_count', 1);

        $request->refresh();
        $this->assertSame(ProviderChangeRequest::STATUS_PENDING, $request->status);
    }

    public function test_review_all_fields_endpoint_closes_request(): void
    {
        [$admin, $request] = $this->seedPendingBrandingRequest();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.provider.profile_change_review', ['id' => $request->id]), [
                'decisions' => [
                    ['field_key' => 'cover_image', 'approved' => 0],
                    ['field_key' => 'logo', 'approved' => 1],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('content.request_closed', true)
            ->assertJsonPath('content.remaining_count', 0);

        $request->refresh();
        $this->assertSame(ProviderChangeRequest::STATUS_APPROVED, $request->status);
    }

    public function test_details_page_shows_status_badges_after_partial_review(): void
    {
        [$admin, $request] = $this->seedPendingBrandingRequest();

        app(\Modules\ProviderManagement\Services\ProviderProfileChangeRequestService::class)
            ->reviewSingleField($request, 'logo', true, $admin->id);

        $response = $this->actingAs($admin)
            ->get(route('admin.provider.profile_change_details', ['id' => $request->id]));

        $response->assertOk()
            ->assertSee('Accepted', false)
            ->assertSee('change-review-accept', false)
            ->assertSee('new-cover.png', false);
    }

    /**
     * @return array{0: User, 1: ProviderChangeRequest}
     */
    private function seedPendingBrandingRequest(): array
    {
        $admin = User::query()->create([
            'id' => (string) Str::uuid(),
            'first_name' => 'HTTP',
            'last_name' => 'Admin',
            'email' => 'http-admin-'.Str::random(6).'@test.local',
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
            'email' => 'provider-http-'.Str::random(6).'@test.local',
            'phone' => '8'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'user_type' => 'provider-admin',
            'is_active' => 1,
        ]);

        $provider = Provider::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $ownerId,
            'company_name' => 'HTTP Review Provider',
            'company_phone' => '7000000002',
            'company_email' => 'provider-http-co-'.Str::random(6).'@test.local',
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

        return [$admin, $request];
    }
}
