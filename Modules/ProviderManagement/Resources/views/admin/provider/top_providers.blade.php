@extends('adminmodule::layouts.master')

@section('title', translate('top_providers'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-30">
                <h2 class="page-title">Top Providers</h2>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th style="width: 80px;">{{translate('Sl')}}</th>
                                        <th>{{translate('Provider')}}</th>
                                        <th style="width: 220px;">{{translate('Category')}}</th>
                                        <th style="min-width: 168px; width: 168px;" class="text-end text-nowrap">{{translate('Performance_Score')}}</th>
                                        <th style="width: 140px;" class="text-end">{{translate('Bookings')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($providers as $key => $provider)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                                <div class="media align-items-center gap-3">
                                                    <div class="avatar avatar-lg">
                                                        <a href="{{route('admin.provider.details', [$provider->id, 'web_page' => 'overview'])}}">
                                                            <img class="avatar-img radius-5"
                                                                 src="{{ $provider->logo_full_path }}"
                                                                 alt="{{ translate('provider-logo') }}">
                                                        </a>
                                                    </div>
                                                    <div class="media-body">
                                                        <h5 class="mb-1">
                                                            <a href="{{route('admin.provider.details', [$provider->id, 'web_page' => 'overview'])}}">
                                                                {{ $provider->company_name }}
                                                            </a>
                                                        </h5>
                                                        <p class="m-0 fs-12 opacity-75">
                                                            {{ Str::limit($provider->company_address ?? '', 50) }}
                                                        </p>
                                                    </div>
                                                </div>
                                    </td>
                                            <td>
                                                @php(
                                                    $categoryNames = $provider->subscribed_services
                                                        ? $provider->subscribed_services->pluck('category.name')->filter()->unique()->values()->all()
                                                        : []
                                                )
                                                <span>{{ $categoryNames[0] ?? '—' }}</span>
                                            </td>
                                    <td class="text-end text-nowrap">
                                        <span class="fw-medium">{{ (int) ($provider->performance_score ?? 0) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-medium">{{ $provider->completed_bookings_count }}</span>
                                    </td>
                                </tr>
                            @empty
                                        <tr>
                                            <td colspan="5" class="text-center opacity-75">
                                        {{ translate('No Record Found') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="fs-12 opacity-75 mt-3">
                        Showing top 20 providers by performance score (highest first), among those with at least 1 completed booking counted for revenue.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

