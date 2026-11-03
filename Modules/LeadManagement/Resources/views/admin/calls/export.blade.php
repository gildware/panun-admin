@extends('adminmodule::layouts.new-master')

@section('title', translate('Export_calls'))

@push('css_or_js')
    <style>
        .lead-export-page .main-content {
            min-height: calc(100vh - 4.5rem);
        }
        .lead-export-wrap {
            max-width: 1120px;
            margin-left: auto;
            margin-right: auto;
        }
        .lead-export-head {
            text-align: center;
            margin-bottom: 12px;
        }
        .lead-export-head .page-title {
            margin-bottom: 4px;
        }
        .lead-wizard {
            display: flex;
            align-items: stretch;
            justify-content: center;
            gap: 0;
            margin: 0 auto 14px;
            padding: 0;
            list-style: none;
        }
        .lead-wizard li {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-width: 0;
            padding: 8px 10px;
            background: #fff;
            border: 1px solid #e6e8ef;
            color: #98a2b3;
            text-align: center;
        }
        .lead-wizard li + li {
            border-left: 0;
        }
        .lead-wizard li:first-child {
            border-radius: 14px 0 0 14px;
        }
        .lead-wizard li:last-child {
            border-radius: 0 14px 14px 0;
        }
        .lead-wizard a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: inherit;
            text-decoration: none;
            min-width: 0;
            width: 100%;
        }
        .lead-wizard .num {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            background: #f2f4f7;
            color: #98a2b3;
            flex-shrink: 0;
        }
        .lead-wizard strong {
            display: block;
            font-size: 14px;
            line-height: 1.2;
        }
        .lead-wizard small {
            display: block;
            font-size: 12px;
            font-weight: 500;
            margin-top: 2px;
        }
        .lead-wizard li.is-current {
            background: #f5f7ff;
            border-color: #c9d4fb;
            color: #24356b;
        }
        .lead-wizard li.is-current .num,
        .lead-wizard li.is-done .num {
            background: #4158d0;
            color: #fff;
        }
        .lead-wizard li.is-done {
            color: #344054;
        }
        .lead-export-card {
            background: #fff;
            border: 1px solid #e6e8ef;
            border-radius: 16px;
            padding: 18px 18px 14px;
            margin-left: auto;
            margin-right: auto;
        }
        .lead-section-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #667085;
            margin: 10px 0 8px;
            text-align: center;
        }
        .lead-filter-grid .form-label {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .lead-filter-grid .form-control,
        .lead-filter-grid .form-select {
            min-height: 36px;
            font-size: 13px;
        }
        .lead-export-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #eef0f4;
        }
        .lead-step-intro {
            text-align: center;
            margin-bottom: 18px;
        }
        #calls-export-column-search {
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
        }
        .lead-column-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }
        .lead-column-group {
            border: 1px solid #e6e8ef;
            border-radius: 12px;
            padding: 14px 14px 6px;
            background: #fafbfe;
        }
        .lead-column-group h3 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #667085;
            margin-bottom: 10px;
        }
        .lead-export-option {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        .lead-preview-table thead th {
            position: sticky;
            top: 0;
            background: #f6f8fc;
            white-space: nowrap;
            z-index: 1;
        }
        .lead-preview-table td {
            white-space: nowrap;
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lead-preview-scroll {
            overflow: auto;
            max-height: calc(100vh - 320px);
            border: 1px solid #e6e8ef;
            border-radius: 12px;
        }
    </style>
@endpush

@section('content')
    <script>document.body.classList.add('lead-export-page');</script>
    @php
        $stepTitles = [
            1 => translate('Set_the_filters_for_this_export'),
            2 => translate('Choose_the_columns_for_the_file'),
            3 => translate('Preview_the_calls_then_download'),
        ];
        $stepOneQuery = array_merge($sharedQuery, ['step' => 1]);
        $stepTwoQuery = array_merge($sharedQuery, ['step' => 2, 'column_selection' => 1, 'columns' => $selectedKeys]);
    @endphp
    <div class="main-content">
        <div class="container-fluid lead-export-wrap">
            <div class="lead-export-head">
                <h2 class="page-title mb-1">{{ translate('Export_calls') }}</h2>
                <p class="mb-2 text-muted">{{ $stepTitles[$step] ?? '' }}</p>
                <a href="{{ $backUrl }}" class="btn btn--secondary btn-sm" data-turbo="false">{{ translate('Back_to_calls') }}</a>
            </div>

            <ol class="lead-wizard">
                <li class="{{ $step === 1 ? 'is-current' : 'is-done' }}">
                    @if($step > 1)
                        <a href="{{ route('admin.calls.export', $stepOneQuery) }}" data-turbo="false">
                    @endif
                        <span class="num">1</span>
                        <span>
                            <strong>{{ translate('Filter') }}</strong>
                            <small>{{ translate('Which_calls') }}</small>
                        </span>
                    @if($step > 1)
                        </a>
                    @endif
                </li>
                <li class="{{ $step === 2 ? 'is-current' : ($step > 2 ? 'is-done' : '') }}">
                    @if($step > 2)
                        <a href="{{ route('admin.calls.export', $stepTwoQuery) }}" data-turbo="false">
                    @endif
                        <span class="num">2</span>
                        <span>
                            <strong>{{ translate('Choose_columns') }}</strong>
                            <small>{{ translate('What_goes_in_the_file') }}</small>
                        </span>
                    @if($step > 2)
                        </a>
                    @endif
                </li>
                <li class="{{ $step === 3 ? 'is-current' : '' }}">
                    <span class="num">3</span>
                    <span>
                        <strong>{{ translate('Preview') }}</strong>
                        <small>{{ translate('Check_then_download') }}</small>
                    </span>
                </li>
            </ol>

            @if($step === 1)
                <form method="GET" action="{{ route('admin.calls.export') }}" class="lead-export-card" data-turbo="false" id="calls-export-step-form">
                    <input type="hidden" name="step" value="2">

                    <div class="lead-section-label">{{ translate('Filter') }}</div>
                    <div class="row g-2 lead-filter-grid">
                        <div class="col-md-6">
                            <label class="form-label" for="calls-export-q">{{ translate('Search') }}</label>
                            <input type="search" id="calls-export-q" name="q" class="form-control" value="{{ $q }}" placeholder="{{ translate('Phone_name_or_agent') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="calls-export-outcome">{{ translate('Call_result') }}</label>
                            <select id="calls-export-outcome" name="outcome" class="form-select">
                                <option value="">{{ translate('All') }}</option>
                                @foreach($outcomeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($outcome === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="calls-export-agent">{{ translate('Employee') }}</label>
                            <select id="calls-export-agent" name="agent" class="form-select">
                                <option value="">{{ translate('All') }}</option>
                                @foreach($agents as $agentName)
                                    <option value="{{ $agentName }}" @selected($agent === $agentName)>{{ $agentName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="calls-export-matched">{{ translate('Match_type') }}</label>
                            <select id="calls-export-matched" name="matched" class="form-select">
                                <option value="">{{ translate('All') }}</option>
                                <option value="yes" @selected($matched === 'yes')>{{ translate('Matched') }}</option>
                                <option value="no" @selected($matched === 'no')>{{ translate('Unmatched') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="calls-export-direction">{{ translate('Direction') }}</label>
                            <select id="calls-export-direction" name="direction" class="form-select">
                                <option value="">{{ translate('All') }}</option>
                                <option value="INBOUND" @selected($callDirection === 'INBOUND')>{{ translate('Inbound') }}</option>
                                <option value="OUTBOUND" @selected($callDirection === 'OUTBOUND')>{{ translate('Outbound') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="calls-export-from">{{ translate('From') }}</label>
                            <input type="date" id="calls-export-from" name="from" class="form-control" value="{{ $from }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="calls-export-to">{{ translate('To') }}</label>
                            <input type="date" id="calls-export-to" name="to" class="form-control" value="{{ $to }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="calls-export-sort">{{ translate('Sort_by') }}</label>
                            <select id="calls-export-sort" name="sort" class="form-select">
                                @foreach($sortOptions as $sortKey => $sortLabel)
                                    <option value="{{ $sortKey }}" @selected($sort === $sortKey)>{{ $sortLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="calls-export-dir">{{ translate('Sort_order') }}</label>
                            <select id="calls-export-dir" name="dir" class="form-select">
                                <option value="desc" @selected($dir === 'desc')>{{ translate('descending') }}</option>
                                <option value="asc" @selected($dir === 'asc')>{{ translate('ascending') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="lead-export-actions">
                        <a href="{{ $backUrl }}" class="btn btn--secondary" data-turbo="false">{{ translate('Back') }}</a>
                        <button type="submit" class="btn btn--primary">{{ translate('Continue') }}</button>
                    </div>
                </form>
            @endif

            @if($step === 2)
                <form method="GET" action="{{ route('admin.calls.export') }}" class="lead-export-card" data-turbo="false" id="calls-export-step-form">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="column_selection" value="1">
                    @foreach($sharedQuery as $name => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <div class="lead-step-intro">
                        <p class="mb-2 text-muted">{{ translate('Export_calls_columns_help') }}</p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn--secondary btn-sm" id="calls-export-all">{{ translate('Select_all') }}</button>
                            <button type="button" class="btn btn--secondary btn-sm" id="calls-export-none">{{ translate('clear') }}</button>
                        </div>
                    </div>
                    <input type="search" class="form-control mb-3" id="calls-export-column-search" placeholder="{{ translate('Search') }}">

                    <div class="lead-column-layout">
                        @foreach($groups as $groupName => $groupColumns)
                            <section class="lead-column-group">
                                <h3>
                                    {{ $groupName }}
                                    <button type="button" class="btn btn-link btn-sm p-0 calls-export-group-toggle" data-group="{{ $groupName }}">{{ translate('All') }}</button>
                                </h3>
                                @foreach($groupColumns as $key => $label)
                                    <label class="lead-export-option" data-column-label="{{ strtolower($label) }}">
                                        <input type="checkbox" name="columns[]" value="{{ $key }}" data-export-group="{{ $groupName }}" {{ in_array($key, $selectedKeys, true) ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </section>
                        @endforeach
                    </div>

                    <div class="lead-export-actions">
                        <a href="{{ route('admin.calls.export', $stepOneQuery) }}" class="btn btn--secondary" data-turbo="false">{{ translate('Back') }}</a>
                        <button type="submit" class="btn btn--primary" id="calls-export-columns-next">{{ translate('Continue') }}</button>
                    </div>
                </form>
            @endif

            @if($step === 3)
                <form method="POST" action="{{ route('admin.calls.export.download') }}" class="lead-export-card" data-turbo="false">
                    @csrf
                    <input type="hidden" name="column_selection" value="1">
                    @foreach($sharedQuery as $name => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    @foreach($selectedKeys as $key)
                        <input type="hidden" name="columns[]" value="{{ $key }}">
                    @endforeach

                    <div class="lead-step-intro">
                        <div>
                            <h2 class="mb-1">{{ translate('Preview') }}</h2>
                            <p class="mb-3 text-muted">
                                @if($total === 0)
                                    {{ translate('No_results_found') }}
                                @else
                                    Showing the first {{ count($previewRows) }} of {{ $total }}. The download includes every matching call, in this order.
                                @endif
                            </p>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="{{ route('admin.calls.export', $stepTwoQuery) }}" class="btn btn--secondary" data-turbo="false">{{ translate('Back') }}</a>
                            <button type="submit" class="btn btn--primary" @disabled($total === 0)>{{ translate('Download_Excel') }}</button>
                        </div>
                    </div>

                    <div class="lead-preview-scroll">
                        <table class="table table-hover align-middle mb-0 lead-preview-table">
                            <thead>
                                <tr>
                                    @foreach($selectedKeys as $key)
                                        <th>{{ $columns[$key] ?? $key }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($previewRows as $row)
                                    <tr>
                                        @foreach($selectedKeys as $key)
                                            @php $cell = $row[$key] ?? ''; @endphp
                                            <td title="{{ $cell }}">{{ $cell !== '' ? $cell : '—' }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ max(count($selectedKeys), 1) }}" class="text-center text-muted py-5">{{ translate('No_results_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var form = document.getElementById('calls-export-step-form');
            if (!form) {
                return;
            }

            var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="columns[]"]'));
            var next = document.getElementById('calls-export-columns-next');
            var search = document.getElementById('calls-export-column-search');

            function syncNext() {
                if (!next) {
                    return;
                }
                next.disabled = boxes.length > 0 && !boxes.some(function (box) { return box.checked; });
            }

            if (boxes.length) {
                boxes.forEach(function (box) {
                    box.addEventListener('change', syncNext);
                });

                var selectAll = document.getElementById('calls-export-all');
                if (selectAll) {
                    selectAll.addEventListener('click', function () {
                        boxes.forEach(function (box) { box.checked = true; });
                        syncNext();
                    });
                }

                var clearAll = document.getElementById('calls-export-none');
                if (clearAll) {
                    clearAll.addEventListener('click', function () {
                        boxes.forEach(function (box) { box.checked = false; });
                        syncNext();
                    });
                }

                form.querySelectorAll('.calls-export-group-toggle').forEach(function (button) {
                    button.addEventListener('click', function () {
                        boxes.filter(function (box) {
                            return box.dataset.exportGroup === button.dataset.group;
                        }).forEach(function (box) {
                            box.checked = true;
                        });
                        syncNext();
                    });
                });

                if (search) {
                    search.addEventListener('input', function () {
                        var needle = (search.value || '').toLowerCase().trim();
                        form.querySelectorAll('.lead-export-option').forEach(function (option) {
                            var label = option.getAttribute('data-column-label') || '';
                            option.hidden = needle !== '' && label.indexOf(needle) === -1;
                        });
                    });
                }

                syncNext();
            }
        })();
    </script>
@endpush
