<?php

namespace Modules\ProviderManagement\Services;

use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderChangeRequest;
use Modules\ProviderManagement\Entities\ProviderSetting;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\UserManagement\Entities\User;
use Modules\ZoneManagement\Entities\Zone;

class ProviderProfileChangeDiffService
{
    /**
     * @return array<int, array{field: string, from: string, to: string, type?: string, from_url?: ?string, to_url?: ?string}>
     */
    public function build(ProviderChangeRequest $changeRequest): array
    {
        $provider = $changeRequest->provider;
        if (!$provider) {
            return [];
        }

        $payload = $changeRequest->payload ?? [];

        return match ($changeRequest->change_type) {
            'profile' => $this->diffProfile($provider, $provider->owner, $payload),
            'branding' => $this->diffBranding($provider, $payload),
            'business_settings' => $this->diffBusinessSettings($provider->id, $payload),
            'services' => $this->diffServices($provider->id, $payload),
            default => [],
        };
    }

    /**
     * @return array<int, array{field: string, from: string, to: string, type?: string, from_url?: ?string, to_url?: ?string}>
     */
    private function diffProfile(Provider $provider, ?User $owner, array $payload): array
    {
        $changes = [];
        $coords = is_array($provider->coordinates) ? $provider->coordinates : [];

        $scalarFields = [
            'contact_person_name' => [$provider->contact_person_name, $payload['contact_person_name'] ?? null, 'contact_person_name'],
            'contact_person_phone' => [$provider->contact_person_phone, $payload['contact_person_phone'] ?? null, 'contact_person_phone'],
            'contact_person_email' => [$provider->contact_person_email, $payload['contact_person_email'] ?? null, 'contact_person_email'],
            'company_name' => [$provider->company_name, $payload['company_name'] ?? null, 'company_name'],
            'company_phone' => [$provider->company_phone, $payload['company_phone'] ?? null, 'company_phone'],
            'company_email' => [$provider->company_email, $payload['company_email'] ?? null, 'company_email'],
            'company_address' => [$provider->company_address, $payload['company_address'] ?? null, 'company_address'],
            'street' => [$provider->street, $payload['street'] ?? null, 'street'],
            'city' => [$provider->city, $payload['city'] ?? null, 'city'],
            'pincode' => [$provider->pincode, $payload['pincode'] ?? null, 'pincode'],
            'latitude' => [$coords['latitude'] ?? null, $payload['latitude'] ?? null, 'latitude'],
            'longitude' => [$coords['longitude'] ?? null, $payload['longitude'] ?? null, 'longitude'],
            'identity_type' => [$owner?->identification_type, $payload['identity_type'] ?? null, 'identity Type'],
            'identity_number' => [$owner?->identification_number, $payload['identity_number'] ?? null, 'Identity_Number'],
            'company_identity_type' => [$provider->company_identity_type, $payload['company_identity_type'] ?? null, 'Company_Identity_Type'],
            'company_identity_number' => [$provider->company_identity_number, $payload['company_identity_number'] ?? null, 'Company_Identity_Number'],
        ];

        foreach ($scalarFields as $labelKey => [$from, $to, $translateKey]) {
            $this->pushScalarChange($changes, translate($translateKey), $from, $to);
        }

        $currentZoneIds = $provider->zones()->pluck('zones.id')->sort()->values()->all();
        $proposedZoneIds = collect($payload['leaf_zone_ids'] ?? [])->sort()->values()->all();
        if (!$this->valuesEqual($currentZoneIds, $proposedZoneIds)) {
            $changes[] = [
                'field' => translate('zone'),
                'from' => $this->formatZoneNames($currentZoneIds),
                'to' => $this->formatZoneNames($proposedZoneIds),
            ];
        }

        if (!empty($payload['password'])) {
            $changes[] = [
                'field' => translate('password'),
                'from' => '********',
                'to' => translate('Updated'),
            ];
        }

        $this->pushImageChange(
            $changes,
            translate('contact_person_photo'),
            $provider->contact_person_photo_full_path,
            $payload['contact_person_photo'] ?? null,
            'provider/contact_person_photo/'
        );

        if ($owner) {
            $this->pushIdentityImagesChange(
                $changes,
                translate('Identity_documents'),
                $owner->identification_image ?? [],
                $payload['deleted_identity_images'] ?? [],
                $payload['new_identity_images'] ?? []
            );
        }

        $this->pushIdentityImagesChange(
            $changes,
            translate('Company_identity_documents'),
            $provider->company_identity_images ?? [],
            $payload['deleted_company_identity_images'] ?? [],
            $payload['new_company_identity_images'] ?? []
        );

        return $changes;
    }

