
@if(isset($variants))
    @php
        $variant_keys = $variants->pluck('variant_key')->unique()->toArray();
    @endphp
    @foreach($variant_keys as $key => $item)
        @php
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
        @endphp
        <tr>
            <th scope="row">
                {{ str_replace('-', ' ', $item) }}
                <input name="variants[]" value="{{ $item }}" class="hide-div">
            </th>
            <td>
                <input type="number"
                       name="variant_default_price[{{ $item }}]"
                       value="{{ $defaultVal }}"
                       class="theme-input-style"
                       id="default-set-{{ $key }}-update"
                       min="0"
                       step="any"
                       onkeyup="set_update_values('{{ $key }}','{{ $item }}')">
                {{-- Zone-wise prices live only in the modal; keep them as hidden inputs for form submit. --}}
                @foreach($zones as $zone)
                    <input type="hidden"
                           name="{{ $item }}_{{ $zone->id }}_price"
                           value="{{ $variants->where('zone_id', $zone->id)->where('variant_key', $item)->first()->price ?? 0 }}"
                           class="default-get-{{ $key }}-update">
                @endforeach
            </td>
            <td>
                <div class="d-inline-flex align-items-center gap-2 me-2">
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
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-primary service-zone-pricing-btn me-1"
                        data-variant-key="{{ $item }}"
                        data-variant-index="{{ $key }}"
                        {!! $zonePricingOn ? '' : 'disabled aria-disabled="true"' !!}
                        title="{{ $zonePricingOn ? '' : 'Enable zone pricing to edit' }}">
                    Set different pricing for zones
                </button>
                <a class="btn btn-sm btn--danger service-ajax-remove-variant"
                   data-route="{{ route('admin.service.ajax-delete-db-variant', [$item, $variants->first()->service_id]) }}"
                   data-id="variation-update-table"
                   data-item="{{ count($variant_keys) }}">
                    <span class="material-icons m-0">delete</span>
                </a>
            </td>
        </tr>
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
