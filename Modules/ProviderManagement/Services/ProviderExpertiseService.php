<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\ProviderManagement\Entities\ProviderExpertise;
use Modules\ProviderManagement\Entities\SubscribedService;
use Modules\ServiceManagement\Entities\Service;

class ProviderExpertiseService
{
    /**
     * Subscribed subcategories, grouped by category, with the current expertise choice.
     *
     * @return array{
     *     groups: array<int, array<string, mixed>>,
     *     all_services_count: int,
     *     selected_services_count: int
     * }
     */
    public function groupsForProvider(string $providerId): array
    {
        $subscribed = SubscribedService::query()
            ->with(['category', 'sub_category'])
            ->where('provider_id', $providerId)
            ->where('is_subscribed', 1)
            ->get()
            ->filter(fn ($row) => $row->sub_category !== null)
            ->keyBy(fn ($row) => (string) $row->sub_category_id);

        $servicesBySub = $subscribed->isEmpty()
            ? collect()
            : Service::query()
                ->where('is_active', 1)
                ->whereIn('sub_category_id', $subscribed->keys()->all())
                ->ordered()
                ->get(['id', 'name', 'sub_category_id'])
                ->groupBy(fn ($service) => (string) $service->sub_category_id);

        $existing = ProviderExpertise::query()
            ->where('provider_id', $providerId)
            ->get()
            ->groupBy(fn ($row) => (string) $row->sub_category_id);

        $allServicesCount = 0;
        $selectedServicesCount = 0;

        $groups = $subscribed
            ->groupBy(fn ($row) => (string) $row->category_id)
            ->map(function (Collection $rows) use ($servicesBySub, $existing, &$allServicesCount, &$selectedServicesCount) {
                $first = $rows->first();
                $subcategories = $rows
                    ->sortBy(fn ($row) => mb_strtolower((string) $row->sub_category->name))
                    ->values()
                    ->map(function ($row) use ($servicesBySub, $existing, &$allServicesCount, &$selectedServicesCount) {
                        $subId = (string) $row->sub_category_id;
                        $saved = $existing->get($subId, collect());
                        $mode = 'none';
                        $selectedIds = [];
                        if ($saved->contains(fn ($item) => $item->scope === 'sub_category')) {
                            $mode = 'all';
                            $allServicesCount++;
                        } elseif ($saved->contains(fn ($item) => $item->scope === 'service')) {
                            $mode = 'some';
                            $selectedIds = $saved
                                ->where('scope', 'service')
                                ->pluck('service_id')
                                ->map(fn ($id) => (string) $id)
                                ->all();
                        }

                        $services = ($servicesBySub->get($subId) ?? collect())
                            ->map(function ($service) use ($selectedIds, $mode, &$selectedServicesCount) {
                                $selected = $mode === 'some' && in_array((string) $service->id, $selectedIds, true);
                                if ($selected) {
                                    $selectedServicesCount++;
                                }

                                return [
                                    'id' => (string) $service->id,
                                    'name' => (string) $service->name,
                                    'selected' => $selected,
                                ];
                            })
                            ->values()
                            ->all();

                        return [
                            'id' => $subId,
                            'name' => (string) $row->sub_category->name,
                            'mode' => $mode,
                            'services' => $services,
                        ];
                    })
                    ->all();

                return [
                    'category_id' => (string) ($first->category_id ?? ''),
                    'category_name' => (string) ($first->category?->name ?? ''),
                    'subcategories' => $subcategories,
                ];
            })
            ->sortBy(fn ($group) => mb_strtolower((string) $group['category_name']))
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'all_services_count' => $allServicesCount,
            'selected_services_count' => $selectedServicesCount,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(string $providerId, array $input): void
    {
        $subscribed = SubscribedService::query()
            ->where('provider_id', $providerId)
            ->where('is_subscribed', 1)
            ->get()
            ->keyBy(fn ($row) => (string) $row->sub_category_id);

        $activeServiceIds = $subscribed->isEmpty()
            ? collect()
            : Service::query()
                ->where('is_active', 1)
                ->whereIn('sub_category_id', $subscribed->keys()->all())
                ->get(['id', 'sub_category_id'])
                ->groupBy(fn ($service) => (string) $service->sub_category_id)
                ->map(fn (Collection $rows) => $rows->map(fn ($service) => (string) $service->id)->all());

        $rows = [];
        $errors = [];

        foreach ($subscribed as $subId => $subscription) {
            $payload = $input[$subId] ?? [];
            if (! is_array($payload)) {
                $payload = [];
            }
            $mode = (string) ($payload['mode'] ?? 'none');
            if (! in_array($mode, ['none', 'all', 'some'], true)) {
                $errors["expertise.$subId.mode"] = translate('Select_a_valid_expertise_option');
                continue;
            }
            if ($mode === 'none') {
                continue;
            }
            if ($mode === 'all') {
                $rows[] = [
                    'provider_id' => $providerId,
                    'category_id' => (string) $subscription->category_id,
                    'sub_category_id' => $subId,
                    'service_id' => null,
                    'scope' => 'sub_category',
                ];
                continue;
            }

            $requested = $payload['services'] ?? [];
            if (! is_array($requested)) {
                $requested = [];
            }
            $allowed = array_flip($activeServiceIds->get($subId, []));
            $chosen = [];
            foreach ($requested as $serviceId) {
                $serviceId = (string) $serviceId;
                if (Str::isUuid($serviceId) && isset($allowed[$serviceId])) {
                    $chosen[$serviceId] = true;
                }
            }
            if ($chosen === []) {
                $errors["expertise.$subId.services"] = translate('Select_at_least_one_service');
                continue;
            }
            foreach (array_keys($chosen) as $serviceId) {
                $rows[] = [
                    'provider_id' => $providerId,
                    'category_id' => (string) $subscription->category_id,
                    'sub_category_id' => $subId,
                    'service_id' => $serviceId,
                    'scope' => 'service',
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($providerId, $rows) {
            ProviderExpertise::query()->where('provider_id', $providerId)->delete();
            foreach ($rows as $row) {
                ProviderExpertise::query()->create($row);
            }
        });
    }
}
