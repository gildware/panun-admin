<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderChangeRequest;
use Carbon\Carbon;
use Modules\BusinessSettingsModule\Entities\PackageSubscriber;
use Modules\BusinessSettingsModule\Entities\PackageSubscriberLimit;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\ProviderSetting;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ZoneManagement\Services\ZoneCoverageNormalizationService;
use Modules\UserManagement\Entities\User;

class ProviderProfileChangeRequestService
{
    public function hasPending(string $providerId): bool
    {
        return ProviderChangeRequest::where('provider_id', $providerId)
            ->where('status', ProviderChangeRequest::STATUS_PENDING)
            ->exists();
    }

    public function submit(string $providerId, string $changeType, array $payload): ProviderChangeRequest
    {
        ProviderChangeRequest::where('provider_id', $providerId)
            ->where('change_type', $changeType)
            ->where('status', ProviderChangeRequest::STATUS_PENDING)
            ->update(['status' => ProviderChangeRequest::STATUS_DENIED]);

        return ProviderChangeRequest::create([
            'provider_id' => $providerId,
            'change_type' => $changeType,
            'status' => ProviderChangeRequest::STATUS_PENDING,
            'payload' => $payload,
        ]);
    }

    /**
     * Public URLs for images in the pending branding change request (for provider app preview).
     *
     * @return array{logo_url: ?string, cover_url: ?string}
     */
    public function pendingBrandingPreviewUrls(string $providerId): array
    {
        $request = ProviderChangeRequest::where('provider_id', $providerId)
            ->where('change_type', 'branding')
            ->where('status', ProviderChangeRequest::STATUS_PENDING)
            ->latest()
            ->first();

        if (! $request) {
            return ['logo_url' => null, 'cover_url' => null];
        }

        $payload = $request->payload ?? [];

        return [
            'logo_url' => ! empty($payload['logo'])
                ? resolve_media_storage_url((string) $payload['logo'], 'provider/logo/')
                : null,
            'cover_url' => ! empty($payload['cover_image'])
                ? resolve_media_storage_url((string) $payload['cover_image'], 'provider/logo/')
                : null,
        ];
    }

    public function buildProfilePayload(Request $request, Provider $provider, User $owner): array
    {
        $providerType = $provider->provider_type ?? 'company';
        $leafZoneIds = app(ZoneCoverageNormalizationService::class)->normalizeToLeafZoneIds(
            $request->input('zone_ids', []),
            $request->input('zone_excluded_ids', []) ?: []
        );

        $payload = [
            'provider_type' => $providerType,
            'leaf_zone_ids' => $leafZoneIds,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_phone' => $request->contact_person_phone,
            'contact_person_email' => $request->filled('contact_person_email') ? $request->contact_person_email : null,
            'company_name' => $request->company_name,
            'company_phone' => $request->company_phone,
            'company_email' => $request->filled('company_email') ? $request->company_email : null,
            'company_address' => $request->company_address,
            'street' => $request->filled('street') ? trim((string) $request->street) : null,
            'city' => $request->filled('city') ? trim((string) $request->city) : null,
            'pincode' => $request->filled('pincode') ? trim((string) $request->pincode) : null,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'identity_type' => $request->identity_type,
            'identity_number' => $request->identity_number,
            'deleted_identity_images' => is_string($request->deleted_identity_images)
                ? json_decode($request->deleted_identity_images, true)
                : ($request->deleted_identity_images ?? []),
            'company_identity_type' => $request->company_identity_type,
            'company_identity_number' => $request->company_identity_number,
            'deleted_company_identity_images' => is_string($request->deleted_company_identity_images)
                ? json_decode($request->deleted_company_identity_images, true)
                : ($request->deleted_company_identity_images ?? []),
            'password' => $request->filled('password') ? $request->password : null,
        ];

        if ($request->hasFile('contact_person_photo')) {
            $payload['contact_person_photo'] = file_uploader(
                'provider/contact_person_photo/',
                APPLICATION_IMAGE_FORMAT,
                $request->file('contact_person_photo')
            );
        }

        $newIdentityImages = [];
        foreach ($request->uploaded_identity_images ?? [] as $image) {
            $newIdentityImages[] = [
                'image' => file_uploader('provider/identity/', APPLICATION_IMAGE_FORMAT, $image),
                'storage' => getDisk(),
            ];
        }
        $payload['new_identity_images'] = $newIdentityImages;

        $newCompanyImages = [];
        foreach ($request->uploaded_company_identity_images ?? [] as $image) {
            $newCompanyImages[] = [
                'image' => file_uploader('provider/company-identity/', APPLICATION_IMAGE_FORMAT, $image),
                'storage' => getDisk(),
            ];
        }
        $payload['new_company_identity_images'] = $newCompanyImages;

        return $payload;
    }

