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

    /**
     * Pending subscribe/unsubscribe actions keyed by sub_category_id.
     *
     * @return array<string, 'subscribe'|'unsubscribe'>
     */
    public function pendingSubscriptionActions(string $providerId): array
    {
        $request = ProviderChangeRequest::where('provider_id', $providerId)
            ->where('change_type', 'services')
            ->where('status', ProviderChangeRequest::STATUS_PENDING)
            ->latest()
            ->first();

        if (! $request) {
            return [];
        }

        $actions = [];
        foreach ($this->subscriptionChangesFromPayload($providerId, $request->payload ?? []) as $change) {
            $actions[$change['sub_category_id']] = $change['action'];
        }

        return $actions;
    }

    /**
     * @param  array<string, 'subscribe'|'unsubscribe'>  $pendingActions
     */
    public function applySubscriptionPendingFlags(object $item, string $subCategoryId, array $pendingActions): void
    {
        if (isset($pendingActions[$subCategoryId])) {
            $item->subscription_pending = 1;
            $item->pending_subscription_action = $pendingActions[$subCategoryId];
        } else {
            $item->subscription_pending = 0;
            $item->pending_subscription_action = null;
        }
    }

    public function submit(string $providerId, string $changeType, array $payload): ProviderChangeRequest
    {
        if ($changeType === 'services') {
            return $this->submitServicesChange($providerId, $payload);
        }

        ProviderChangeRequest::where('provider_id', $providerId)
            ->where('change_type', $changeType)
            ->where('status', ProviderChangeRequest::STATUS_PENDING)
            ->update(['status' => ProviderChangeRequest::STATUS_DENIED]);

        $changeRequest = ProviderChangeRequest::create([
            'provider_id' => $providerId,
            'change_type' => $changeType,
            'status' => ProviderChangeRequest::STATUS_PENDING,
            'payload' => $payload,
        ]);

        if (function_exists('admin_inbox_notify_profile_change_request')) {
            admin_inbox_notify_profile_change_request($changeRequest);
        }

        return $changeRequest;
    }

    /**
     * @return array<int, array{sub_category_id: string, action: 'subscribe'|'unsubscribe'}>
     */
    public function subscriptionChangesFromPayload(string $providerId, array $payload): array
    {
        if (! empty($payload['subscription_changes']) && is_array($payload['subscription_changes'])) {
            return collect($payload['subscription_changes'])
                ->filter(fn ($change) => is_array($change) && ! empty($change['sub_category_id']))
                ->map(function ($change) {
                    $action = ($change['action'] ?? 'subscribe') === 'unsubscribe' ? 'unsubscribe' : 'subscribe';

                    return [
                        'sub_category_id' => (string) $change['sub_category_id'],
                        'action' => $action,
                    ];
                })
                ->values()
                ->all();
        }

        $ids = $payload['sub_category_id'] ?? [];
        if (! is_array($ids) || $ids === []) {
            return [];
        }

        $changes = [];
        foreach ($ids as $id) {
            $id = (string) $id;
            if ($id === '') {
                continue;
            }

            $changes[] = [
                'sub_category_id' => $id,
                'action' => $this->resolveIntendedSubscriptionAction($providerId, $id),
            ];
        }

        return $changes;
    }

    private function submitServicesChange(string $providerId, array $payload): ProviderChangeRequest
    {
        $incomingIds = $payload['sub_category_id'] ?? [];
        if (! is_array($incomingIds)) {
            $incomingIds = [];
        }

        $existing = ProviderChangeRequest::where('provider_id', $providerId)
            ->where('change_type', 'services')
            ->where('status', ProviderChangeRequest::STATUS_PENDING)
            ->first();

        $changes = $existing
            ? $this->subscriptionChangesFromPayload($providerId, $existing->payload ?? [])
            : [];

        foreach ($incomingIds as $id) {
            $id = (string) $id;
            if ($id === '') {
                continue;
            }

            $action = $this->resolveIntendedSubscriptionAction($providerId, $id);
            $changes = $this->mergeSubscriptionChange($changes, $id, $action);
        }

        if ($existing) {
            if ($changes === []) {
                $existing->status = ProviderChangeRequest::STATUS_DENIED;
                $existing->save();

                return $existing;
            }

            $existing->payload = $this->buildServicesPayload($changes);
            $existing->touch();
            $existing->save();

            if (function_exists('admin_inbox_notify_profile_change_request')) {
                admin_inbox_notify_profile_change_request($existing);
            }

            return $existing;
        }

        $changeRequest = ProviderChangeRequest::create([
            'provider_id' => $providerId,
            'change_type' => 'services',
            'status' => ProviderChangeRequest::STATUS_PENDING,
            'payload' => $this->buildServicesPayload($changes),
        ]);

        if (function_exists('admin_inbox_notify_profile_change_request')) {
            admin_inbox_notify_profile_change_request($changeRequest);
        }

        return $changeRequest;
    }

    /**
     * @param  array<int, array{sub_category_id: string, action: 'subscribe'|'unsubscribe'}>  $changes
     * @return array{subscription_changes: array<int, array{sub_category_id: string, action: 'subscribe'|'unsubscribe'}>, sub_category_id: array<int, string>}
     */
    private function buildServicesPayload(array $changes): array
    {
        return [
            'subscription_changes' => $changes,
            'sub_category_id' => array_column($changes, 'sub_category_id'),
        ];
    }

    /**
     * @param  array<int, array{sub_category_id: string, action: 'subscribe'|'unsubscribe'}>  $changes
     * @return array<int, array{sub_category_id: string, action: 'subscribe'|'unsubscribe'}>
     */
    private function mergeSubscriptionChange(array $changes, string $subCategoryId, string $action): array
    {
        $keyed = [];
        foreach ($changes as $change) {
            $keyed[$change['sub_category_id']] = $change['action'];
        }

        if (isset($keyed[$subCategoryId])) {
            if ($keyed[$subCategoryId] === $action) {
                return $changes;
            }

            unset($keyed[$subCategoryId]);
        } else {
            $keyed[$subCategoryId] = $action;
        }

        return collect($keyed)
            ->map(fn ($itemAction, $id) => [
                'sub_category_id' => (string) $id,
                'action' => $itemAction,
            ])
            ->values()
            ->all();
    }

    private function resolveIntendedSubscriptionAction(string $providerId, string $subCategoryId): string
    {
        $row = SubscribedService::where('sub_category_id', $subCategoryId)
            ->where('provider_id', $providerId)
            ->first();

        return ($row && (int) $row->is_subscribed === 1) ? 'unsubscribe' : 'subscribe';
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

    /**
     * Apply only approved service subscription rows from a pending request.
     *
     * @param  array<int, string>  $approvedSubCategoryIds
     * @return array{approved_count: int, denied_count: int, denied_names: array<int, string>}
     */
    public function reviewServicesChange(ProviderChangeRequest $changeRequest, array $approvedSubCategoryIds): array
    {
        $provider = $changeRequest->provider()->firstOrFail();
        $allChanges = $this->subscriptionChangesFromPayload($provider->id, $changeRequest->payload ?? []);
        $approvedSet = array_flip(array_map('strval', $approvedSubCategoryIds));

        $toApply = array_values(array_filter(
            $allChanges,
            fn (array $change) => isset($approvedSet[$change['sub_category_id']])
        ));
        $denied = array_values(array_filter(
            $allChanges,
            fn (array $change) => ! isset($approvedSet[$change['sub_category_id']])
        ));

        if ($toApply !== []) {
            $this->applyServices($provider, $this->buildServicesPayload($toApply));
        }

        $categoryNames = Category::whereIn('id', array_column($denied, 'sub_category_id'))
            ->pluck('name', 'id');

        return [
            'approved_count' => count($toApply),
            'denied_count' => count($denied),
            'denied_names' => array_map(
                fn (array $change) => $categoryNames->get($change['sub_category_id']) ?? $change['sub_category_id'],
                $denied
            ),
        ];
    }

    /**
     * @return array<int, array{sub_category_id: string, action: 'subscribe'|'unsubscribe'}>
     */
    public function pendingServiceChangesForRequest(ProviderChangeRequest $changeRequest): array
    {
        if ($changeRequest->change_type !== 'services') {
            return [];
        }

        $providerId = (string) $changeRequest->provider_id;

        return $this->subscriptionChangesFromPayload($providerId, $changeRequest->payload ?? []);
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

        $previousLeafIds = collect($provider->coveredLeafZoneIds())->sort()->values()->all();
        $newLeafIds = collect($leafZoneIds)->sort()->values()->all();
        $zonesChanged = $previousLeafIds !== $newLeafIds;

        if ($zonesChanged) {
            $this->unsubscribeSubCategoriesLostOnZoneRemoval(
                (string) $provider->id,
                $previousLeafIds,
                $newLeafIds
            );
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
        if ($zonesChanged) {
            $provider->zone_id = $leafZoneIds[0] ?? $provider->zone_id;
        }
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

        DB::transaction(function () use ($provider, $owner, $leafZoneIds, $zonesChanged) {
            if ($zonesChanged) {
                $owner->zones()->sync($leafZoneIds);
            }
            $owner->save();
            $provider->save();
            if ($zonesChanged) {
                $provider->zones()->sync(
                    collect($leafZoneIds)->mapWithKeys(fn (string $zid) => [$zid => []])->all()
                );
            }
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
        $subscriptionChanges = $this->subscriptionChangesFromPayload($provider->id, $payload);
        if ($subscriptionChanges === []) {
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

        foreach ($subscriptionChanges as $change) {
            $id = $change['sub_category_id'];
            $action = $change['action'];
            $row = $subscribedService::where('sub_category_id', $id)->where('provider_id', $providerId)->first();

            if ($action === 'subscribe') {
                if ($row && (int) $row->is_subscribed === 1) {
                    continue;
                }

                if (! $row) {
                    if ($packageSubscriberLimit <= $categoryCount && $packageSubscriber && $isLimit && $isPackageEnded) {
                        continue;
                    }
                    $row = new SubscribedService();
                    $row->is_subscribed = 1;
                } elseif ($row->is_subscribed == 0) {
                    if ($packageSubscriberLimit <= $categoryCount && $packageSubscriber && $isLimit && $isPackageEnded) {
                        continue;
                    }
                    $row->is_subscribed = 1;
                }
            } else {
                if (! $row || (int) $row->is_subscribed === 0) {
                    continue;
                }
                $row->is_subscribed = 0;
            }

            $row->provider_id = $providerId;
            $row->sub_category_id = $id;
            $parent = $category->where('id', $id)->first();
            if ($parent) {
                $row->category_id = $parent->parent_id;
            }
            $row->save();

            if ((int) $row->is_subscribed === 1) {
                $categoryCount++;
            } elseif ($categoryCount > 0) {
                $categoryCount--;
            }
        }
    }

    /**
     * When provider zones shrink, unsubscribe only sub-categories whose parent category
     * is not available in any remaining zone. Adding zones does not change subscriptions.
     *
     * @param  array<int, string>  $previousZoneIds
     * @param  array<int, string>  $remainingZoneIds
     */
    private function unsubscribeSubCategoriesLostOnZoneRemoval(
        string $providerId,
        array $previousZoneIds,
        array $remainingZoneIds
    ): void {
        $previous = collect($previousZoneIds)->map(fn ($id) => (string) $id)->unique()->values();
        $remaining = collect($remainingZoneIds)->map(fn ($id) => (string) $id)->unique()->values();

        if ($previous->diff($remaining)->isEmpty()) {
            return;
        }

        if ($remaining->isEmpty()) {
            DB::table('subscribed_services')->where('provider_id', $providerId)->update(['is_subscribed' => 0]);

            return;
        }

        $remainingZoneIds = $remaining->all();

        $subscribed = SubscribedService::query()
            ->where('provider_id', $providerId)
            ->where('is_subscribed', 1)
            ->get(['id', 'category_id']);

        if ($subscribed->isEmpty()) {
            return;
        }

        $parentCategoryIds = $subscribed->pluck('category_id')->filter()->unique()->values()->all();
        if ($parentCategoryIds === []) {
            return;
        }

        $parentsById = Category::query()
            ->withoutGlobalScope('translate')
            ->withoutGlobalScope('zone_wise_data')
            ->whereIn('id', $parentCategoryIds)
            ->with(['zones' => fn ($query) => $query->withoutGlobalScope('translate')->select('zones.id')])
            ->get()
            ->keyBy('id');

        $idsToUnsubscribe = [];

        foreach ($subscribed as $row) {
            $parent = $parentsById->get($row->category_id);
            if (! $parent) {
                continue;
            }

            $parentZoneIds = $parent->zones
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $stillAvailable = array_intersect($parentZoneIds, $remainingZoneIds) !== [];

            if (! $stillAvailable) {
                $idsToUnsubscribe[] = $row->id;
            }
        }

        if ($idsToUnsubscribe !== []) {
            SubscribedService::query()
                ->whereIn('id', $idsToUnsubscribe)
                ->update(['is_subscribed' => 0]);
        }
    }
}
