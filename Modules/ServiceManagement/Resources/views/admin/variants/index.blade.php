@extends('adminmodule::layouts.master')

@section('title', translate('price_variation'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="page-title mb-1">{{ translate('price_variation') }}</h2>
                    <p class="text-muted mb-0">{{ $service->name }}</p>
                </div>
                <div class="d-flex gap-2">
                    @can('service_update')
                        <a href="{{ route('admin.service.variants.create', $service->id) }}" class="btn btn--primary">
                            {{ translate('add_new') }}
                        </a>
                    @endcan
                    <a href="{{ route('admin.service.edit', ['id' => $service->id, 'tab' => 'variations']) }}" class="btn btn--secondary">
                        {{ translate('back') }}
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @include('servicemanagement::admin.partials._variants-list-table', ['service' => $service])
                </div>
            </div>
        </div>
    </div>
@endsection
