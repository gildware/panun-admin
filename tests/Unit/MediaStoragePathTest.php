<?php

namespace Tests\Unit;

use App\Support\MediaStoragePath;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;
use PHPUnit\Framework\TestCase;

class MediaStoragePathTest extends TestCase
{
    public function test_main_category_dir_from_name(): void
    {
        $this->assertSame('category/home-appliances/', MediaStoragePath::categoryDirFromName('Home Appliances', false));
    }

    public function test_sub_category_dir_from_name(): void
    {
        $this->assertSame('subcategory/air-conditioner/', MediaStoragePath::categoryDirFromName('Air Conditioner', true));
    }

    public function test_main_category_dir_from_model(): void
    {
        $category = new Category(['name' => 'Plumbing', 'parent_id' => 0, 'slug' => 'plumbing']);

        $this->assertSame('category/plumbing/', MediaStoragePath::categoryDir($category));
    }

    public function test_sub_category_dir_from_model(): void
    {
        $category = new Category;
        $category->forceFill(['name' => 'Pipe Repair', 'parent_id' => 12, 'slug' => 'pipe-repair']);

        $this->assertSame('subcategory/pipe-repair/', MediaStoragePath::categoryDir($category));
    }

    public function test_service_dir_from_name(): void
    {
        $this->assertSame('service/ac-installation/', MediaStoragePath::serviceDir('AC Installation'));
    }

    public function test_service_dir_from_model_uses_slug(): void
    {
        $service = new Service(['name' => 'Deep Cleaning', 'slug' => 'deep-cleaning']);

        $this->assertSame('service/deep-cleaning/', MediaStoragePath::serviceDir($service));
    }

    public function test_provider_section_dirs(): void
    {
        $provider = new Provider;
        $provider->forceFill(['id' => 'abc12345-uuid', 'company_name' => 'Kashmir Cool Tech']);

        $this->assertSame('provider/kashmir-cool-tech/showcase/', MediaStoragePath::providerSectionDir($provider, 'showcase'));
        $this->assertSame('provider/kashmir-cool-tech/advertisement/', MediaStoragePath::advertisementDir($provider));
    }

    public function test_customer_profile_dir_from_name(): void
    {
        $this->assertSame('customer/ali-ahmad/profile/', MediaStoragePath::customerProfileDirFromName('Ali Ahmad'));
    }

    public function test_customer_slug_falls_back_to_phone(): void
    {
        $user = new User;
        $user->forceFill(['id' => 'user-uuid-1', 'first_name' => '', 'last_name' => '', 'phone' => '+919876543210']);

        $this->assertSame('customer/customer-919876543210/profile/', MediaStoragePath::customerProfileDir($user));
    }

    public function test_sanitize_slug_handles_special_characters(): void
    {
        $this->assertSame('category/cleaning-at-123/', MediaStoragePath::categoryDirFromName('Cleaning @ 123!!!', false));
    }

    public function test_empty_name_gets_fallback_slug(): void
    {
        $this->assertSame('category/item/', MediaStoragePath::categoryDirFromName('   ', false));
    }
}
