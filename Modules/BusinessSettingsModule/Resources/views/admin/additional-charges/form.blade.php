@extends('adminmodule::layouts.new-master')

@section('title', $charge ? translate('Edit_additional_charge') : translate('Add_additional_charge'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                <h2 class="page-title mb-0">{{ $charge ? translate('Edit_additional_charge') : translate('Add_additional_charge') }}</h2>
                <a href="{{ route('admin.business-settings.get-business-information', ['web_page' => 'additional_charges']) }}"
                   class="btn btn--secondary">{{ translate('back') }}</a>
            </div>
            <div class="card">
                <div class="card-body">
                    <form action="{{ $charge ? route('admin.business-settings.additional-charges.update', $charge->id) : route('admin.business-settings.additional-charges.store') }}"
                          method="post" id="additional-charge-type-form">
                        @csrf
                        @if($charge)
                            @method('PUT')
                        @endif
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required maxlength="191"
                                       value="{{ old('name', $charge->name ?? '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ translate('sort_order') }}</label>
                                <input type="number" name="sort_order" class="form-control" min="0"
                                       value="{{ old('sort_order', $charge->sort_order ?? 0) }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                        {{ old('is_active', $charge->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ translate('active') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <input type="hidden" name="customizable_at_booking" value="0">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="customizable_at_booking" value="1" id="customizable_at_booking"
                                        {{ old('customizable_at_booking', $charge->customizable_at_booking ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="customizable_at_booking">{{ translate('Additional_charge_customizable_at_booking') }}</label>
                                </div>
                                <p class="fz-12 text-muted mb-0">{{ translate('Additional_charge_customizable_at_booking_hint') }}</p>
                            </div>
                            <div class="col-md-6">
                                <input type="hidden" name="is_commissionable" value="0">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_commissionable" value="1" id="is_commissionable"
                                        {{ old('is_commissionable', $charge->is_commissionable ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_commissionable">{{ translate('Additional_charge_included_in_commission') }}</label>
                                </div>
                                <p class="fz-12 text-muted mb-0">{{ translate('Additional_charge_commission_hint') }}</p>
                            </div>
                            <div class="col-12">
                                <h6 class="text-dark mb-2">{{ translate('Charge_rules') }}</h6>
                                <p class="fz-12 text-muted">{{ translate('Additional_charges_tier_help') }}</p>
                                @include('businesssettingsmodule::admin.partials.additional-charge-setup-fields', [
                                    'tier' => $tier,
                                    'fieldSuffix' => '',
                                ])
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                                <a href="{{ route('admin.business-settings.get-business-information', ['web_page' => 'additional_charges']) }}"
                                   class="btn btn--secondary">{{ translate('cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('businesssettingsmodule::admin.partials.additional-charge-form-scripts', ['formSelector' => '#additional-charge-type-form'])
@endpush
