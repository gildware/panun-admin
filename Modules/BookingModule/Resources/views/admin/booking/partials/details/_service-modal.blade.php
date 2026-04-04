@php
    $bookingProviderId = $booking->provider_id ?? null;
    $editCategories = $bookingEditCategories ?? collect();
@endphp
<div class="modal fade" id="serviceUpdateModal--{{$booking['id']}}" tabindex="-1"
     aria-labelledby="serviceUpdateModalLabel"
     aria-hidden="true"
     data-booking-provider-id="{{ $bookingProviderId }}"
     data-booking-zone-id="{{ $booking->zone_id }}">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header px-4 pt-4 border-0 pb-1">
                <h3 class="text-capitalize">{{translate('update_booking_list')}}</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4">
                <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <div class="mb-30">
                            <label class="form-label small text-muted mb-1">{{ translate('category') }}</label>
                            <select class="theme-input-style w-100" id="category_selector__select"
                                    name="category_id">
                                <option value="" disabled {{ $category?->id ? '' : 'selected' }}>{{ translate('Select_Category') }}</option>
                                @foreach($editCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string)($category?->id) === (string)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="mb-30">
                            <label class="form-label small text-muted mb-1">{{ translate('Sub_Category') }}</label>
                            <select class="theme-input-style w-100" id="sub_category_selector__select"
                                    name="sub_category_id">
                                <option value="" selected disabled>{{ translate('Select_Sub_Category') }}</option>
                                @if($subCategory?->id)
                                    <option value="{{ $subCategory->id }}" selected>{{ $subCategory->name }}</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="mb-30">
                            <label class="form-label small text-muted mb-1">{{ translate('service') }}</label>
                            <select class="theme-input-style w-100" id="service_selector__select" name="service_id"
                                    required>
                                <option value="" selected disabled>{{translate('Select Service')}}</option>
                                @foreach($services as $service)
                                    <option value="{{$service->id}}" data-sub-category-id="{{ $service->sub_category_id ?? '' }}">{{$service->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="mb-30">
                            <label class="form-label small text-muted mb-1">{{ translate('variant') }}</label>
                            <select class="theme-input-style w-100" id="service_variation_selector__select"
                                    name="variant_key" required>
                                <option selected disabled>{{translate('Select Service Variant')}}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="mb-30">
                            <div class="form-floating">
                                <input type="number" class="form-control" name="service_quantity" id="service_quantity"
                                       placeholder="{{translate('service_quantity')}}" min="1"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                                <label>{{translate('service_quantity')}}</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <input type="hidden" name="booking_id" value="{{$booking->id}}">
                        <div class="d-flex gap-3 justify-content-end mb-4">
                            <button type="reset" class="btn btn--secondary">{{translate('reset')}}</button>
                            <button type="submit" class="btn btn--primary"
                                    id="add-service">{{translate('Add Service')}}</button>
                        </div>
                    </div>
                </div>

                <form action="{{route('admin.booking.service.update_booking_service')}}" method="POST"
                      id="booking-edit-table">
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle mb-0" id="service-edit-table">
                            @csrf
                            @method('put')
                            <thead>
                            <tr>
                                <th class="ps-lg-3">{{translate('Service')}}</th>
                                <th>{{translate('Unit_Price') . ' (' . currency_symbol() . ')'}}</th>
                                <th>{{translate('Qty') }}</th>
                                <th>{{translate('Discount') . ' (' . currency_symbol() . ')'}}</th>
                                <th>{{translate('Total') . ' (' . currency_symbol() . ')'}}</th>
                                <th class="text-center">{{translate('Action')}}</th>
                            </tr>
                            </thead>

                            <tbody id="service-edit-tbody">
                            @php $sub_total = 0; @endphp
                            @foreach($booking->detail as $key=>$detail)
                                @php
                                    $zoneVariations = collect();
                                    if ($detail->service) {
                                        $zoneVariations = \Modules\ServiceManagement\Entities\Variation::listForBookingZone(
                                            (string) $detail->service_id,
                                            (string) $booking->zone_id
                                        );
                                        $currentVk = (string) ($detail->variant_key ?? '');
                                        if ($currentVk !== '' && ! $zoneVariations->contains(fn ($v) => (string) $v->variant_key === $currentVk)) {
                                            $fallbackVar = \Modules\ServiceManagement\Entities\Variation::firstForBookingZone(
                                                (string) $detail->service_id,
                                                $currentVk,
                                                (string) $booking->zone_id,
                                                false
                                            );
                                            if ($fallbackVar) {
                                                $zoneVariations = $zoneVariations->push($fallbackVar)->unique('variant_key')->sortBy('variant_key')->values();
                                            }
                                        }
                                    }
                                    $lineDisc = (float) $detail->discount_amount + (float) ($detail->campaign_discount_amount ?? 0);
                                    $rowTaxPct = isset($detail->service) ? effective_service_tax_percentage($detail->service) : company_default_tax_percentage();
                                @endphp
                                <tr id="service-row--{{ $detail->id }}" data-detail-id="{{ $detail->id }}" data-tax-percent="{{ $rowTaxPct }}">
                                    <td class="text-wrap ps-lg-3">
                                        @if(isset($detail->service))
                                            <select name="service_ids[]" class="theme-input-style row-service-select w-100" required
                                                    data-zone-id="{{ $booking->zone_id }}">
                                                @foreach($services as $svc)
                                                    <option value="{{ $svc->id }}" {{ $detail->service_id === $svc->id ? 'selected' : '' }}>{{ Str::limit($svc->name, 40) }}</option>
                                                @endforeach
                                            </select>
                                            <select name="variant_keys[]" class="theme-input-style row-variant-select w-100 mt-1" required>
                                                @forelse($zoneVariations as $v)
                                                    <option value="{{ $v->variant_key }}" {{ (string) $detail->variant_key === (string) $v->variant_key ? 'selected' : '' }}>{{ Str::limit($v->variant ?? $v->variant_key, 40) }}</option>
                                                @empty
                                                    @if($detail->variant_key)
                                                        <option value="{{ $detail->variant_key }}" selected>{{ Str::limit($detail->variant_key, 40) }}</option>
                                                    @endif
                                                @endforelse
                                            </select>
                                        @else
                                            <span class="badge badge-pill badge-danger">{{ translate('Service_unavailable') }}</span>
                                            <input type="hidden" name="service_ids[]" value="{{ $detail->service_id }}">
                                            <input type="hidden" name="variant_keys[]" value="{{ $detail->variant_key }}">
                                        @endif
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" min="0" name="line_unit_prices[]" class="form-control form-control-sm row-unit-price"
                                               value="{{ $detail->service_cost }}">
                                    </td>
                                    <td>
                                        <input type="number" min="1" name="qty[]" class="form-control qty-width row-qty"
                                               value="{{ $detail->quantity }}"
                                               oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                    </td>
                                    <td>
                                        <input type="number" step="0.001" min="0" name="line_discount_amounts[]" class="form-control form-control-sm row-discount"
                                               value="{{ $lineDisc }}">
                                    </td>
                                    <td class="row-total-cost">{{ $detail->total_cost }}</td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            <span class="material-icons text-danger cursor-pointer remove-service-row"
                                                  data-row="service-row--{{ $detail->id }}">delete</span>
                                        </div>
                                    </td>
                                    <input type="hidden" name="booking_detail_ids[]" value="{{ $detail->id }}">
                                </tr>
                                @php $sub_total += $detail->service_cost * $detail->quantity; @endphp
                            @endforeach
                            <input type="hidden" name="zone_id" value="{{$booking->zone_id}}">
                            <input type="hidden" name="booking_id" value="{{$booking->id}}">
                            </tbody>
                        </table>
                    </div>
                </form>

            </div>
            <div class="modal-footer d-flex justify-content-end gap-3 border-0 pt-0 pb-4">
                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal"
                        aria-label="Close">{{translate('Cancel')}}</button>
                <button type="submit" class="btn btn--primary"
                        form="booking-edit-table">{{translate('update_cart')}}</button>
            </div>
        </div>
    </div>
</div>

<script>
    "use strict";

    $(".remove-service-row").on('click', function (){
        let row = $(this).data('row');
        removeServiceRow(row)
    })
</script>
