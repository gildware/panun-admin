<div class="card border border-dashed mb-3">
    <div class="card-body">
        <div class="d-flex gap-3 align-items-start flex-wrap">
            <div class="flex-shrink-0">
                @include('servicemanagement::admin.partials._variant-uploader', [
                    'inputId' => 'variant-image',
                ])
            </div>

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-2">
                    <div class="form-floating flex-grow-1" style="min-width: 180px;">
                        <input type="text" class="form-control" id="variant-name"
                               placeholder="{{ translate('add_variant') }} *">
                        <label>{{ translate('add_variant') }} *</label>
                    </div>
                    <div class="form-floating" style="width: 150px;">
                        <input type="number" class="form-control" id="variant-price"
                               placeholder="{{ translate('price') }} *" value="0" min="0" step="any">
                        <label>{{ translate('price') }} *</label>
                    </div>
                </div>
                <div class="form-floating">
                    <textarea class="form-control" id="variant-description"
                              placeholder="{{ translate('description') }}" style="min-height: 72px;"></textarea>
                    <label>{{ translate('description') }}</label>
                </div>
            </div>

            <div class="flex-shrink-0 align-self-end">
                <button type="button" class="btn btn--primary" id="service-ajax-variation"@if(!empty($variantAddOnclick)) onclick="{{ $variantAddOnclick }}"@endif>
                    <span class="material-icons">add</span>
                    {{ translate('add') }}
                </button>
            </div>
        </div>
    </div>
</div>
