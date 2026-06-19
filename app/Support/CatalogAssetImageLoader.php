<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Loads catalog images from bundled seed assets and stores them as webp on the app disk.
 */
class CatalogAssetImageLoader
{
    private string $assetsRoot;

    public function __construct(?string $assetsRoot = null)
    {
        $this->assetsRoot = $assetsRoot ?? database_path('seeders/assets/home-appliances-ac');
    }

    public function publish(string $assetFilename, string $diskDir): string
    {
        $source = $this->assetsRoot . '/' . $assetFilename;

        if (! is_readable($source)) {
            throw new \RuntimeException("Catalog asset not found: {$assetFilename}");
        }

        $disk = function_exists('getDisk') ? getDisk() : 'public';
        $diskDir = rtrim($diskDir, '/') . '/';
        $filename = now()->toDateString() . '-' . uniqid() . '.webp';
        $absolute = storage_path("app/{$disk}/{$diskDir}{$filename}");

        if (! Storage::disk($disk)->exists($diskDir)) {
            Storage::disk($disk)->makeDirectory($diskDir);
        }

        $this->convertToWebp($source, $absolute);

        return $filename;
    }

    public function publicUrl(string $diskDir, string $filename): string
    {
        $disk = function_exists('getDisk') ? getDisk() : 'public';

        if ($disk === 's3') {
            return Storage::disk($disk)->url(rtrim($diskDir, '/') . '/' . $filename);
        }

        return url('storage/' . rtrim($diskDir, '/') . '/' . $filename);
    }

    private function convertToWebp(string $source, string $destination): void
    {
        $info = @getimagesize($source);
        if (! $info) {
            throw new \RuntimeException("Invalid image asset: {$source}");
        }

        $mime = strtolower($info['mime']);
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($source),
            'image/png' => imagecreatefrompng($source),
            'image/webp' => imagecreatefromwebp($source),
            default => null,
        };

        if (! $image) {
            throw new \RuntimeException("Unsupported image type for asset: {$source}");
        }

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $maxSize = 2500;
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxSize || $height > $maxSize) {
            $ratio = min($maxSize / $width, $maxSize / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        imagewebp($image, $destination, 82);
        imagedestroy($image);
    }
}
