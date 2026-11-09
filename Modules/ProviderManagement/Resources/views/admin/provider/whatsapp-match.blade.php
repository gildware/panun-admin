@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp_Provider_Match'))

@push('css_or_js')
    <style>
        .wa-page-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem 1rem;
            margin-bottom: 1rem;
        }
        .wa-page-header h4 {
            margin: 0 0 .2rem;
            font-size: 1.2rem;
            font-weight: 650;
        }
        .wa-page-header p {
            margin: 0;
            color: #667085;
            font-size: .875rem;
        }
        .wa-stats {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-bottom: .9rem;
        }
        .wa-stat {
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: .55rem;
            padding: .55rem .85rem;
            min-width: 5.75rem;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
        .wa-stat strong {
            display: block;
            font-size: 1.15rem;
            line-height: 1.15;
            color: #0f766e;
            font-weight: 700;
        }
        .wa-stat span {
            font-size: .72rem;
            color: #667085;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .wa-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem 1rem;
            align-items: center;
            margin-bottom: .75rem;
            padding: .7rem .85rem;
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: .55rem;
        }
        .wa-toolbar input[type="search"] {
            flex: 1 1 14rem;
            min-width: 12rem;
            max-width: 20rem;
            height: 2.25rem;
            border: 1px solid #d0d5dd;
            border-radius: .45rem;
            padding: 0 .75rem;
            font-size: .875rem;
            background: #fff;
        }
        .wa-toolbar input[type="search"]:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
        }
        .wa-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        .wa-filters label {
            cursor: pointer;
            margin: 0;
            display: inline-flex;
            align-items: center;
            user-select: none;
        }
        .wa-filters input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .wa-filters span {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
            padding: .2rem .75rem;
            border: 1px solid #d0d5dd;
            border-radius: 999px;
            background: #fff;
            color: #475467;
            font-size: .8rem;
            font-weight: 600;
            line-height: 1.2;
            transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
        }
        .wa-filters label:hover span {
            border-color: #98a2b3;
            color: #101828;
        }
        .wa-filters input:checked + span {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
            box-shadow: 0 1px 2px rgba(15, 118, 110, .25);
        }
        .wa-filters input:focus-visible + span {
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .18);
        }
        .wa-table-wrap {
            overflow: auto;
            border: 1px solid #e4e7ec;
            border-radius: .65rem;
            background: #fff;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
        .wa-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .84rem;
            margin: 0;
        }
        .wa-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #1d2939;
            color: #fff;
            text-align: left;
            padding: .7rem .75rem;
            white-space: nowrap;
            font-weight: 600;
            font-size: .78rem;
        }
        .wa-table td {
            padding: .65rem .75rem;
            border-bottom: 1px solid #f2f4f7;
            vertical-align: top;
            line-height: 1.35;
        }
        .wa-table tr.cat-head td {
            background: #f0fdf9;
            border-bottom: 1px solid #99f6e4;
            font-weight: 650;
            color: #134e4a;
            padding: .6rem .75rem;
        }
        .wa-table tr.cat-head .cat-meta {
            font-weight: 500;
            color: #667085;
            font-size: .78rem;
            margin-left: .45rem;
        }
        .wa-table tr.unmatched td {
            color: #667085;
            background: #fcfcfd;
        }
        .wa-table tbody tr:not(.cat-head):hover td {
            background: #f8fafc;
        }
        .wa-phone {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            white-space: nowrap;
            color: #101828;
        }
        .wa-check { text-align: center; white-space: nowrap; }
        .wa-yes,
        .wa-no {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.4rem;
            padding: .15rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 650;
            line-height: 1.3;
        }
        .wa-yes { background: #dcfce7; color: #166534; }
        .wa-no { background: #ffedd5; color: #9a3412; }
        .wa-miss { color: #98a2b3; }
        .wa-table a {
            color: #0f766e;
            font-weight: 600;
            text-decoration: none;
        }
        .wa-table a:hover { text-decoration: underline; }
        .wa-note {
            margin-top: .85rem;
            color: #667085;
            font-size: .8rem;
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
            <div class="wa-page-header">
                <div>
                    <h4>{{ translate('WhatsApp_Provider_Match') }}</h4>
                    <p>{{ translate('Match_WhatsApp_provider_numbers_against_provider_list_onboarding_and_leads') }}</p>
                </div>
                <a href="{{ route('admin.provider.whatsapp-match.lookup') }}" class="btn btn--primary">
                    {{ translate('Lookup_phone_numbers') }}
                </a>
            </div>

            <div class="wa-stats">
                <div class="wa-stat"><strong>{{ $stats['unique_phones'] ?? 0 }}</strong><span>{{ translate('Unique_numbers') }}</span></div>
                <div class="wa-stat"><strong>{{ $stats['matched'] ?? 0 }}</strong><span>{{ translate('Any_match') }}</span></div>
                <div class="wa-stat"><strong>{{ $stats['in_list'] ?? 0 }}</strong><span>{{ translate('Provider_list') }}</span></div>
                <div class="wa-stat"><strong>{{ $stats['in_onboarding'] ?? 0 }}</strong><span>{{ translate('Onboarding') }}</span></div>
                <div class="wa-stat"><strong>{{ $stats['in_lead'] ?? 0 }}</strong><span>{{ translate('Provider_lead') }}</span></div>
                <div class="wa-stat"><strong>{{ $stats['none'] ?? 0 }}</strong><span>{{ translate('No_match') }}</span></div>
            </div>

            <div class="wa-toolbar">
                <input type="search" id="wa-match-q" placeholder="{{ translate('Search_phone_category_name') }}">
                <div class="wa-filters" role="radiogroup" aria-label="{{ translate('Filter') }}">
                    <label><input type="radio" name="wa-match-f" value="all" checked><span>{{ translate('All') }}</span></label>
                    <label><input type="radio" name="wa-match-f" value="matched"><span>{{ translate('Matched') }}</span></label>
                    <label><input type="radio" name="wa-match-f" value="unmatched"><span>{{ translate('Unmatched') }}</span></label>
                    <label><input type="radio" name="wa-match-f" value="list"><span>{{ translate('In_provider_list') }}</span></label>
                    <label><input type="radio" name="wa-match-f" value="onboarding"><span>{{ translate('In_onboarding') }}</span></label>
                    <label><input type="radio" name="wa-match-f" value="lead"><span>{{ translate('In_lead') }}</span></label>
                </div>
            </div>

            <div class="wa-table-wrap">
                <table class="wa-table" id="wa-match-table">
                    <thead>
                        <tr>
                            <th>{{ translate('Phone_number') }}</th>
                            <th>{{ translate('WhatsApp_group') }}</th>
                            <th>{{ translate('In_list') }}</th>
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

            <p class="wa-note">
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