    /**
     * @return array<int, array{field: string, from: string, to: string, type?: string, from_url?: ?string, to_url?: ?string}>
     */
    private function diffBranding(Provider $provider, array $payload): array
    {
        $changes = [];
        $this->pushImageChange($changes, translate('logo'), $provider->logo_full_path, $payload['logo'] ?? null, 'provider/logo/');
        $this->pushImageChange($changes, translate('cover_image'), $provider->cover_image_full_path, $payload['cover_image'] ?? null, 'provider/logo/');

        return $changes;
    }

    /**
     * @return array<int, array{field: string, from: string, to: string}>
     */
    private function diffBusinessSettings(string $providerId, array $payload): array
    {
        $changes = [];
        $items = $payload['data'] ?? [];

        foreach ($items as $item) {
            $key = $item['key'] ?? null;
            if (!$key) {
                continue;
            }

            $proposed = $item['value'] ?? null;
            $current = ProviderSetting::where('provider_id', $providerId)
                ->where('key_name', $key)
                ->first()?->live_values;

            if (is_array($current)) {
                $current = json_encode($current);
            }
            if (is_array($proposed)) {
                $proposed = json_encode($proposed);
            }

            if ($this->valuesEqual($current, $proposed)) {
                continue;
            }

            $changes[] = [
                'field' => $this->labelForSettingKey($key),
                'from' => $this->formatSettingValue($key, $current),
                'to' => $this->formatSettingValue($key, $proposed),
            ];
        }

        return $changes;
    }

    /**
     * @return array<int, array{field: string, from: string, to: string}>
     */
    private function diffServices(string $providerId, array $payload): array
    {
        $changes = [];
        $subscriptionChanges = app(ProviderProfileChangeRequestService::class)
            ->subscriptionChangesFromPayload($providerId, $payload);
        $ids = array_column($subscriptionChanges, 'sub_category_id');
        $categories = Category::whereIn('id', $ids)->get()->keyBy('id');

        foreach ($subscriptionChanges as $item) {
            $id = $item['sub_category_id'];
            $action = $item['action'];
            $row = SubscribedService::where('sub_category_id', $id)->where('provider_id', $providerId)->first();
            $currentlySubscribed = (int) ($row?->is_subscribed ?? 0) === 1;

            $changes[] = [
                'field' => $categories->get($id)?->name ?? (string) $id,
                'from' => $currentlySubscribed ? translate('Subscribed') : translate('Unsubscribed'),
                'to' => $action === 'subscribe' ? translate('Subscribed') : translate('Unsubscribed'),
            ];
        }

        return $changes;
    }

    /**
     * @param array<int, array{field: string, from: string, to: string, type?: string, from_url?: ?string, to_url?: ?string}> $changes
     */
    private function pushScalarChange(array &$changes, string $label, mixed $from, mixed $to): void
    {
        if ($this->valuesEqual($from, $to)) {
            return;
        }

        $changes[] = [
            'field' => $label,
            'from' => $this->formatScalar($from),
            'to' => $this->formatScalar($to),
        ];
    }

