@extends('adminmodule::layouts.master')

@section('title',translate('provider_preview'))

@push('css_or_js')
    <style>
        .ob-preview-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem 1rem;
            margin-bottom: 1rem;
        }
        .ob-preview-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem .75rem;
            font-size: .82rem;
            color: #6c757d;
        }
        .ob-badge {
            display: inline-flex;
            align-items: center;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
            background: rgba(0, 105, 217, .1);
            color: #0069d9;
        }
        .ob-badge--ok {
            background: rgba(11, 156, 49, .12);
            color: #0b9c31;
        }
        .ob-card {
            border: 1px solid rgba(0, 0, 0, .06);
            border-radius: .65rem;
            padding: .85rem 1rem;
            background: #fff;
            height: 100%;
        }
        .ob-card__title {
            font-size: .88rem;
            font-weight: 600;
            color: #2f3b49;
            margin: 0 0 .65rem;
            display: flex;
            align-items: center;
            gap: .35rem;
        }
        .ob-card__title .material-symbols-outlined,
        .ob-card__title .material-icons {
            font-size: 1.1rem;
            color: var(--c1, #0069d9);
        }
        .ob-person {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
        }
        .ob-person__photo {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: .5rem;
            border: 1px solid rgba(0, 0, 0, .08);
            flex-shrink: 0;
        }
        .ob-person__name {
            font-size: .95rem;
            font-weight: 600;
            margin: 0 0 .25rem;
            line-height: 1.25;
        }
        .ob-line {
            display: flex;
            align-items: flex-start;
            gap: .35rem;
            font-size: .84rem;
            margin-bottom: .2rem;
            word-break: break-word;
        }
        .ob-line:last-child { margin-bottom: 0; }
        .ob-line .material-symbols-outlined {
            font-size: 1rem;
            color: #8a9199;
            margin-top: .1rem;
        }
        .ob-kv-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem .75rem;
        }
        .ob-kv-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        @media (max-width: 575.98px) {
            .ob-kv-grid, .ob-kv-grid--3 { grid-template-columns: 1fr; }
        }
        .ob-kv .k {
            font-size: .72rem;
            color: #7a7f85;
            text-transform: uppercase;
            letter-spacing: .02em;
            margin-bottom: .1rem;
        }
        .ob-kv .v {
            font-size: .86rem;
            font-weight: 500;
            color: #1f2d3d;
            word-break: break-word;
        }
        .ob-doc-grid {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .ob-doc-thumb {
            width: 88px;
            height: 88px;
            object-fit: cover;
            border-radius: .4rem;
            border: 1px solid rgba(0, 0, 0, .08);
        }
        .ob-zone-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            max-height: 140px;
            overflow-y: auto;
        }
        .ob-zone-chip {
            font-size: .75rem;
            padding: .2rem .5rem;
            border-radius: .35rem;
            background: #f1f5f9;
            color: #334155;
            line-height: 1.3;
        }
        .ob-services-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .ob-service-cat {
            font-size: .8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: .2rem;
        }
        .ob-service-subs {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin-bottom: .5rem;
        }
        .ob-service-subs:last-child { margin-bottom: 0; }
        .ob-service-sub {
            font-size: .72rem;
            padding: .15rem .4rem;
            border-radius: .25rem;
            background: rgba(0, 105, 217, .08);
            color: #0069d9;
        }
        .ob-map {
            width: 100%;
            height: 160px;
            border: 0;
            border-radius: .5rem;
        }
        .ob-map-placeholder {
            height: 160px;
            border-radius: .5rem;
            background: #f8f9fa;
            font-size: .8rem;
        }
        .ob-layout > [class*="col-"] { margin-bottom: .75rem; }
        .ob-divider {
            border-top: 1px dashed rgba(0, 0, 0, .08);
            margin: .65rem 0;
        }
    </style>
@endpush

