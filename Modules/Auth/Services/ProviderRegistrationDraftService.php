<?php

namespace Modules\Auth\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ProviderManagement\Entities\ProviderRegistrationDraft;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

use function getDisk;

class ProviderRegistrationDraftService
{
    public const STEPS_INDIVIDUAL = [
        'provider_type',
        'contact_info',
        'identity_verification',
        'service_areas',
        'current_address',
        'service_categories',
        'service_subcategories',
        'review',
    ];

    public const STEPS_COMPANY = [
        'provider_type',
        'contact_info',
        'identity_verification',
        'company_information',
        'company_documents',
        'service_areas',
        'current_address',
        'service_categories',
        'service_subcategories',
        'review',
    ];

    public function findOrCreateForPhone(string $phone): ProviderRegistrationDraft
    {
        $phone = trim($phone);
        $draft = ProviderRegistrationDraft::query()->where('phone', $phone)->first();

        if ($draft) {
            $draft->expires_at = Carbon::now()->addDays(30);
            $draft->save();

            return $draft;
        }

        return ProviderRegistrationDraft::query()->create([
            'phone' => $phone,
            'registration_token' => Str::random(48),
            'current_step' => 'provider_type',
            'completed_steps' => [],
            'form_data' => ['file_paths' => []],
            'expires_at' => Carbon::now()->addDays(30),
        ]);
    }

