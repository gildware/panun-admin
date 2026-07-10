@php
    $isEdit = isset($variant) && $variant;
    $formAction = $isEdit
        ? route('admin.service.variants.update', [$service->id, $variant->id])
        : route('admin.service.variants.store', $service->id);
    $formMethod = $isEdit ? 'PUT' : 'POST';
    $zonePricingOn = $zonePricingOn ?? false;
    $defaultPrice = $defaultPrice ?? old('default_price');
    $panelUid = $isEdit ? 'edit-'.$variant->id : 'create';
@endphp
<div class="service-variations-panel" data-panel="form">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <button type="button" class="btn btn--secondary btn-sm js-variations-panel-back">
            <span class="material-icons align-middle" style="font-size:16px">arrow_back</span>
            {{ translate('back') }}
        </button>
        <h6 class="mb-0 text-dark flex-grow-1 text-end">
            {{ $isEdit ? translate('update') : translate('add_new') }}
        </h6>
    </div>

    <form class="js-variations-panel-form" action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="row g-3 align-items-start">
            <div class="col-md-3 col-lg-2">
                @include('servicemanagement::admin.partials._variant-uploader', [
                    'inputName' => 'image',
                    'inputId' => 'variant-image-'.$panelUid,
                    'previewUrl' => $isEdit ? $variant->image_full_path : asset('assets/admin-module/img/media/upload-file.png'),
                ])
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label fs-12 mb-1">{{ translate('title') }}</label>
                        <input type="text" name="title" class="form-control form-control-sm"
                               value="{{ $isEdit ? $variant->getRawOriginal('title') : old('title') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fs-12 mb-1">{{ translate('default_price') }}</label>
                        <input type="number" name="default_price" class="form-control form-control-sm" min="0.01" step="any"
                               value="{{ $defaultPrice }}" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   id="variant-active-{{ $panelUid }}" {{ ($isEdit ? $variant->is_active : true) ? 'checked' : '' }}>
                            <label class="form-check-label fs-12" for="variant-active-{{ $panelUid }}">{{ translate('active') }}</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fs-12 mb-1">{{ translate('description') }}</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2">{{ $isEdit ? $variant->getRawOriginal('description') : old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fs-12 mb-1">{{ translate('variant_note') }}</label>
                        <textarea name="note" class="form-control form-control-sm" rows="2"
                                  placeholder="{{ translate('variant_note_hint') }}">{{ $isEdit ? $variant->getRawOriginal('note') : old('note') }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input js-variant-zone-pricing-toggle" type="checkbox"
                                   name="variant_use_zone_pricing" value="1"
                                   id="variant-zone-pricing-{{ $panelUid }}" {{ $zonePricingOn ? 'checked' : '' }}>
                            <label class="form-check-label fs-12" for="variant-zone-pricing-{{ $panelUid }}">{{ translate('zone_pricing') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($zones->count())
            <div class="table-responsive mt-2 js-variant-zone-price-table {{ $zonePricingOn ? '' : 'opacity-50' }}">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="text-nowrap">
                    <tr>
                        <th class="py-1">{{ translate('zone') }}</th>
                        <th class="py-1" style="width:140px">{{ translate('price') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($zones as $zone)
                        @php
                            $zonePrice = $isEdit ? $variant->zonePrices->firstWhere('zone_id', $zone->id) : null;
                            $zoneInputName = $isEdit
                                ? $variant->variant_key.'_'.$zone->id.'_price'
                                : 'zone_prices['.$zone->id.']';
                            $zoneInputValue = $isEdit
                                ? ($zonePrice->price ?? 0)
                                : old('zone_prices.'.$zone->id, 0);
                        @endphp
                        <tr>
                            <td class="py-1 fs-12">{{ $zone->name }}</td>
                            <td class="py-1">
                                <input type="number" class="form-control form-control-sm js-variant-zone-price-input"
                                       name="{{ $zoneInputName }}"
                                       value="{{ $zoneInputValue }}" min="0" step="any"
                                       {{ $zonePricingOn ? '' : 'readonly' }}>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
            <button type="button" class="btn btn--secondary btn-sm js-variations-panel-back">{{ translate('cancel') }}</button>
            <button type="submit" class="btn btn--primary btn-sm">
                {{ $isEdit ? translate('update') : translate('save') }}
            </button>
        </div>
    </form>
</div>
