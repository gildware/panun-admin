<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\CategoryManagement\Entities\Category;
use Modules\CategoryManagement\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use Modules\CategoryManagement\Http\Controllers\Web\Admin\SubCategoryController as AdminSubCategoryController;
use Modules\ServiceManagement\Entities\Service;
use Tests\TestCase;

class CustomerAppVisibilityCatalogTest extends TestCase
{
    private string $categoryId;

    private string $subCategoryId;

    private string $serviceId;

    private string $zoneId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCatalogSchema();
        $this->seedCatalog();
    }

    public function test_customer_query_hides_category_and_cascades_to_subcategory_and_service(): void
    {
        $this->asCustomerApi();

        $this->assertTrue($this->customerSeesCategory());
        $this->assertTrue($this->customerSeesSubCategory());
        $this->assertTrue($this->customerSeesService());

        DB::table('categories')->where('id', $this->categoryId)->update(['is_visible_in_customer_app' => 0]);

        $this->assertFalse($this->customerSeesCategory());
        $this->assertFalse($this->customerSeesSubCategory());
        $this->assertFalse($this->customerSeesService());
    }

    public function test_customer_query_hides_subcategory_and_cascades_to_service(): void
    {
        $this->asCustomerApi();

        $sibling = $this->insertSubcategoryWithService('sibling-sub', 'sibling-service');

        DB::table('categories')->where('id', $this->subCategoryId)->update(['is_visible_in_customer_app' => 0]);

        $this->assertTrue($this->customerSeesCategory());
        $this->assertFalse($this->customerSeesSubCategory());
        $this->assertFalse($this->customerSeesService());
        $this->assertTrue(
            Category::withoutGlobalScopes()
                ->ofStatus(1)
                ->ofType('sub')
                ->whereHas('parent', fn ($query) => $query->withoutGlobalScopes()->ofStatus(1))
                ->withActiveServices()
                ->whereKey($sibling['sub_id'])
                ->exists()
        );
        $this->assertTrue(
            Service::withoutGlobalScopes()->active()->whereKey($sibling['service_id'])->exists()
        );
    }

    public function test_customer_query_hides_service_and_empty_subcategory(): void
    {
        $this->asCustomerApi();

        $siblingServiceId = (string) Str::uuid();
        DB::table('services')->insert([
            'id' => $siblingServiceId,
            'name' => 'Sibling Service',
            'category_id' => $this->categoryId,
            'sub_category_id' => $this->subCategoryId,
            'is_active' => 1,
            'is_visible_in_customer_app' => 1,
            'slug' => 'sibling-service',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('services')->where('id', $this->serviceId)->update(['is_visible_in_customer_app' => 0]);

        $this->assertTrue($this->customerSeesCategory());
        $this->assertTrue($this->customerSeesSubCategory());
        $this->assertFalse($this->customerSeesService());
        $this->assertTrue(Service::withoutGlobalScopes()->active()->whereKey($siblingServiceId)->exists());
    }

    public function test_provider_query_still_sees_catalog_when_customer_app_visibility_is_off(): void
    {
        DB::table('categories')->update(['is_visible_in_customer_app' => 0]);
        DB::table('services')->update(['is_visible_in_customer_app' => 0]);

        $this->asProviderApi();

        $this->assertTrue(
            Category::withoutGlobalScopes()
                ->ofStatus(1)
                ->ofType('main')
                ->whereKey($this->categoryId)
                ->exists()
        );
        $this->assertTrue(
            Category::withoutGlobalScopes()
                ->ofStatus(1)
                ->ofType('sub')
                ->whereKey($this->subCategoryId)
                ->exists()
        );
        $this->assertTrue(
            Service::withoutGlobalScopes()
                ->active()
                ->whereKey($this->serviceId)
                ->exists()
        );
    }

    public function test_admin_toggles_flip_customer_app_visibility_without_changing_active(): void
    {
        $user = new \Modules\UserManagement\Entities\User();
        $user->forceFill([
            'id' => (string) Str::uuid(),
            'user_type' => 'super-admin',
            'first_name' => 'Test',
            'last_name' => 'Admin',
        ]);
        $this->actingAs($user);
        Gate::before(static fn () => true);

        $this->app->make(AdminCategoryController::class)
            ->customerAppVisibilityUpdate(Request::create('/'), $this->categoryId);
        $category = DB::table('categories')->where('id', $this->categoryId)->first();
        $this->assertSame(0, (int) $category->is_visible_in_customer_app);
        $this->assertSame(1, (int) $category->is_active);

        $this->app->make(AdminSubCategoryController::class)
            ->customerAppVisibilityUpdate(Request::create('/'), $this->subCategoryId);
        $sub = DB::table('categories')->where('id', $this->subCategoryId)->first();
        $this->assertSame(0, (int) $sub->is_visible_in_customer_app);
        $this->assertSame(1, (int) $sub->is_active);
    }

    private function asCustomerApi(): void
    {
        $this->app->instance('request', Request::create('/api/v1/customer/category', 'GET'));
        \Illuminate\Support\Facades\Config::set('zone_id', $this->zoneId);
    }

    private function asProviderApi(): void
    {
        $this->app->instance('request', Request::create('/api/v1/provider/category', 'GET'));
    }

    private function customerSeesCategory(): bool
    {
        return Category::withoutGlobalScopes()
            ->ofStatus(1)
            ->ofType('main')
            ->mainWithActiveCatalog()
            ->whereKey($this->categoryId)
            ->exists();
    }

    private function customerSeesSubCategory(): bool
    {
        return Category::withoutGlobalScopes()
            ->ofStatus(1)
            ->ofType('sub')
            ->whereHas('parent', fn ($query) => $query->withoutGlobalScopes()->ofStatus(1))
            ->withActiveServices()
            ->whereKey($this->subCategoryId)
            ->exists();
    }

    private function customerSeesService(): bool
    {
        return Service::withoutGlobalScopes()
            ->active()
            ->whereKey($this->serviceId)
            ->exists();
    }

    private function createCatalogSchema(): void
    {
        Schema::dropIfExists('category_zone');
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('translations');

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('position')->default(1);
            $table->boolean('is_active')->default(1);
            $table->boolean('is_visible_in_customer_app')->default(1);
            $table->string('slug')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('sub_category_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_visible_in_customer_app')->default(1);
            $table->string('slug')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('category_zone', function (Blueprint $table) {
            $table->id();
            $table->uuid('category_id');
            $table->uuid('zone_id');
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('translationable_type');
            $table->uuid('translationable_id');
            $table->string('locale');
            $table->string('key');
            $table->text('value')->nullable();
        });
    }

    private function seedCatalog(): void
    {
        $now = now();
        $this->categoryId = (string) Str::uuid();
        $this->subCategoryId = (string) Str::uuid();
        $this->serviceId = (string) Str::uuid();
        $this->zoneId = (string) Str::uuid();

        DB::table('zones')->insert([
            'id' => $this->zoneId,
            'name' => 'Visibility Test Zone',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('categories')->insert([
            [
                'id' => $this->categoryId,
                'parent_id' => null,
                'name' => 'Visibility Test Category',
                'position' => 1,
                'is_active' => 1,
                'is_visible_in_customer_app' => 1,
                'slug' => 'visibility-test-category',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->subCategoryId,
                'parent_id' => $this->categoryId,
                'name' => 'Visibility Test Sub',
                'position' => 2,
                'is_active' => 1,
                'is_visible_in_customer_app' => 1,
                'slug' => 'visibility-test-sub',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('category_zone')->insert([
            'category_id' => $this->categoryId,
            'zone_id' => $this->zoneId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('services')->insert([
            'id' => $this->serviceId,
            'name' => 'Visibility Test Service',
            'category_id' => $this->categoryId,
            'sub_category_id' => $this->subCategoryId,
            'is_active' => 1,
            'is_visible_in_customer_app' => 1,
            'slug' => 'visibility-test-service',
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @return array{sub_id: string, service_id: string}
     */
    private function insertSubcategoryWithService(string $subSlug, string $serviceSlug): array
    {
        $now = now();
        $subId = (string) Str::uuid();
        $serviceId = (string) Str::uuid();

        DB::table('categories')->insert([
            'id' => $subId,
            'parent_id' => $this->categoryId,
            'name' => $subSlug,
            'position' => 2,
            'is_active' => 1,
            'is_visible_in_customer_app' => 1,
            'slug' => $subSlug,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('services')->insert([
            'id' => $serviceId,
            'name' => $serviceSlug,
            'category_id' => $this->categoryId,
            'sub_category_id' => $subId,
            'is_active' => 1,
            'is_visible_in_customer_app' => 1,
            'slug' => $serviceSlug,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ['sub_id' => $subId, 'service_id' => $serviceId];
    }
}
