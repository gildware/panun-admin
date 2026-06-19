<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Generates consistent catalog images (category icons, service thumbnails & covers).
 */
class CatalogImageFactory
{
    private const BRAND_PRIMARY = [0, 102, 179];

    private const BRAND_ACCENT = [0, 168, 150];

    private const BRAND_DARK = [26, 35, 50];

    /**
     * @param  array{0:int,1:int,2:int}  $from
     * @param  array{0:int,1:int,2:int}  $to
     */
    public function saveCategoryIcon(string $title, string $subtitle, string $diskDir, array $from, array $to): string
    {
        $size = 512;
        $image = imagecreatetruecolor($size, $size);
        $this->fillGradient($image, $size, $size, $from, $to);

        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocatealpha($image, 255, 255, 255, 40);
        imagefilledellipse($image, (int) ($size * 0.72), (int) ($size * 0.28), 180, 180, $muted);
        imagefilledellipse($image, (int) ($size * 0.3), (int) ($size * 0.72), 220, 220, $muted);

        $this->drawHomeApplianceGlyph($image, (int) ($size * 0.5), (int) ($size * 0.38), 120, $white);

        $this->drawCenteredText($image, $title, (int) ($size * 0.5), (int) ($size * 0.72), 28, $white, true);
        $this->drawCenteredText($image, $subtitle, (int) ($size * 0.5), (int) ($size * 0.82), 16, $white, false);

        return $this->storeWebp($image, $diskDir);
    }

    public function saveAirConditionerIcon(string $title, string $subtitle, string $diskDir): string
    {
        $size = 512;
        $image = imagecreatetruecolor($size, $size);
        $this->fillGradient($image, $size, $size, [41, 128, 185], [52, 152, 219]);

        $white = imagecolorallocate($image, 255, 255, 255);
        $ice = imagecolorallocatealpha($image, 200, 230, 255, 60);
        imagefilledellipse($image, (int) ($size * 0.75), (int) ($size * 0.25), 160, 160, $ice);

        $this->drawAcUnitGlyph($image, (int) ($size * 0.5), (int) ($size * 0.4), 150, $white);

        $this->drawCenteredText($image, $title, (int) ($size * 0.5), (int) ($size * 0.72), 26, $white, true);
        $this->drawCenteredText($image, $subtitle, (int) ($size * 0.5), (int) ($size * 0.82), 15, $white, false);

        return $this->storeWebp($image, $diskDir);
    }

    public function saveServiceThumbnail(string $title, string $glyph, string $diskDir, array $colors): string
    {
        $size = 400;
        $image = imagecreatetruecolor($size, $size);
        $this->fillGradient($image, $size, $size, $colors[0], $colors[1]);

        $white = imagecolorallocate($image, 255, 255, 255);
        $this->drawServiceGlyph($image, $glyph, (int) ($size * 0.5), (int) ($size * 0.42), 95, $white);
        $this->drawCenteredText($image, $title, (int) ($size * 0.5), (int) ($size * 0.78), 20, $white, true);

        return $this->storeWebp($image, $diskDir);
    }

    public function saveServiceCover(string $title, string $subtitle, string $glyph, string $diskDir, array $colors): string
    {
        $width = 1200;
        $height = 675;
        $image = imagecreatetruecolor($width, $height);
        $this->fillGradient($image, $width, $height, $colors[0], $colors[1]);

        $white = imagecolorallocate($image, 255, 255, 255);
        $soft = imagecolorallocatealpha($image, 255, 255, 255, 80);
        imagefilledellipse($image, 980, 120, 280, 280, $soft);
        imagefilledellipse($image, 180, 560, 320, 320, $soft);

        $this->drawServiceGlyph($image, $glyph, 900, 290, 150, $white);
        $this->drawLeftText($image, $title, 80, 250, 46, $white, true);
        $this->drawLeftText($image, $subtitle, 80, 320, 22, $white, false);
        $this->drawLeftText($image, 'Panun Kaergar', 80, 560, 18, $white, false);

        return $this->storeWebp($image, $diskDir);
    }

