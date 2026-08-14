<?php

namespace App\Console\Commands;

use App\Support\CloudStorageConfigurator;
use App\Support\StoragePathPrefix;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\Finder;

class SyncPublicAssetsToR2 extends Command
{
    protected $signature = 'assets:sync-to-r2
                            {--dry-run : List files without uploading}
                            {--force : Re-upload even when the object already exists}
                            {--only= : Comma-separated top-level folders under public/assets (e.g. admin-module,landing)}
                            {--paths= : Comma-separated relative paths under public/assets (e.g. admin-module/css/foo.css)}';

    protected $description = 'Upload public/assets CSS/JS/images to Cloudflare R2 for STATIC_ASSET_URL';

    public function handle(): int
    {
        CloudStorageConfigurator::apply();

        if (! CloudStorageConfigurator::isConfigured()) {
            $this->error('R2/S3 is not configured. Set Admin → Storage Connection (or AWS_* in .env), then re-run.');

            return self::FAILURE;
        }

        $localRoot = public_path('assets');
        if (! is_dir($localRoot)) {
            $this->error('Missing directory: public/assets');

            return self::FAILURE;
        }

        $only = array_values(array_filter(array_map(
            static fn (string $part) => trim($part),
            explode(',', (string) $this->option('only'))
        )));

        $paths = array_values(array_filter(array_map(
            static fn (string $part) => trim(str_replace('\\', '/', $part), '/'),
            explode(',', (string) $this->option('paths'))
        )));

        if ($only !== [] && $paths !== []) {
            $this->error('Use either --only or --paths, not both.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($paths !== []) {
            return $this->syncExplicitPaths($localRoot, $paths, $dryRun, $force);
        }

        $finder = (new Finder)
            ->files()
            ->in($localRoot)
            ->ignoreDotFiles(true);

        if ($only !== []) {
            $finder->filter(function (\SplFileInfo $file) use ($localRoot, $only): bool {
                $absolute = str_replace('\\', '/', $file->getPathname());
                $root = rtrim(str_replace('\\', '/', $localRoot), '/').'/';
                if (! str_starts_with($absolute, $root)) {
                    return false;
                }
                $relative = substr($absolute, strlen($root));
                $top = explode('/', $relative, 2)[0] ?? '';

                return in_array($top, $only, true);
            });
        }

        $total = 0;
        $uploaded = 0;
        $skipped = 0;
        $failed = 0;

        if ($dryRun) {
            $this->warn('Dry run — no files will be uploaded.');
        }

        $prefix = StoragePathPrefix::segment();
        if ($prefix !== '') {
            $this->info('Environment folder in bucket: '.$prefix.'/');
        }

        $publicBase = rtrim((string) CloudStorageConfigurator::publicBaseUrl(), '/');
        $assetUrl = $publicBase === ''
            ? null
            : ($prefix !== '' ? $publicBase.'/'.$prefix : $publicBase);

        if ($assetUrl !== null) {
            $this->line('After sync, set on live .env:');
            $this->line('  STATIC_ASSET_URL='.$assetUrl);
            $this->line('Then: php artisan config:clear && php artisan view:clear');
            $this->newLine();
        }

        foreach ($finder as $file) {
            $total++;
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $remoteKey = StoragePathPrefix::apply('assets/'.$relativePath);

            try {
                if (! $force && Storage::disk('s3')->exists($remoteKey)) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line('Would upload: '.$remoteKey);
                    $uploaded++;

                    continue;
                }

                $stream = fopen($file->getRealPath(), 'r');
                Storage::disk('s3')->put($remoteKey, $stream, [
                    'visibility' => 'public',
                    'CacheControl' => 'public, max-age=31536000, immutable',
                ]);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                $uploaded++;
                if ($this->output->isVerbose()) {
                    $this->line('Uploaded: '.$remoteKey);
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error('Failed: '.$relativePath.' — '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Scanned: {$total}");
        $this->info($dryRun ? "Would upload: {$uploaded}" : "Uploaded: {$uploaded}");
        $this->info('Already on R2 (skipped): '.$skipped);
        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        if (! $dryRun && $uploaded > 0 && $assetUrl !== null) {
            $sample = $assetUrl.'/assets/admin-module/css/style.css';
            $this->newLine();
            $this->line('Verify in browser: '.$sample);
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  list<string>  $paths  Relative paths under public/assets
     */
    private function syncExplicitPaths(string $localRoot, array $paths, bool $dryRun, bool $force): int
    {
        if ($dryRun) {
            $this->warn('Dry run — no files will be uploaded.');
        }

        $prefix = StoragePathPrefix::segment();
        if ($prefix !== '') {
            $this->info('Environment folder in bucket: '.$prefix.'/');
        }

        $uploaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($paths as $relativePath) {
            $localPath = $localRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (! is_file($localPath)) {
                $failed++;
                $this->error('Missing local file: public/assets/'.$relativePath);

                continue;
            }

            $remoteKey = StoragePathPrefix::apply('assets/'.$relativePath);

            try {
                if (! $force && Storage::disk('s3')->exists($remoteKey)) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line('Would upload: '.$remoteKey);
                    $uploaded++;

                    continue;
                }

                $stream = fopen($localPath, 'r');
                Storage::disk('s3')->put($remoteKey, $stream, [
                    'visibility' => 'public',
                    'CacheControl' => 'public, max-age=31536000, immutable',
                ]);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                $uploaded++;
                $this->line('Uploaded: '.$remoteKey.' ('.filesize($localPath).' bytes)');
            } catch (\Throwable $e) {
                $failed++;
                $this->error('Failed: '.$relativePath.' — '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Scanned: '.count($paths));
        $this->info($dryRun ? "Would upload: {$uploaded}" : "Uploaded: {$uploaded}");
        $this->info('Already on R2 (skipped): '.$skipped);
        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