    public function buildBrandingPayload(Request $request): array
    {
        $payload = [];

        if ($request->hasFile('logo')) {
            $payload['logo'] = file_uploader('provider/logo/', APPLICATION_IMAGE_FORMAT, $request->file('logo'));
        }

        if ($request->hasFile('cover_image')) {
            $payload['cover_image'] = file_uploader('provider/logo/', APPLICATION_IMAGE_FORMAT, $request->file('cover_image'));
        }

        return $payload;
    }

    public function apply(ProviderChangeRequest $changeRequest): void
    {
        $provider = $changeRequest->provider()->with('owner')->firstOrFail();
        $owner = $provider->owner;

        match ($changeRequest->change_type) {
            'profile' => $this->applyProfile($provider, $owner, $changeRequest->payload),
            'branding' => $this->applyBranding($provider, $changeRequest->payload),
            'business_settings' => $this->applyBusinessSettings($provider, $changeRequest->payload),
            'services' => $this->applyServices($provider, $changeRequest->payload),
            default => null,
        };
    }

    public function applyBranding(Provider $provider, array $payload): void
    {
        if (!empty($payload['logo'])) {
            $provider->logo = $payload['logo'];
        }
        if (!empty($payload['cover_image'])) {
            $provider->cover_image = $payload['cover_image'];
        }
        $provider->save();
    }

    private function applyProfile(Provider $provider, User $owner, array $payload): void
    {
        $providerType = $payload['provider_type'] ?? 'company';
        $leafZoneIds = $payload['leaf_zone_ids'] ?? [];

        $previousLeafIds = $provider->zones()->pluck('zones.id')->sort()->values()->all();
        if ($previousLeafIds !== collect($leafZoneIds)->sort()->values()->all()) {
            DB::table('subscribed_services')->where('provider_id', $provider->id)->update(['is_subscribed' => 0]);
        }

        if ($providerType === 'company') {
            $provider->company_name = $payload['company_name'];
            $provider->company_phone = $payload['company_phone'];
            if (!empty($payload['company_email'])) {
                $provider->company_email = $payload['company_email'];
            }
        } else {
            $provider->company_name = $payload['contact_person_name'];
            $provider->company_phone = $payload['contact_person_phone'];
            $provider->company_email = $payload['contact_person_email'] ?? null;
        }

        if (!empty($payload['contact_person_photo'])) {
            $provider->contact_person_photo = $payload['contact_person_photo'];
        }
        $provider->company_address = $payload['company_address'];
        $provider->street = $payload['street'] ?? null;
        $provider->city = $payload['city'] ?? null;
        $provider->pincode = $payload['pincode'] ?? null;
        $provider->contact_person_name = $payload['contact_person_name'];
        $provider->contact_person_phone = $payload['contact_person_phone'];
        $provider->contact_person_email = $payload['contact_person_email'] ?? null;
        $provider->zone_id = $leafZoneIds[0] ?? $provider->zone_id;
        $provider->coordinates = [
            'latitude' => $payload['latitude'],
            'longitude' => $payload['longitude'],
        ];

        if (!empty($payload['contact_person_email'])) {
            $owner->email = $payload['contact_person_email'];
        }
        $owner->phone = $payload['contact_person_phone'];
        $owner->is_phone_verified = 1;
        if (!empty($payload['password'])) {
            $owner->password = bcrypt($payload['password']);
        }

        $existingImages = is_string($owner->identification_image)
            ? json_decode($owner->identification_image, true)
            : ($owner->identification_image ?? []);
        $deletedImages = $payload['deleted_identity_images'] ?? [];
        $filteredImages = [];

        foreach ($existingImages as $item) {
            if (is_string($item)) {
                if (in_array($item, $deletedImages)) {
                    file_remover('provider/identity', $item);
                    continue;
                }
                $filteredImages[] = ['image' => $item, 'storage' => getDisk()];
            } elseif (is_array($item) && isset($item['image'])) {
                if (in_array($item['image'], $deletedImages)) {
                    file_remover('provider/identity', $item);
                    continue;
                }
                $filteredImages[] = $item;
            }
        }
        foreach ($payload['new_identity_images'] ?? [] as $item) {
            $filteredImages[] = $item;
        }

        $owner->identification_image = array_values($filteredImages);
        $owner->identification_number = $payload['identity_number'];
        $owner->identification_type = $payload['identity_type'];

        if ($providerType === 'company') {
            $existingCompanyImages = is_array($provider->company_identity_images) ? $provider->company_identity_images : [];
            $deletedCompanyImages = $payload['deleted_company_identity_images'] ?? [];
            $filteredCompanyImages = [];

            foreach ($existingCompanyImages as $item) {
                $fileName = is_string($item) ? $item : ($item['image'] ?? null);
                if ($fileName && in_array($fileName, $deletedCompanyImages, true)) {
                    file_remover('provider/company-identity/', $fileName);
                    continue;
                }
                if (is_string($item)) {
                    $filteredCompanyImages[] = ['image' => $item, 'storage' => getDisk()];
                } elseif (is_array($item) && isset($item['image'])) {
                    $filteredCompanyImages[] = $item;
                }
            }
            foreach ($payload['new_company_identity_images'] ?? [] as $item) {
                $filteredCompanyImages[] = $item;
            }

            $provider->company_identity_type = $payload['company_identity_type'];
            $provider->company_identity_number = $payload['company_identity_number'];
            if ($filteredCompanyImages !== [] || !empty($payload['company_identity_type'])) {
                $provider->company_identity_images = array_values($filteredCompanyImages);
            }
        }

        DB::transaction(function () use ($provider, $owner, $leafZoneIds) {
            $owner->zones()->sync($leafZoneIds);
            $owner->save();
            $provider->save();
            $provider->zones()->sync(
                collect($leafZoneIds)->mapWithKeys(fn (string $zid) => [$zid => []])->all()
            );
        });
    }

