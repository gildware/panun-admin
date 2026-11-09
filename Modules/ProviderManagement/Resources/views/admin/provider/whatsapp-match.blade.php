@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp_Provider_Match'))

@push('css_or_js')
    <style>
        .wa-match-stats {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: .85rem;
        }
        .wa-match-stat {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            padding: .55rem .8rem;
            min-width: 6.5rem;
        }
        .wa-match-stat b {
            display: block;
            font-size: 1.15rem;
            color: #0f766e;
            line-height: 1.1;
        }
        .wa-match-stat span {
            font-size: .72rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .wa-match-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            align-items: center;
            margin-bottom: .75rem;
        }
        .wa-match-toolbar input[type="search"] {
            flex: 1 1 14rem;
            min-width: 12rem;
            max-width: 22rem;
            height: 2.1rem;
            border: 1px solid #d1d5db;
            border-radius: .4rem;
            padding: 0 .65rem;
            font-size: .85rem;
        }
        .wa-match-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem .75rem;
            font-size: .8rem;
            color: #6b7280;
        }
        .wa-match-filters label {
            cursor: pointer;
            margin: 0;
            white-space: nowrap;
        }
        .wa-match-table-wrap {
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: .55rem;
            background: #fff;
        }
        .wa-match-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8125rem;
            margin: 0;
        }
        .wa-match-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #111827;
            color: #fff;
            text-align: left;
            padding: .65rem .55rem;
            white-space: nowrap;
            font-weight: 600;
        }
        .wa-match-table td {
            padding: .55rem;
            border-bottom: 1px solid #eef0f3;
            vertical-align: top;
        }
        .wa-match-table tr.cat-head td {
            background: #ecfdf5;
            border-bottom: 2px solid #99f6e4;
            font-weight: 600;
        }
        .wa-match-table tr.cat-head .cat-meta {
            font-weight: 400;
            color: #6b7280;
            font-size: .78rem;
            margin-left: .4rem;
        }
        .wa-match-table tr.unmatched td {
            opacity: .72;
        }
        .wa-match-table tbody tr:not(.cat-head):hover td {
            background: #f8fafc;
        }
        .wa-phone {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            white-space: nowrap;
        }
        .wa-check {
            text-align: center;
            white-space: nowrap;
        }
        .wa-yes,
        .wa-no {
            display: inline-block;
            padding: .1rem .45rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
        }
        .wa-yes { background: #dcfce7; color: #166534; }
        .wa-no { background: #ffedd5; color: #9a3412; }
        .wa-miss { color: #a8a29e; }
        .wa-match-note {
            margin-top: .75rem;
            color: #6b7280;
            font-size: .78rem;
        }
    </style>
@endpush

@section('content')
    @php
        $yes = '<span class="wa-yes">Yes</span>';
        $no = '<span class="wa-no">No</span>';
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="mb-1">{{ translate('WhatsApp_Provider_Match') }}</h4>
                    <div class="text-muted small">
                        {{ translate('Match_WhatsApp_provider_numbers_against_provider_list_onboarding_and_leads') }}
                    </div>
                </div>
            </div>

            <div class="wa-match-stats">
                <div class="wa-match-stat"><b>{{ $stats['unique_phones'] ?? 0 }}</b><span>{{ translate('Unique_numbers') }}</span></div>
                <div class="wa-match-stat"><b>{{ $stats['rows'] ?? 0 }}</b><span>{{ translate('Rows') }}</span></div>
                <div class="wa-match-stat"><b>{{ $stats['matched'] ?? 0 }}</b><span>{{ translate('Any_match') }}</span></div>
                <div class="wa-match-stat"><b>{{ $stats['in_list'] ?? 0 }}</b><span>{{ translate('Provider_list') }}</span></div>
                <div class="wa-match-stat"><b>{{ $stats['in_onboarding'] ?? 0 }}</b><span>{{ translate('Onboarding') }}</span></div>
                <div class="wa-match-stat"><b>{{ $stats['in_lead'] ?? 0 }}</b><span>{{ translate('Provider_lead') }}</span></div>
                <div class="wa-match-stat"><b>{{ $stats['none'] ?? 0 }}</b><span>{{ translate('No_match') }}</span></div>
            </div>

            <div class="wa-match-toolbar">
                <input type="search" id="wa-match-q" placeholder="{{ translate('Search_phone_category_name') }}">
                <div class="wa-match-filters">
                    <label><input type="radio" name="wa-match-f" value="all" checked> {{ translate('All') }}</label>
                    <label><input type="radio" name="wa-match-f" value="matched"> {{ translate('Matched') }}</label>
                    <label><input type="radio" name="wa-match-f" value="unmatched"> {{ translate('Unmatched') }}</label>
                    <label><input type="radio" name="wa-match-f" value="list"> {{ translate('In_provider_list') }}</label>
                    <label><input type="radio" name="wa-match-f" value="onboarding"> {{ translate('In_onboarding') }}</label>
                    <label><input type="radio" name="wa-match-f" value="lead"> {{ translate('In_lead') }}</label>
                </div>
            </div>

            <div class="wa-match-table-wrap">
                <table class="wa-match-table" id="wa-match-table">
                    <thead>
                        <tr>
                            <th>{{ translate('Phone_number') }}</th>
                            <th>{{ translate('WhatsApp_group') }}</th>
                            <th>{{ translate('In_list') }}?</th>
                            <th>{{ translate('Provider_Profile') }}</th>
                            <th>{{ translate('In_onboarding') }}?</th>
                            <th>{{ translate('Onboarding_Profile') }}</th>
                            <th>{{ translate('In_lead') }}?</th>
                            <th>{{ translate('Provider_Lead') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $prevCat = null; @endphp
                        @forelse($rows as $row)
                            @if($prevCat !== $row['category'])
                                @php
                                    $prevCat = $row['category'];
                                    $catRows = collect($rows)->where('category', $row['category']);
                                    $matchedCount = $catRows->where('has_any', true)->count();
                                    $unmatchedCount = $catRows->count() - $matchedCount;
                                @endphp
                                <tr class="cat-head" data-cat="{{ $row['category'] }}">
                                    <td colspan="8">
                                        {{ $row['category'] }}
                                        <span class="cat-meta">{{ $matchedCount }} matched · {{ $unmatchedCount }} unmatched</span>
                                    </td>
                                </tr>
                            @endif
                            <tr class="{{ $row['has_any'] ? 'matched' : 'unmatched' }}" data-cat="{{ $row['category'] }}">
                                <td class="wa-phone">{{ $row['phone'] }}</td>
                                <td>{{ $row['category'] }}</td>
                                <td class="wa-check">{!! $row['in_list'] ? $yes : $no !!}</td>
                                <td>
                                    @if(empty($row['provider_profile']))
                                        <span class="wa-miss">—</span>
                                    @else
                                        @foreach($row['provider_profile'] as $item)
                                            @php
                                                $active = !empty($item['is_active']) ? 'Active' : 'Inactive';
                                                $extra = ((int) ($item['is_approved'] ?? 1) === 1) ? '' : ', approved='.$item['is_approved'];
                                                $label = ($item['name'] ?? 'Unnamed').' ('.$active.$extra.')';
                                                $url = ((int) ($item['is_approved'] ?? 0) === 2)
                                                    ? route('admin.provider.onboarding_details', $item['id'])
                                                    : route('admin.provider.details', [$item['id'], 'web_page' => 'overview']);
                                            @endphp
                                            <div><a href="{{ $url }}">{{ $label }}</a></div>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="wa-check">{!! $row['in_onboarding'] ? $yes : $no !!}</td>
                                <td>
                                    @if(empty($row['onboarding']))
                                        <span class="wa-miss">—</span>
                                    @else
                                        @foreach($row['onboarding'] as $item)
                                            @php
                                                $active = !empty($item['is_active']) ? 'Active' : 'Inactive';
                                                $label = ($item['name'] ?? 'Unnamed').' ('.$active.')';
                                            @endphp
                                            <div>
                                                <a href="{{ route('admin.provider.onboarding_details', [$item['id']]) }}">{{ $label }}</a>
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="wa-check">{!! $row['in_lead'] ? $yes : $no !!}</td>
                                <td>
                                    @if(empty($row['provider_lead']))
                                        <span class="wa-miss">—</span>
                                    @else
                                        @foreach($row['provider_lead'] as $item)
                                            <div>
                                                @if(!empty($item['url']))
                                                    <a href="{{ $item['url'] }}">{{ $item['label'] ?? $item['name'] }}</a>
                                                @else
                                                    {{ $item['label'] ?? $item['name'] }}
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    {{ translate('No_WhatsApp_provider_numbers_configured') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="wa-match-note">
                {{ translate('Sorted_by_category_then_matches_before_no_matches') }}.
                {{ translate('Phone_match_uses_last_10_digits') }}.
            </p>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const q = document.getElementById('wa-match-q');
            const radios = document.querySelectorAll('input[name="wa-match-f"]');
            const table = document.getElementById('wa-match-table');
            if (!q || !table) return;

            function apply() {
                const term = (q.value || '').trim().toLowerCase();
                const filter = [...radios].find(r => r.checked)?.value || 'all';
                const heads = table.querySelectorAll('tbody tr.cat-head');

                table.querySelectorAll('tbody tr:not(.cat-head)').forEach(tr => {
                    const text = tr.innerText.toLowerCase();
                    const matched = tr.classList.contains('matched');
                    const cells = tr.querySelectorAll('td');
                    const inList = (cells[2]?.innerText || '').includes('Yes');
                    const inOb = (cells[4]?.innerText || '').includes('Yes');
                    const inLead = (cells[6]?.innerText || '').includes('Yes');
                    let ok = true;
                    if (filter === 'matched') ok = matched;
                    if (filter === 'unmatched') ok = !matched;
                    if (filter === 'list') ok = inList;
                    if (filter === 'onboarding') ok = inOb;
                    if (filter === 'lead') ok = inLead;
                    if (term && !text.includes(term)) ok = false;
                    tr.style.display = ok ? '' : 'none';
                });

                heads.forEach(head => {
                    const cat = head.dataset.cat;
                    const anyVisible = [...table.querySelectorAll('tbody tr:not(.cat-head)')]
                        .some(tr => tr.dataset.cat === cat && tr.style.display !== 'none');
                    head.style.display = anyVisible ? '' : 'none';
                });
            }

            q.addEventListener('input', apply);
            radios.forEach(r => r.addEventListener('change', apply));
        })();
    </script>
@endpush
