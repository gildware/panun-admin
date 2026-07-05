
@if(session()->has('variations'))
    @foreach(session('variations') as $key=>$item)
        @php
            $previewUrl = !empty($item['image'])
                ? getSingleImageFullPath(
                    imagePath: resolve_stored_media_key($item['image'], \App\Support\MediaStoragePath::legacyPrefixForService()),
                    s3Storage: null,
                    defaultPath: asset('assets/admin-module/img/placeholder.png')
                )
                : asset('assets/admin-module/img/placeholder.png');
        @endphp
        <div class="service-variant-card card mb-3 shadow-sm" data-variant-key="{{ $item['variant_key'] }}">
            <div class="card-body">
                <div class="d-flex gap-3 align-items-start flex-wrap">
                    <div class="flex-shrink-0">
                        <div class="upload-file ratio-1 w-100px input-disabled">
                            <div class="upload-file__img border-dashed-1-gray rounded">
                                <img src="{{ $previewUrl }}" alt="{{ translate('image') }}" class="w-100">
                            </div>
                        </div>
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 fw-semibold text-dark">{{ $item['variant'] }}</h6>
                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0 text-nowrap">{{ translate('price') }}</label>
                                <input type="number"
                                       name="variant_default_price[{{ $item['variant_key'] }}]"
                                       value="{{ $item['price'] }}"
                                       class="theme-input-style"
                                       id="default-set-{{ $key }}"
                                       min="0"
                                       step="any"
                                       required
                                       style="width: 130px"
                                       onkeyup="set_values('{{ $key }}','{{ $item['variant_key'] }}')">
                            </div>
                        </div>
                        @if(!empty($item['description']))
                            <p class="text-muted small mb-0">{{ $item['description'] }}</p>
                        @else
                            <p class="text-muted small mb-0 fst-italic">—</p>
                        @endif
                        <input type="hidden"
                               name="variant_description[{{ $item['variant_key'] }}]"
                               value="{{ $item['description'] ?? '' }}">
                        <input name="variants[]" value="{{ $item['variant_key'] }}" class="hide-div">
                    </div>

                    <div class="flex-shrink-0 d-flex flex-column align-items-end gap-2 ms-auto">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input service-zone-pricing-toggle"
                                   type="checkbox"
                                   role="switch"
                                   name="variant_use_zone_pricing[{{ $item['variant_key'] }}]"
                                   value="1"
                                   data-variant-key="{{ $item['variant_key'] }}"
                                   id="zone-pricing-{{ $item['variant_key'] }}">
                            <label class="form-check-label small" for="zone-pricing-{{ $item['variant_key'] }}">Zone pricing</label>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary service-zone-pricing-btn"
                                data-variant-key="{{ $item['variant_key'] }}"
                                data-variant-index="{{ $key }}"
                                disabled
                                aria-disabled="true"
                                    title="Enable zone pricing to edit">
                            Set different pricing for zones
                        </button>
                        <a class="btn btn-sm btn--danger service-ajax-remove-variant"
                           data-id="variation-table"
                           data-route="{{ route('admin.service.ajax-remove-variant', [$item['variant_key']]) }}">
                            <span class="material-icons m-0" style="font-size:18px;">delete</span>
                        </a>
                    </div>
                </div>

                @foreach($zones as $zone)
                    <input type="hidden"
                           name="{{ $item['variant_key'] }}_{{ $zone->id }}_price"
                           value="{{ $item['price'] }}"
                           class="default-get-{{ $key }}">
                @endforeach
            </div>
        </div>
    @endforeach
@endif

<script>
    "use strict";

    document.querySelectorAll('.service-ajax-remove-variant').forEach(function(element) {
        element.addEventListener('click', function() {
            var route = this.getAttribute('data-route');
            var id = this.getAttribute('data-id');
            ajax_remove_variant(route, id);
        });
    });

    function set_values(key, variantKey) {
        if (window.serviceZonePricingCustomMode && window.serviceZonePricingCustomMode[variantKey]) {
            return;
        }
        document.querySelectorAll('.default-get-' + key).forEach(function(element) {
            var el = document.getElementById('default-set-' + key);
            element.value = el ? el.value : '';
        });
    }
</script>