    private function applyBusinessSettings(Provider $provider, array $payload): void
    {
        $data = $payload['data'] ?? [];
        $serviceLocation = [];
        $providerSetting = app(ProviderSetting::class);

        foreach ($data as $item) {
            $key = $item['key'] ?? null;
            $value = $item['value'] ?? null;
            $settingType = in_array($key, ['provider_serviceman_can_edit_booking', 'provider_serviceman_can_cancel_booking'])
                ? 'serviceman_config'
                : null;

            if ($settingType) {
                $providerSetting->updateOrCreate(
                    ['key_name' => $key, 'provider_id' => $provider->id],
                    [
                        'key_name' => $key,
                        'live_values' => $value,
                        'test_values' => $value,
                        'settings_type' => $settingType,
                        'mode' => 'live',
                        'is_active' => 1,
                    ]
                );
            }

            if ($key === 'customer_location' && $value == '1') {
                $serviceLocation[] = 'customer';
            }
            if ($key === 'provider_location' && $value == '1') {
                $serviceLocation[] = 'provider';
            }
        }

        if (!empty($serviceLocation)) {
            $providerSetting->updateOrCreate(
                ['key_name' => 'service_location', 'provider_id' => $provider->id, 'settings_type' => 'provider_config'],
                [
                    'key_name' => 'service_location',
                    'live_values' => json_encode($serviceLocation),
                    'test_values' => json_encode($serviceLocation),
                    'settings_type' => 'provider_config',
                    'mode' => 'live',
                    'is_active' => 1,
                ]
            );
        }
    }

    private function applyServices(Provider $provider, array $payload): void
    {
        $subCategoryIds = $payload['sub_category_id'] ?? [];
        if ($subCategoryIds === []) {
            return;
        }

        $subscribedService = app(SubscribedService::class);
        $category = app(Category::class);
        $packageSubscriber = PackageSubscriber::where('provider_id', $provider->id)->first();
        $limit = PackageSubscriberLimit::where('provider_id', $provider->id)
            ->where('subscription_package_id', $packageSubscriber?->subscription_package_id)
            ->where('key', 'category')
            ->first();

        $packageSubscriberLimit = $limit?->limit_count;
        $isLimit = $limit?->is_limited;
        $endDate = $packageSubscriber?->package_end_date;
        $providerId = $provider->id;
        $currentDate = Carbon::now()->subDays();
        $packageEndDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        $isPackageEnded = $packageEndDate ? $currentDate->diffInDays($packageEndDate, false) : null;

        $categoryCount = $subscribedService->where('provider_id', $providerId)->where('is_subscribed', 1)->count();

        foreach ($subCategoryIds as $id) {
            $row = $subscribedService::where('sub_category_id', $id)->where('provider_id', $providerId)->first();
            if (!$row) {
                if ($packageSubscriberLimit <= $categoryCount && $packageSubscriber && $isLimit && $isPackageEnded) {
                    continue;
                }
                $row = new SubscribedService();
                $row->is_subscribed = 1;
            } elseif ($row->is_subscribed == 0) {
                if ($packageSubscriberLimit <= $categoryCount && $packageSubscriber && $isLimit && $isPackageEnded) {
                    continue;
                }
                $row->is_subscribed = !$row->is_subscribed;
            } else {
                $row->is_subscribed = !$row->is_subscribed;
            }

            $row->provider_id = $providerId;
            $row->sub_category_id = $id;
            $parent = $category->where('id', $id)->first();
            if ($parent) {
                $row->category_id = $parent->parent_id;
            }
            $row->save();
        }
    }
}
