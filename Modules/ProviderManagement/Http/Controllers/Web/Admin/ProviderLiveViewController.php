<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Modules\BookingModule\Entities\Booking;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ProviderManagement\Entities\ProviderSetting;
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
                'subscribed_services' => function ($query) {
                    $query->where('is_subscribed', 1);
                },
                'subscribed_services.category:id,name',
                'subscribed_services.sub_category:id,name,parent_id',
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

        $calendarWindowDays = 90;
        $calendarStartMaxDays = 365;
        $calendarFrom = now()->startOfDay();
        $calendarTo = now()->addDays($calendarStartMaxDays + $calendarWindowDays)->endOfDay();
        $jobsByProvider = $this->calendarJobsByProvider($providers->pluck('id'), $calendarFrom, $calendarTo);
        $settingsByProvider = $this->scheduleSettingsByProvider($providers->pluck('id'));

        $providerPayload = $providers->map(function (Provider $provider) use ($zonePayload, $jobsByProvider, $settingsByProvider) {
            $id = (string) $provider->id;

            return $this->serializeProvider(
                $provider,
                $zonePayload,
                $settingsByProvider->get($id, collect()),
                $jobsByProvider->get($id, collect())
            );
        })->values()->all();

        $categories = Category::ofType('main')->ofStatus(1)->ordered()->get(['id', 'name']);
        $subcategories = Category::ofType('sub')->ofStatus(1)->ordered()->get(['id', 'name', 'parent_id']);

        return view('providermanagement::admin.provider.live-view', [
            'zonesJson' => $zonePayload,
            'providersJson' => $providerPayload,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'subcategoriesJson' => $subcategories->map(fn (Category $cat) => [
                'id' => (string) $cat->id,
                'name' => (string) $cat->name,
                'parent_id' => $cat->parent_id ? (string) $cat->parent_id : null,
            ])->values()->all(),
            'categoriesJson' => $categories->map(fn (Category $cat) => [
                'id' => (string) $cat->id,
                'name' => (string) $cat->name,
            ])->values()->all(),
            'zoneTreeOptions' => $zoneTreeOptions,
            'defaultZoneId' => $defaultZoneId,
            'calendarFrom' => $calendarFrom->toDateString(),
            'calendarTo' => $calendarTo->toDateString(),
            'calendarWindowDays' => $calendarWindowDays,
            'calendarStartMaxDays' => $calendarStartMaxDays,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $zonePayload
     * @param  Collection<int, ProviderSetting>  $settings
     * @param  Collection<int, Booking>  $jobs
     * @return array<string, mixed>
     */
    private function serializeProvider(Provider $provider, array $zonePayload, $settings, $jobs): array
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

        $subcategories = $provider->subscribed_services
            ->map(fn ($row) => $row->sub_category)
            ->filter()
            ->unique('id')
            ->map(fn ($cat) => [
                'id' => (string) $cat->id,
                'name' => (string) $cat->name,
                'parent_id' => $cat->parent_id ? (string) $cat->parent_id : null,
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
            'logo' => $provider->list_avatar_full_path,
            'lat' => $lat,
            'lng' => $lng,
            'avail' => $avail,
            'active' => (int) $provider->is_active === 1,
            'ongoing' => $ongoing,
            'rating' => round((float) ($provider->avg_rating ?? 0), 1),
            'zone_ids' => $zoneIds,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'details_url' => route('admin.provider.details', [$provider->id, 'web_page' => 'overview']),
            'appOn' => $availableForJobs,
            'hours' => $this->hoursFromSettings($settings),
            'weekends' => $this->weekendsFromSettings($settings),
            'jobs' => $this->serializeCalendarJobs($jobs),
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
     * Prefer the city service area (Srinagar) over a valley-wide parent like Kashmir Region.
     *
     * @param  \Illuminate\Support\Collection<int, Zone>  $zones
     * @param  array<int, array<string, mixed>>  $zonePayload
     */
    private function defaultTopLevelZoneId($zones, array $zonePayload): ?string
    {
        if ($zones->isEmpty()) {
            return null;
        }

        $namedCity = $zones->first(function (Zone $zone) {
            return preg_match('/srinagar\s+(and\s+nearby|district)/i', (string) $zone->name) === 1;
        });
        if ($namedCity) {
            return (string) $namedCity->id;
        }

        $srinagarNamed = $zones->filter(function (Zone $zone) {
            return preg_match('/srinagar/i', (string) $zone->name) === 1;
        });
        if ($srinagarNamed->isNotEmpty()) {
            $payloadById = [];
            foreach ($zonePayload as $row) {
                $payloadById[(string) $row['id']] = $row;
            }
            $best = null;
            $bestSpan = -1.0;
            foreach ($srinagarNamed as $zone) {
                $span = $this->pathSpan($payloadById[(string) $zone->id]['paths'] ?? []) ?? 0.0;
                if ($span >= $bestSpan) {
                    $bestSpan = $span;
                    $best = $zone;
                }
            }

            return $best ? (string) $best->id : (string) $srinagarNamed->first()->id;
        }

        $roots = $zones->filter(function (Zone $zone) {
            return $zone->parent_id === null || $zone->parent_id === '';
        })->values();

        if ($roots->isEmpty()) {
            return (string) $zones->first()->id;
        }

        $payloadById = [];
        foreach ($zonePayload as $row) {
            $payloadById[(string) $row['id']] = $row;
        }

        $candidates = $roots;
        if ($roots->count() === 1) {
            $rootId = (string) $roots->first()->id;
            $children = $zones->filter(function (Zone $zone) use ($rootId) {
                return (string) $zone->parent_id === $rootId;
            })->values();
            if ($children->isNotEmpty()) {
                $candidates = $children;
            }
        }

        $bestId = null;
        $bestSpan = null;
        foreach ($candidates as $zone) {
            $paths = $payloadById[(string) $zone->id]['paths'] ?? [];
            $span = $this->pathSpan(is_array($paths) ? $paths : []);
            if ($span === null) {
                continue;
            }
            if ($bestSpan === null || $span < $bestSpan) {
                $bestSpan = $span;
                $bestId = (string) $zone->id;
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

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $providerIds
     * @return Collection<string, Collection<int, Booking>>
     */
    private function calendarJobsByProvider($providerIds, Carbon $from, Carbon $to): Collection
    {
        if ($providerIds->isEmpty()) {
            return collect();
        }

        return Booking::query()
            ->whereIn('provider_id', $providerIds)
            ->whereIn('booking_status', ['pending', 'accepted', 'ongoing'])
            ->whereNotNull('service_schedule')
            ->where('service_schedule', '>=', $from->toDateTimeString())
            ->where('service_schedule', '<=', $to->toDateTimeString())
            ->orderBy('service_schedule')
            ->get(['id', 'provider_id', 'readable_id', 'booking_status', 'service_schedule'])
            ->groupBy(fn (Booking $booking) => (string) $booking->provider_id);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $providerIds
     * @return Collection<string, Collection<int, ProviderSetting>>
     */
    private function scheduleSettingsByProvider($providerIds): Collection
    {
        if ($providerIds->isEmpty()) {
            return collect();
        }

        return ProviderSetting::query()
            ->whereIn('provider_id', $providerIds)
            ->where('settings_type', 'service_schedule')
            ->whereIn('key_name', ['time_schedule', 'weekends'])
            ->get()
            ->groupBy(fn (ProviderSetting $setting) => (string) $setting->provider_id);
    }

    /**
     * @param  Collection<int, ProviderSetting>  $settings
     * @return array{start: string, end: string}
     */
    private function hoursFromSettings($settings): array
    {
        $row = $settings->firstWhere('key_name', 'time_schedule');
        $raw = $row?->live_values;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            return ['start' => '09:00', 'end' => '18:00'];
        }

        $start = (string) ($raw['start_time'] ?? $raw['start'] ?? '09:00');
        $end = (string) ($raw['end_time'] ?? $raw['end'] ?? '18:00');

        return [
            'start' => substr($start, 0, 5) ?: '09:00',
            'end' => substr($end, 0, 5) ?: '18:00',
        ];
    }

    /**
     * @param  Collection<int, ProviderSetting>  $settings
     * @return array<int, string>
     */
    private function weekendsFromSettings($settings): array
    {
        $row = $settings->firstWhere('key_name', 'weekends');
        $raw = $row?->live_values;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($day) {
            return strtolower(trim((string) $day));
        }, $raw)));
    }

    /**
     * @param  Collection<int, Booking>  $jobs
     * @return array<int, array<string, mixed>>
     */
    private function serializeCalendarJobs($jobs): array
    {
        return $jobs->map(function (Booking $booking) {
            try {
                $start = Carbon::parse($booking->service_schedule);
            } catch (\Throwable $e) {
                return null;
            }
            $end = $start->copy()->addHours(2);
            $status = strtolower((string) $booking->booking_status);
            $kind = $status === 'ongoing' ? 'ongoing' : 'scheduled';
            $readable = (string) ($booking->readable_id ?: $booking->id);

            return [
                'date' => $start->toDateString(),
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'status' => $kind,
                'title' => $readable.' · '.$kind,
                'url' => route('admin.booking.details', [$booking->id, 'web_page' => 'details']),
            ];
        })->filter()->values()->all();
    }
}
