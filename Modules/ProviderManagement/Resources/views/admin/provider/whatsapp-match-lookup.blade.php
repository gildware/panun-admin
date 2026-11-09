@extends('adminmodule::layouts.master')

@section('title', translate('Manage_Data'))

@push('css_or_js')
    <style>
        .plu-page-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem 1rem;
            margin-bottom: 1rem;
        }
        .plu-page-header h4 {
            margin: 0 0 .2rem;
            font-size: 1.2rem;
            font-weight: 650;
        }
        .plu-page-header p {
            margin: 0;
            color: #667085;
            font-size: .875rem;
        }
        .plu-search-card {
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: .65rem;
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
        .plu-search-card label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: #344054;
            margin-bottom: .4rem;
        }
        .plu-search-card textarea {
            width: 100%;
            min-height: 5.5rem;
            border: 1px solid #d0d5dd;
            border-radius: .5rem;
            padding: .7rem .85rem;
            font-size: .9rem;
            font-variant-numeric: tabular-nums;
            line-height: 1.45;
            resize: vertical;
            background: #fff;
        }
        .plu-search-card textarea:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
        }
        .plu-search-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-top: .75rem;
        }
        .plu-stats {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-bottom: .75rem;
        }
        .plu-stat {
            background: #fff;
            border: 1px solid #e4e7ec;
            border-radius: .55rem;
            padding: .55rem .85rem;
            min-width: 5.75rem;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
        .plu-stat strong {
            display: block;
            font-size: 1.15rem;
            line-height: 1.15;
            color: #0f766e;
            font-weight: 700;
        }
        .plu-stat span {
            font-size: .72rem;
            color: #667085;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .plu-toolbar {
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
        .plu-toolbar input[type="search"] {
            flex: 1 1 12rem;
            min-width: 10rem;
            max-width: 18rem;
            height: 2.25rem;
            border: 1px solid #d0d5dd;
            border-radius: .45rem;
            padding: 0 .75rem;
            font-size: .875rem;
        }
        .plu-toolbar input[type="search"]:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .12);
        }
        .plu-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        .plu-filters label {
            cursor: pointer;
            margin: 0;
            display: inline-flex;
            align-items: center;
            user-select: none;
        }
        .plu-filters input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .plu-filters span {
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
        .plu-filters label:hover span {
            border-color: #98a2b3;
            color: #101828;
        }
        .plu-filters input:checked + span {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
            box-shadow: 0 1px 2px rgba(15, 118, 110, .25);
        }
        .plu-filters input:focus-visible + span {
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .18);
        }
        .plu-sort {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-left: auto;
            font-size: .82rem;
            color: #475467;
        }
        .plu-sort select {
            height: 2.25rem;
            border: 1px solid #d0d5dd;
            border-radius: .45rem;
            padding: 0 .55rem;
            font-size: .82rem;
            background: #fff;
            color: #101828;
        }
        .plu-table-wrap {
            overflow: auto;
            border: 1px solid #e4e7ec;
            border-radius: .65rem;
            background: #fff;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
        .plu-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .84rem;
            margin: 0;
        }
        .plu-table th,
        .plu-table td {
            text-align: center;
            vertical-align: middle;
            padding: .7rem .65rem;
            line-height: 1.4;
        }
        .plu-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #1d2939;
            color: #fff;
            white-space: nowrap;
            font-weight: 600;
            font-size: .78rem;
        }
        .plu-table td {
            border-bottom: 1px solid #f2f4f7;
        }
        .plu-table tbody tr:nth-child(even) td { background: #f9fafb; }
        .plu-table tbody tr:hover td { background: #f0fdf9; }
        .plu-table tbody tr.miss td {
            color: #667085;
            background: #fcfcfd;
        }
        .plu-phone {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            white-space: nowrap;
            color: #101828;
            font-size: .9rem;
        }
        .plu-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .15rem;
        }
        .plu-link {
            display: inline-block;
            font-weight: 650;
            color: #0f766e;
            text-decoration: none;
        }
        .plu-link:hover { text-decoration: underline; }
        .plu-meta {
            color: #667085;
            font-size: .78rem;
        }
        .plu-empty { color: #98a2b3; }
        .plu-empty-row {
            display: none;
            text-align: center;
            color: #667085;
            padding: 1.25rem;
            font-size: .875rem;
        }
        .plu-empty-row.is-visible { display: block; }
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
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="plu-page-header">
                <div>
                    <h4>{{ translate('Manage_Data') }}</h4>
                    <p>{{ translate('Paste_multiple_numbers_to_find_provider_onboarding_and_leads') }}</p>
                </div>
                @include('providermanagement::admin.provider.partials._whatsapp-match-nav', ['active' => 'lookup'])
            </div>

            <div class="plu-search-card">
                <form method="POST" action="{{ route('admin.provider.whatsapp-match.lookup') }}">
                    @csrf
                    <label for="phones">{{ translate('Phone_numbers') }}</label>
                    <textarea
                        id="phones"
                        name="phones"
                        placeholder="{{ translate('Paste_numbers_separated_by_new_line_comma_or_space') }}"
                    >{{ old('phones', $rawPhones) }}</textarea>
                    <div class="plu-search-actions">
                        <button type="submit" class="btn btn--primary">{{ translate('Search_numbers') }}</button>
                        <a href="{{ route('admin.provider.whatsapp-match.lookup') }}" class="btn btn-light">{{ translate('Clear') }}</a>
                    </div>
                </form>
            </div>

            @if($searched)
                <div class="plu-stats">
                    <div class="plu-stat"><strong>{{ $stats['searched'] ?? 0 }}</strong><span>{{ translate('Searched') }}</span></div>
                    <div class="plu-stat"><strong>{{ $stats['matched'] ?? 0 }}</strong><span>{{ translate('Any_match') }}</span></div>
                    <div class="plu-stat"><strong>{{ $stats['none'] ?? 0 }}</strong><span>{{ translate('No_match') }}</span></div>
                </div>

                <div class="plu-toolbar">
                    <input type="search" id="plu-q" placeholder="{{ translate('Search_phone_category_name') }}">
                    <div class="plu-filters" role="radiogroup" aria-label="{{ translate('Filter') }}">
                        <label><input type="radio" name="plu-f" value="all" checked><span>{{ translate('All') }}</span></label>
                        <label><input type="radio" name="plu-f" value="matched"><span>{{ translate('Matched') }}</span></label>
                        <label><input type="radio" name="plu-f" value="unmatched"><span>{{ translate('Unmatched') }}</span></label>
                        <label><input type="radio" name="plu-f" value="list"><span>{{ translate('In_provider_list') }}</span></label>
                        <label><input type="radio" name="plu-f" value="onboarding"><span>{{ translate('In_onboarding') }}</span></label>
                        <label><input type="radio" name="plu-f" value="lead"><span>{{ translate('In_lead') }}</span></label>
                    </div>
                    <div class="plu-sort">
                        <span>{{ translate('Sort') }}</span>
                        <select id="plu-sort">
                            <option value="match-first">{{ translate('Match_first') }}</option>
                            <option value="no-match-first">{{ translate('No_match_first') }}</option>
                            <option value="phone-asc">{{ translate('Phone_A_to_Z') }}</option>
                            <option value="phone-desc">{{ translate('Phone_Z_to_A') }}</option>
                        </select>
                    </div>
                </div>

                <div class="plu-table-wrap">
                    <table class="plu-table" id="plu-table">
                        <thead>
                            <tr>
                                <th>{{ translate('Phone_number') }}</th>
                                <th>{{ translate('In_list') }}</th>
                                <th>{{ translate('Provider_Profile') }}</th>
                                <th>{{ translate('In_onboarding') }}?</th>
                                <th>{{ translate('Onboarding_Profile') }}</th>
                                <th>{{ translate('In_lead') }}?</th>
                                <th>{{ translate('Provider_Lead') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $row)
                                <tr
                                    class="{{ $row['has_any'] ? 'matched' : 'miss' }}"
                                    data-matched="{{ $row['has_any'] ? '1' : '0' }}"
                                    data-list="{{ $row['in_list'] ? '1' : '0' }}"
                                    data-onboarding="{{ $row['in_onboarding'] ? '1' : '0' }}"
                                    data-lead="{{ $row['in_lead'] ? '1' : '0' }}"
                                    data-phone="{{ $row['phone'] }}"
                                >
                                    <td class="plu-phone">{{ $row['phone'] }}</td>
                                    <td>
                                        @if($row['in_list'])
                                            <span class="wa-yes">Yes</span>
                                        @else
                                            <span class="wa-no">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="plu-cell">
                                            @php $providers = array_merge($row['provider_list'], $row['other_providers']); @endphp
                                            @forelse($providers as $item)
                                                <a class="plu-link" href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                                                <div class="plu-meta">
                                                    {{ $item['status_label'] }} · {{ !empty($item['is_active']) ? translate('Active') : translate('Inactive') }}
                                                </div>
                                            @empty
                                                <span class="plu-empty">—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        @if($row['in_onboarding'])
                                            <span class="wa-yes">Yes</span>
                                        @else
                                            <span class="wa-no">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="plu-cell">
                                            @forelse($row['onboarding'] as $item)
                                                <a class="plu-link" href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                                                <div class="plu-meta">
                                                    {{ $item['status_label'] }} · {{ !empty($item['is_active']) ? translate('Active') : translate('Inactive') }}
                                                </div>
                                            @empty
                                                <span class="plu-empty">—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        @if($row['in_lead'])
                                            <span class="wa-yes">Yes</span>
                                        @else
                                            <span class="wa-no">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="plu-cell">
                                            @forelse($row['leads'] as $item)
                                                @if(!empty($item['url']))
                                                    <a class="plu-link" href="{{ $item['url'] }}">{{ $item['label'] ?? $item['name'] }}</a>
                                                @else
                                                    <div class="plu-link" style="color:#101828">{{ $item['label'] ?? $item['name'] }}</div>
                                                @endif
                                                <div class="plu-meta">{{ ucfirst($item['kind'] ?? 'lead') }}</div>
                                            @empty
                                                <span class="plu-empty">—</span>
                                            @endforelse
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted py-4">
                                        {{ translate('No_valid_phone_numbers_found_in_input') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="plu-empty-row" id="plu-empty">{{ translate('No_rows_match_current_filters') }}</div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const table = document.getElementById('plu-table');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const q = document.getElementById('plu-q');
            const sortEl = document.getElementById('plu-sort');
            const radios = document.querySelectorAll('input[name="plu-f"]');
            const empty = document.getElementById('plu-empty');

            function rows() {
                return [...tbody.querySelectorAll('tr[data-phone]')];
            }

            function applyFilter() {
                const term = (q?.value || '').trim().toLowerCase();
                const filter = [...radios].find(r => r.checked)?.value || 'all';
                let visible = 0;

                rows().forEach(tr => {
                    const matched = tr.dataset.matched === '1';
                    const inList = tr.dataset.list === '1';
                    const inOb = tr.dataset.onboarding === '1';
                    const inLead = tr.dataset.lead === '1';
                    const text = tr.innerText.toLowerCase();

                    let ok = true;
                    if (filter === 'matched') ok = matched;
                    if (filter === 'unmatched') ok = !matched;
                    if (filter === 'list') ok = inList;
                    if (filter === 'onboarding') ok = inOb;
                    if (filter === 'lead') ok = inLead;
                    if (term && !text.includes(term)) ok = false;

                    tr.style.display = ok ? '' : 'none';
                    if (ok) visible += 1;
                });

                if (empty) empty.classList.toggle('is-visible', visible === 0);
            }

            function applySort() {
                const mode = sortEl?.value || 'match-first';
                const list = rows();

                list.sort((a, b) => {
                    const aMatch = a.dataset.matched === '1' ? 1 : 0;
                    const bMatch = b.dataset.matched === '1' ? 1 : 0;
                    const aPhone = a.dataset.phone || '';
                    const bPhone = b.dataset.phone || '';

                    if (mode === 'match-first' && aMatch !== bMatch) return bMatch - aMatch;
                    if (mode === 'no-match-first' && aMatch !== bMatch) return aMatch - bMatch;
                    if (mode === 'phone-desc') return bPhone.localeCompare(aPhone);
                    return aPhone.localeCompare(bPhone);
                });

                list.forEach(tr => tbody.appendChild(tr));
                applyFilter();
            }

            q?.addEventListener('input', applyFilter);
            radios.forEach(r => r.addEventListener('change', applyFilter));
            sortEl?.addEventListener('change', applySort);
            applySort();
        })();
    </script>
@endpush
