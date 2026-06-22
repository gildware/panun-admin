@push('css_or_js')
    <style>
        .provider-identity-name {
            color: #1f2d3d;
            font-weight: 900;
            font-size: 1.05rem;
            line-height: 1.2;
        }

        .status-pill {
            border-radius: 999px;
            padding: .35rem .75rem;
            font-weight: 700;
            font-size: .875rem;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        .status-pill--on {
            background: rgba(54, 179, 126, .15);
            border-color: rgba(54, 179, 126, .35);
            color: #138a57;
        }

        .status-pill--off {
            background: rgba(231, 76, 60, .12);
            border-color: rgba(231, 76, 60, .35);
            color: #c0392b;
        }
    </style>
@endpush

@php
    $company = trim((string) ($provider->company_name ?? ''));
    $contact = trim((string) ($provider->contact_person_name ?? ''));
    $providerDisplayName = $company !== '' && $contact !== ''
        ? $company . ' | ' . $contact
        : ($company !== '' ? $company : ($contact !== '' ? $contact : translate('Provider')));
@endphp

<div class="page-title-wrap mb-3">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="profile-role-pill profile-role-pill--provider">
                    {{ translate('Provider') }}
                </span>
                <h2 class="page-title mb-0 provider-identity-name">{{ $providerDisplayName }}</h2>
                @include('reviewmodule::admin.partials._profile-role-rating', [
                    'compact' => true,
                    'avgRating' => $provider->avg_rating ?? 0,
                    'ratingCount' => $provider->rating_count ?? 0,
                ])
            </div>
            <div class="profile-rating-meta">
                {{ (int) ($provider->rating_count ?? 0) }} {{ translate('provider_ratings') }}
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="status-pill {{ !empty($provider->service_availability) ? 'status-pill--on' : 'status-pill--off' }}">
                Service Availability {{ !empty($provider->service_availability) ? 'ON' : 'OFF' }}
            </span>

            @php($isActive = (int) ($provider?->owner?->is_active ?? 0))
            <span class="status-pill {{ $isActive === 1 ? 'status-pill--on' : 'status-pill--off' }}">
                Status {{ $isActive === 1 ? 'ON' : 'OFF' }}
            </span>

            <span class="status-pill {{ !empty($provider->app_availability) ? 'status-pill--on' : 'status-pill--off' }}">
                App Availability {{ !empty($provider->app_availability) ? 'ON' : 'OFF' }}
            </span>
        </div>
    </div>
</div>
