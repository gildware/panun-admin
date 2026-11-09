@extends('adminmodule::layouts.master')

@section('title', translate('Phone_Number_Lookup'))

@push('css_or_js')
    <style>
        .phone-lookup-form textarea {
            min-height: 8rem;
            font-variant-numeric: tabular-nums;
            font-size: .9rem;
        }
        .phone-lookup-stats {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin: .85rem 0;
        }
        .phone-lookup-stat {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            padding: .55rem .8rem;
            min-width: 6.5rem;
        }
        .phone-lookup-stat b {
            display: block;
            font-size: 1.15rem;
            color: #0f766e;
            line-height: 1.1;
        }
        .phone-lookup-stat span {
            font-size: .72rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .phone-lookup-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: .65rem;
            margin-bottom: .85rem;
            overflow: hidden;
        }
        .phone-lookup-card__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem .75rem;
            padding: .75rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #eef0f3;
        }
        .phone-lookup-card__phone {
            font-size: 1.05rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            margin: 0;
        }
        .phone-lookup-card__body {
            padding: .85rem 1rem 1rem;
        }
        .phone-lookup-section {
            margin-bottom: .85rem;
        }
        .phone-lookup-section:last-child {
            margin-bottom: 0;
        }
        .phone-lookup-section h6 {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #6b7280;
            margin: 0 0 .4rem;
            font-weight: 700;
        }
        .phone-lookup-item {
            border: 1px solid #eef0f3;
            border-radius: .45rem;
            padding: .55rem .65rem;
            margin-bottom: .4rem;
            background: #fcfcfd;
        }
        .phone-lookup-item:last-child { margin-bottom: 0; }
        .phone-lookup-item__title {
            font-weight: 600;
            margin: 0 0 .15rem;
        }
        .phone-lookup-item__meta {
            color: #6b7280;
            font-size: .8rem;
            margin: 0;
        }
        .phone-lookup-empty {
            color: #a8a29e;
            font-size: .85rem;
            margin: 0;
        }
        .wa-yes, .wa-no {
            display: inline-block;
            padding: .1rem .45rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
        }
        .wa-yes { background: #dcfce7; color: #166534; }
        .wa-no { background: #ffedd5; color: #9a3412; }
        .phone-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
            margin-top: .35rem;
        }
        .phone-chip {
            background: #ecfdf5;
            color: #0f766e;
            border: 1px solid #99f6e4;
            border-radius: 999px;
            padding: .05rem .5rem;
            font-size: .75rem;
            font-variant-numeric: tabular-nums;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="mb-1">{{ translate('Phone_Number_Lookup') }}</h4>
                    <div class="text-muted small">
                        {{ translate('Paste_multiple_numbers_to_find_provider_onboarding_leads_and_profile_relations') }}
                    </div>
                </div>
                <a href="{{ route('admin.provider.whatsapp-match') }}" class="btn btn-outline-secondary btn-sm">
                    {{ translate('Back_to_WhatsApp_match') }}
                </a>
            </div>

            <div class="card phone-lookup-form mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.provider.whatsapp-match.lookup') }}">
                        <label class="form-label fw-semibold" for="phones">{{ translate('Phone_numbers') }}</label>
                        <textarea
                            class="form-control"
                            id="phones"
                            name="phones"
                            placeholder="{{ translate('Paste_numbers_separated_by_new_line_comma_or_space') }}"
                        >{{ old('phones', $rawPhones) }}</textarea>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn--primary">
                                <span class="material-icons" style="font-size:1rem;vertical-align:middle;">search</span>
                                {{ translate('Search_numbers') }}
                            </button>
                            <a href="{{ route('admin.provider.whatsapp-match.lookup') }}" class="btn btn-light">{{ translate('Clear') }}</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($searched)
                <div class="phone-lookup-stats">
                    <div class="phone-lookup-stat"><b>{{ $stats['searched'] ?? 0 }}</b><span>{{ translate('Searched') }}</span></div>
                    <div class="phone-lookup-stat"><b>{{ $stats['matched'] ?? 0 }}</b><span>{{ translate('Any_match') }}</span></div>
                    <div class="phone-lookup-stat"><b>{{ $stats['none'] ?? 0 }}</b><span>{{ translate('No_match') }}</span></div>
                </div>

                @forelse($results as $row)
                    <div class="phone-lookup-card">
                        <div class="phone-lookup-card__head">
                            <p class="phone-lookup-card__phone">{{ $row['phone'] }}</p>
                            @if($row['in_list'])
                                <span class="wa-yes">{{ translate('Provider_list') }}</span>
                            @endif
                            @if($row['in_onboarding'])
                                <span class="wa-yes">{{ translate('Onboarding') }}</span>
                            @endif
                            @if($row['in_lead'])
                                <span class="wa-yes">{{ translate('Provider_lead') }}</span>
                            @endif
                            @if(!$row['has_any'])
                                <span class="wa-no">{{ translate('No_match') }}</span>
                            @endif
                        </div>
                        <div class="phone-lookup-card__body">
                            <div class="row g-3">
                                <div class="col-md-6 col-xl-3">
                                    <div class="phone-lookup-section">
                                        <h6>{{ translate('Provider_Profile') }}</h6>
                                        @forelse($row['provider_list'] as $item)
                                            <div class="phone-lookup-item">
                                                <p class="phone-lookup-item__title">
                                                    <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                                                </p>
                                                <p class="phone-lookup-item__meta">
                                                    {{ $item['status_label'] }} · {{ !empty($item['is_active']) ? translate('Active') : translate('Inactive') }}
                                                    @if(!empty($item['contact_person_name']))
                                                        · {{ $item['contact_person_name'] }}
                                                    @endif
                                                </p>
                                                <p class="phone-lookup-item__meta">
                                                    {{ translate('Company') }}: {{ $item['company_phone'] ?: '—' }}
                                                    · {{ translate('Contact') }}: {{ $item['contact_person_phone'] ?: '—' }}
                                                    · {{ translate('Owner') }}: {{ $item['owner_phone'] ?: '—' }}
                                                </p>
                                            </div>
                                        @empty
                                            <p class="phone-lookup-empty">—</p>
                                        @endforelse

                                        @foreach($row['other_providers'] as $item)
                                            <div class="phone-lookup-item">
                                                <p class="phone-lookup-item__title">
                                                    <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                                                </p>
                                                <p class="phone-lookup-item__meta">
                                                    {{ $item['status_label'] }} · {{ !empty($item['is_active']) ? translate('Active') : translate('Inactive') }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="phone-lookup-section">
                                        <h6>{{ translate('Onboarding_Profile') }}</h6>
                                        @forelse($row['onboarding'] as $item)
                                            <div class="phone-lookup-item">
                                                <p class="phone-lookup-item__title">
                                                    <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                                                </p>
                                                <p class="phone-lookup-item__meta">
                                                    {{ $item['status_label'] }} · {{ !empty($item['is_active']) ? translate('Active') : translate('Inactive') }}
                                                    @if(!empty($item['contact_person_name']))
                                                        · {{ $item['contact_person_name'] }}
                                                    @endif
                                                </p>
                                            </div>
                                        @empty
                                            <p class="phone-lookup-empty">—</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="phone-lookup-section">
                                        <h6>{{ translate('Provider_Lead') }}</h6>
                                        @forelse($row['leads'] as $item)
                                            <div class="phone-lookup-item">
                                                <p class="phone-lookup-item__title">
                                                    @if(!empty($item['url']))
                                                        <a href="{{ $item['url'] }}">{{ $item['label'] ?? $item['name'] }}</a>
                                                    @else
                                                        {{ $item['label'] ?? $item['name'] }}
                                                    @endif
                                                </p>
                                                <p class="phone-lookup-item__meta">{{ ucfirst($item['kind'] ?? 'lead') }}</p>
                                            </div>
                                        @empty
                                            <p class="phone-lookup-empty">—</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="col-md-6 col-xl-3">
                                    <div class="phone-lookup-section">
                                        <h6>{{ translate('Profile_relations') }}</h6>
                                        @forelse($row['relations'] as $item)
                                            <div class="phone-lookup-item">
                                                <p class="phone-lookup-item__title">
                                                    @if(!empty($item['url']))
                                                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                                                    @else
                                                        {{ $item['label'] }}
                                                    @endif
                                                </p>
                                                <p class="phone-lookup-item__meta">
                                                    {{ str_replace('_', ' ', $item['type'] ?? '') }}
                                                    · via {{ $item['via'] ?? '—' }}
                                                    @if(!empty($item['note']))
                                                        · {{ $item['note'] }}
                                                    @endif
                                                </p>
                                                @if(!empty($item['linked_from_lead_url']))
                                                    <p class="phone-lookup-item__meta">
                                                        {{ translate('Linked_from_lead') }}:
                                                        <a href="{{ $item['linked_from_lead_url'] }}">#{{ $item['linked_from_lead_id'] }}</a>
                                                    </p>
                                                @endif
                                                @if(!empty($item['related_phones']))
                                                    <div class="phone-chips">
                                                        @foreach($item['related_phones'] as $relatedPhone)
                                                            <span class="phone-chip">{{ $relatedPhone }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="phone-lookup-empty">—</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-light border">{{ translate('No_valid_phone_numbers_found_in_input') }}</div>
                @endforelse
            @endif
        </div>
    </div>
@endsection
