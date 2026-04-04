@php
    $zoneId = $data['zone_id'] ?? ($booking->zone_id ?? '');
    $rowId = $rowKey ?? ('new-' . \Illuminate\Support\Str::uuid()->toString());
    $svcList = $services ?? collect();
    $selectedServiceId = $data['service_id'] ?? '';
    $selectedVariant = $data['variant_key'] ?? '';
    $taxPct = $data['tax_percent'] ?? company_default_tax_percentage();
    $discBearer = strtolower((string) ($data['discount_cost_bearer'] ?? 'none'));
    if (! in_array($discBearer, ['admin', 'provider', 'none', 'both'], true)) {
        $discBearer = 'none';
    }
@endphp
<tr id="service-row--{{ $rowId }}" data-detail-id="" data-tax-percent="{{ $taxPct }}">
    <td class="text-wrap ps-lg-3">
        @if($svcList->isNotEmpty() && $zoneId)
            <select name="service_ids[]" class="theme-input-style row-service-select w-100" required data-zone-id="{{ $zoneId }}">
                @foreach($svcList as $svc)
                    <option value="{{ $svc->id }}" {{ (string)$selectedServiceId === (string)$svc->id ? 'selected' : '' }}>{{ Str::limit($svc->name, 40) }}</option>
                @endforeach
            </select>
            <select name="variant_keys[]" class="theme-input-style row-variant-select w-100 mt-1" required>
                <option value="{{ $selectedVariant }}" selected>{{ Str::limit($selectedVariant, 40) }}</option>
            </select>
        @else
            <div class="d-flex flex-column">
                <a href="{{ route('admin.service.detail', [$data['service_id']]) }}" class="fw-bold">{{ Str::limit($data['service_name'], 30) }}</a>
                <div>{{ Str::limit($data['variant_key'], 50) }}</div>
            </div>
            <input type="hidden" name="service_ids[]" value="{{ $data['service_id'] }}">
            <input type="hidden" name="variant_keys[]" value="{{ $data['variant_key'] }}">
        @endif
    </td>
    <td>
        <input type="number" step="0.001" min="0" name="line_unit_prices[]" class="form-control form-control-sm row-unit-price"
               value="{{ $data['service_cost'] }}">
    </td>
    <td>
        <input type="number" min="1" name="qty[]" class="form-control qty-width row-qty" value="{{ $data['quantity'] }}"
               oninput="this.value = this.value.replace(/[^0-9]/g, '');">
    </td>
    <td>
        <input type="number" step="0.001" min="0" name="line_discount_amounts[]" class="form-control form-control-sm row-discount"
               value="{{ $data['total_discount_amount'] }}">
    </td>
    <td>
        <select name="line_discount_cost_bearers[]" class="form-control form-control-sm row-discount-bearer">
            <option value="none" @selected($discBearer === 'none')>{{ translate('Discount_bearer_none') }}</option>
            <option value="admin" @selected($discBearer === 'admin')>{{ translate('Discount_bearer_admin') }}</option>
            <option value="provider" @selected($discBearer === 'provider')>{{ translate('Discount_bearer_provider') }}</option>
            <option value="both" @selected($discBearer === 'both')>{{ translate('Discount_bearer_both') }}</option>
        </select>
    </td>
    <td class="row-total-cost">{{ $data['total_cost'] }}</td>
    <td>
        <div class="d-flex justify-content-center">
            <span class="material-icons text-danger cursor-pointer remove-service-row"
                  data-row="service-row--{{ $rowId }}">delete</span>
        </div>
    </td>
    <input type="hidden" name="booking_detail_ids[]" value="">
</tr>

<script>
    "use strict";

    $(".remove-service-row").on('click', function (){
        let row = $(this).data('row');
        removeServiceRow(row)
    })
</script>
