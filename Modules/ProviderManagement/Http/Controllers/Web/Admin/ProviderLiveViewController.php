<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ZoneManagement\Entities\Zone;

class ProviderLiveViewController extends Controller
{
    use AuthorizesRequests;
    public function index(): Renderable
    {
        $this->authorize('provider_view');

        try {
            $zones = Zone::withoutGlobalScope('translate')
                ->ofStatus(1)
                ->selectRaw('*, ST_AsText(ST_Centroid(`coordinates`)) as center')
                ->orderBy('name')
                ->get();
        } catch (\Throwable $e) {
            $zones = Zone::withoutGlobalScope('translate')
                ->ofStatus(1)
                ->orderBy('name')
                ->get();
        }

        $zonePayload = $zones->map(fn (Zone $zone) => $this->serializeZone($zone))->values()->all();
        $zoneTreeOptions = Zone::flatTreeOptionsForSelect($zones);
        $defaultZoneId = $this->defaultTopLevelZoneId($zones, $zonePayload);

        $providers = Provider::query()
            ->with([
                'owner:id,first_name,last_name,phone,email',
                'zones:id,name',
                'zone:id,name',
                'subscribed_services.category:id,name',
                'storage',
            ])
            ->withCount([
                'bookings as ongoing_jobs_count' => function ($query) {
                    $query->whereIn('booking_status', ['accepted', 'ongoing']);
                },
            ])
            ->ofApproval(1)
            ->orderBy('company_name')
            ->get();

        $providerPayload = $providers->map(function (Provider $provider) use ($zonePayload) {
            return $this->serializeProvider($provider, $zonePayload);
        })->values()->all();

        $categories = Category::ofType('main')->ofStatus(1)->ordered()->get(['id', 'name']);

        return view('providermanagement::admin.provider.live-view', [
            'zonesJson' => $zonePayload,
            'providersJson' => $providerPayload,
            'categories' => $categories,
            'zoneTreeOptions' => $zoneTreeOptions,
            'defaultZoneId' => $defaultZoneId,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $zonePayload
     * @return array<string, mixed>
     */
    private function serializeProvider(Provider $provider, array $zonePayload): array
    {
        $lat = $this->numericCoord(data_get($provider->coordinates, 'latitude'));
        $lng = $this->numericCoord(data_get($provider->coordinates, 'longitude'));

        $zoneIds = $provider->zones->pluck('id')->filter()->values();
        if ($zoneIds->isEmpty() && $provider->zone_id) {
            $zoneIds = collect([(string) $provider->zone_id]);
        }
        $zoneIds = $zoneIds->map(fn ($id) => (string) $id)->unique()->values()->all();

        if ($lat === null || $lng === null) {
            foreach ($zonePayload as $zone) {
                if (in_array((string) $zone['id'], $zoneIds, true) && $zone['lat'] !== null && $zone['lng'] !== null) {
                    $lat = $zone['lat'];
                    $lng = $zone['lng'];
                    break;
                }
            }
        }

        $categories = $provider->subscribed_services
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->map(fn ($cat) => [
                'id' => (string) $cat->id,
                'name' => (string) $cat->name,
            ])
            ->values()
            ->all();

        $availableForJobs = (int) $provider->is_active === 1
            && (int) ($provider->service_availability ?? 1) === 1
            && (int) ($provider->is_active_for_jobs ?? 1) === 1
            && ! $this->isUnavailablePerformance($provider);

        $ongoing = (int) ($provider->ongoing_jobs_count ?? 0);
        if (! $availableForJobs) {
            $avail = 'offline';
        } elseif ($ongoing > 0) {
            $avail = 'onjob';
        } else {
            $avail = 'available';
        }

        $phone = trim((string) ($provider->contact_person_phone ?: $provider->company_phone ?: $provider->owner?->phone ?: ''));

        return [
            'id' => (string) $provider->id,
            'name' => (string) $provider->company_name,
            'phone' => $phone,
            'address' => (string) ($provider->company_address ?? ''),
            'logo' => $provider->logo_full_path,
            'lat' => $lat,
            'lng' => $lng,
            'avail' => $avail,
            'active' => (int) $provider->is_active === 1,
            'ongoing' => $ongoing,
            'rating' => round((float) ($provider->avg_rating ?? 0), 1),
            'zone_ids' => $zoneIds,
            'categories' => $categories,
            'details_url' => route('admin.provider.details', [$provider->id, 'web_page' => 'overview']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeZone(Zone $zone): array
    {
        $paths = [];
        $lat = null;
        $lng = null;

        if ($zone->coordinates !== null) {
            $firstRing = $zone->coordinates[0] ?? null;
            if ($firstRing !== null) {
                $decoded = json_decode($firstRing->toJson(), true);
                $coords = is_array($decoded) ? ($decoded['coordinates'] ?? null) : null;
                if (isset($coords[0][0]) && is_array($coords[0][0])) {
                    $coords = $coords[0];
                }
                if (is_array($coords)) {
                    foreach ($coords as $coord) {
                        if (! is_array($coord) || count($coord) < 2 || ! is_numeric($coord[0]) || ! is_numeric($coord[1])) {
                            continue;
                        }
                        $paths[] = [
                            'lat' => (float) $coord[1],
                            'lng' => (float) $coord[0],
                        ];
                    }
                }
            }
        }

        if (! empty($zone->center) && is_string($zone->center)
            && preg_match('/POINT\s*\(\s*([^\s]+)\s+([^\s]+)\s*\)/i', $zone->center, $centerMatch)) {
            $lng = (float) trim($centerMatch[1], " \t\n\r\0\x0B'\"");
            $lat = (float) trim($centerMatch[2], " \t\n\r\0\x0B'\"");
        } elseif ($paths !== []) {
            $lat = array_sum(array_column($paths, 'lat')) / count($paths);
            $lng = array_sum(array_column($paths, 'lng')) / count($paths);
        }

        return [
            'id' => (string) $zone->id,
            'name' => (string) ($zone->name ?? ''),
            'parent_id' => $zone->parent_id ? (string) $zone->parent_id : null,
            'paths' => $paths,
            'lat' => $lat,
            'lng' => $lng,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Zone>  $zones
     * @param  array<int, array<string, mixed>>  $zonePayload
     */
    private function defaultTopLevelZoneId($zones, array $zonePayload): ?string
    {
        $roots = $zones->filter(function (Zone $zone) {
            return $zone->parent_id === null || $zone->parent_id === '';
        })->values();

        if ($roots->isEmpty()) {
            $first = $zones->first();

            return $first ? (string) $first->id : null;
        }

        if ($roots->count() === 1) {
            return (string) $roots->first()->id;
        }

        $payloadById = [];
        foreach ($zonePayload as $row) {
            $payloadById[(string) $row['id']] = $row;
        }

        $bestId = null;
        $bestSpan = null;
        foreach ($roots as $root) {
            $paths = $payloadById[(string) $root->id]['paths'] ?? [];
            $span = $this->pathSpan(is_array($paths) ? $paths : []);
            if ($span === null) {
                continue;
            }
            if ($bestSpan === null || $span < $bestSpan) {
                $bestSpan = $span;
                $bestId = (string) $root->id;
            }
        }

        return $bestId ?? (string) $roots->first()->id;
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $paths
     */
    private function pathSpan(array $paths): ?float
    {
        if (count($paths) < 2) {
            return null;
        }

        $lats = array_column($paths, 'lat');
        $lngs = array_column($paths, 'lng');

        return (max($lats) - min($lats)) + (max($lngs) - min($lngs));
    }

    private function numericCoord(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;

        return $number;
    }

    private function isUnavailablePerformance(Provider $provider): bool
    {
        $status = (string) ($provider->performance_status ?? '');

        return in_array($status, ['blacklisted', 'suspended'], true)
            || (int) ($provider->is_suspended ?? 0) === 1;
    }
}
