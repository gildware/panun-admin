@php
    $service->loadMissing(['serviceVariants.zonePrices', 'variations']);
@endphp
<div class="service-variations-panel" data-panel="list">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <div>
            <h6 class="mb-0 text-dark">{{ translate('price_variation') }}</h6>
            <p class="fs-11 text-muted mb-0">{{ translate('Manage service price variations') }}</p>
        </div>
        @can('service_update')
            <button type="button"
                    class="btn btn--primary btn-sm"
                    data-variations-panel-url="{{ route('admin.service.variants.create', ['service' => $service->id, 'panel' => 1]) }}">
                {{ translate('add_new') }}
            </button>
        @endcan
    </div>

    @include('servicemanagement::admin.partials._variants-list-table', ['service' => $service])
</div>
