{{--
  Same payment method UX as admin booking create (CAS / digital gateways / wallet / offline methods).
  Expects: $instanceId (string), $advancePaymentMethodGroups (array), $advancePmDisabled (bool), $advancePmSelected (string, optional)
--}}
@php
    $instanceId = (string) ($instanceId ?? 'default');
    $advancePmSelected = (string) ($advancePmSelected ?? '');
    $advancePmDisabled = (bool) ($advancePmDisabled ?? false);
    $apmBuckets = $advancePaymentMethodGroups ?? [];
    $gCas = collect($apmBuckets)->firstWhere('id', 'cas');
    $gDig = collect($apmBuckets)->firstWhere('id', 'digital');
    $gOff = collect($apmBuckets)->firstWhere('id', 'offline');
    $digOpts = $gDig['options'] ?? [];
    $offOpts = $gOff['options'] ?? [];
    $advancePmTier1 = '';
    if ($advancePmSelected !== '') {
        if ($advancePmSelected === 'cash_after_service') {
            $advancePmTier1 = 'cas';
        } elseif (str_starts_with($advancePmSelected, 'offline:')) {
            $advancePmTier1 = 'offline';
        } else {
            $advancePmTier1 = 'digital';
        }
    }
    $showDigWrap = $advancePmTier1 === 'digital' && count($digOpts) > 0;
    $showOffWrap = $advancePmTier1 === 'offline' && count($offOpts) > 0;
    $catName = '_pk_apm_cat_' . $instanceId;
    $digName = '_pk_apm_dig_' . $instanceId;
    $offName = '_pk_apm_off_' . $instanceId;
@endphp
<div class="col-12 pk-apm-scope" data-pk-apm-instance="{{ $instanceId }}">
    <div class="mb-3" id="advance-payment-method-wrap-{{ $instanceId }}">
        <p class="form-label mb-2">{{ translate('Advance_payment_method') }} <span class="text-danger">*</span></p>
        <input type="hidden" name="advance_payment_method" class="pk-apm-hidden" value="{{ $advancePmSelected }}" autocomplete="off">
        @if(empty($advancePaymentMethodGroups))
            <p class="text-muted small mb-0">{{ translate('No_active_payment_methods_for_advance') }}</p>
        @else
            <div class="border rounded-3 p-3">
                <p class="small text-muted mb-2">{{ translate('Select_advance_payment_method') }}</p>
                <div class="d-flex flex-wrap align-items-center gap-4 advance-pm-tier1-row">
                    @if(!empty($gCas['options']))
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input pk-apm-tier1" type="radio" name="{{ $catName }}" value="cas" id="pk-apm-t1-cas-{{ $instanceId }}"
                                   @if($advancePmTier1 === 'cas') checked @endif @if($advancePmDisabled) disabled @endif>
                            <label class="form-check-label text-nowrap" for="pk-apm-t1-cas-{{ $instanceId }}">{{ translate('Cash_After_Service') }}</label>
                        </div>
                    @endif
                    @if(count($digOpts) > 0)
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input pk-apm-tier1" type="radio" name="{{ $catName }}" value="digital" id="pk-apm-t1-digital-{{ $instanceId }}"
                                   @if($advancePmTier1 === 'digital') checked @endif @if($advancePmDisabled) disabled @endif>
                            <label class="form-check-label text-nowrap" for="pk-apm-t1-digital-{{ $instanceId }}">{{ translate('Digital_payment') }}</label>
                        </div>
                    @endif
                    @if(count($offOpts) > 0)
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input pk-apm-tier1" type="radio" name="{{ $catName }}" value="offline" id="pk-apm-t1-offline-{{ $instanceId }}"
                                   @if($advancePmTier1 === 'offline') checked @endif @if($advancePmDisabled) disabled @endif>
                            <label class="form-check-label text-nowrap" for="pk-apm-t1-offline-{{ $instanceId }}">{{ translate('offline_payment') }}</label>
                        </div>
                    @endif
                </div>

                <div class="pk-apm-tier2-digital-wrap mt-3 pt-3 border-top @if(!$showDigWrap) d-none @endif">
                    <p class="small fw-semibold mb-2">{{ translate('Digital_payment') }}</p>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        @foreach($digOpts as $di => $apm)
                            @php
                                $apmRid = 'pk-apm-dig-' . $instanceId . '-' . $di;
                                $apmChecked = $advancePmSelected !== '' && $advancePmSelected === (string) ($apm['key'] ?? '');
                            @endphp
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input pk-apm-tier2-digital" type="radio" name="{{ $digName }}" value="{{ $apm['key'] }}" id="{{ $apmRid }}"
                                       @if($apmChecked) checked @endif @if($advancePmDisabled) disabled @endif>
                                <label class="form-check-label text-nowrap" for="{{ $apmRid }}">{{ $apm['label'] }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pk-apm-tier2-offline-wrap mt-3 pt-3 border-top @if(!$showOffWrap) d-none @endif">
                    <p class="small fw-semibold mb-2">{{ translate('offline_payment') }}</p>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        @foreach($offOpts as $oi => $apm)
                            @php
                                $apmRid = 'pk-apm-off-' . $instanceId . '-' . $oi;
                                $apmChecked = $advancePmSelected !== '' && $advancePmSelected === (string) ($apm['key'] ?? '');
                            @endphp
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input pk-apm-tier2-offline" type="radio" name="{{ $offName }}" value="{{ $apm['key'] }}" id="{{ $apmRid }}"
                                       @if($apmChecked) checked @endif @if($advancePmDisabled) disabled @endif>
                                <label class="form-check-label text-nowrap" for="{{ $apmRid }}">{{ $apm['label'] }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        @error('advance_payment_method')
        <span class="text-danger d-block mt-1">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-12 px-0" id="advance-payment-method-dynamic-fields-wrap-{{ $instanceId }}">
        <div class="pk-apm-dynamic-fields row g-3"></div>
        @error('advance_transaction_id')
        <span class="text-danger d-block mt-1">{{ $message }}</span>
        @enderror
    </div>
</div>
