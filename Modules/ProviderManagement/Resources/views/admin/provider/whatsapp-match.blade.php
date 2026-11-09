@extends('adminmodule::layouts.master')

@section('title', translate('Manage_Data'))

@push('css_or_js')
    <style>
        .wa-page-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
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
        .wa-toolbar input[type="search"],
        .wa-toolbar select {
            height: 2.25rem;
            border: 1px solid #d0d5dd;
            border-radius: .45rem;
            padding: 0 .75rem;
            font-size: .875rem;
            background: #fff;
            color: #101828;
        }
        .wa-toolbar input[type="search"] {
            flex: 1 1 14rem;
            min-width: 12rem;
            max-width: 20rem;
        }
        .wa-toolbar select {
            min-width: 12rem;
            max-width: 18rem;
        }
        .wa-toolbar input[type="search"]:focus,
        .wa-toolbar select:focus {
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
        }
        .wa-filters input:checked + span {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
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
        .wa-table th.sortable {
            cursor: pointer;
            user-select: none;
        }
        .wa-table th.sortable:hover { background: #344054; }
        .wa-table th .sort-ind {
            display: inline-block;
            margin-left: .3rem;
            opacity: .45;
            font-size: .7rem;
        }
        .wa-table th.is-asc .sort-ind,
        .wa-table th.is-desc .sort-ind { opacity: 1; }
        .wa-table th.is-asc .sort-ind::after { content: '▲'; }
        .wa-table th.is-desc .sort-ind::after { content: '▼'; }
        .wa-table th.sortable:not(.is-asc):not(.is-desc) .sort-ind::after { content: '↕'; }
        .wa-table td {
            padding: .65rem .75rem;
            border-bottom: 1px solid rgba(16, 24, 40, .06);
            vertical-align: top;
            line-height: 1.35;
        }
        .wa-table tbody tr[data-color] td {
            background-color: var(--wa-row-bg) !important;
        }
        .wa-table tr.unmatched td { color: #475467; }
        .wa-table tbody tr:hover td { filter: brightness(0.97); }
        .wa-phone {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            white-space: nowrap;
            color: #101828;
        }
        .wa-contact-name { font-weight: 600; color: #101828; }
        .wa-group {
            font-weight: 600;
            color: #134e4a;
            white-space: nowrap;
        }
        .wa-group-swatch {
            display: inline-block;
            width: .75rem;
            height: .75rem;
            border-radius: 999px;
            margin-right: .4rem;
            border: 1px solid rgba(16, 24, 40, .15);
            vertical-align: middle;
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
        .wa-empty {
            text-align: center;
            color: #667085;
            padding: 1.5rem .75rem;
        }
    </style>
@endpush

@section('content')
    @php
        $yes = '<span class="wa-yes">Yes</span>';
        $no = '<span class="wa-no">No</span>';
        $groupNames = collect($categories ?? [])->values()->all();
        if ($groupNames === []) {
            $groupNames = collect($rows)->pluck('category')->unique()->filter()->values()->all();
        }
        $groupColors = $colors ?? collect($rows)->pluck('color', 'category')->filter()->all();
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="wa-page-header">
                <div>
                    <h4>{{ translate('Manage_Data') }}</h4>
                    <p>{{ translate('Match_WhatsApp_provider_numbers_against_provider_list_onboarding_and_leads') }}</p>
                </div>
                @include('providermanagement::admin.provider.partials._whatsapp-match-nav', ['active' => 'match'])
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
                <select id="wa-match-group" aria-label="{{ translate('WhatsApp_group') }}">
                    <option value="">{{ translate('All_groups') }}</option>
                    @foreach($groupNames as $groupName)
                        <option value="{{ $groupName }}">{{ $groupName }}</option>
                    @endforeach
                </select>
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
                            <th class="sortable" data-sort="contact_name">{{ translate('Contact_name') }}<span class="sort-ind"></span></th>
                            <th class="sortable" data-sort="phone">{{ translate('Phone_number') }}<span class="sort-ind"></span></th>
                            <th class="sortable is-asc" data-sort="group">{{ translate('WhatsApp_group') }}<span class="sort-ind"></span></th>
                            <th class="sortable" data-sort="list">{{ translate('In_list') }}<span class="sort-ind"></span></th>
                            <th class="sortable" data-sort="provider">{{ translate('Provider_Profile') }}<span class="sort-ind"></span></th>
                            <th class="sortable" data-sort="onboarding">{{ translate('In_onboarding') }}?<span class="sort-ind"></span></th>
                            <th class="sortable" data-sort="onboarding_profile">{{ translate('Onboarding_Profile') }}<span class="sort-ind"></span></th>
                            <th class="sortable" data-sort="lead">{{ translate('In_lead') }}?<span class="sort-ind"></span></th>
                            <th class="sortable" data-sort="lead_profile">{{ translate('Provider_Lead') }}<span class="sort-ind"></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $providerLabel = collect($row['provider_profile'] ?? [])->map(function ($item) {
                                    $active = !empty($item['is_active']) ? 'Active' : 'Inactive';
                                    $extra = ((int) ($item['is_approved'] ?? 1) === 1) ? '' : ', approved='.$item['is_approved'];
                                    return ($item['name'] ?? 'Unnamed').' ('.$active.$extra.')';
                                })->implode(' ');
                                $onboardingLabel = collect($row['onboarding'] ?? [])->map(function ($item) {
                                    $active = !empty($item['is_active']) ? 'Active' : 'Inactive';
                                    return ($item['name'] ?? 'Unnamed').' ('.$active.')';
                                })->implode(' ');
                                $leadLabel = collect($row['provider_lead'] ?? [])->map(function ($item) {
                                    return $item['label'] ?? $item['name'] ?? '';
                                })->implode(' ');
                                $rowColor = $row['color'] ?? ($groupColors[$row['category']] ?? '#F2F4F7');
                            @endphp
                            <tr
                                class="{{ $row['has_any'] ? 'matched' : 'unmatched' }}"
                                data-group="{{ $row['category'] }}"
                                data-phone="{{ $row['phone'] }}"
                                data-contact-name="{{ $row['contact_name'] ?? '' }}"
                                data-list="{{ $row['in_list'] ? '1' : '0' }}"
                                data-onboarding="{{ $row['in_onboarding'] ? '1' : '0' }}"
                                data-lead="{{ $row['in_lead'] ? '1' : '0' }}"
                                data-provider="{{ $providerLabel }}"
                                data-onboarding-profile="{{ $onboardingLabel }}"
                                data-lead-profile="{{ $leadLabel }}"
                                data-color="{{ $rowColor }}"
                                style="--wa-row-bg: {{ $rowColor }};"
                            >
                                <td class="wa-contact-name">
                                    @if(!empty($row['contact_name']))
                                        {{ $row['contact_name'] }}
                                    @else
                                        <span class="wa-miss">—</span>
                                    @endif
                                </td>
                                <td class="wa-phone">{{ $row['phone'] }}</td>
                                <td class="wa-group">
                                    <span class="wa-group-swatch" style="background: {{ $rowColor }};"></span>{{ $row['category'] }}
                                </td>
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
                            <tr class="wa-empty-row">
                                <td colspan="9" class="wa-empty">
                                    {{ translate('No_WhatsApp_provider_numbers_configured') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="wa-note">
                {{ translate('Phone_match_uses_last_10_digits') }}.
                {{ translate('Click_column_headers_to_sort') }}.
            </p>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const q = document.getElementById('wa-match-q');
            const groupSelect = document.getElementById('wa-match-group');
            const radios = document.querySelectorAll('input[name="wa-match-f"]');
            const table = document.getElementById('wa-match-table');
            if (!q || !table) return;

            const tbody = table.querySelector('tbody');
            const headers = table.querySelectorAll('th.sortable');
            let sortKey = 'group';
            let sortDir = 'asc';

            function rowValue(tr, key) {
                if (key === 'phone') return (tr.dataset.phone || '').toLowerCase();
                if (key === 'group') return (tr.dataset.group || '').toLowerCase();
                if (key === 'contact_name') return (tr.dataset.contactName || '').toLowerCase();
                if (key === 'list') return tr.dataset.list === '1' ? 1 : 0;
                if (key === 'onboarding') return tr.dataset.onboarding === '1' ? 1 : 0;
                if (key === 'lead') return tr.dataset.lead === '1' ? 1 : 0;
                if (key === 'provider') return (tr.dataset.provider || '').toLowerCase();
                if (key === 'onboarding_profile') return (tr.dataset.onboardingProfile || '').toLowerCase();
                if (key === 'lead_profile') return (tr.dataset.leadProfile || '').toLowerCase();
                return '';
            }

            function sortRows() {
                const rows = [...tbody.querySelectorAll('tr:not(.wa-empty-row)')];
                rows.sort((a, b) => {
                    const av = rowValue(a, sortKey);
                    const bv = rowValue(b, sortKey);
                    let cmp = 0;
                    if (typeof av === 'number' && typeof bv === 'number') cmp = av - bv;
                    else cmp = String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' });
                    if (cmp === 0 && sortKey !== 'group') {
                        cmp = rowValue(a, 'group').localeCompare(rowValue(b, 'group'), undefined, { numeric: true, sensitivity: 'base' });
                    }
                    if (cmp === 0 && sortKey !== 'phone') {
                        cmp = rowValue(a, 'phone').localeCompare(rowValue(b, 'phone'), undefined, { numeric: true, sensitivity: 'base' });
                    }
                    return sortDir === 'asc' ? cmp : -cmp;
                });
                rows.forEach(row => tbody.appendChild(row));
            }

            function updateSortHeaders() {
                headers.forEach(th => {
                    th.classList.remove('is-asc', 'is-desc');
                    if (th.dataset.sort === sortKey) th.classList.add(sortDir === 'asc' ? 'is-asc' : 'is-desc');
                });
            }

            function apply() {
                const term = (q.value || '').trim().toLowerCase();
                const group = (groupSelect?.value || '').trim();
                const filter = [...radios].find(r => r.checked)?.value || 'all';
                let visible = 0;

                tbody.querySelectorAll('tr:not(.wa-empty-row)').forEach(tr => {
                    const text = tr.innerText.toLowerCase();
                    const matched = tr.classList.contains('matched');
                    const inList = tr.dataset.list === '1';
                    const inOb = tr.dataset.onboarding === '1';
                    const inLead = tr.dataset.lead === '1';
                    let ok = true;
                    if (group && tr.dataset.group !== group) ok = false;
                    if (filter === 'matched') ok = ok && matched;
                    if (filter === 'unmatched') ok = ok && !matched;
                    if (filter === 'list') ok = ok && inList;
                    if (filter === 'onboarding') ok = ok && inOb;
                    if (filter === 'lead') ok = ok && inLead;
                    if (term && !text.includes(term)) ok = false;
                    tr.style.display = ok ? '' : 'none';
                    if (ok) visible += 1;
                });

                let empty = tbody.querySelector('tr.wa-empty-row');
                if ({{ count($rows) }} > 0) {
                    if (!empty) {
                        empty = document.createElement('tr');
                        empty.className = 'wa-empty-row';
                        empty.innerHTML = '<td colspan="9" class="wa-empty">{{ translate('No_results_found') }}</td>';
                        tbody.appendChild(empty);
                    }
                    empty.style.display = visible === 0 ? '' : 'none';
                }
            }

            headers.forEach(th => {
                th.addEventListener('click', () => {
                    const key = th.dataset.sort;
                    if (sortKey === key) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    else { sortKey = key; sortDir = 'asc'; }
                    updateSortHeaders();
                    sortRows();
                    apply();
                });
            });

            q.addEventListener('input', apply);
            groupSelect?.addEventListener('change', apply);
            radios.forEach(r => r.addEventListener('change', apply));
            updateSortHeaders();
            sortRows();
            apply();
        })();
    </script>
@endpush
