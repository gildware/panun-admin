@extends('adminmodule::layouts.master')

@section('title', translate('provider_details'))

@push('css_or_js')
    <style>
        .work-showcase-card {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: .75rem;
            background: #fff;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 6px 14px rgba(0, 0, 0, .03);
        }
        .work-showcase-card__media {
            position: relative;
            background: #111827;
            aspect-ratio: 4 / 3;
        }
        .work-showcase-card__media img,
        .work-showcase-card__media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .work-showcase-card__status {
            position: absolute;
            top: .6rem;
            left: .6rem;
        }
        .work-showcase-card__body {
            padding: .85rem .9rem 1rem;
            display: flex;
            flex-direction: column;
            gap: .45rem;
            flex: 1;
        }
        .work-showcase-card__title {
            margin: 0;
            font-size: .95rem;
            font-weight: 700;
            color: #1f2d3d;
        }
        .work-showcase-card__description {
            margin: 0;
            color: #5c6b7a;
            font-size: .82rem;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .work-showcase-card__meta {
            margin-top: auto;
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .75rem;
            color: #758590;
            font-size: .75rem;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                @include('providermanagement::admin.provider.partials._provider-detail-tabs', ['webPage' => $webPage])
            </div>

            <ul class="nav nav--tabs nav--tabs__style2 mb-3">
                @foreach(['all' => 'All', 'approved' => 'Approved', 'pending' => 'Pending', 'denied' => 'Denied'] as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ ($status ?? 'all') === $key ? 'active' : '' }}"
                           href="{{ url()->current() }}?web_page=work_showcase&status={{ $key }}">
                            {{ translate($label) }}
                            <sup class="c2-bg py-1 px-2 radius-50 text-white-absolute">{{ $counts[$key] ?? 0 }}</sup>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="row g-3">
                @forelse($items as $item)
                    @php
                        $approval = (int) $item->is_approved;
                        $statusClass = match ($approval) {
                            \Modules\ProviderManagement\Entities\ProviderShowcaseItem::STATUS_APPROVED => 'badge-success',
                            \Modules\ProviderManagement\Entities\ProviderShowcaseItem::STATUS_DENIED => 'badge-danger',
                            default => 'badge-warning',
                        };
                        $statusLabel = match ($approval) {
                            \Modules\ProviderManagement\Entities\ProviderShowcaseItem::STATUS_APPROVED => translate('Approved'),
                            \Modules\ProviderManagement\Entities\ProviderShowcaseItem::STATUS_DENIED => translate('Denied'),
                            default => translate('Pending'),
                        };
                    @endphp
                    <div class="col-sm-6 col-lg-4 col-xl-3">
                        <div class="work-showcase-card">
                            <div class="work-showcase-card__media">
                                @if($item->media_type === 'video')
                                    <video src="{{ $item->media_full_path }}" controls preload="metadata"></video>
                                @else
                                    <a href="{{ $item->media_full_path }}" target="_blank" rel="noopener">
                                        <img src="{{ $item->media_full_path }}" alt="{{ $item->title ?: translate('image') }}">
                                    </a>
                                @endif
                                <span class="badge {{ $statusClass }} work-showcase-card__status">{{ $statusLabel }}</span>
                            </div>
                            <div class="work-showcase-card__body">
                                <h4 class="work-showcase-card__title">{{ $item->title ?: '-' }}</h4>
                                @if($item->description)
                                    <p class="work-showcase-card__description">{{ $item->description }}</p>
                                @endif
                                <div class="work-showcase-card__meta">
                                    <span>{{ $item->media_type === 'video' ? translate('video') : translate('image') }}</span>
                                    <span>{{ (int) $item->is_active === 1 ? translate('Active') : translate('Inactive') }}</span>
                                    @if($item->created_at)
                                        <span>{{ $item->created_at->format('d M Y') }}</span>
                                    @endif
                                </div>
                                @if($approval === \Modules\ProviderManagement\Entities\ProviderShowcaseItem::STATUS_PENDING)
                                    @can('onboarding_request_approve_or_deny')
                                        <div class="d-flex gap-2 mt-2">
                                            <button type="button" class="btn btn-soft--danger btn-sm showcase_deny" data-id="{{ $item->id }}">{{ translate('Deny') }}</button>
                                            <button type="button" class="btn btn--success btn-sm showcase_approve" data-id="{{ $item->id }}">{{ translate('Accept') }}</button>
                                        </div>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                {{ translate('No_work_showcase_items') }}
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            @if($items->hasPages())
                <div class="d-flex justify-content-end mt-3">{!! $items->links() !!}</div>
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        $('.showcase_deny').on('click', function () {
            let id = $(this).data('id');
            let route = '{{ route('admin.provider.showcase_approval_update', ['id' => ':id', 'status' => 'deny']) }}'.replace(':id', id);
            route_alert_reload(route, '{{ translate('want_to_deny') }}', true);
        });
        $('.showcase_approve').on('click', function () {
            let id = $(this).data('id');
            let route = '{{ route('admin.provider.showcase_approval_update', ['id' => ':id', 'status' => 'approve']) }}'.replace(':id', id);
            route_alert_reload(route, '{{ translate('want_to_approve') }}', true);
        });
    </script>
@endpush
