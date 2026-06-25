<?php

namespace App\Console\Commands;

use App\Support\CloudStorageConfigurator;
use App\Support\StoragePathPrefix;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class SyncLocalStorageToR2 extends Command
{
    protected $signature = 'storage:sync-to-r2
                            {--dry-run : List files without uploading}
                            {--delete-local : Remove local copies after successful upload}';

    protected $description = 'Copy existing files from storage/app/public to Cloudflare R2 (keeps folder structure)';

    public function handle(): int
    {
        CloudStorageConfigurator::apply();

        if (! CloudStorageConfigurator::isConfigured()) {
            $this->error('R2/S3 is not configured. Set credentials first, then re-run.');

            return self::FAILURE;
        }

        $localRoot = storage_path('app/public');
        if (! is_dir($localRoot)) {
            $this->warn('No local public storage directory found.');

            return self::SUCCESS;
        }

        $finder = (new Finder)
            ->files()
            ->in($localRoot)
            ->ignoreDotFiles(true);

        $total = 0;
        $uploaded = 0;
        $skipped = 0;
        $failed = 0;
        $dryRun = (bool) $this->option('dry-run');
        $deleteLocal = (bool) $this->option('delete-local');

        if ($dryRun) {
            $this->warn('Dry run — no files will be uploaded.');
        }

        $prefix = StoragePathPrefix::segment();
        if ($prefix !== '') {
            $this->info('Environment folder in bucket: '.$prefix.'/');
        }

        foreach ($finder as $file) {
            $total++;
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $remoteKey = StoragePathPrefix::apply($relativePath);

            try {
                if (Storage::disk('s3')->exists($remoteKey)) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line('Would upload: '.$remoteKey);
                    $uploaded++;

                    continue;
                }

                $stream = fopen($file->getRealPath(), 'r');
                Storage::disk('s3')->put($remoteKey, $stream, ['visibility' => 'public']);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                $uploaded++;

                if ($deleteLocal) {
                    File::delete($file->getRealPath());
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error('Failed: '.$relativePath.' — '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Scanned: {$total}");
        $this->info($dryRun ? "Would upload: {$uploaded}" : "Uploaded: {$uploaded}");
        $this->info("Already on R2: {$skipped}");
        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        if (! $dryRun && $uploaded > 0) {
            $this->line('Switch Admin → Storage Connection → 3rd Party Storage when ready.');
            $this->line('Run: php artisan storage:verify-r2');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
