<?php

namespace App\Console\Commands;

use App\Support\CloudStorageConfigurator;
use App\Support\StoragePathPrefix;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VerifyR2Storage extends Command
{
    protected $signature = 'storage:verify-r2';

    protected $description = 'Verify Cloudflare R2 / S3 credentials by uploading and reading a test object';

    public function handle(): int
    {
        CloudStorageConfigurator::apply();

        if (! CloudStorageConfigurator::isConfigured()) {
            $this->error('R2/S3 is not configured.');
            $this->line('Set credentials in Admin → Configuration → Storage Connection, or in .env (AWS_*).');

            return self::FAILURE;
        }

        $disk = config('filesystems.disks.s3');
        $this->info('Bucket: '.($disk['bucket'] ?? '?'));
        $this->info('Endpoint: '.($disk['endpoint'] ?? '?'));
        $this->info('Public URL base: '.($disk['url'] ?? '(not set — set AWS_URL / admin Url field)'));

        $prefix = StoragePathPrefix::segment();
        if ($prefix !== '') {
            $this->info('Environment folder: '.$prefix.'/');
        }

        $testKey = StoragePathPrefix::apply('test/r2-connection-'.now()->format('Ymd-His').'.txt');
        $payload = 'panun-kaergar-r2-check-'.uniqid();

        try {
            $this->line('Uploading test object...');
            Storage::disk('s3')->put($testKey, $payload, ['visibility' => 'public']);

            if (! Storage::disk('s3')->exists($testKey)) {
                $this->error('Upload reported success but object was not found on R2.');

                return self::FAILURE;
            }

            $publicUrl = function_exists('cloud_storage_public_url')
                ? cloud_storage_public_url($testKey)
                : Storage::disk('s3')->url($testKey);

            $this->info('Upload OK.');
            $this->line('Public URL: '.$publicUrl);

            if (function_exists('curl_init')) {
                $ch = curl_init($publicUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_NOBODY => false,
                ]);
                $body = curl_exec($ch);
                $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($status >= 200 && $status < 300 && str_contains((string) $body, $payload)) {
                    $this->info('HTTP fetch OK (status '.$status.').');
                } elseif ($status === 0) {
                    $this->warn('Could not HTTP-fetch public URL (enable R2 public access or custom domain / r2.dev URL).');
                } else {
                    $this->warn('HTTP fetch returned status '.$status.'. Enable public access on the bucket or fix the Url field.');
                }
            }

            Storage::disk('s3')->delete($testKey);
            $this->info('Cleanup OK. R2 is ready for uploads.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('R2 verification failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
