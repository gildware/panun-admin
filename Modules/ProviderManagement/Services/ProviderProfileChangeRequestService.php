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

        $existing = ProviderChangeRequest::where('provider_id', $providerId)
            ->where('change_type', $changeType)
            ->where('status', ProviderChangeRequest::STATUS_PENDING)
            ->first();

        if ($existing) {
            $mergedPayload = $this->mergePendingChangePayload($changeType, $existing->payload ?? [], $payload);
            $existing->payload = $mergedPayload;
            $existing->touch();
            $existing->save();

            if (function_exists('admin_inbox_notify_profile_change_request')) {
                admin_inbox_notify_profile_change_request($existing);
            }

            return $existing;
        }

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
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergePendingChangePayload(string $changeType, array $existing, array $incoming): array
    {
        if ($changeType === 'business_settings') {
            return $this->mergeBusinessSettingsPayload($existing, $incoming);
        }

        return $this->mergeScalarPayload($existing, $incoming);
    }

    /**
     * Merge top-level payload keys (branding, profile, etc.) without replacing unrelated pending fields.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeScalarPayload(array $existing, array $incoming): array
    {
        $reviews = is_array($existing['field_reviews'] ?? null) ? $existing['field_reviews'] : [];
        $merged = array_merge($existing, $incoming);

        foreach (array_keys($incoming) as $key) {
            if ($key === 'field_reviews') {
                continue;
            }

            unset($reviews[$key]);
        }

        if ($reviews !== []) {
            $merged['field_reviews'] = $reviews;
        } else {
            unset($merged['field_reviews']);
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeBusinessSettingsPayload(array $existing, array $incoming): array
    {
        $existingByKey = collect($existing['data'] ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['key']))
            ->mapWithKeys(fn ($item) => [(string) $item['key'] => $item]);

        foreach ($incoming['data'] ?? [] as $item) {
            if (! is_array($item) || empty($item['key'])) {
                continue;
            }

            $existingByKey[(string) $item['key']] = $item;
        }

        $reviews = is_array($existing['field_reviews'] ?? null) ? $existing['field_reviews'] : [];
        foreach ($incoming['data'] ?? [] as $item) {
            if (! is_array($item) || empty($item['key'])) {
                continue;
            }

            unset($reviews[(string) $item['key']]);
        }

        $merged = $existing;
        $merged['data'] = $existingByKey->values()->all();

        if ($reviews !== []) {
            $merged['field_reviews'] = $reviews;
        } else {
            unset($merged['field_reviews']);
        }

        return $merged;
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
            $payload['logo'] = media_file_uploader('provider/logo/', APPLICATION_IMAGE_FORMAT, $request->file('logo'));
        }

        if ($request->hasFile('cover_image')) {
            $payload['cover_image'] = media_file_uploader('provider/logo/', APPLICATION_IMAGE_FORMAT, $request->file('cover_image'));
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
        return $this->reviewFieldChanges($changeRequest, $approvedSubCategoryIds);
    }

    /**
     * @return array<int, string>
     */
    public function pendingFieldChangesForRequest(ProviderChangeRequest $changeRequest): array
    {
        return collect(app(ProviderProfileChangeDiffService::class)->build($changeRequest))
            ->pluck('field_key')
            ->filter()
            ->map(fn ($key) => (string) $key)
            ->values()
            ->all();
    }

    /**
     * Review one pending field immediately.
     *
     * @return array{
     *     field_key: string,
     *     field_label: string,
     *     approved: bool,
     *     remaining_count: int,
     *     request_closed: bool,
     *     message_key: 'profile_change_approve'|'profile_change_deny'
     * }
     */
    public function reviewSingleField(
        ProviderChangeRequest $changeRequest,
        string $fieldKey,
        bool $approved,
        ?string $reviewedBy = null
    ): array {
        $changeRequest->loadMissing('provider.owner');
        $pendingKeys = $this->pendingFieldChangesForRequest($changeRequest);

        if (! in_array($fieldKey, $pendingKeys, true)) {
            throw new \InvalidArgumentException('Field is not pending review.');
        }

        $labelsByKey = collect(app(ProviderProfileChangeDiffService::class)->build($changeRequest))
            ->mapWithKeys(fn (array $change) => [(string) $change['field_key'] => $change['field']]);
        $changeRow = collect(app(ProviderProfileChangeDiffService::class)->build($changeRequest))
            ->firstWhere('field_key', $fieldKey);

        if ($approved) {
            $this->applyPartial($changeRequest, [$fieldKey]);
        }

        $payload = $changeRequest->payload ?? [];
        $payload['field_reviews'] = is_array($payload['field_reviews'] ?? null) ? $payload['field_reviews'] : [];
        $payload['field_reviews'][$fieldKey] = [
            'decision' => $approved ? 'approve' : 'deny',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now()->toIso8601String(),
            'field_label' => (string) ($changeRow['field'] ?? $labelsByKey->get($fieldKey) ?? $fieldKey),
            'from' => (string) ($changeRow['from'] ?? ''),
            'to' => (string) ($changeRow['to'] ?? ''),
            'type' => $changeRow['type'] ?? null,
            'from_url' => $changeRow['from_url'] ?? null,
            'to_url' => $changeRow['to_url'] ?? null,
        ];
        $this->removeReviewedFieldFromPayload((string) $changeRequest->change_type, $payload, $fieldKey);

        $changeRequest->payload = $payload;
        $changeRequest->save();
        $changeRequest->refresh();

        $remainingKeys = $this->pendingFieldChangesForRequest($changeRequest);
        $requestClosed = $remainingKeys === [];

        if ($requestClosed) {
            $this->finalizeReviewedChangeRequest($changeRequest, $reviewedBy, $labelsByKey);
            $changeRequest->refresh();
        }

        return [
            'field_key' => $fieldKey,
            'field_label' => (string) ($labelsByKey->get($fieldKey) ?? $fieldKey),
            'approved' => $approved,
            'remaining_count' => count($remainingKeys),
            'request_closed' => $requestClosed,
            'message_key' => $approved ? 'profile_change_approve' : 'profile_change_deny',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<string, string>  $labelsByKey
     */
    private function finalizeReviewedChangeRequest(
        ProviderChangeRequest $changeRequest,
        ?string $reviewedBy,
        $labelsByKey
    ): void {
        $reviews = is_array($changeRequest->payload['field_reviews'] ?? null)
            ? $changeRequest->payload['field_reviews']
            : [];

        $approvedCount = 0;
        $deniedNames = [];

        foreach ($reviews as $key => $review) {
            if (! is_array($review)) {
                continue;
            }

            if (($review['decision'] ?? '') === 'approve') {
                $approvedCount++;
            } elseif (($review['decision'] ?? '') === 'deny') {
                $deniedNames[] = (string) ($review['field_label'] ?? $labelsByKey->get((string) $key) ?? $key);
            }
        }

        $changeRequest->reviewed_by = $reviewedBy;
        $changeRequest->reviewed_at = now();
        $changeRequest->status = $approvedCount > 0
            ? ProviderChangeRequest::STATUS_APPROVED
            : ProviderChangeRequest::STATUS_DENIED;

        if ($approvedCount > 0 && $deniedNames !== []) {
            $changeRequest->admin_note = translate('Denied_items').': '.implode(', ', $deniedNames);
        }

        $changeRequest->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function removeReviewedFieldFromPayload(string $changeType, array &$payload, string $fieldKey): void
    {
        if ($changeType === 'services') {
            $changes = collect($payload['subscription_changes'] ?? [])
                ->filter(fn ($change) => is_array($change) && (string) ($change['sub_category_id'] ?? '') !== $fieldKey)
                ->values()
                ->all();
            $payload['subscription_changes'] = $changes;
            $payload['sub_category_id'] = array_column($changes, 'sub_category_id');

            return;
        }

        if ($changeType === 'business_settings') {
            $payload['data'] = collect($payload['data'] ?? [])
                ->filter(fn ($item) => is_array($item) && (string) ($item['key'] ?? '') !== $fieldKey)
                ->values()
                ->all();

            return;
        }

        if ($changeType === 'branding') {
            unset($payload[$fieldKey]);

            return;
        }

        if ($fieldKey === 'coordinates') {
            unset($payload['latitude'], $payload['longitude']);

            return;
        }

        if ($fieldKey === 'zone') {
            unset($payload['leaf_zone_ids']);

            return;
        }

        if ($fieldKey === 'identity_documents') {
            unset($payload['deleted_identity_images'], $payload['new_identity_images']);

            return;
        }

        if ($fieldKey === 'company_identity_documents') {
            unset($payload['deleted_company_identity_images'], $payload['new_company_identity_images']);

            return;
        }

        unset($payload[$fieldKey]);
    }

    /**
     * @return array<int, array{
     *     field_key: string,
     *     field: string,
     *     from: string,
     *     to: string,
     *     review_status: 'pending'|'approved'|'denied',
     *     type?: string,
     *     from_url?: ?string,
     *     to_url?: ?string
     * }>
     */
    public function buildReviewDisplayChanges(ProviderChangeRequest $changeRequest): array
    {
        $displayChanges = [];
        $reviews = is_array($changeRequest->payload['field_reviews'] ?? null)
            ? $changeRequest->payload['field_reviews']
            : [];

        foreach ($reviews as $fieldKey => $review) {
            if (! is_array($review)) {
                continue;
            }

            $displayChanges[] = [
                'field_key' => (string) $fieldKey,
                'field' => (string) ($review['field_label'] ?? $fieldKey),
                'from' => (string) ($review['from'] ?? translate('not_set')),
                'to' => (string) ($review['to'] ?? translate('not_set')),
                'review_status' => ($review['decision'] ?? '') === 'approve' ? 'approved' : 'denied',
                'type' => $review['type'] ?? null,
                'from_url' => $review['from_url'] ?? null,
                'to_url' => $review['to_url'] ?? null,
            ];
        }

        foreach (app(ProviderProfileChangeDiffService::class)->build($changeRequest) as $change) {
            if (empty($change['field_key'])) {
                continue;
            }

            $displayChanges[] = [
                'field_key' => (string) $change['field_key'],
                'field' => (string) $change['field'],
                'from' => (string) $change['from'],
                'to' => (string) $change['to'],
                'review_status' => 'pending',
                'type' => $change['type'] ?? null,
                'from_url' => $change['from_url'] ?? null,
                'to_url' => $change['to_url'] ?? null,
                'sub_category_id' => $change['sub_category_id'] ?? null,
                'action' => $change['action'] ?? null,
            ];
        }

        return $displayChanges;
    }

    public function pendingReviewCount(ProviderChangeRequest $changeRequest): int
    {
        return count($this->pendingFieldChangesForRequest($changeRequest));
    }

    /**
     * @param  array<int, string>  $approvedFieldKeys
     * @return array{approved_count: int, denied_count: int, denied_names: array<int, string>}
     */
    public function reviewFieldChanges(ProviderChangeRequest $changeRequest, array $approvedFieldKeys): array
    {
        $changes = app(ProviderProfileChangeDiffService::class)->build($changeRequest);
        $allKeys = collect($changes)
            ->pluck('field_key')
            ->map(fn ($key) => (string) $key)
            ->values()
            ->all();
        $approvedSet = array_flip(array_map('strval', $approvedFieldKeys));

        $toApply = array_values(array_filter($allKeys, fn (string $key) => isset($approvedSet[$key])));
        $denied = array_values(array_filter($allKeys, fn (string $key) => ! isset($approvedSet[$key])));

        if ($toApply !== []) {
            $this->applyPartial($changeRequest, $toApply);
        }

        $labelsByKey = collect($changes)->mapWithKeys(
            fn (array $change) => [(string) $change['field_key'] => $change['field']]
        );

        return [
            'approved_count' => count($toApply),
            'denied_count' => count($denied),
            'denied_names' => array_map(
                fn (string $key) => $labelsByKey->get($key) ?? $key,
                $denied
            ),
        ];
    }

    /**
     * @param  array<int, string>  $approvedFieldKeys
     */
    public function applyPartial(ProviderChangeRequest $changeRequest, array $approvedFieldKeys): void
    {
        $provider = $changeRequest->provider()->with('owner')->firstOrFail();
        $owner = $provider->owner;
        $payload = $changeRequest->payload ?? [];

        match ($changeRequest->change_type) {
            'profile' => $this->applyProfileFields($provider, $owner, $payload, $approvedFieldKeys),
            'branding' => $this->applyBrandingFields($provider, $payload, $approvedFieldKeys),
            'business_settings' => $this->applyBusinessSettingsFields($provider, $payload, $approvedFieldKeys),
            'services' => $this->applyServices(
                $provider,
                $this->buildServicesPayload(
                    array_values(array_filter(
                        $this->subscriptionChangesFromPayload($provider->id, $payload),
                        fn (array $change) => in_array($change['sub_category_id'], $approvedFieldKeys, true)
                    ))
                )
            ),
            default => null,
        };
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
        $this->applyBrandingFields($provider, $payload, ['logo', 'cover_image']);
    }

    /**
     * @param  array<int, string>  $approvedFieldKeys
     */
    public function applyBrandingFields(Provider $provider, array $payload, array $approvedFieldKeys): void
    {
        $approved = array_flip(array_map('strval', $approvedFieldKeys));
        $changed = false;

        if (isset($approved['logo']) && ! empty($payload['logo'])) {
            $provider->logo = $payload['logo'];
            $changed = true;
        }
        if (isset($approved['cover_image']) && ! empty($payload['cover_image'])) {
            $provider->cover_image = $payload['cover_image'];
            $changed = true;
        }

        if ($changed) {
            $provider->save();
        }
    }

    /**
     * @param  array<int, string>  $approvedFieldKeys
     */
    public function applyBusinessSettingsFields(Provider $provider, array $payload, array $approvedFieldKeys): void
    {
        $approved = array_flip(array_map('strval', $approvedFieldKeys));
        $data = collect($payload['data'] ?? [])
            ->filter(fn (array $item) => isset($approved[(string) ($item['key'] ?? '')]))
            ->values()
            ->all();

        if ($data !== []) {
            $this->applyBusinessSettings($provider, ['data' => $data]);
        }
    }

    /**
     * @param  array<int, string>  $approvedFieldKeys
     */
    public function applyProfileFields(Provider $provider, User $owner, array $payload, array $approvedFieldKeys): void
    {
        $approved = array_flip(array_map('strval', $approvedFieldKeys));
        $providerType = $payload['provider_type'] ?? ($provider->provider_type ?? 'company');
        $leafZoneIds = $payload['leaf_zone_ids'] ?? [];
        $zonesChanged = false;
        $newLeafIds = [];

        if (isset($approved['zone'])) {
            $previousLeafIds = collect($provider->coveredLeafZoneIds())->sort()->values()->all();
            $newLeafIds = collect($leafZoneIds)->sort()->values()->all();
            $zonesChanged = $previousLeafIds !== $newLeafIds;

            if ($zonesChanged) {
                $this->unsubscribeSubCategoriesLostOnZoneRemoval(
                    (string) $provider->id,
                    $previousLeafIds,
                    $newLeafIds
                );
                $provider->zone_id = $newLeafIds[0] ?? $provider->zone_id;
            }
        }

        if ($providerType === 'company') {
            if (isset($approved['company_name']) && array_key_exists('company_name', $payload)) {
                $provider->company_name = $payload['company_name'];
            }
            if (isset($approved['company_phone']) && array_key_exists('company_phone', $payload)) {
                $provider->company_phone = $payload['company_phone'];
            }
            if (isset($approved['company_email']) && ! empty($payload['company_email'])) {
                $provider->company_email = $payload['company_email'];
            }
        } else {
            if (isset($approved['contact_person_name']) && array_key_exists('contact_person_name', $payload)) {
                $provider->company_name = $payload['contact_person_name'];
            }
            if (isset($approved['contact_person_phone']) && array_key_exists('contact_person_phone', $payload)) {
                $provider->company_phone = $payload['contact_person_phone'];
            }
            if (isset($approved['contact_person_email']) && array_key_exists('contact_person_email', $payload)) {
                $provider->company_email = $payload['contact_person_email'] ?? null;
            }
        }

        if (isset($approved['contact_person_photo']) && ! empty($payload['contact_person_photo'])) {
            $provider->contact_person_photo = $payload['contact_person_photo'];
        }
        if (isset($approved['company_address']) && array_key_exists('company_address', $payload)) {
            $provider->company_address = $payload['company_address'];
        }
        if (isset($approved['street']) && array_key_exists('street', $payload)) {
            $provider->street = $payload['street'] ?? null;
        }
        if (isset($approved['city']) && array_key_exists('city', $payload)) {
            $provider->city = $payload['city'] ?? null;
        }
        if (isset($approved['pincode']) && array_key_exists('pincode', $payload)) {
            $provider->pincode = $payload['pincode'] ?? null;
        }
        if (isset($approved['contact_person_name']) && array_key_exists('contact_person_name', $payload)) {
            $provider->contact_person_name = $payload['contact_person_name'];
        }
        if (isset($approved['contact_person_phone']) && array_key_exists('contact_person_phone', $payload)) {
            $provider->contact_person_phone = $payload['contact_person_phone'];
        }
        if (isset($approved['contact_person_email']) && array_key_exists('contact_person_email', $payload)) {
            $provider->contact_person_email = $payload['contact_person_email'] ?? null;
        }
        if (isset($approved['coordinates']) && (array_key_exists('latitude', $payload) || array_key_exists('longitude', $payload))) {
            $provider->coordinates = [
                'latitude' => $payload['latitude'] ?? null,
                'longitude' => $payload['longitude'] ?? null,
            ];
        }

        if (isset($approved['contact_person_email']) && ! empty($payload['contact_person_email'])) {
            $owner->email = $payload['contact_person_email'];
        }
        if (isset($approved['contact_person_phone']) && array_key_exists('contact_person_phone', $payload)) {
            $owner->phone = $payload['contact_person_phone'];
            $owner->is_phone_verified = 1;
        }
        if (isset($approved['password']) && ! empty($payload['password'])) {
            $owner->password = bcrypt($payload['password']);
        }

        if (isset($approved['identity_documents'])) {
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
        }

        if (isset($approved['identity_number']) && array_key_exists('identity_number', $payload)) {
            $owner->identification_number = $payload['identity_number'];
        }
        if (isset($approved['identity_type']) && array_key_exists('identity_type', $payload)) {
            $owner->identification_type = $payload['identity_type'];
        }

        if ($providerType === 'company') {
            if (isset($approved['company_identity_documents'])) {
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

                $provider->company_identity_images = array_values($filteredCompanyImages);
            }

            if (isset($approved['company_identity_type']) && array_key_exists('company_identity_type', $payload)) {
                $provider->company_identity_type = $payload['company_identity_type'];
            }
            if (isset($approved['company_identity_number']) && array_key_exists('company_identity_number', $payload)) {
                $provider->company_identity_number = $payload['company_identity_number'];
            }
        }

        DB::transaction(function () use ($provider, $owner, $newLeafIds, $zonesChanged) {
            if ($zonesChanged) {
                $owner->zones()->sync($newLeafIds);
            }
            $owner->save();
            $provider->save();
            if ($zonesChanged) {
                $provider->zones()->sync(
                    collect($newLeafIds)->mapWithKeys(fn (string $zid) => [$zid => []])->all()
                );
            }
        });
    }

    private function applyProfile(Provider $provider, User $owner, array $payload): void
    {
        $fieldKeys = collect(app(ProviderProfileChangeDiffService::class)->build(
            tap(
                new ProviderChangeRequest([
                    'change_type' => 'profile',
                    'payload' => $payload,
                    'provider_id' => $provider->id,
                ]),
                function (ProviderChangeRequest $request) use ($provider) {
                    $request->setRelation('provider', $provider->loadMissing('owner', 'zones'));
                }
            )
        ))->pluck('field_key')->map(fn ($key) => (string) $key)->all();

        $this->applyProfileFields($provider, $owner, $payload, $fieldKeys);
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
