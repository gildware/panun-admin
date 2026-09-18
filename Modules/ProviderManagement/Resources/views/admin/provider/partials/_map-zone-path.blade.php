@php
    $mapZonePath = $mapZonePath ?? [];
    $mapZonePathLive = ! empty($mapZonePathLive);
    $mapZonePathId = $mapZonePathId ?? 'provider-map-zone-path';
@endphp
<div
    id="{{ $mapZonePathId }}"
    class="provider-map-zone-path"
    @if($mapZonePathLive)
        data-url="{{ route('admin.provider.zone-from-location') }}"
        data-empty="{{ translate('Location_not_in_any_zone') }}"
        data-checking="{{ translate('Checking_zone') }}"
    @endif
>
    <div class="provider-map-zone-path__label">{{ translate('Zone_from_map_location') }}</div>
    <div class="provider-map-zone-path__value" data-role="path-value">
        @if(count($mapZonePath) > 0)
            @foreach($mapZonePath as $i => $crumb)
                @if($i > 0)<span class="provider-map-zone-path__sep" aria-hidden="true">→</span>@endif
                <span class="provider-map-zone-path__crumb">{{ $crumb['name'] }}</span>
            @endforeach
        @else
            <span class="text-muted">{{ translate('Location_not_in_any_zone') }}</span>
        @endif
    </div>
</div>
<style>
    .provider-map-zone-path {
        margin-top: 1rem;
        padding: .85rem 1rem;
        background: #f4f7fb;
        border: 1px solid #e6edf5;
        border-radius: .5rem;
    }
    .provider-map-zone-path__label {
        font-size: .72rem;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: #6b7c8f;
        margin-bottom: .4rem;
        font-weight: 600;
    }
    .provider-map-zone-path__value {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .3rem .45rem;
        font-size: .92rem;
        line-height: 1.4;
        color: #1f3b57;
        font-weight: 600;
    }
    .provider-map-zone-path__crumb {
        background: #fff;
        border: 1px solid #d7e2ee;
        border-radius: 999px;
        padding: .15rem .65rem;
    }
    .provider-map-zone-path__sep {
        color: #8aa0b5;
        font-weight: 500;
    }
</style>
