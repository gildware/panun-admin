
@if(isset($variants))
    @php($variant_keys = $variants->pluck('variant_key')->unique()->toArray())
    @foreach($variant_keys as $key=>$item)
        <tr>
            <th scope="row">
                {{str_replace('-',' ',$item)}}
                <input name="variants[]" value="{{$item}}" class="hide-div">
            </th>
            <td>
                <input type="number"
                       value="{{$variants->where('price','>',0)->where('variant_key',$item)->first()->price??0}}"
                       class="theme-input-style" id="default-set-{{$key}}-update"
                       onkeyup="set_update_values('{{$key}}','{{$item}}')">
                {{-- Zone-wise prices live only in the modal; keep them as hidden inputs for form submit. --}}
                @foreach($zones as $zone)
                    <input type="hidden"
                           name="{{$item}}_{{$zone->id}}_price"
                           value="{{$variants->where('zone_id',$zone->id)->where('variant_key',$item)->first()->price??0}}"
                           class="default-get-{{$key}}-update">
                @endforeach
            </td>
            <td>
                <div class="d-inline-flex align-items-center gap-2 me-2">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input service-zone-pricing-toggle"
                               type="checkbox"
                               role="switch"
                               data-variant-key="{{ $item }}"
                               id="zone-pricing-{{ $item }}">
                        <label class="form-check-label small" for="zone-pricing-{{ $item }}">Zone pricing</label>
                    </div>
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-primary service-zone-pricing-btn me-1"
                        data-variant-key="{{ $item }}"
                        data-variant-index="{{ $key }}"
                        disabled
                        aria-disabled="true"
                        title="Enable zone pricing to edit">
                    Set different pricing for zones
                </button>
                <a class="btn btn-sm btn--danger service-ajax-remove-variant"
                   data-route="{{ route('admin.service.ajax-delete-db-variant',[$item,$variants->first()->service_id]) }}"
                   data-id="variation-update-table" data-item="{{count($variant_keys)}}" >
                    <span class="material-icons m-0">delete</span>
                </a>
            </td>
        </tr>
    @endforeach
@endif

<script>
    "use strict";
    document.addEventListener('DOMContentLoaded', function () {
        var elements = document.querySelectorAll('.service-ajax-remove-variant');
        elements.forEach(function (element) {
            element.addEventListener('click', function () {
                var route = this.getAttribute('data-route');
                var id = this.getAttribute('data-id');
                ajax_remove_variant(route, id);
            });
        });

        function set_update_values(key, variantKey) {
            if (window.serviceZonePricingCustomMode && window.serviceZonePricingCustomMode[variantKey]) {
                return;
            }
            var updateElements = document.querySelectorAll('.default-get-' + key + '-update');
            var setValue = document.getElementById('default-set-' + key + '-update').value;
            updateElements.forEach(function (element) {
                element.value = setValue;
            });
        }
    });
</script>
