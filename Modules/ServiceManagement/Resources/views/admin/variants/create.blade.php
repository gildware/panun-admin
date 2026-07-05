@extends('adminmodule::layouts.master')

@section('title', translate('add_new'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h2 class="page-title mb-0">{{ translate('add_new') }} {{ translate('price_variation') }}</h2>
                <a href="{{ route('admin.service.edit', ['id' => $service->id, 'tab' => 'variations']) }}" class="btn btn--secondary">{{ translate('back') }}</a>
            </div>

            <div class="card">
                <div class="card-body p-30">
                    <form action="{{ route('admin.service.variants.store', $service->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-4">
                            <div class="col-md-4">
                                @include('servicemanagement::admin.partials._variant-uploader', [
                                    'inputName' => 'image',
                                    'previewUrl' => asset('assets/admin-module/img/media/upload-file.png'),
                                ])
                            </div>
                            <div class="col-md-8">
                                <div class="form-floating mb-3">
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                                    <label>{{ translate('title') }}</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <textarea name="description" class="form-control" style="min-height:120px;">{{ old('description') }}</textarea>
                                    <label>{{ translate('description') }}</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="variant-active" checked>
                                    <label class="form-check-label" for="variant-active">{{ translate('active') }}</label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">{{ translate('default_price') }}</h5>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <input type="number" name="default_price" class="form-control" min="0.01" step="any"
                                       value="{{ old('default_price') }}" required>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="variant_use_zone_pricing" value="1"
                                           id="variant-zone-pricing" {{ old('variant_use_zone_pricing') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="variant-zone-pricing">{{ translate('zone_pricing') }}</label>
                                </div>
                            </div>
                        </div>

                        @if($zones->count())
                            <div class="table-responsive mt-4 opacity-50" id="variant-zone-price-table">
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>{{ translate('zone') }}</th>
                                        <th>{{ translate('price') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($zones as $zone)
                                        <tr>
                                            <td>{{ $zone->name }}</td>
                                            <td>
                                                <input type="number" class="form-control"
                                                       name="zone_prices[{{ $zone->id }}]"
                                                       value="{{ old('zone_prices.'.$zone->id, 0) }}" min="0" step="any" readonly>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn--primary">{{ translate('save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.getElementById('variant-zone-pricing')?.addEventListener('change', function () {
            const enabled = this.checked;
            document.querySelectorAll('#variant-zone-price-table input[type="number"]').forEach(function (el) {
                el.readOnly = !enabled;
            });
            document.getElementById('variant-zone-price-table')?.classList.toggle('opacity-50', !enabled);
        });
    </script>
@endpush
