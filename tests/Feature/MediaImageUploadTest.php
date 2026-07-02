<?php

namespace Tests\Feature;

use App\Support\MediaStoragePath;
use App\Support\StoragePathPrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\CategoryManagement\Entities\Category;
use Tests\TestCase;

class MediaImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('APPLICATION_IMAGE_FORMAT')) {
            define('APPLICATION_IMAGE_FORMAT', 'webp');
        }
    }

    public function test_category_image_upload_to_local_public_disk(): void
    {
        $this->forceStorageDisk('public');
        Storage::fake('public');

        $file = UploadedFile::fake()->image('category.jpg', 400, 400);
        $storedKey = media_file_uploader(
            MediaStoragePath::categoryDirFromName('Home Appliances', false),
            APPLICATION_IMAGE_FORMAT,
            $file
        );

        $this->assertStringStartsWith('category/home-appliances/', $storedKey);
        $this->assertStringEndsWith('.webp', $storedKey);

        $prefixedKey = StoragePathPrefix::apply($storedKey);
        Storage::disk('public')->assertExists($prefixedKey);

        $url = resolve_media_storage_url($storedKey, '', null, 'fallback');
        $this->assertNotSame('fallback', $url);
    }

    public function test_category_image_upload_to_s3_disk_with_env_prefix(): void
    {
        $this->forceStorageDisk('s3');
        Storage::fake('s3');
        putenv('STORAGE_PATH_PREFIX=dev');
        StoragePathPrefix::resetCache();

        $file = UploadedFile::fake()->image('category.png', 300, 300);
        $storedKey = media_file_uploader(
            MediaStoragePath::categoryDirFromName('Electrical', false),
            APPLICATION_IMAGE_FORMAT,
            $file
        );

        $prefixedKey = StoragePathPrefix::apply($storedKey);
        $this->assertStringStartsWith('dev/category/electrical/', $prefixedKey);
        Storage::disk('s3')->assertExists($prefixedKey);

        config(['filesystems.disks.s3.url' => 'https://pub-example.r2.dev']);
        $url = resolve_media_storage_url($storedKey);
        $this->assertStringStartsWith('https://pub-example.r2.dev/dev/category/electrical/', $url);
    }

    public function test_sub_category_image_upload(): void
    {
        $this->forceStorageDisk('public');
        Storage::fake('public');

        $file = UploadedFile::fake()->image('sub.jpg');
        $storedKey = media_file_uploader(
            MediaStoragePath::categoryDirFromName('AC Repair', true),
            APPLICATION_IMAGE_FORMAT,
            $file
        );

        $this->assertStringStartsWith('subcategory/ac-repair/', $storedKey);
        Storage::disk('public')->assertExists(StoragePathPrefix::apply($storedKey));
    }

    public function test_service_thumbnail_upload(): void
    {
        $this->forceStorageDisk('public');
        Storage::fake('public');

        $file = UploadedFile::fake()->image('thumb.jpg');
        $storedKey = media_file_uploader(
            MediaStoragePath::serviceDir('AC Installation'),
            APPLICATION_IMAGE_FORMAT,
            $file
        );

        $this->assertStringStartsWith('service/ac-installation/', $storedKey);
        Storage::disk('public')->assertExists(StoragePathPrefix::apply($storedKey));
    }

    public function test_category_update_replaces_old_image(): void
    {
        $this->forceStorageDisk('public');
        Storage::fake('public');

        $category = new Category;
        $category->name = 'Plumbing';
        $category->image = 'category/plumbing/old-file.webp';
        $category->parent_id = 0;
        $category->position = 1;
        $category->is_active = 1;
        $category->save();

        Storage::disk('public')->put(StoragePathPrefix::apply($category->image), 'old');

        $file = UploadedFile::fake()->image('new.jpg');
        $newKey = media_file_uploader(
            MediaStoragePath::categoryDir($category),
            APPLICATION_IMAGE_FORMAT,
            $file,
            $category->image
        );

        $this->assertStringStartsWith('category/plumbing/', $newKey);
        Storage::disk('public')->assertExists(StoragePathPrefix::apply($newKey));
        Storage::disk('public')->assertMissing(StoragePathPrefix::apply($category->image));
    }

    public function test_legacy_flat_category_filename_still_resolves(): void
    {
        $this->forceStorageDisk('public');
        Storage::fake('public');

        $legacyFile = '2026-06-25-legacy.webp';
        Storage::disk('public')->put(
            StoragePathPrefix::apply('category/'.$legacyFile),
            'bytes'
        );

        $url = resolve_media_storage_url($legacyFile, 'category/');
        $this->assertStringContainsString($legacyFile, $url);
    }

    public function test_unprefixed_s3_provider_identity_file_resolves_with_exact_object_key(): void
    {
        $this->forceStorageDisk('s3');
        Storage::fake('s3');
        putenv('STORAGE_PATH_PREFIX=local');
        StoragePathPrefix::resetCache();

        $filename = '2026-07-02-test-identity.webp';
        $objectKey = 'provider/identity/'.$filename;
        Storage::disk('s3')->put($objectKey, 'bytes');

        config(['filesystems.disks.s3.url' => 'https://pub-example.r2.dev']);

        $url = resolve_media_storage_url($filename, 'provider/identity/', 's3', null, true);

        $this->assertSame('https://pub-example.r2.dev/'.$objectKey, $url);
    }

    public function test_media_storage_delete_removes_prefixed_object(): void
    {
        $this->forceStorageDisk('public');
        Storage::fake('public');

        $key = 'category/test/delete-me.webp';
        Storage::disk('public')->put(StoragePathPrefix::apply($key), 'x');

        media_storage_delete($key);

        Storage::disk('public')->assertMissing(StoragePathPrefix::apply($key));
    }

    private function forceStorageDisk(string $disk): void
    {
        BusinessSettings::query()->updateOrCreate(
            ['key_name' => 'storage_connection_type', 'settings_type' => 'storage_settings'],
            [
                'live_values' => $disk === 's3' ? 's3' : 'local',
                'test_values' => $disk === 's3' ? 's3' : 'local',
                'mode' => 'live',
                'is_active' => 1,
            ]
        );

        if ($disk === 's3') {
            config([
                'filesystems.disks.s3' => [
                    'driver' => 's3',
                    'key' => 'test',
                    'secret' => 'test',
                    'region' => 'auto',
                    'bucket' => 'test-bucket',
                    'url' => 'https://pub-example.r2.dev',
                    'endpoint' => 'https://example.r2.cloudflarestorage.com',
                    'use_path_style_endpoint' => true,
                    'throw' => false,
                ],
            ]);
        }
    }
}