    /**
     * @param  resource  $image
     * @param  array{0:int,1:int,2:int}  $from
     * @param  array{0:int,1:int,2:int}  $to
     */
    private function fillGradient($image, int $width, int $height, array $from, array $to): void
    {
        for ($y = 0; $y < $height; $y++) {
            $ratio = $height > 1 ? $y / ($height - 1) : 0;
            $r = (int) ($from[0] + ($to[0] - $from[0]) * $ratio);
            $g = (int) ($from[1] + ($to[1] - $from[1]) * $ratio);
            $b = (int) ($from[2] + ($to[2] - $from[2]) * $ratio);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
    }

    /** @param  resource  $image */
    private function drawHomeApplianceGlyph($image, int $cx, int $cy, int $size, int $color): void
    {
        $w = (int) ($size * 1.1);
        $h = (int) ($size * 0.75);
        imagefilledrectangle($image, $cx - (int) ($w / 2), $cy - (int) ($h / 2), $cx + (int) ($w / 2), $cy + (int) ($h / 2), $color);
        imagefilledrectangle($image, $cx - (int) ($w * 0.35), $cy - (int) ($h * 0.55), $cx + (int) ($w * 0.35), $cy - (int) ($h * 0.15), imagecolorallocate($image, 41, 128, 185));
        imagefilledellipse($image, $cx - (int) ($w * 0.25), $cy + (int) ($h * 0.15), 28, 28, imagecolorallocate($image, 41, 128, 185));
        imagefilledellipse($image, $cx + (int) ($w * 0.25), $cy + (int) ($h * 0.15), 28, 28, imagecolorallocate($image, 41, 128, 185));
    }

    /** @param  resource  $image */
    private function drawAcUnitGlyph($image, int $cx, int $cy, int $size, int $color): void
    {
        $w = $size;
        $h = (int) ($size * 0.55);
        imagefilledrectangle($image, $cx - (int) ($w / 2), $cy - (int) ($h / 2), $cx + (int) ($w / 2), $cy + (int) ($h / 2), $color);
        $vent = imagecolorallocate($image, 52, 152, 219);
        for ($i = 0; $i < 5; $i++) {
            $y = $cy - (int) ($h * 0.2) + ($i * 12);
            imageline($image, $cx - (int) ($w * 0.35), $y, $cx + (int) ($w * 0.35), $y, $vent);
        }
        imagefilledellipse($image, $cx, $cy + (int) ($h * 0.75), 18, 18, $color);
        imageline($image, $cx, $cy + (int) ($h * 0.75), $cx, $cy + (int) ($h * 1.2), $color);
    }

    /** @param  resource  $image */
    private function drawServiceGlyph($image, string $glyph, int $cx, int $cy, int $size, int $color): void
    {
        match ($glyph) {
            'repair' => $this->drawRepairGlyph($image, $cx, $cy, $size, $color),
            'install' => $this->drawInstallGlyph($image, $cx, $cy, $size, $color),
            'uninstall' => $this->drawUninstallGlyph($image, $cx, $cy, $size, $color),
            'service' => $this->drawServiceCleanGlyph($image, $cx, $cy, $size, $color),
            'gas' => $this->drawGasGlyph($image, $cx, $cy, $size, $color),
            default => $this->drawAcUnitGlyph($image, $cx, $cy, $size, $color),
        };
    }

    /** @param  resource  $image */
    private function drawRepairGlyph($image, int $cx, int $cy, int $size, int $color): void
    {
        $this->drawAcUnitGlyph($image, $cx, $cy - 20, (int) ($size * 0.85), $color);
        imageline($image, $cx - 40, $cy + 55, $cx + 55, $cy - 10, $color);
        imagefilledellipse($image, $cx + 60, $cy - 15, 22, 22, $color);
    }

    /** @param  resource  $image */
    private function drawInstallGlyph($image, int $cx, int $cy, int $size, int $color): void
    {
        $this->drawAcUnitGlyph($image, $cx, $cy + 25, (int) ($size * 0.8), $color);
        $points = [
            $cx, $cy - (int) ($size * 0.45),
            $cx - 30, $cy - (int) ($size * 0.1),
            $cx + 30, $cy - (int) ($size * 0.1),
        ];
        imagefilledpolygon($image, $points, $color);
    }

    /** @param  resource  $image */
    private function drawUninstallGlyph($image, int $cx, int $cy, int $size, int $color): void
    {
        $this->drawAcUnitGlyph($image, $cx, $cy - 15, (int) ($size * 0.8), $color);
        $points = [
            $cx, $cy + (int) ($size * 0.42),
            $cx - 30, $cy + (int) ($size * 0.05),
            $cx + 30, $cy + (int) ($size * 0.05),
        ];
        imagefilledpolygon($image, $points, $color);
    }

    /** @param  resource  $image */
    private function drawServiceCleanGlyph($image, int $cx, int $cy, int $size, int $color): void
    {
        $this->drawAcUnitGlyph($image, $cx - 15, $cy, (int) ($size * 0.75), $color);
        imagefilledellipse($image, $cx + 55, $cy + 35, 40, 40, $color);
        imageline($image, $cx + 75, $cy + 55, $cx + 105, $cy + 85, $color);
    }

    /** @param  resource  $image */
    private function drawGasGlyph($image, int $cx, int $cy, int $size, int $color): void
    {
        $this->drawAcUnitGlyph($image, $cx - 35, $cy, (int) ($size * 0.7), $color);
        imagefilledrectangle($image, $cx + 15, $cy - 45, $cx + 55, $cy + 45, $color);
        imagefilledellipse($image, $cx + 35, $cy - 55, 28, 28, $color);
    }

    /** @param  resource  $image */
    private function drawCenteredText($image, string $text, int $cx, int $y, int $fontSize, int $color, bool $bold): void
    {
        $font = $this->fontPath();
        if ($font && function_exists('imagettfbbox')) {
            $bbox = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = abs($bbox[2] - $bbox[0]);
            imagettftext($image, $fontSize, 0, (int) ($cx - $textWidth / 2), $y, $color, $font, $text);

            return;
        }

        $textWidth = imagefontwidth(5) * strlen($text);
        imagestring($image, $bold ? 5 : 3, (int) ($cx - $textWidth / 2), $y - 10, $text, $color);
    }

    /** @param  resource  $image */
    private function drawLeftText($image, string $text, int $x, int $y, int $fontSize, int $color, bool $bold): void
    {
        $font = $this->fontPath();
        if ($font && function_exists('imagettftext')) {
            imagettftext($image, $fontSize, 0, $x, $y, $color, $font, $text);

            return;
        }

        imagestring($image, $bold ? 5 : 3, $x, $y - 10, $text, $color);
    }

    private function fontPath(): ?string
    {
        $candidates = [
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /** @param  resource  $image */
    private function storeWebp($image, string $diskDir): string
    {
        $disk = function_exists('getDisk') ? getDisk() : 'public';
        $diskDir = rtrim($diskDir, '/') . '/';
        $filename = now()->toDateString() . '-' . uniqid() . '.webp';
        $absolute = storage_path("app/{$disk}/{$diskDir}{$filename}");

        if (! Storage::disk($disk)->exists($diskDir)) {
            Storage::disk($disk)->makeDirectory($diskDir);
        }

        imagewebp($image, $absolute, 82);
        imagedestroy($image);

        return $filename;
    }
}
