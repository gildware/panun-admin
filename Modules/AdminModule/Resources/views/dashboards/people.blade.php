@extends('adminmodule::layouts.new-master')

@section('title', 'People & HR')

@push('css_or_js')
    @include('adminmodule::dashboards._styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="ws-dash">
                <div class="ws-dash-head">
                    <h1>People & HR</h1>
                    <p>{{ $name }}@if($profile->job_title) · {{ $profile->job_title }}@endif @if($profile->department) · {{ $profile->department }}@endif</p>
                </div>

                <div class="ws-dash-pair">
                    <article class="ws-dash-box ws-dash-box--birthday">
                        <header class="ws-dash-box-head">
                            <h2><span class="material-icons" aria-hidden="true">cake</span> Team birthdays</h2>
                            <span class="ws-dash-box-count">{{ $birthdays->count() }}</span>
                        </header>
                        <div class="ws-dash-box-body">
                            @if($birthdays->isEmpty())
                                <p class="ws-dash-empty">No birthdays on file.</p>
                            @else
                                <ul class="ws-dash-box-list">
                                    @foreach($birthdays as $person)
                                        <li class="{{ $person['today'] ? 'is-today' : '' }}">
                                            <div class="ws-dash-date">
                                                <b>{{ $person['day'] }}</b>
                                                <i>{{ $person['month'] }}</i>
                                            </div>
                                            <div class="ws-dash-box-copy">
                                                <strong>{{ $person['name'] }}</strong>
                                                @if($person['detail'] !== '')
                                                    <em>{{ $person['detail'] }}</em>
                                                @endif
                                            </div>
                                            <span class="ws-dash-pill">{{ $person['label'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </article>
                    <article class="ws-dash-box ws-dash-box--holiday" data-holiday-widget>
                        <header class="ws-dash-box-head">
                            <h2><span class="material-icons" aria-hidden="true">event</span> Holidays</h2>
                            <div class="ws-dash-switch" role="tablist" aria-label="Holiday list">
                                <button type="button" class="is-on" role="tab" aria-selected="true" data-holiday-pane="upcoming">Upcoming <span>{{ $holidays->count() }}</span></button>
                                <button type="button" role="tab" aria-selected="false" data-holiday-pane="previous">Previous <span>{{ $previousHolidays->count() }}</span></button>
                            </div>
                        </header>
                        @foreach(['upcoming' => $holidays, 'previous' => $previousHolidays] as $pane => $items)
                            <div class="ws-dash-box-body" role="tabpanel" data-holiday-list="{{ $pane }}" @if($pane !== 'upcoming') hidden @endif>
                                @if($items->isEmpty())
                                    <p class="ws-dash-empty">{{ $pane === 'upcoming' ? 'No holidays ahead.' : 'No previous holidays.' }}</p>
                                @else
                                    <ul class="ws-dash-box-list">
                                        @foreach($items as $holiday)
                                            <li class="{{ $holiday['today'] ? 'is-today' : '' }}">
                                                <div class="ws-dash-date">
                                                    <b>{{ $holiday['day'] }}</b>
                                                    <i>{{ $holiday['month'] }}</i>
                                                    @if($holiday['year'] !== '')
                                                        <small>{{ $holiday['year'] }}</small>
                                                    @endif
                                                </div>
                                                <div class="ws-dash-box-copy">
                                                    <strong>{{ $holiday['name'] }}</strong>
                                                    @if($holiday['detail'] !== '')
                                                        <em>{{ $holiday['detail'] }}</em>
                                                    @endif
                                                </div>
                                                <span class="ws-dash-pill">{{ $holiday['label'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </article>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var box = document.querySelector('[data-holiday-widget]');
            if (!box) return;

            box.querySelectorAll('[data-holiday-pane]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var pane = button.getAttribute('data-holiday-pane');
                    box.querySelectorAll('[data-holiday-pane]').forEach(function (item) {
                        var on = item === button;
                        item.classList.toggle('is-on', on);
                        item.setAttribute('aria-selected', on ? 'true' : 'false');
                    });
                    box.querySelectorAll('[data-holiday-list]').forEach(function (list) {
                        list.hidden = list.getAttribute('data-holiday-list') !== pane;
                    });
                });
            });
        })();
    </script>
@endpush