@section('content')
    @php
        $isCompany = ($provider->provider_type ?? 'individual') === 'company';
        $lat = data_get($provider->coordinates, 'latitude');
        $lng = data_get($provider->coordinates, 'longitude');
        $providerTypeLabel = $isCompany ? translate('Company') : translate('Individual');
        $phoneVerified = (bool) ($provider?->owner?->is_phone_verified ?? 0);
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="ob-preview-header">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Provider_Preview') }}</h2>
                    <div class="ob-preview-meta">
                        <span>{{ translate('Requested to join at') }} {{ date('d M Y, h:i A', strtotime($provider->created_at)) }}</span>
                        <span class="ob-badge">{{ $providerTypeLabel }}</span>
                        @if($phoneVerified)
                            <span class="ob-badge ob-badge--ok">{{ translate('Phone_Verified') }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    @can('onboarding_request_update')
                        <a href="{{ route('admin.provider.edit', [$provider->id]) }}" class="btn btn--primary btn-sm">
                            <span class="material-icons" style="font-size:1.1rem">border_color</span>
                            {{ translate('Edit & Approve') }}
                        </a>
                    @endcan
                    @can('onboarding_request_approve_or_deny')
                        @if($provider->is_approved == 2)
                            <button type="button" class="btn btn-danger btn-sm text-capitalize provider_approval"
                                    data-approve="{{ $provider->id }}">{{ translate('Reject') }}</button>
                        @endif
                        @if($provider->is_approved == 0 || $provider->is_approved == 2)
                            <button type="button" class="btn btn--success btn-sm text-capitalize approval_provider"
                                    data-approve="{{ $provider->id }}">{{ translate('Approve') }}</button>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-body p-3">
                    <div class="row ob-layout g-2">

                        @if($isCompany)
                        <div class="col-xl-4 col-md-6">
                            <div class="ob-card">
                                <h3 class="ob-card__title">
                                    <span class="material-symbols-outlined">business</span>
                                    {{ translate('Company_Information') }}
                                </h3>
                                <div class="ob-person">
                                    <img class="ob-person__photo" src="{{ $provider->logo_full_path }}" alt="{{ translate('logo') }}"
                                         onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="ob-person__name">{{ $provider->company_name ?: '-' }}</p>
                                        <div class="ob-line">
                                            <span class="material-symbols-outlined">call</span>
                                            <a href="tel:{{ $provider->company_phone }}">{{ $provider->company_phone ?: '-' }}</a>
                                        </div>
                                        <div class="ob-line">
                                            <span class="material-symbols-outlined">mail</span>
                                            @if($provider->company_email)
                                                <a href="mailto:{{ $provider->company_email }}">{{ $provider->company_email }}</a>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="ob-divider"></div>
                                <div class="ob-kv-grid mb-2">
                                    <div class="ob-kv">
                                        <div class="k">{{ translate('Identity_Type') }}</div>
                                        <div class="v">{{ ucfirst(str_replace('_', ' ', $provider->company_identity_type ?? '-')) }}</div>
                                    </div>
                                    <div class="ob-kv">
                                        <div class="k">{{ translate('Identity_Number') }}</div>
                                        <div class="v">{{ $provider->company_identity_number ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="ob-doc-grid">
                                    @forelse($provider->company_identity_images_full_path ?? [] as $image)
                                        @php $ext = strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION)); @endphp
                                        @if($ext === 'pdf')
                                            <a href="{{ $image }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm py-1 px-2">PDF</a>
                                        @else
                                            <a href="{{ $image }}" target="_blank" rel="noopener">
                                                <img class="ob-doc-thumb" src="{{ $image }}" alt=""
                                                     onerror="this.onerror=null;this.src='{{ asset('assets/admin-module/img/media/provider-id.png') }}'">
                                            </a>
                                        @endif
                                    @empty
                                        <span class="text-muted small">{{ translate('No_data_found') }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="{{ $isCompany ? 'col-xl-4 col-md-6' : 'col-xl-6 col-md-6' }}">
                            <div class="ob-card">
                                <h3 class="ob-card__title">
                                    <span class="material-symbols-outlined">person</span>
                                    {{ translate('Contact_Person') }}
                                </h3>
                                <div class="ob-person">
                                    <img class="ob-person__photo" src="{{ $provider->contact_person_photo_full_path }}"
                                         alt="{{ translate('Contact_Person_Photo') }}"
                                         onerror="this.onerror=null;this.src='{{ asset('assets/admin-module/img/placeholder.png') }}'">
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="ob-person__name">{{ $provider->contact_person_name ?: '-' }}</p>
                                        <div class="ob-line">
                                            <span class="material-symbols-outlined">call</span>
                                            <a href="tel:{{ $provider->contact_person_phone }}">{{ $provider->contact_person_phone ?: '-' }}</a>
                                        </div>
                                        <div class="ob-line">
                                            <span class="material-symbols-outlined">mail</span>
                                            @if($provider->contact_person_email)
                                                <a href="mailto:{{ $provider->contact_person_email }}">{{ $provider->contact_person_email }}</a>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="ob-divider"></div>
                                <div class="ob-kv-grid mb-2">
                                    <div class="ob-kv">
                                        <div class="k">{{ translate('Identity_Type') }}</div>
                                        <div class="v">{{ ucfirst(str_replace(['_', '-'], ' ', $provider?->owner?->identification_type ?? '-')) }}</div>
                                    </div>
                                    <div class="ob-kv">
                                        <div class="k">{{ translate('Identity_Number') }}</div>
                                        <div class="v">{{ $provider?->owner?->identification_number ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="ob-doc-grid">
                                    @forelse($provider?->owner?->identification_image_full_path ?? [] as $image)
                                        @php $ext = strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION)); @endphp
                                        @if($ext === 'pdf')
                                            <a href="{{ $image }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm py-1 px-2">PDF</a>
                                        @else
                                            <a href="{{ $image }}" target="_blank" rel="noopener">
                                                <img class="ob-doc-thumb" src="{{ $image }}" alt=""
                                                     onerror="this.onerror=null;this.src='{{ asset('assets/admin-module/img/media/provider-id.png') }}'">
                                            </a>
                                        @endif
                                    @empty
                                        <span class="text-muted small">{{ translate('No_data_found') }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="{{ $isCompany ? 'col-xl-4 col-md-6' : 'col-xl-6 col-md-6' }}">
                            <div class="ob-card">
                                <h3 class="ob-card__title">
                                    <span class="material-symbols-outlined">map</span>
                                    {{ translate('service_zones') }}
                                </h3>
                                <div class="ob-zone-chips">
                                    @if(!empty($serviceZoneLines))
                                        @foreach($serviceZoneLines as $line)
                                            <span class="ob-zone-chip">{{ $line }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted small">{{ $provider?->zone?->name ?: translate('No_data_found') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-5 col-md-6">
                            <div class="ob-card">
                                <h3 class="ob-card__title">
                                    <span class="material-symbols-outlined">location_on</span>
                                    {{ translate('address_information') }}
                                </h3>
                                <div class="ob-kv mb-2">
                                    <div class="k">{{ translate('Address') }}</div>
                                    <div class="v">{{ $provider->company_address ?: '-' }}</div>
                                </div>
                                <div class="ob-kv-grid mb-2">
                                    <div class="ob-kv">
                                        <div class="k">{{ translate('latitude') }}</div>
                                        <div class="v">{{ filled($lat) ? $lat : '-' }}</div>
                                    </div>
                                    <div class="ob-kv">
                                        <div class="k">{{ translate('longitude') }}</div>
                                        <div class="v">{{ filled($lng) ? $lng : '-' }}</div>
                                    </div>
                                </div>
                                @if(filled($lat) && filled($lng))
                                    <iframe class="ob-map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                                            src="https://maps.google.com/maps?q={{ urlencode($lat . ',' . $lng) }}&z=15&output=embed"></iframe>
                                @else
                                    <div class="ob-map-placeholder d-flex align-items-center justify-content-center text-muted">
                                        {{ translate('No_data_found') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-xl-7 col-md-6">
                            <div class="ob-card">
                                <h3 class="ob-card__title">
                                    <span class="material-symbols-outlined">home_repair_service</span>
                                    {{ translate('Services') }}
                                </h3>
                                <div class="ob-services-list">
                                    @if(($subscribedServicesByCategory ?? collect())->isEmpty())
                                        <span class="text-muted small">{{ translate('No_data_found') }}</span>
                                    @else
                                        @foreach($subscribedServicesByCategory as $group)
                                            <div class="ob-service-cat">{{ $group['category_name'] }}</div>
                                            <div class="ob-service-subs">
                                                @forelse($group['subcategories'] as $subName)
                                                    <span class="ob-service-sub">{{ $subName }}</span>
                                                @empty
                                                    <span class="text-muted small">-</span>
                                                @endforelse
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        @include('providermanagement::admin.provider.detail._pending-showcase')

                    </div>
                </div>
            </div>
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

        $('.provider_approval').on('click', function () {
            let itemId = $(this).data('approve');
            let route = '{{ route('admin.provider.update-approval', ['id' => ':itemId', 'status' => 'deny']) }}';
            route = route.replace(':itemId', itemId);
            route_alert_reload(route, '{{ translate('want_to_deny_the_provider') }}', true);
        });

        $('.approval_provider').on('click', function () {
            let itemId = $(this).data('approve');
            let route = '{{ route('admin.provider.update-approval', ['id' => ':itemId', 'status' => 'approve']) }}';
            route = route.replace(':itemId', itemId);
            route_alert_reload(route, '{{ translate('want_to_approve_the_provider') }}', true);
        });
    </script>
@endpush
