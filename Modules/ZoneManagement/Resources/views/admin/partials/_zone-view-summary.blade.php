@php
    $viewAreaNames = $zone->relationLoaded('areas') ? $zone->areas->pluck('name')->filter()->values() : collect();
    $viewDescription = trim((string) (\Modules\ZoneManagement\Entities\Zone::descriptionIncludingBoundary(
        $zone->description ?? null,
        $zone->boundary_demarcation ?? null
    ) ?? ''));
    $childCount = (int) $zone->child_zones_count;
    $providerCount = (int) $zone->providers_count;
    $categoryCount = (int) $zone->categories_count;
@endphp
<div class="zone-view-summary">
    <div class="zone-view-head">
        <h3 class="zone-view-name">{{ $zone->getRawOriginal('name') ?: $zone->name }}</h3>
        <p class="zone-view-parent mb-0">
            <span>{{ translate('Parent_zone') }}</span>
            {{ $zone->parentZone->name ?? translate('No_parent_root_zone') }}
        </p>
    </div>

    <div class="zone-view-metrics">
        @if($childCount > 0)
            <a class="zone-view-metric" href="{{ route('admin.zone.children', $zone->id) }}">
                <strong>{{ $childCount }}</strong>
                <span>Children</span>
            </a>
        @else
            <div class="zone-view-metric">
                <strong>0</strong>
                <span>Children</span>
            </div>
        @endif
        <div class="zone-view-metric">
            <strong>{{ $providerCount }}</strong>
            <span>{{ translate('providers') }}</span>
        </div>
        <div class="zone-view-metric">
            <strong>{{ $categoryCount }}</strong>
            <span>{{ translate('Category') }}</span>
        </div>
    </div>

    <section class="zone-view-section">
        <h4>{{ translate('Zone_description') }}</h4>
        @if($viewDescription !== '')
            <p class="zone-view-copy">{{ $viewDescription }}</p>
        @else
            <p class="zone-view-empty">No description yet.</p>
        @endif
    </section>

    <section class="zone-view-section">
        <h4>
            {{ translate('Area_Encompassed_Mohalla') }}
            <span class="zone-view-count">{{ $viewAreaNames->count() }}</span>
        </h4>
        @if($viewAreaNames->isNotEmpty())
            <div class="zone-area-chips">
                @foreach($viewAreaNames as $areaName)
                    <span class="zone-area-chip">{{ $areaName }}</span>
                @endforeach
            </div>
        @else
            <p class="zone-view-empty">No areas recorded for this zone.</p>
        @endif
    </section>
</div>
