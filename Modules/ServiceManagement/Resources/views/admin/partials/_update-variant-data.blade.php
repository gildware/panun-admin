
@if(isset($variants))
    @php
        $variant_keys = $variants->pluck('variant_key')->unique()->toArray();
        $serviceVariantsByKey = (isset($service) && $service?->serviceVariants)
            ? $service->serviceVariants->keyBy('variant_key')
            : collect();
    @endphp
    @foreach($variant_keys as $key => $item)
        @php
            $meta = $serviceVariantsByKey->get($item);
            $vp = [];
            if (isset($service) && $service !== null) {
                $vp = is_array($service->variation_pricing ?? null) ? $service->variation_pricing : [];
            }
            $stored = $vp[$item] ?? null;
            if (is_array($stored) && array_key_exists('use_zone_pricing', $stored)) {
                $zonePricingOn = (bool) $stored['use_zone_pricing'];
                $defaultVal = (float) ($stored['default_price'] ?? 0);
            } else {
                $zonePrices = $variants->where('variant_key', $item)->pluck('price')->map(function ($p) {
                    return round((float) $p, 4);
                })->unique();
                $zonePricingOn = $zonePrices->count() > 1;
                $firstVar = $variants->where('variant_key', $item)->first();
                $defaultVal = (float) ($firstVar->price ?? 0);
            }
            $title = $meta?->title ?? str_replace('-', ' ', $item);
            $description = $meta?->getRawOriginal('description');
            $variantNote = $meta?->getRawOriginal('note');
            $previewUrl = $meta?->image
                ? $meta->image_full_path
                : asset('assets/admin-module/img/img-upload-new.png');
        @endphp
        <div class="service-variant-card card mb-3 shadow-sm" data-variant-key="{{ $item }}">
            <div class="card-body">
                <div class="d-flex gap-3 align-items-start flex-wrap">
                    <div class="flex-shrink-0">
                        @include('servicemanagement::admin.partials._variant-uploader', [
                            'inputName' => 'variant_image['.$item.']',
                            'previewUrl' => $previewUrl,
                        ])
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 fw-semibold text-dark">{{ $title }}</h6>
                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0 text-nowrap">{{ translate('price') }}</label>
                                <input type="number"
                                       name="variant_default_price[{{ $item }}]"
                                       value="{{ $defaultVal }}"
                                       class="theme-input-style"
                                       id="default-set-{{ $key }}-update"
                                       min="0"
                                       step="any"
                                       style="width: 130px"
                                       onkeyup="set_update_values('{{ $key }}','{{ $item }}')">
                            </div>
                        </div>
                        <textarea name="variant_description[{{ $item }}]"
                                  class="form-control"
                                  rows="2"
                                  placeholder="{{ translate('description') }}">{{ $description }}</textarea>
                        <textarea name="variant_note[{{ $item }}]"
                                  class="form-control mt-2"
                                  rows="2"
                                  placeholder="{{ translate('variant_note_hint') }}">{{ $variantNote }}</textarea>
                        <input name="variants[]" value="{{ $item }}" class="hide-div">
                    </div>

                    <div class="flex-shrink-0 d-flex flex-column align-items-end gap-2 ms-auto">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input service-zone-pricing-toggle"
                                   type="checkbox"
                                   role="switch"
                                   name="variant_use_zone_pricing[{{ $item }}]"
                                   value="1"
                                   data-variant-key="{{ $item }}"
                                   id="zone-pricing-{{ $item }}"
                                   {!! $zonePricingOn ? 'checked' : '' !!}>
                            <label class="form-check-label small" for="zone-pricing-{{ $item }}">Zone pricing</label>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary service-zone-pricing-btn"
                                data-variant-key="{{ $item }}"
                                data-variant-index="{{ $key }}"
                                {!! $zonePricingOn ? '' : 'disabled aria-disabled="true"' !!}
                                title="{{ $zonePricingOn ? '' : 'Enable zone pricing to edit' }}">
                            Set different pricing for zones
                        </button>
                        @if(isset($service) && $meta)
                            <a href="{{ route('admin.service.variants.edit', [$service->id, $meta->id]) }}"
                               class="btn btn-sm btn-outline-secondary"
                               title="{{ translate('edit') }}">
                                <span class="material-icons m-0" style="font-size:18px;">edit</span>
                            </a>
                        @endif
                        <a class="btn btn-sm btn--danger service-ajax-remove-variant"
                           data-route="{{ route('admin.service.ajax-delete-db-variant', [$item, $variants->first()->service_id]) }}"
                           data-id="variation-update-table"
                           data-item="{{ count($variant_keys) }}">
                            <span class="material-icons m-0" style="font-size:18px;">delete</span>
                        </a>
                    </div>
                </div>

                @foreach($zones as $zone)
                    <input type="hidden"
                           name="{{ $item }}_{{ $zone->id }}_price"
                           value="{{ $variants->where('zone_id', $zone->id)->where('variant_key', $item)->first()->price ?? 0 }}"
                           class="default-get-{{ $key }}-update">
                @endforeach
            </div>
        </div>
    @endforeach
@endif

<script>
    "use strict";
    (function () {
        function set_update_values(key, variantKey) {
            if (window.serviceZonePricingCustomMode && window.serviceZonePricingCustomMode[variantKey]) {
                return;
            }
            var updateElements = document.querySelectorAll('.default-get-' + key + '-update');
            var setInput = document.getElementById('default-set-' + key + '-update');
            var setValue = setInput ? setInput.value : '';
            updateElements.forEach(function (element) {
                element.value = setValue;
            });
        }
        window.set_update_values = set_update_values;
    })();
</script>