    /**
     * @param array<int, array{field: string, from: string, to: string, type?: string, from_url?: ?string, to_url?: ?string}> $changes
     */
    private function pushImageChange(array &$changes, string $label, ?string $currentUrl, ?string $newFilename, string $folder): void
    {
        if (empty($newFilename)) {
            return;
        }

        $newUrl = $this->assetUrl($newFilename, $folder);
        if ($this->valuesEqual($currentUrl, $newUrl)) {
            return;
        }

        $changes[] = [
            'field' => $label,
            'from' => $currentUrl ? translate('Current_image') : translate('not_set'),
            'to' => translate('New_image_uploaded'),
            'type' => 'image',
            'from_url' => $currentUrl,
            'to_url' => $newUrl,
        ];
    }

    /**
     * @param array<int, array{field: string, from: string, to: string}> $changes
     * @param array<int, mixed> $currentImages
     * @param array<int, string> $deleted
     * @param array<int, array{image?: string}> $added
     */
    private function pushIdentityImagesChange(
        array &$changes,
        string $label,
        array $currentImages,
        array $deleted,
        array $added
    ): void {
        if ($deleted === [] && $added === []) {
            return;
        }

        $currentCount = $this->countIdentityImages($currentImages);
        $newCount = max(0, $currentCount - count($deleted) + count($added));

        $from = $currentCount === 0
            ? translate('not_set')
            : $currentCount . ' ' . translate('file_s');

        if ($deleted !== []) {
            $from .= ' (' . translate('removing') . ': ' . implode(', ', $deleted) . ')';
        }

        $to = $newCount === 0
            ? translate('not_set')
            : $newCount . ' ' . translate('file_s');

        if ($added !== []) {
            $addedNames = collect($added)
                ->map(fn ($item) => is_array($item) ? ($item['image'] ?? '') : (string) $item)
                ->filter()
                ->all();
            if ($addedNames !== []) {
                $to .= ' (' . translate('adding') . ': ' . implode(', ', $addedNames) . ')';
            }
        }

        if ($this->valuesEqual($from, $to)) {
            return;
        }

        $changes[] = [
            'field' => $label,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function countIdentityImages(array $images): int
    {
        return collect($images)->filter(function ($item) {
            if (is_string($item)) {
                return $item !== '';
            }

            return is_array($item) && !empty($item['image']);
        })->count();
    }

    private function formatScalar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return translate('not_set');
        }

        if (is_bool($value)) {
            return $value ? translate('Yes') : translate('No');
        }

        if (is_float($value) || is_int($value)) {
            return (string) $value;
        }

        return (string) $value;
    }

    private function formatSettingValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return translate('not_set');
        }

        if (in_array($key, ['customer_location', 'provider_location', 'provider_serviceman_can_edit_booking', 'provider_serviceman_can_cancel_booking'], true)) {
            return ((string) $value === '1') ? translate('Active') : translate('Inactive');
        }

        return $this->formatScalar($value);
    }

    private function labelForSettingKey(string $key): string
    {
        $translated = translate($key);
        if ($translated !== $key) {
            return $translated;
        }

        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * @param array<int, string> $zoneIds
     */
    private function formatZoneNames(array $zoneIds): string
    {
        if ($zoneIds === []) {
            return translate('not_set');
        }

        $names = Zone::whereIn('id', $zoneIds)->orderBy('name')->pluck('name')->all();

        return $names !== [] ? implode(', ', $names) : implode(', ', $zoneIds);
    }

    private function assetUrl(string $filename, string $folder): ?string
    {
        return resolve_media_storage_url($filename, $folder);
    }

    private function valuesEqual(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return json_encode($a) === json_encode($b);
        }

        $normalize = static function (mixed $v): string {
            if ($v === null || $v === '') {
                return '';
            }
            if (is_bool($v)) {
                return $v ? '1' : '0';
            }
            if (is_numeric($v)) {
                return (string) round((float) $v, 8);
            }

            return trim((string) $v);
        };

        return $normalize($a) === $normalize($b);
    }
}
