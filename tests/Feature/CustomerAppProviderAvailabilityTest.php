<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\CustomerModule\Services\CustomerHomeBundleAppAvailabilityFilter;
use Modules\ProviderManagement\Entities\Provider;
use Tests\TestCase;

class CustomerAppProviderAvailabilityTest extends TestCase
{
    private string $visibleId;

    private string $hiddenId;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('providers');
        Schema::create('providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('app_availability')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        $this->visibleId = (string) Str::uuid();
        $this->hiddenId = (string) Str::uuid();

        DB::table('providers')->insert([
            [
                'id' => $this->visibleId,
                'company_name' => 'Visible Kaergar',
                'is_active' => 1,
                'app_availability' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $this->hiddenId,
                'company_name' => 'Hidden Kaergar',
                'is_active' => 1,
                'app_availability' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_customer_api_of_status_hides_app_availability_off(): void
    {
        $this->app->instance('request', Request::create('/api/v1/customer/provider/list', 'POST'));

        $ids = Provider::query()->ofStatus(1)->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->assertContains($this->visibleId, $ids);
        $this->assertNotContains($this->hiddenId, $ids);
    }

    public function test_admin_web_of_status_still_includes_app_availability_off(): void
    {
        $this->app->instance('request', Request::create('/admin/provider', 'GET'));

        $ids = Provider::query()->ofStatus(1)->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->assertContains($this->visibleId, $ids);
        $this->assertContains($this->hiddenId, $ids);
    }

    public function test_home_bundle_filter_drops_providers_with_app_availability_off(): void
    {
        $bundle = [
            'providers' => [
                'data' => [
                    ['id' => $this->visibleId, 'company_name' => 'Visible Kaergar'],
                    ['id' => $this->hiddenId, 'company_name' => 'Hidden Kaergar'],
                ],
                'total' => 2,
            ],
            'nearby_providers' => [
                'data' => [
                    ['id' => $this->hiddenId, 'company_name' => 'Hidden Kaergar'],
                ],
                'total' => 1,
            ],
            'advertisements' => [
                'data' => [
                    ['id' => 'ad-1', 'provider_id' => $this->visibleId, 'provider' => ['id' => $this->visibleId]],
                    ['id' => 'ad-2', 'provider_id' => $this->hiddenId, 'provider' => ['id' => $this->hiddenId]],
                ],
                'total' => 2,
            ],
        ];

        $filtered = app(CustomerHomeBundleAppAvailabilityFilter::class)->apply($bundle);

        $this->assertSame([$this->visibleId], array_column($filtered['providers']['data'], 'id'));
        $this->assertSame(1, $filtered['providers']['total']);
        $this->assertSame([], $filtered['nearby_providers']['data']);
        $this->assertSame(['ad-1'], array_column($filtered['advertisements']['data'], 'id'));
    }
}
