<div class="service-variations-panel" data-panel="view">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <button type="button" class="btn btn--secondary btn-sm js-variations-panel-back">
            <span class="material-icons align-middle" style="font-size:16px">arrow_back</span>
            {{ translate('back') }}
        </button>
        <div class="d-flex gap-2">
            @can('service_update')
                <button type="button"
                        class="btn btn--primary btn-sm"
                        data-variations-panel-url="{{ route('admin.service.variants.edit', [$service->id, $variant->id, 'panel' => 1]) }}">
                    {{ translate('edit') }}
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-auto">
            <img src="{{ $variant->image_full_path }}" alt="{{ $variant->getRawOriginal('title') }}"
                 class="rounded border" width="64" height="64" style="object-fit:cover;">
        </div>
        <div class="col">
            <h6 class="mb-1 text-dark">{{ $variant->getRawOriginal('title') }}</h6>
            <p class="fs-12 text-muted mb-2">{{ $variant->getRawOriginal('description') ?: '—' }}</p>
            <div class="d-flex flex-wrap gap-3 fs-12">
                <span><span class="text-muted">{{ translate('default_price') }}:</span> <strong class="c1">{{ with_currency_symbol($defaultPrice) }}</strong></span>
                <span>
                    <span class="text-muted">{{ translate('status') }}:</span>
                    @if($variant->is_active)
                        <span class="badge badge-success">{{ translate('active') }}</span>
                    @else
                        <span class="badge badge-danger">{{ translate('inactive') }}</span>
                    @endif
                </span>
                <span><span class="text-muted">{{ translate('zone_pricing') }}:</span> {{ $zonePricingOn ? translate('enabled') : translate('disabled') }}</span>
            </div>
        </div>
    </div>

    @if($zones->count())
        <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered mb-0">
                <thead class="text-nowrap">
                <tr>
                    <th class="py-1">{{ translate('zone') }}</th>
                    <th class="py-1">{{ translate('price') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($zones as $zone)
                    @php
                        $zonePrice = $variant->zonePrices->firstWhere('zone_id', $zone->id);
                    @endphp
                    <tr>
                        <td class="py-1 fs-12">{{ $zone->name }}</td>
                        <td class="py-1 fs-12">{{ with_currency_symbol($zonePrice->price ?? $defaultPrice) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
