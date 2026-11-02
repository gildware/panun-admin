<?php

namespace Modules\Auth\Services;

use App\Support\StoragePathPrefix;
use Illuminate\Support\Facades\Storage;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use setasign\Fpdi\Fpdi;

class AgreementPdfStampService
{
    /**
     * Normalized signature boxes (top-left origin) matching the default Provider Agreement layout.
     * Values: [left, top, right, bottom] as fractions of page width/height.
     */
    private const BOXES = [
        [0.680, 0.867, 0.918, 0.934], // page 1
        [0.683, 0.924, 0.921, 0.955], // page 2
        [0.683, 0.924, 0.921, 0.955], // page 3
        [0.684, 0.905, 0.922, 0.936], // page 4
        [0.684, 0.905, 0.922, 0.936], // page 5
        [0.684, 0.905, 0.922, 0.936], // page 6
        [0.684, 0.905, 0.922, 0.936], // page 7
    ];

    public const STORAGE_DIR = 'business/provider-agreement/';

    public function templatePath(): string
    {
        $uploaded = $this->resolveUploadedTemplatePath();
        if ($uploaded !== null) {
            return $uploaded;
        }

        $path = storage_path('app/agreements/PK_Revised_Agreement.pdf');
        if (is_readable($path)) {
            return $path;
        }

        $fallback = public_path('assets/admin-module/process-guide/training/PK_Revised_Agreement.pdf');
        if (is_readable($fallback)) {
            return $fallback;
        }

        throw new \RuntimeException('Service Provider Agreement PDF template is missing. Upload it from Business Settings → Providers → Provider Agreement.');
    }

    /**
     * Public URL for the agreement PDF used by the provider app / admin previews.
     */
    public function templatePublicUrl(): ?string
    {
        $filename = $this->uploadedFilename();
        if ($filename === null) {
            return null;
        }

        return getBusinessSettingsImageFullPath(
            key: 'provider_agreement_pdf',
            settingType: 'provider_config',
            path: self::STORAGE_DIR,
            defaultPath: null
        );
    }

    /**
     * Stamp [signatureAbsolutePath] into every page's "Signature Of Provider" box.
     * Returns absolute path to the generated signed PDF.
     */
    public function stamp(string $signatureAbsolutePath, ?string $outputAbsolutePath = null): string
    {
        if (! is_readable($signatureAbsolutePath)) {
            throw new \InvalidArgumentException('Signature image is not readable.');
        }

        $outputAbsolutePath ??= storage_path('app/tmp/agreement_signed_' . uniqid('', true) . '.pdf');
        $dir = dirname($outputAbsolutePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($this->templatePath());

        for ($page = 1; $page <= $pageCount; $page++) {
            $tpl = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($tpl);
            $width = (float) $size['width'];
            $height = (float) $size['height'];
            $orientation = $size['orientation'] ?? 'P';

            $pdf->AddPage($orientation, [$width, $height]);
            $pdf->useTemplate($tpl, 0, 0, $width, $height);

            $box = self::BOXES[min($page - 1, count(self::BOXES) - 1)];
            [$leftN, $topN, $rightN, $bottomN] = $box;

            $boxX = $width * $leftN;
            $boxY = $height * $topN;
            $boxW = $width * ($rightN - $leftN);
            $boxH = $height * ($bottomN - $topN);

            $insetX = max(1.5, $boxW * 0.08);
            $insetY = max(1.0, $boxH * 0.08);
            $fitW = max(1.0, $boxW - ($insetX * 2));
            $fitH = max(1.0, $boxH - ($insetY * 2));

            [$imgW, $imgH] = $this->fitImageSize($signatureAbsolutePath, $fitW, $fitH);
            $x = $boxX + $insetX + (($fitW - $imgW) / 2);
            $y = $boxY + $insetY + (($fitH - $imgH) / 2);

            $pdf->Image($signatureAbsolutePath, $x, $y, $imgW, $imgH);
        }

        $pdf->Output('F', $outputAbsolutePath);

        return $outputAbsolutePath;
    }

    private function uploadedFilename(): ?string
    {
        $setting = business_config('provider_agreement_pdf', 'provider_config');
        $value = $setting?->live_values;

        if (is_array($value)) {
            $value = $value['file'] ?? $value['path'] ?? $value['filename'] ?? null;
        }

        if (! is_string($value) || $value === '' || $value === 'def.png') {
            return null;
        }

        return $value;
    }

    private function resolveUploadedTemplatePath(): ?string
    {
        $filename = $this->uploadedFilename();
        if ($filename === null) {
            return null;
        }

        $relative = self::STORAGE_DIR . ltrim($filename, '/');
        $diskName = $this->storageDiskForSetting();

        foreach (StoragePathPrefix::keyVariants($relative) as $key) {
            try {
                if (! Storage::disk($diskName)->exists($key)) {
                    continue;
                }
            } catch (\Throwable) {
                continue;
            }

            if ($diskName === 's3') {
                $tmp = storage_path('app/tmp/provider_agreement_' . md5($key) . '.pdf');
                $tmpDir = dirname($tmp);
                if (! is_dir($tmpDir)) {
                    mkdir($tmpDir, 0775, true);
                }
                file_put_contents($tmp, Storage::disk('s3')->get($key));

                return $tmp;
            }

            return Storage::disk($diskName)->path($key);
        }

        return null;
    }

    private function storageDiskForSetting(): string
    {
        $setting = BusinessSettings::with('storage')
            ->where('key_name', 'provider_agreement_pdf')
            ->where('settings_type', 'provider_config')
            ->first();

        if ($setting?->storage?->storage_type === 's3') {
            return 's3';
        }

        return function_exists('getDisk') ? getDisk() : 'public';
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function fitImageSize(string $imagePath, float $fitW, float $fitH): array
    {
        $info = @getimagesize($imagePath);
        if (! $info || empty($info[0]) || empty($info[1])) {
            return [$fitW, $fitH * 0.45];
        }

        $aspect = $info[0] / $info[1];
        if (($fitW / $fitH) > $aspect) {
            $h = $fitH;
            $w = $fitH * $aspect;
        } else {
            $w = $fitW;
            $h = $fitW / $aspect;
        }

        return [max(1.0, $w), max(1.0, $h)];
    }
}