    public function saveStep(ProviderRegistrationDraft $draft, Request $request): ProviderRegistrationDraft
    {
        $step = (string) $request->input('step');
        $formData = is_array($draft->form_data) ? $draft->form_data : [];
        $filePaths = is_array($formData['file_paths'] ?? null) ? $formData['file_paths'] : [];

        $scalarFields = [
            'provider_type',
            'contact_person_name',
            'contact_person_phone',
            'contact_person_email',
            'identity_type',
            'identity_number',
            'company_name',
            'company_phone',
            'company_email',
            'company_identity_type',
            'company_identity_number',
            'company_address',
            'street',
            'city',
            'state',
            'pincode',
            'latitude',
            'longitude',
        ];

        foreach ($scalarFields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                $formData[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        if ($request->has('zone_ids')) {
            $zoneIds = $request->input('zone_ids');
            $formData['zone_ids'] = is_array($zoneIds) ? array_values(array_filter($zoneIds)) : [];
        }

        if ($request->has('selected_category_ids')) {
            $ids = $request->input('selected_category_ids');
            $formData['selected_category_ids'] = is_array($ids) ? array_values(array_filter($ids)) : [];
        }

        if ($request->has('subscribed_sub_category_ids')) {
            $ids = $request->input('subscribed_sub_category_ids');
            $formData['subscribed_sub_category_ids'] = is_array($ids) ? array_values(array_filter($ids)) : [];
        } elseif ($request->has('selected_service_keys')) {
            $keys = $request->input('selected_service_keys');
            $formData['subscribed_sub_category_ids'] = is_array($keys) ? array_values(array_filter($keys)) : [];
        }

        if ($request->filled('provider_type')) {
            $draft->provider_type = strtolower((string) $request->input('provider_type'));
        }

        $baseDir = 'provider/registration-drafts/' . $draft->id;
        $disk = Storage::disk('public');

        if ($request->hasFile('contact_person_photo')) {
            $filePaths['contact_person_photo'] = $this->storeDraftFile(
                $disk,
                $baseDir,
                $request->file('contact_person_photo'),
                'contact_person_photo'
            );
        }

        if ($request->hasFile('logo')) {
            $filePaths['logo'] = $this->storeDraftFile($disk, $baseDir, $request->file('logo'), 'logo');
        }

        if ($request->hasFile('identity_image_front')) {
            $filePaths['identity_image_front'] = $this->storeDraftFile(
                $disk,
                $baseDir,
                $request->file('identity_image_front'),
                'identity_front'
            );
        }

        if ($request->hasFile('identity_image_back')) {
            $filePaths['identity_image_back'] = $this->storeDraftFile(
                $disk,
                $baseDir,
                $request->file('identity_image_back'),
                'identity_back'
            );
        }

        if ($request->hasFile('identity_images')) {
            $existing = is_array($filePaths['identity_images'] ?? null) ? $filePaths['identity_images'] : [];
            foreach ($request->file('identity_images') as $index => $image) {
                if ($image) {
                    $existing[$index] = $this->storeDraftFile($disk, $baseDir, $image, 'identity_' . $index);
                }
            }
            $filePaths['identity_images'] = array_values($existing);
        }

        $legacyIdentityImages = is_array($filePaths['identity_images'] ?? null) ? $filePaths['identity_images'] : [];
        $identityFront = $filePaths['identity_image_front'] ?? ($legacyIdentityImages[0] ?? null);
        $identityBack = $filePaths['identity_image_back'] ?? ($legacyIdentityImages[1] ?? null);
        if ($identityFront) {
            $filePaths['identity_image_front'] = $identityFront;
        }
        if ($identityBack) {
            $filePaths['identity_image_back'] = $identityBack;
        }
        if ($identityFront || $identityBack) {
            $filePaths['identity_images'] = array_values(array_filter([$identityFront, $identityBack]));
        }

        if ($request->hasFile('company_identity_image')) {
            $filePaths['company_identity_images'] = [
                $this->storeDraftFile(
                    $disk,
                    $baseDir,
                    $request->file('company_identity_image'),
                    'company_identity_0'
                ),
            ];
        } elseif ($request->hasFile('company_identity_images')) {
            $stored = [];
            $images = $request->file('company_identity_images');
            $images = is_array($images) ? $images : [$images];
            foreach ($images as $index => $image) {
                if ($image) {
                    $stored[] = $this->storeDraftFile($disk, $baseDir, $image, 'company_identity_' . $index);
                }
            }
            if ($stored !== []) {
                $filePaths['company_identity_images'] = array_slice($stored, 0, 1);
            }
        }

        $formData['file_paths'] = $filePaths;
        $draft->form_data = $formData;

        $completed = is_array($draft->completed_steps) ? $draft->completed_steps : [];
        if ($step !== '' && ! in_array($step, $completed, true)) {
            $completed[] = $step;
        }
        $draft->completed_steps = $completed;

        $flow = $this->flowFor($draft->provider_type ?? ($formData['provider_type'] ?? 'individual'));
        $nextStep = $this->nextStep($flow, $step);
        if ($nextStep !== null) {
            $draft->current_step = $nextStep;
        }

        $draft->expires_at = Carbon::now()->addDays(30);
        $draft->save();

        return $draft->fresh();
    }

    public function toApiPayload(ProviderRegistrationDraft $draft): array
    {
        $formData = is_array($draft->form_data) ? $draft->form_data : [];
        $filePaths = is_array($formData['file_paths'] ?? null) ? $formData['file_paths'] : [];

        return [
            'registration_token' => $draft->registration_token,
            'phone' => $draft->phone,
            'provider_type' => $draft->provider_type ?? ($formData['provider_type'] ?? 'individual'),
            'current_step' => $draft->current_step,
            'completed_steps' => $draft->completed_steps ?? [],
            'form_data' => $formData,
            'files' => $this->fileUrls($filePaths),
        ];
    }

    public function deleteByToken(?string $token): void
    {
        if (! $token) {
            return;
        }

        $draft = ProviderRegistrationDraft::query()->where('registration_token', $token)->first();
        if (! $draft) {
            return;
        }

        $baseDir = 'provider/registration-drafts/' . $draft->id;
        try {
            Storage::disk('public')->deleteDirectory($baseDir);
        } catch (\Throwable $e) {
            report($e);
        }

        $draft->delete();
    }

    /**
     * Attach draft scalar fields and stored files to the final registration request.
     */
    public function mergeDraftIntoRegistrationRequest(Request $request, ProviderRegistrationDraft $draft): void
    {
        $formData = is_array($draft->form_data) ? $draft->form_data : [];
        unset($formData['file_paths']);

        foreach ($formData as $key => $value) {
            if ($key === 'selected_service_keys') {
                if (! $request->has('subscribed_sub_category_ids')) {
                    $request->merge(['subscribed_sub_category_ids' => $value]);
                }
                continue;
            }
            if ($key === 'zone_ids' || $key === 'subscribed_sub_category_ids') {
                if (! $request->has($key)) {
                    $request->merge([$key => $value]);
                }
                continue;
            }
            if (! $request->filled($key) && $value !== null && $value !== '') {
                $request->merge([$key => $value]);
            }
        }

        if (! $request->filled('provider_type') && $draft->provider_type) {
            $request->merge(['provider_type' => $draft->provider_type]);
        }

        $filePaths = is_array($draft->form_data['file_paths'] ?? null) ? $draft->form_data['file_paths'] : [];
        $this->attachStoredFile($request, 'contact_person_photo', $filePaths['contact_person_photo'] ?? null);
        $this->attachStoredFile($request, 'logo', $filePaths['logo'] ?? null);

        if (! $request->hasFile('identity_images')) {
            $files = [];
            if (! $request->hasFile('identity_image_front') && ! empty($filePaths['identity_image_front'])) {
                $uploaded = $this->uploadedFileFromPublicPath($filePaths['identity_image_front']);
                if ($uploaded) {
                    $request->files->set('identity_image_front', $uploaded);
                    $files[] = $uploaded;
                }
            } elseif ($request->hasFile('identity_image_front')) {
                $files[] = $request->file('identity_image_front');
            }

            if (! $request->hasFile('identity_image_back') && ! empty($filePaths['identity_image_back'])) {
                $uploaded = $this->uploadedFileFromPublicPath($filePaths['identity_image_back']);
                if ($uploaded) {
                    $request->files->set('identity_image_back', $uploaded);
                    $files[] = $uploaded;
                }
            } elseif ($request->hasFile('identity_image_back')) {
                $files[] = $request->file('identity_image_back');
            }

            if ($files === [] && ! empty($filePaths['identity_images'])) {
                foreach ($filePaths['identity_images'] as $path) {
                    $uploaded = $this->uploadedFileFromPublicPath($path);
                    if ($uploaded) {
                        $files[] = $uploaded;
                    }
                }
            }

            if ($files !== []) {
                $request->files->set('identity_images', $files);
            }
        }

        if (! $request->hasFile('company_identity_images') && ! $request->hasFile('company_identity_image')) {
            $paths = is_array($filePaths['company_identity_images'] ?? null)
                ? $filePaths['company_identity_images']
                : [];
            if ($paths !== []) {
                $uploaded = $this->uploadedFileFromPublicPath($paths[0]);
                if ($uploaded) {
                    $request->files->set('company_identity_image', $uploaded);
                }
            }
        }
    }

    private function flowFor(string $providerType): array
    {
        return strtolower($providerType) === 'company' ? self::STEPS_COMPANY : self::STEPS_INDIVIDUAL;
    }

    private function nextStep(array $flow, string $completedStep): ?string
    {
        $index = array_search($completedStep, $flow, true);
        if ($index === false) {
            return $flow[0] ?? null;
        }

        return $flow[$index + 1] ?? 'review';
    }

    private function storeDraftFile($disk, string $baseDir, UploadedFile $file, string $prefix): string
    {
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $name = $prefix . '_' . Str::random(8) . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $relative = $baseDir . '/' . $name;
        $disk->put($relative, file_get_contents($file->getRealPath()));

        return $relative;
    }

    private function fileUrls(array $filePaths): array
    {
        $urls = [];
        foreach ($filePaths as $key => $path) {
            if (is_array($path)) {
                $urls[$key] = array_map(fn ($p) => $this->publicUrl($p), $path);
            } else {
                $urls[$key] = $this->publicUrl($path);
            }
        }

        return $urls;
    }

    private function publicUrl(string $relativePath): string
    {
        return asset('storage/' . ltrim($relativePath, '/'));
    }

    public function getDraftFilePath(ProviderRegistrationDraft $draft, string $field): ?string
    {
        $filePaths = is_array($draft->form_data['file_paths'] ?? null) ? $draft->form_data['file_paths'] : [];
        $path = $filePaths[$field] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function draftFileExists(ProviderRegistrationDraft $draft, string $field): bool
    {
        $path = $this->getDraftFilePath($draft, $field);

        return $path !== null && Storage::disk('public')->exists($path);
    }

    /**
     * @return list<string>
     */
    public function draftIdentityFilePaths(ProviderRegistrationDraft $draft): array
    {
        $filePaths = is_array($draft->form_data['file_paths'] ?? null) ? $draft->form_data['file_paths'] : [];
        $paths = [];

        foreach (['identity_image_front', 'identity_image_back'] as $key) {
            $path = $filePaths[$key] ?? null;
            if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
                $paths[] = $path;
            }
        }

        if (count($paths) >= 2) {
            return $paths;
        }

        $legacy = is_array($filePaths['identity_images'] ?? null) ? $filePaths['identity_images'] : [];
        $paths = [];
        foreach ($legacy as $path) {
            if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    public function draftCompanyIdentityFilePaths(ProviderRegistrationDraft $draft): array
    {
        $filePaths = is_array($draft->form_data['file_paths'] ?? null) ? $draft->form_data['file_paths'] : [];
        $legacy = is_array($filePaths['company_identity_images'] ?? null) ? $filePaths['company_identity_images'] : [];
        $paths = [];
        foreach ($legacy as $path) {
            if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function copyDraftFileToProviderStorage(string $relativePath, string $destinationDir, string $format): ?string
    {
        $relativePath = ltrim($relativePath, '/');
        if (! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        $disk = getDisk();
        $dir = rtrim($destinationDir, '/') . '/';
        $imageName = now()->toDateString() . '-' . uniqid() . '.' . $format;

        try {
            if (! Storage::disk($disk)->exists($dir)) {
                Storage::disk($disk)->makeDirectory($dir);
            }
            Storage::disk($disk)->put(
                $dir . $imageName,
                Storage::disk('public')->get($relativePath)
            );
        } catch (\Throwable) {
            return null;
        }

        return $imageName;
    }

    private function attachStoredFile(Request $request, string $field, ?string $relativePath): void
    {
        if ($request->hasFile($field) || ! $relativePath) {
            return;
        }

        $uploaded = $this->uploadedFileFromPublicPath($relativePath);
        if ($uploaded) {
            $request->files->set($field, $uploaded);
        }
    }

    private function uploadedFileFromPublicPath(string $relativePath): ?UploadedFile
    {
        $relativePath = ltrim($relativePath, '/');
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            return null;
        }

        $full = $disk->path($relativePath);
        if (! is_readable($full)) {
            return null;
        }

        try {
            $symfonyFile = new SymfonyUploadedFile(
                $full,
                basename($full),
                mime_content_type($full) ?: 'application/octet-stream',
                UPLOAD_ERR_OK,
                true
            );

            return UploadedFile::createFromBase($symfonyFile);
        } catch (\Throwable) {
            return null;
        }
    }
}
