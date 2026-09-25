@extends('adminmodule::layouts.new-master')

@section('title', 'Company holidays')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
@php
    $byDate = $holidays->keyBy(fn ($holiday) => $holiday->holiday_on->toDateString());
    $today = now()->toDateString();
    $weekdays = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
    $isTable = $view === 'table';
    $currentYear = (int) now()->year;
    $holidayQuery = function (?string $view = null, ?int $forYear = null) use ($year, $isTable, $currentYear) {
        $params = [];
        $forYear = $forYear ?? $year;
        if ($forYear !== $currentYear) {
            $params['year'] = $forYear;
        }
        $useTable = $view === null ? $isTable : $view === 'table';
        if ($useTable) {
            $params['view'] = 'table';
        }

        return $params;
    };
    $holidayRecords = $holidays->map(fn ($holiday) => [
        'id' => $holiday->id,
        'name' => $holiday->name,
        'date' => $holiday->holiday_on->toDateString(),
        'description' => $holiday->description,
    ])->values();
@endphp
<div class="main-content">
    <div class="container-fluid">
        @include('adminmodule::admin.people._open', [
            'desk' => 'hr',
            'section' => 'holidays',
            'links' => [],
        ])

        <div class="people-ws-head">
            <div>
                <h1>{{ $year }} company holidays</h1>
                <p>These days are off for everyone. Employees see a holiday as soon as you save it.</p>
            </div>
            <div class="people-ws-head-actions">
                <nav class="people-ws-year-nav" aria-label="Holiday year">
                    @if($year > $currentYear - 1)
                        <a href="{{ route('admin.people.holidays', $holidayQuery(null, $year - 1)) }}" aria-label="{{ $year - 1 }}" title="{{ $year - 1 }}">
                            <span class="material-icons">chevron_left</span>
                        </a>
                    @else
                        <span class="is-disabled" aria-hidden="true"><span class="material-icons">chevron_left</span></span>
                    @endif
                    <strong>{{ $year }}</strong>
                    @if($year < $currentYear + 1)
                        <a href="{{ route('admin.people.holidays', $holidayQuery(null, $year + 1)) }}" aria-label="{{ $year + 1 }}" title="{{ $year + 1 }}">
                            <span class="material-icons">chevron_right</span>
                        </a>
                    @else
                        <span class="is-disabled" aria-hidden="true"><span class="material-icons">chevron_right</span></span>
                    @endif
                </nav>
                <div class="people-ws-view" role="group" aria-label="Holiday view">
                    <a class="{{ $isTable ? '' : 'is-on' }}" href="{{ route('admin.people.holidays', $holidayQuery('calendar')) }}" aria-label="Calendar" title="Calendar">
                        <span class="material-icons">calendar_month</span>
                    </a>
                    <a class="{{ $isTable ? 'is-on' : '' }}" href="{{ route('admin.people.holidays', $holidayQuery('table')) }}" aria-label="Table" title="Table">
                        <span class="material-icons">table_chart</span>
                    </a>
                </div>
                <button class="btn-pw primary" type="button" data-holiday-add>Add holiday</button>
            </div>
        </div>

        @if($isTable)
            <article class="people-ws-card people-ws-mt people-ws-scroll">
                <table>
                    <thead><tr><th>Date</th><th>Day</th><th>Title</th><th>Description</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($holidays as $holiday)
                        <tr class="people-ws-person" data-holiday-view="{{ $holiday->id }}">
                            <td>{{ $holiday->holiday_on->format('j M Y') }}</td>
                            <td>{{ $holiday->holiday_on->format('l') }}</td>
                            <td><button class="people-ws-text-btn" type="button" data-holiday-view="{{ $holiday->id }}">{{ $holiday->name }}</button></td>
                            <td>{{ $holiday->description ?: '—' }}</td>
                            <td><span class="people-ws-badge {{ $holiday->holiday_on->lt(now()->startOfDay()) ? 'is-draft' : 'is-info' }}">{{ $holiday->holiday_on->lt(now()->startOfDay()) ? 'Passed' : 'Upcoming' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="people-ws-note">No holidays for {{ $year }} yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </article>
        @else
            <div class="people-ws-year people-ws-mt">
                @for($month = 1; $month <= 12; $month++)
                    @php
                        $start = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
                        $pad = $start->dayOfWeekIso - 1;
                    @endphp
                    <article class="people-ws-card people-ws-month">
                        <h2>{{ $start->format('F') }}</h2>
                        <div class="people-ws-cal-head">
                            @foreach($weekdays as $weekday)
                                <span>{{ $weekday }}</span>
                            @endforeach
                        </div>
                        <div class="people-ws-cal">
                            @for($blank = 0; $blank < $pad; $blank++)
                                <span></span>
                            @endfor
                            @for($day = 1; $day <= $start->daysInMonth; $day++)
                                @php
                                    $date = $start->copy()->day($day);
                                    $key = $date->toDateString();
                                    $holiday = $byDate->get($key);
                                    $classes = 'people-ws-cal-day';
                                    if ($date->isSunday()) {
                                        $classes .= ' is-sunday';
                                    }
                                    if ($holiday) {
                                        $classes .= ' is-holiday';
                                    }
                                    if ($key === $today) {
                                        $classes .= ' is-today';
                                    }
                                @endphp
                                <button
                                    class="{{ $classes }}"
                                    type="button"
                                    @if($holiday)
                                        data-holiday-view="{{ $holiday->id }}"
                                        title="{{ $holiday->name }}"
                                    @else
                                        data-holiday-add="{{ $key }}"
                                    @endif
                                ><strong>{{ $day }}</strong></button>
                            @endfor
                        </div>
                    </article>
                @endfor
            </div>
        @endif

        <div class="modal fade" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content people-ws-holiday-modal">
                    <form
                        id="holiday-form"
                        method="post"
                        action="{{ route('admin.people.holidays.store') }}"
                        data-store="{{ route('admin.people.holidays.store') }}"
                        data-update="{{ route('admin.people.holidays.update', ['holiday' => '__ID__']) }}"
                        data-destroy="{{ route('admin.people.holidays.destroy', ['holiday' => '__ID__']) }}"
                    >
                        @csrf
                        <input type="hidden" name="_method" id="holiday-method" value="PUT" disabled>
                        <input type="hidden" name="editing_id" id="holiday-editing-id" value="{{ old('editing_id') }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        @if($isTable)
                            <input type="hidden" name="view" value="table">
                        @endif
                        <div class="modal-header">
                            <h2 class="modal-title" id="holidayModalLabel">Add a holiday</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="holiday-view" hidden>
                                <p class="people-holiday-kicker" id="holiday-view-when"></p>
                                <h3 id="holiday-view-name"></h3>
                                <p class="people-holiday-note" id="holiday-view-description"></p>
                            </div>
                            <div id="holiday-fields">
                            @if($errors->any())
                                <ul class="people-ws-errors">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="field">
                                <label for="holiday_title">Title</label>
                                <input id="holiday_title" name="name" maxlength="120" value="{{ old('name') }}" required placeholder="Republic Day">
                            </div>
                            <div class="field">
                                <label for="holiday_on">Date</label>
                                <input id="holiday_on" type="date" name="holiday_on" min="{{ $year }}-01-01" max="{{ $year }}-12-31" value="{{ old('holiday_on') }}" required>
                            </div>
                            <div class="field">
                                <label for="holiday_description">Description</label>
                                <textarea id="holiday_description" name="description" maxlength="2000" placeholder="Optional note for this day">{{ old('description') }}</textarea>
                            </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-pw danger" type="submit" id="holiday-remove-btn" form="holiday-remove" hidden>Remove this holiday</button>
                            <div class="people-ws-actions" id="holiday-view-actions" hidden>
                                <button class="btn-pw" type="button" data-bs-dismiss="modal">Close</button>
                                <button class="btn-pw primary" type="button" id="holiday-start-edit">Edit</button>
                            </div>
                            <div class="people-ws-actions" id="holiday-edit-actions">
                                <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn-pw primary" type="submit" id="holiday-submit">Add holiday</button>
                            </div>
                        </div>
                    </form>
                    <form id="holiday-remove" method="post" action="{{ route('admin.people.holidays.store') }}">
                        @csrf
                        @method('delete')
                        <input type="hidden" name="year" value="{{ $year }}">
                        @if($isTable)
                            <input type="hidden" name="view" value="table">
                        @endif
                    </form>
                </div>
            </div>
        </div>
        <script type="application/json" id="holiday-records">@json($holidayRecords)</script>

        @include('adminmodule::admin.people._close')
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    var records = {};
    var payload = document.getElementById('holiday-records');
    if (payload) {
        JSON.parse(payload.textContent).forEach(function (row) {
            records[row.id] = row;
        });
    }

    var form = document.getElementById('holiday-form');
    var method = document.getElementById('holiday-method');
    var editing = document.getElementById('holiday-editing-id');
    var title = document.getElementById('holidayModalLabel');
    var submit = document.getElementById('holiday-submit');
    var removeForm = document.getElementById('holiday-remove');
    var removeBtn = document.getElementById('holiday-remove-btn');
    var viewPane = document.getElementById('holiday-view');
    var fields = document.getElementById('holiday-fields');
    var viewActions = document.getElementById('holiday-view-actions');
    var editActions = document.getElementById('holiday-edit-actions');
    var current = null;
    if (!form || !window.bootstrap) {
        return;
    }

    function showHolidayMode(mode) {
        var viewing = mode === 'view';
        viewPane.hidden = !viewing;
        fields.hidden = viewing;
        viewActions.hidden = !viewing;
        editActions.hidden = viewing;
        removeBtn.hidden = viewing || mode !== 'edit';
    }

    function openHoliday(mode, holiday) {
        holiday = holiday || {};
        current = holiday;
        document.getElementById('holiday_title').value = holiday.name || '';
        document.getElementById('holiday_on').value = holiday.date || '';
        document.getElementById('holiday_description').value = holiday.description || '';
        document.getElementById('holiday-view-name').textContent = holiday.name || 'Holiday';
        document.getElementById('holiday-view-when').textContent = holiday.date ? new Date(holiday.date + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '';
        document.getElementById('holiday-view-description').textContent = holiday.description || 'No description.';

        if ((mode === 'edit' || mode === 'view') && holiday.id) {
            form.action = form.getAttribute('data-update').replace('__ID__', holiday.id);
            method.disabled = false;
            editing.value = holiday.id;
            removeForm.action = form.getAttribute('data-destroy').replace('__ID__', holiday.id);
            title.textContent = mode === 'view' ? 'Holiday' : 'Edit holiday';
            submit.textContent = 'Save holiday';
        } else {
            form.action = form.getAttribute('data-store');
            method.disabled = true;
            editing.value = '';
            title.textContent = 'Add a holiday';
            submit.textContent = 'Add holiday';
            mode = 'add';
        }

        showHolidayMode(mode);
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('holidayModal')).show();
    }

    document.getElementById('holiday-start-edit').addEventListener('click', function () {
        if (!current || !current.id) return;
        title.textContent = 'Edit holiday';
        showHolidayMode('edit');
    });

    document.addEventListener('click', function (event) {
        var view = event.target.closest('[data-holiday-view]');
        if (view) {
            openHoliday('view', records[view.getAttribute('data-holiday-view')]);
            return;
        }
        var add = event.target.closest('[data-holiday-add]');
        if (add) {
            openHoliday('add', { date: add.getAttribute('data-holiday-add') || '' });
        }
    });

    @if($errors->any())
    openHoliday(@json(old('editing_id') ? 'edit' : 'add'), {
        id: @json(old('editing_id')),
        name: @json(old('name')),
        date: @json(old('holiday_on')),
        description: @json(old('description')),
    });
    @endif
})();
</script>
@endpush
