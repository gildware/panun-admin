@extends('adminmodule::layouts.new-master')

@section('title', 'Call tests')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
    <style>
        .call-table-tools { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; flex-wrap: wrap; }
        .call-table-tools .form-control { max-width: 280px; flex: 1; }
        .call-table-tools .btn { margin-left: auto; }
        .trn button.call-sort {
            border: 0; background: transparent; padding: 0; margin: 0; font: inherit;
            letter-spacing: inherit; text-transform: inherit; color: inherit; cursor: pointer;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .trn button.call-sort:hover,
        .trn button.call-sort.is-sorted { color: var(--lms-ink); }
        .trn button.call-sort .sort-mark { font-size: .68rem; opacity: .4; }
        .trn button.call-sort.is-sorted .sort-mark { opacity: 1; }
        .trn .call-tests th, .trn .call-tests td { white-space: nowrap; }
        .trn .call-tests td.call-name { white-space: normal; min-width: 180px; }
        .trn .call-actions { display: flex; justify-content: flex-end; gap: 6px; }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">Call tests</h2>
                    <div class="trn-meta mt-1">Choose one scenario and the skills, publish the test, then assign it. It stays separate from courses.</div>
                </div>
                @include('adminmodule::admin.training.manage._nav')
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="call-table-tools">
                        <label class="visually-hidden" for="call-test-search">Search call tests</label>
                        <input id="call-test-search" class="form-control" type="search" placeholder="Search name, status, or difficulty" autocomplete="off">
                        <span class="trn-meta" id="call-tests-count">{{ $tests->count() }} {{ $tests->count() === 1 ? 'test' : 'tests' }}</span>
                        <a class="btn btn--primary btn-sm" href="{{ route('admin.training.calls.manage.create') }}">Add new</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 call-tests" id="call-tests">
                            <thead>
                            <tr>
                                <th><button class="call-sort is-sorted" type="button" data-key="name" aria-sort="ascending">Test <span class="sort-mark">↑</span></button></th>
                                <th><button class="call-sort" type="button" data-key="status" aria-sort="none">Status <span class="sort-mark">↕</span></button></th>
                                <th><button class="call-sort" type="button" data-key="difficulty" aria-sort="none">Difficulty <span class="sort-mark">↕</span></button></th>
                                <th><button class="call-sort" type="button" data-key="scenario" aria-sort="none">Scenario <span class="sort-mark">↕</span></button></th>
                                <th><button class="call-sort" type="button" data-key="skills" aria-sort="none">Skills <span class="sort-mark">↕</span></button></th>
                                <th><button class="call-sort" type="button" data-key="pass" aria-sort="none">Pass <span class="sort-mark">↕</span></button></th>
                                <th><button class="call-sort" type="button" data-key="assigned" aria-sort="none">Assigned <span class="sort-mark">↕</span></button></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($tests as $test)
                                @php
                                    $status = $test->isPublished() ? 'Published' : 'Draft';
                                    $difficulty = \Modules\AdminModule\Entities\TrainingCallTest::DIFFICULTIES[$test->difficulty ?: 'standard'] ?? 'Standard';
                                    $scenarioId = $test->scenarios[0] ?? null;
                                    $scenarioName = is_string($scenarioId) ? (\Modules\AdminModule\Support\CallSimulationCatalog::scenario($scenarioId)['name'] ?? '—') : '—';
                                    $skillCount = count($test->skills ?? []);
                                @endphp
                                <tr
                                    data-name="{{ $test->name }}"
                                    data-status="{{ $status }}"
                                    data-difficulty="{{ $difficulty }}"
                                    data-scenario="{{ $scenarioName }}"
                                    data-skills="{{ $skillCount }}"
                                    data-pass="{{ (int) $test->pass_mark }}"
                                    data-assigned="{{ (int) $test->assignments_count }}"
                                    data-search="{{ strtolower($test->name.' '.$status.' '.$difficulty) }}"
                                >
                                    <td class="call-name">
                                        <div class="fw-semibold">{{ $test->name }}</div>
                                    </td>
                                    <td>
                                        @include('adminmodule::admin.training._badge', [
                                            'text' => $status,
                                            'tone' => $test->isPublished() ? 'ok' : 'warn',
                                        ])
                                    </td>
                                    <td>@include('adminmodule::admin.training._difficulty', ['difficulty' => $test->difficulty ?: 'standard'])</td>
                                    <td>{{ $scenarioName }}</td>
                                    <td>{{ $skillCount }}</td>
                                    <td>{{ $test->pass_mark }}</td>
                                    <td>{{ $test->assignments_count }}</td>
                                    <td>
                                        <div class="call-actions">
                                            <a class="btn btn--primary btn-sm" href="{{ route('admin.training.calls.manage.edit', $test) }}">Set up</a>
                                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.manage.assign', $test) }}">Assign</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="call-tests-none">
                                    <td colspan="8" class="text-muted">No call tests yet.</td>
                                </tr>
                            @endforelse
                            <tr id="call-tests-empty" hidden>
                                <td colspan="8" class="text-muted">No call tests match that search.</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const table = document.getElementById('call-tests');
            const search = document.getElementById('call-test-search');
            const count = document.getElementById('call-tests-count');
            const empty = document.getElementById('call-tests-empty');
            if (!table || !search) return;
            const tbody = table.querySelector('tbody');
            const rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-name]'));
            const numeric = { skills: true, pass: true, assigned: true };
            let key = 'name';
            let dir = 1;

            function value(row) {
                const raw = row.dataset[key] || '';
                return numeric[key] ? Number(raw) || 0 : raw.toLowerCase();
            }

            function paintSort() {
                table.querySelectorAll('.call-sort').forEach(function (button) {
                    const on = button.dataset.key === key;
                    button.classList.toggle('is-sorted', on);
                    button.setAttribute('aria-sort', on ? (dir === 1 ? 'ascending' : 'descending') : 'none');
                    const mark = button.querySelector('.sort-mark');
                    if (mark) mark.textContent = on ? (dir === 1 ? '↑' : '↓') : '↕';
                });
            }

            function render() {
                const query = search.value.trim().toLowerCase();
                const visible = rows.filter(function (row) {
                    return query === '' || (row.dataset.search || '').indexOf(query) !== -1;
                });
                visible.sort(function (a, b) {
                    const left = value(a);
                    const right = value(b);
                    if (left < right) return -1 * dir;
                    if (left > right) return 1 * dir;
                    return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                });
                visible.forEach(function (row) {
                    row.hidden = false;
                    tbody.appendChild(row);
                });
                rows.forEach(function (row) {
                    if (visible.indexOf(row) === -1) {
                        row.hidden = true;
                        tbody.appendChild(row);
                    }
                });
                if (empty) {
                    empty.hidden = rows.length === 0 || visible.length !== 0;
                    tbody.appendChild(empty);
                }
                if (count) {
                    const shown = rows.length === 0 ? 0 : visible.length;
                    const noun = shown === 1 ? 'test' : 'tests';
                    count.textContent = query === '' ? shown + ' ' + noun : shown + ' of ' + rows.length;
                }
                paintSort();
            }

            table.querySelectorAll('.call-sort').forEach(function (button) {
                button.addEventListener('click', function () {
                    const next = button.dataset.key;
                    if (next === key) dir = -dir;
                    else {
                        key = next;
                        dir = 1;
                    }
                    render();
                });
            });
            search.addEventListener('input', render);
        })();
    </script>
@endpush
