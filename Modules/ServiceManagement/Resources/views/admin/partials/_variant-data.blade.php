
@if(session()->has('variations'))
    @foreach(session('variations') as $key=>$item)
        <tr>
            <th scope="row">
                {{$item['variant']}}
                <input name="variants[]" value="{{str_replace(' ','-',$item['variant'])}}" class="hide-div">
            </th>
            <td>
                <input type="number" value="{{$item['price']}}" class="theme-input-style" id="default-set-{{$key}}"
                       onkeyup="set_values('{{$key}}','{{$item['variant_key']}}')" min="0.00001" step="any" required>
                {{-- Zone-wise prices live only in the modal; keep them as hidden inputs for form submit. --}}
                @foreach($zones as $zone)
                    <input type="hidden"
                           name="{{$item['variant_key']}}_{{$zone->id}}_price"
                           value="{{$item['price']}}"
                           class="default-get-{{$key}}">
                @endforeach
            </td>
            <td>
                <div class="d-inline-flex align-items-center gap-2 me-2">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input service-zone-pricing-toggle"
                               type="checkbox"
                               role="switch"
                               data-variant-key="{{ $item['variant_key'] }}"
                               id="zone-pricing-{{ $item['variant_key'] }}">
                        <label class="form-check-label small" for="zone-pricing-{{ $item['variant_key'] }}">Zone pricing</label>
                    </div>
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-primary service-zone-pricing-btn me-1"
                        data-variant-key="{{ $item['variant_key'] }}"
                        data-variant-index="{{ $key }}"
                        disabled
                        aria-disabled="true"
                        title="Enable zone pricing to edit">
                    Set different pricing for zones
                </button>
                <a class="btn btn--danger service-ajax-remove-variant"
                   data-id="variation-table"
                   data-route="{{route('admin.service.ajax-remove-variant',[$item['variant_key']])}}">
                    <span class="material-icons m-0">delete</span>
                </a>
            </td>
        </tr>
    @endforeach
@endif

<script>
    "use strict";

    // Equivalent JavaScript code
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
            element.value = document.getElementById('default-set-' + key).value;
        });
    }

</script>
