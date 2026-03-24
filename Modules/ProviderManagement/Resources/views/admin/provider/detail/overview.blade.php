@extends('adminmodule::layouts.master')

@section('title',translate('provider_details'))

@push('css_or_js')
    <style>
        .overview-widgets-row .col-xl-4 {
            display: flex;
        }
        .overview-widget-card {
            border: 1px solid rgba(0, 0, 0, .06);
            border-radius: .75rem;
            padding: .75rem .85rem;
            background: #fff;
            height: 100%;
            width: 100%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(0, 0, 0, .03);
        }
        .overview-widget-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: linear-gradient(90deg, #0069d9 0%, #36b37e 100%);
        }
        .overview-widget-card.widget-service::before {
            background: linear-gradient(90deg, #5f27cd 0%, #10ac84 100%);
        }
        .overview-widget-card.widget-revenue::before {
            background: linear-gradient(90deg, #f39c12 0%, #e74c3c 100%);
        }
        .overview-widget-total {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2d3d;
            line-height: 1.15;
            margin-bottom: .45rem;
        }
        .overview-widget-title {
            font-size: .86rem;
            font-weight: 600;
            margin-bottom: .35rem;
            color: #5f6b7a;
        }
        .overview-stat-line {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            font-size: .8rem;
            margin-bottom: .25rem;
            color: #4f5d6a;
        }
        .overview-stat-line:last-child {
            margin-bottom: 0;
        }
        .overview-stat-line strong {
            color: #1f2d3d;
        }
        .overview-widget-divider {
            margin: .45rem 0;
            border-top: 1px dashed rgba(0,0,0,.08);
        }
        .booking-status-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem;
            margin-top: .35rem;
        }
        .booking-status-chip {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            padding: .2rem .5rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
            line-height: 1;
            width: 100%;
            justify-content: center;
        }
        .booking-status-chip.accepted {
            background: rgba(11, 156, 49, .12);
            color: #0b9c31;
        }
        .booking-status-chip.ongoing {
            background: rgba(11, 121, 230, .12);
            color: #0b79e6;
        }
        .booking-status-chip.completed {
            background: rgba(85, 71, 216, .12);
            color: #5547d8;
        }
        .booking-status-chip.canceled {
            background: rgba(226, 81, 65, .12);
            color: #e25141;
        }
        .overview-doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: .75rem;
        }
        .overview-doc-grid--two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .overview-doc-thumb {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: .5rem;
            border: 1px solid rgba(0,0,0,.08);
            background: #f8f9fa;
        }
        .overview-section-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: .65rem;
            color: #2f3b49;
        }
        .overview-muted-label {
            font-size: .8rem;
            color: #7a7f85;
            text-transform: uppercase;
            letter-spacing: .03em;
            margin-bottom: .2rem;
        }
        .overview-value {
            margin-bottom: .55rem;
            word-break: break-word;
            color: #1f2d3d;
            font-size: .92rem;
        }
        .overview-info-card {
            border: 1px solid rgba(0, 0, 0, .06);
            border-radius: .75rem;
            padding: .85rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            box-shadow: 0 4px 10px rgba(0,0,0,.02);
        }
        .overview-info-card hr {
            margin: .65rem 0;
            opacity: .08;
        }
        .overview-person-title {
            font-size: .98rem;
            font-weight: 600;
            margin-bottom: .2rem;
            color: #1f2d3d;
        }
        .overview-person-line {
            margin-bottom: .2rem;
            font-size: .86rem;
        }
        .overview-info-heading {
            margin-bottom: .55rem !important;
        }
        .overview-info-list {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }
        .overview-info-item {
            display: flex;
            align-items: flex-start;
            gap: .45rem;
            font-size: .86rem;
            color: #1f2d3d;
            line-height: 1.35;
        }
        .overview-info-item .material-symbols-outlined {
            font-size: 1rem;
            color: #64748b;
            margin-top: .05rem;
        }
        .overview-identity-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-bottom: .55rem;
        }
        .overview-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: .15rem;
            margin-bottom: .35rem;
        }
        .overview-doc-card {
            border: 1px solid rgba(0,0,0,.06) !important;
            border-radius: .7rem !important;
            padding: .75rem !important;
            background: #fff;
        }
        .overview-doc-card h6 {
            font-size: .9rem;
            margin-bottom: .35rem !important;
            color: #243447;
        }
        .overview-doc-card .fs-12 {
            line-height: 1.35;
            margin-bottom: .55rem !important;
        }
        .overview-page-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2a3643;
        }
        .overview-map-frame {
            width: 100%;
            min-height: 220px;
            border: 0;
            border-radius: .6rem;
            background: #f4f6f8;
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
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'overview' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscribed_services' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'performance' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id ?? request()->provider_id }}">{{ translate('Business Plan') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-body p-30">
                    <div class="row g-3 mb-4 overview-widgets-row">
                        <div class="col-xl-4 col-md-6">
                            <div class="overview-widget-card">
                                <h4 class="overview-widget-title mb-0">
                                    {{ translate('Booking_Overview') }} ({{ (int) $provider->bookings_count }})
                                </h4>
                                <div class="booking-status-row">
                                    <span class="booking-status-chip accepted">{{ translate('Accepted') }} ({{ $bookingStatusCounts['accepted'] ?? 0 }})</span>
                                    <span class="booking-status-chip ongoing">{{ translate('Ongoing') }} ({{ $bookingStatusCounts['ongoing'] ?? 0 }})</span>
                                    <span class="booking-status-chip completed">{{ translate('Completed') }} ({{ $bookingStatusCounts['completed'] ?? 0 }})</span>
                                    <span class="booking-status-chip canceled">{{ translate('Canceled') }} ({{ $bookingStatusCounts['canceled'] ?? 0 }})</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="overview-widget-card widget-service">
                                <h4 class="overview-widget-title mb-0">{{ translate('Total_Subscribed_Services') }} ({{ $totalSubscribedServices }})</h4>
                                <div class="overview-widget-divider"></div>
                                @php
                                    $serviceBreakdown = collect($subscribedServiceCategoryCounts)->take(4);
                                @endphp
                                @forelse($serviceBreakdown as $catCount)
                                    <div class="overview-stat-line">
                                        <span class="text-truncate pe-2">{{ data_get($catCount, 'category_name', translate('Unknown')) }}</span>
                                        <strong>{{ (int) $catCount->total }}</strong>
                                    </div>
                                @empty
                                    <p class="mb-0 text-muted fs-12">{{ translate('No_data_found') }}</p>
                                @endforelse
                                @if(collect($subscribedServiceCategoryCounts)->count() > 4)
                                    <div class="overview-stat-line text-muted">
                                        <span>{{ translate('Others') }}</span>
                                        <strong>{{ (int) collect($subscribedServiceCategoryCounts)->slice(4)->sum('total') }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-12">
                            <div class="overview-widget-card widget-revenue">
                                <h4 class="overview-widget-title mb-0">{{ translate('Revenue_Overview') }} ({{ with_currency_symbol($totalRevenue) }})</h4>
                                <div class="overview-widget-divider"></div>
                                <div class="overview-stat-line">
                                    <span>{{ translate('Provider_Net_Earning') }}</span>
                                    <strong>{{ with_currency_symbol($providerNetEarning) }}</strong>
                                </div>
                                <div class="overview-stat-line">
                                    <span>{{ translate('Total_Company_Commission') }}</span>
                                    <strong>{{ with_currency_symbol($totalCompanyCommission) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overview-section-header">
                        <h2 class="overview-page-title mb-0">{{translate('Information_Details')}}</h2>
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            @if($provider->is_approved == 2)
                                <a type="button"
                                   class="btn btn-soft--danger text-capitalize provider_approval"
                                   id="button-deny-{{$provider->id}}" data-approve="{{$provider->id}}"
                                   data-status="deny">
                                    {{translate('Deny')}}
                                </a>
                            @endif
                            @if($provider->is_approved == 0 || $provider->is_approved == 2)
                                <a type="button" class="btn btn--success text-capitalize approval_provider"
                                   id="button-{{$provider->id}}" data-approve="{{$provider->id}}"
                                   data-approve="approve">
                                    {{translate('Accept')}}
                                </a>
                            @endif

                            @can('provider_update')
                                <a href="{{route('admin.provider.edit',[$provider->id])}}" class="btn btn--primary">
                                    <span class="material-icons">border_color</span>
                                    {{translate('Edit')}}
                                </a>
                            @endcan
                        </div>
                    </div>

                    <div class="row g-3">
                        @if(($provider->provider_type ?? 'individual') === 'company')
                            <div class="col-12">
                                <h4 class="overview-section-title">{{ translate('Company_Information') }}</h4>
                            </div>
                            <div class="col-lg-6">
                                <div class="information-details-box overview-info-card h-100">
                                    <h5 class="mb-2 overview-info-heading">{{ translate('Basic_Company_Information') }}</h5>
                                    <div class="d-flex align-items-center gap-3">
                                        <img class="avatar-img radius-5" src="{{ $provider->logo_full_path }}" alt="{{ translate('logo') }}">
                                        <div>
                                            <div class="overview-info-list">
                                                <div class="overview-info-item">
                                                    <span class="material-symbols-outlined">person</span>
                                                    <span>{{ $provider->company_name ?: '-' }}</span>
                                                </div>
                                                <div class="overview-info-item">
                                                    <span class="material-symbols-outlined">call</span>
                                                    <a href="tel:{{ $provider->company_phone }}">{{ $provider->company_phone }}</a>
                                                </div>
                                                <div class="overview-info-item">
                                                    <span class="material-symbols-outlined">mail</span>
                                                    <a href="mailto:{{ $provider->company_email }}">{{ $provider->company_email }}</a>
                                                </div>
                                                <div class="overview-info-item">
                                                    <span class="material-symbols-outlined">location_on</span>
                                                    <span>{{ $provider->company_address ?: '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="information-details-box overview-info-card h-100">
                                    <h5 class="mb-2">{{ translate('Company_Docs_&_Identity') }}</h5>
                                    <div class="overview-identity-row">
                                        <div>
                                            <div class="overview-muted-label">{{ translate('Identity_Type') }}</div>
                                            <div class="overview-value mb-0">{{ ucfirst(str_replace('_', ' ', $provider->company_identity_type ?? '-')) }}</div>
                                        </div>
                                        <div>
                                            <div class="overview-muted-label">{{ translate('Identity_Number') }}</div>
                                            <div class="overview-value mb-0">{{ $provider->company_identity_number ?: '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="overview-doc-grid overview-doc-grid--two">
                                        @php
                                            $companyIdentityImages = $provider->company_identity_images_full_path ?? [];
                                        @endphp
                                        @if(empty($companyIdentityImages))
                                            <span class="text-muted">{{ translate('No_data_found') }}</span>
                                        @else
                                            @foreach(array_slice($companyIdentityImages, 0, 2) as $image)
                                                @if(strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION)) === 'pdf')
                                                    <a class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" target="_blank" rel="noopener" href="{{ $image }}">PDF</a>
                                                @else
                                                    <a href="{{ $image }}" target="_blank" rel="noopener"><img class="overview-doc-thumb" src="{{ $image }}" alt="{{ translate('image') }}"></a>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-12">
                            <h4 class="overview-section-title">{{ translate('Contact_Person') }}</h4>
                        </div>
                        <div class="col-lg-6">
                            <div class="information-details-box overview-info-card h-100">
                                <h5 class="mb-2 overview-info-heading">{{ translate('Basic_Contact_Person_Information') }}</h5>
                                <div class="d-flex align-items-center gap-3">
                                    <img
                                        class="avatar-img radius-5"
                                        src="{{ onErrorImage($provider->contact_person_photo, asset('storage/provider/contact_person_photo') . '/' . $provider->contact_person_photo, asset('assets/admin-module/img/placeholder.png'), 'provider/contact_person_photo/') }}"
                                        alt="{{ translate('Contact_Person_Photo') }}">
                                    <div>
                                        <div class="overview-info-list">
                                            <div class="overview-info-item">
                                                <span class="material-symbols-outlined">person</span>
                                                <span>{{ $provider->contact_person_name ?: '-' }}</span>
                                            </div>
                                            <div class="overview-info-item">
                                                <span class="material-symbols-outlined">call</span>
                                                <a href="tel:{{ $provider->contact_person_phone }}">{{ $provider->contact_person_phone }}</a>
                                            </div>
                                            <div class="overview-info-item">
                                                <span class="material-symbols-outlined">mail</span>
                                                <a href="mailto:{{ $provider->contact_person_email }}">{{ $provider->contact_person_email }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="information-details-box overview-info-card h-100">
                                <h5 class="mb-2">{{ translate('Contact_Person_Identity') }}</h5>
                                <div class="overview-identity-row">
                                    <div>
                                        <div class="overview-muted-label">{{ translate('Identity_Type') }}</div>
                                        <div class="overview-value mb-0">{{ ucfirst(str_replace('_', ' ', $provider?->owner?->identification_type ?? '-')) }}</div>
                                    </div>
                                    <div>
                                        <div class="overview-muted-label">{{ translate('Identity_Number') }}</div>
                                        <div class="overview-value mb-0">{{ $provider?->owner?->identification_number ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="overview-doc-grid">
                                    @forelse(($provider?->owner?->identification_image_full_path ?? []) as $image)
                                        @php $ext = strtolower(pathinfo(parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION)); @endphp
                                        @if($ext === 'pdf')
                                            <a class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" target="_blank" rel="noopener" href="{{ $image }}">PDF</a>
                                        @else
                                            <a href="{{ $image }}" target="_blank" rel="noopener"><img class="overview-doc-thumb" src="{{ $image }}" alt="{{ translate('image') }}"></a>
                                        @endif
                                    @empty
                                        <span class="text-muted">{{ translate('No_data_found') }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <h4 class="overview-section-title">{{ translate('Address_Information') }}</h4>
                        </div>
                        <div class="col-12">
                            <div class="information-details-box overview-info-card">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <div class="overview-muted-label">{{ translate('Zone') }}</div>
                                        <div class="overview-value">{{ $provider?->zone?->name ?: '-' }}</div>

                                        <div class="overview-muted-label">{{ translate('Address') }}</div>
                                        <div class="overview-value">{{ $provider->company_address ?: '-' }}</div>

                                        <div class="overview-muted-label">{{ translate('latitude') }}</div>
                                        <div class="overview-value">{{ data_get($provider->coordinates, 'latitude', '-') }}</div>

                                        <div class="overview-muted-label">{{ translate('longitude') }}</div>
                                        <div class="overview-value mb-0">{{ data_get($provider->coordinates, 'longitude', '-') }}</div>
                                    </div>
                                    <div class="col-lg-6">
                                        @php
                                            $lat = data_get($provider->coordinates, 'latitude');
                                            $lng = data_get($provider->coordinates, 'longitude');
                                        @endphp
                                        @if(filled($lat) && filled($lng))
                                            <iframe
                                                class="overview-map-frame"
                                                loading="lazy"
                                                referrerpolicy="no-referrer-when-downgrade"
                                                src="https://maps.google.com/maps?q={{ urlencode($lat . ',' . $lng) }}&z=15&output=embed"></iframe>
                                        @else
                                            <div class="d-flex align-items-center justify-content-center overview-map-frame text-muted">
                                                {{ translate('No_data_found') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <h4 class="overview-section-title">{{ translate('Additional_Documents') }}</h4>
                        </div>
                        <div class="col-12">
                            <div class="information-details-box overview-info-card">
                                @if($additionalDocuments->isEmpty())
                                    <p class="text-muted mb-0">{{ translate('No_data_found') }}</p>
                                @else
                                    <div class="row g-3">
                                        @foreach($additionalDocuments as $doc)
                                            <div class="col-xl-4 col-lg-6">
                                                <div class="overview-doc-card h-100">
                                                    <h6 class="mb-1">{{ data_get($doc, 'document_name', data_get($doc, 'name', translate('Document'))) }}</h6>
                                                    @if(!empty(data_get($doc, 'document_description', data_get($doc, 'description'))))
                                                        <p class="text-muted fs-12 mb-2">{{ data_get($doc, 'document_description', data_get($doc, 'description')) }}</p>
                                                    @endif
                                                    <div class="overview-doc-grid">
                                                        @forelse(($additionalDocumentFiles[$doc->id] ?? collect()) as $docFile)
                                                            @php
                                                                $filePath = data_get($docFile, 'file_path', data_get($docFile, 'file_name'));
                                                                $fileDisk = data_get($docFile, 'storage', 'public');
                                                                $normalizedPath = null;
                                                                if (is_string($filePath) && $filePath !== '') {
                                                                    if (\Illuminate\Support\Str::startsWith($filePath, ['http://', 'https://'])) {
                                                                        $normalizedPath = $filePath;
                                                                    } elseif (str_contains($filePath, '/')) {
                                                                        $normalizedPath = $filePath;
                                                                    } else {
                                                                        $normalizedPath = 'provider/additional-documents/' . $doc->id . '/' . $filePath;
                                                                    }
                                                                }
                                                                $fileUrl = null;
                                                                if ($normalizedPath) {
                                                                    $fileUrl = \Illuminate\Support\Str::startsWith($normalizedPath, ['http://', 'https://'])
                                                                        ? $normalizedPath
                                                                        : \Illuminate\Support\Facades\Storage::disk($fileDisk)->url($normalizedPath);
                                                                }
                                                                $ext = strtolower(pathinfo((string) $filePath, PATHINFO_EXTENSION));
                                                            @endphp
                                                            @if(!$fileUrl)
                                                                <span class="text-muted">{{ translate('No_data_found') }}</span>
                                                            @elseif($ext === 'pdf')
                                                                <a class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" target="_blank" rel="noopener" href="{{ $fileUrl }}">PDF</a>
                                                            @else
                                                                <a href="{{ $fileUrl }}" target="_blank" rel="noopener"><img class="overview-doc-thumb" src="{{ $fileUrl }}" alt="{{ translate('image') }}"></a>
                                                            @endif
                                                        @empty
                                                            <span class="text-muted">{{ translate('No_data_found') }}</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

    <script>
        "use strict";

        $('.provider_approval').on('click', function () {
            let itemId = $(this).data('approve');
            let route = '{{ route('admin.provider.update-approval', ['id' => ':itemId', 'status' => 'deny']) }}';
            route = route.replace(':itemId', itemId);
            route_alert_reload(route, '{{ translate('want_to_deny_the_provider') }}');
        });

        $('.approval_provider').on('click', function () {
            let itemId = $(this).data('approve');
            let route = '{{ route('admin.provider.update-approval', ['id' => ':itemId', 'status' => 'approve']) }}';
            route = route.replace(':itemId', itemId);
            route_alert_reload(route, '{{ translate('want_to_approve_the_provider') }}');
        });
    </script>
@endpush
