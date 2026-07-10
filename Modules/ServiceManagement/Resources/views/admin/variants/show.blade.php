@extends('adminmodule::layouts.master')

@section('title', translate('view'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="page-title mb-1">{{ translate('view') }} — {{ $variant->title }}</h2>
                    <p class="text-muted mb-0">{{ $service->name }}</p>
                </div>
                <div class="d-flex gap-2">
                    @can('service_update')
                        <a href="{{ route('admin.service.variants.edit', [$service->id, $variant->id]) }}" class="btn btn--primary">
                            {{ translate('edit') }}
                        </a>
                    @endcan
                    <a href="{{ route('admin.service.edit', ['id' => $service->id, 'tab' => 'variations']) }}" class="btn btn--secondary">
                        {{ translate('back') }}
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-30">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="upload-file ratio-1 w-150px input-disabled">
                                <div class="upload-file__img border-dashed-1-gray rounded">
                                    <img src="{{ $variant->image_full_path }}" alt="{{ translate('image') }}" class="w-100">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label text-muted mb-1">{{ translate('title') }}</label>
                                <p class="mb-0 fw-semibold text-dark">{{ $variant->title }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted mb-1">{{ translate('description') }}</label>
                                <p class="mb-0 text-muted">{{ $variant->getRawOriginal('description') ?: '—' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted mb-1">{{ translate('variant_note') }}</label>
                                <p class="mb-0 text-muted">{{ $variant->getRawOriginal('note') ?: '—' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted mb-1">{{ translate('default_price') }}</label>
                                <p class="mb-0 fw-semibold c1">{{ with_currency_symbol($defaultPrice) }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted mb-1">{{ translate('status') }}</label>
                                <p class="mb-0">
                                    @if($variant->is_active)
                                        <span class="badge badge-success">{{ translate('active') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ translate('inactive') }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="mb-0">
                                <label class="form-label text-muted mb-1">{{ translate('zone_pricing') }}</label>
                                <p class="mb-0">{{ $zonePricingOn ? translate('enabled') : translate('disabled') }}</p>
                            </div>
                        </div>
                    </div>

                    @if($zones->count())
                        <hr class="my-4">
                        <h5 class="mb-3">{{ translate('zone_wise_price') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>{{ translate('zone') }}</th>
                                    <th>{{ translate('price') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($zones as $zone)
                                    @php
                                        $zonePrice = $variant->zonePrices->firstWhere('zone_id', $zone->id);
                                    @endphp
                                    <tr>
                                        <td>{{ $zone->name }}</td>
                                        <td>{{ with_currency_symbol($zonePrice->price ?? $defaultPrice) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
