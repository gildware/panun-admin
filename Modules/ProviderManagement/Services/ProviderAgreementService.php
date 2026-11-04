<?php

namespace Modules\ProviderManagement\Services;

use App\Support\StoragePathPrefix;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Services\AgreementPdfStampService;
use Modules\ProviderManagement\Entities\Provider;

class ProviderAgreementService
{
    public const DOCUMENT_NAME = 'Service Provider Agreement';

    public function hasSigned(Provider $provider): bool
    {
        return DB::table('providers_additional_documents')
            ->where('provider_id', $provider->id)
            ->where('document_name', self::DOCUMENT_NAME)
            ->exists();
    }

    /**
     * @return array{has_signed: bool, signed_at: ?string, signed_pdf_url: ?string, signature_url: ?string, template_pdf_url: ?string}
     */
    public function statusFor(Provider $provider): array
    {
        $doc = DB::table('providers_additional_documents')
            ->where('provider_id', $provider->id)
            ->where('document_name', self::DOCUMENT_NAME)
            ->first();

        $signedPdfUrl = null;
        $signatureUrl = null;
        $signedAt = null;

        if ($doc) {
            $signedAt = isset($doc->created_at) ? (string) $doc->created_at : null;
            $files = DB::table('providers_additional_document_files')
                ->where('document_id', $doc->id)
                ->get();

            foreach ($files as $file) {
                $path = (string) ($file->file_path ?? '');
                if ($path === '') {
                    continue;
                }

                $disk = (string) ($file->storage ?? 'public');
                $normalizedPath = str_contains($path, '/')
                    ? $path
                    : 'provider/additional-documents/' . $doc->id . '/' . $path;
                $ext = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
                $url = $this->resolveFileUrl($normalizedPath, $disk);

                if ($ext === 'pdf' && $signedPdfUrl === null) {
                    $signedPdfUrl = $url;
                } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true) && $signatureUrl === null) {
                    $signatureUrl = $url;
                }
            }
        }

        return [
            'has_signed' => $doc !== null,
            'signed_at' => $signedAt,
            'signed_pdf_url' => $signedPdfUrl,
            'signature_url' => $signatureUrl,
            'template_pdf_url' => app(AgreementPdfStampService::class)->templatePublicUrl(),
        ];
    }

    /**
     * Stamp and persist the agreement for a logged-in provider.
     *
     * @return array{has_signed: bool, signed_at: ?string, signed_pdf_url: ?string, signature_url: ?string, template_pdf_url: ?string}
     */
    public function sign(Provider $provider, UploadedFile $signature): array
    {
        if ($this->hasSigned($provider)) {
            return $this->statusFor($provider);
        }

        $tmpSignature = storage_path('app/tmp/agreement_signature_' . uniqid('', true) . '.' . ($signature->getClientOriginalExtension() ?: 'png'));
        $tmpDir = dirname($tmpSignature);
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        file_put_contents($tmpSignature, file_get_contents($signature->getRealPath()));

        $documentId = (string) Str::uuid();
        $destinationDir = 'provider/additional-documents/' . $documentId;
        $fileRows = [];

        try {
            $tmpSignedPdf = app(AgreementPdfStampService::class)->stamp($tmpSignature);
            $pdfFileName = $this->storeLocalFile($tmpSignedPdf, $destinationDir, 'pdf');
            if ($pdfFileName) {
                $fileRows[] = $pdfFileName;
            }
            @unlink($tmpSignedPdf);
        } catch (\Throwable $e) {
            @unlink($tmpSignature);
            report($e);
            throw $e;
        }

        $sigExt = strtolower($signature->getClientOriginalExtension() ?: 'png');
        if (! in_array($sigExt, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $sigExt = 'png';
        }
        $sigFileName = $this->storeLocalFile($tmpSignature, $destinationDir, $sigExt);
        if ($sigFileName) {
            $fileRows[] = $sigFileName;
        }
        @unlink($tmpSignature);

        if ($fileRows === []) {
            throw new \RuntimeException('Failed to store signed agreement files.');
        }

        DB::table('providers_additional_documents')->insert([
            'id' => $documentId,
            'provider_id' => $provider->id,
            'document_name' => self::DOCUMENT_NAME,
            'document_description' => 'Signed in provider app on ' . now()->toIso8601String(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($fileRows as $fileName) {
            DB::table('providers_additional_document_files')->insert([
                'id' => (string) Str::uuid(),
                'document_id' => $documentId,
                'file_path' => $fileName,
                'storage' => getDisk(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->statusFor($provider);
    }

    private function storeLocalFile(string $absolutePath, string $destinationDir, string $format): ?string
    {
        if (! is_readable($absolutePath)) {
            return null;
        }

        $disk = getDisk();
        $dir = StoragePathPrefix::apply(rtrim($destinationDir, '/') . '/');
        $fileName = now()->toDateString() . '-' . uniqid() . '.' . $format;

        try {
            if (! Storage::disk($disk)->exists($dir)) {
                Storage::disk($disk)->makeDirectory($dir);
            }
            Storage::disk($disk)->put($dir . $fileName, file_get_contents($absolutePath));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        return $fileName;
    }

    private function resolveFileUrl(string $normalizedPath, string $disk): ?string
    {
        if (Str::startsWith($normalizedPath, ['http://', 'https://'])) {
            return $normalizedPath;
        }

        if ($disk === 's3') {
            try {
                $storageKey = StoragePathPrefix::apply(StoragePathPrefix::strip($normalizedPath));

                return Storage::disk('s3')->temporaryUrl($storageKey, now()->addMinutes(60));
            } catch (\Throwable) {
                // fall through
            }
        }

        $url = resolve_media_storage_url($normalizedPath, '', $disk, null, false);

        return is_string($url) && $url !== '' ? $url : null;
    }
}
