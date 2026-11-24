@php
    if (! isset($pendingTimesheet)) {
        $pendingTimesheet = once(static function () {
            $user = auth()->user();
            if (! $user instanceof \Modules\UserManagement\Entities\User) {
                return null;
            }

            return app(\Modules\AdminModule\Services\PeopleWorkspace::class)->pendingTimesheetReminder($user);
        });
    }

@endphp

@if(empty($pendingTimesheet))
    <script>
        (function () {
            var state = window.PanunPendingTimesheetPopup;
            if (state && state.timer) {
                clearInterval(state.timer);
                state.timer = null;
            }
            var stale = document.getElementById('ws-ts-popup');
            if (stale) {
                stale.remove();
            }
        })();
    </script>
@else
    @php
        $timesheetWorkspace = app(\Modules\AdminModule\Services\PeopleWorkspace::class);
        $onTimesheetPage = request()->routeIs('admin.people.index')
            && request()->query('section', 'profile') === 'timesheet';
        $popupEnabled = ! $onTimesheetPage && $timesheetWorkspace->timesheetPopupEnabled();
        $popupIntervalSeconds = $timesheetWorkspace->timesheetPopupIntervalSeconds();
        $pendingDays = (int) ($pendingTimesheet['pending_days'] ?? 0);
        $timesheetQuery = array_filter([
            'section' => 'timesheet',
            'month' => $pendingTimesheet['month'] ?? null,
        ]);
        $timesheetHash = isset($pendingTimesheet['oldest_date']) ? '#ts-day-'.$pendingTimesheet['oldest_date'] : '';
        $timesheetUrl = route('admin.people.index', $timesheetQuery).$timesheetHash;
        $pendingTimesheetUrl = route('admin.people.index', array_merge($timesheetQuery, [
            'status' => 'pending',
        ])).$timesheetHash;
        $bannerClass = 'ws-dash-timesheet'.(! empty($pendingTimesheetGate) ? ' ws-dash-timesheet--gate' : '');
        $pendingTitle = $pendingDays === 1
            ? '1 timesheet day still needs to be filled'
            : $pendingDays.' timesheet days still need to be filled';
        $pendingBody = 'Open your timesheet to complete the pending day'.($pendingDays === 1 ? '' : 's').'.';
    @endphp
    <style>
        .ws-dash-timesheet {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: 16px 16px 16px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #fff7ed;
            border: 1px solid #fdba74;
            border-left: 4px solid #c2410c;
        }
        .ws-dash-timesheet--gate {
            margin: 16px auto 16px;
            max-width: 1080px;
            width: calc(100% - 32px);
        }
        .ws-dash-timesheet-copy {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-width: 0;
        }
        .ws-dash-timesheet-copy .material-icons {
            flex: 0 0 auto;
            margin-top: 1px;
            font-size: 22px;
            color: #c2410c;
        }
        .ws-dash-timesheet-copy strong {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #9a3412;
            line-height: 1.3;
        }
        .ws-dash-timesheet-copy p {
            margin: 3px 0 0;
            font-size: 12px;
            color: #b45309;
            line-height: 1.35;
        }
        .ws-dash-timesheet-link {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            padding: 7px 14px;
            border-radius: 999px;
            background: #c2410c;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .ws-dash-timesheet-link:hover { background: #9a3412; color: #fff; }
        .ws-ts-popup {
            position: fixed;
            inset: 0;
            z-index: 20050;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(17, 24, 39, 0.48);
        }
        .ws-ts-popup.is-open { display: flex; }
        .ws-ts-popup-card {
            width: min(420px, 100%);
            background: #fff;
            border-radius: 14px;
            border-top: 4px solid #c2410c;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            padding: 22px 20px 18px;
            text-align: center;
        }
        .ws-ts-popup-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #fff7ed;
            color: #c2410c;
        }
        .ws-ts-popup-icon .material-icons { font-size: 26px; }
        .ws-ts-popup-card h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 750;
            color: #9a3412;
            line-height: 1.35;
        }
        .ws-ts-popup-card p {
            margin: 8px 0 0;
            font-size: 13px;
            color: #b45309;
            line-height: 1.4;
        }
        .ws-ts-popup-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-top: 18px;
        }
        .ws-ts-popup-actions a,
        .ws-ts-popup-actions button {
            appearance: none;
            border: 0;
            border-radius: 999px;
            padding: 9px 16px;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            line-height: 1.2;
        }
        .ws-ts-popup-actions a {
            background: #c2410c;
            color: #fff;
        }
        .ws-ts-popup-actions a:hover { background: #9a3412; color: #fff; }
        .ws-ts-popup-actions a.is-secondary {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fdba74;
        }
        .ws-ts-popup-actions a.is-secondary:hover { background: #ffedd5; color: #9a3412; }
        .ws-ts-popup-actions button {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fdba74;
        }
        .ws-ts-popup-actions button:hover { background: #ffedd5; }
    </style>

    <aside class="{{ $bannerClass }}" role="status">
        <div class="ws-dash-timesheet-copy">
            <span class="material-icons" aria-hidden="true">schedule</span>
            <div>
                <strong>{{ $pendingTitle }}</strong>
                <p>{{ $pendingBody }}</p>
            </div>
        </div>
        <a class="ws-dash-timesheet-link" href="{{ $pendingTimesheetUrl }}">View pending timesheets</a>
    </aside>

    @if($popupEnabled)
        <div class="ws-ts-popup"
             id="ws-ts-popup"
             data-interval-seconds="{{ $popupIntervalSeconds }}"
             data-timesheet-url="{{ $pendingTimesheetUrl }}"
             hidden
             aria-hidden="true"
             role="dialog"
             aria-modal="true"
             aria-labelledby="ws-ts-popup-title">
            <div class="ws-ts-popup-card">
                <div class="ws-ts-popup-icon" aria-hidden="true">
                    <span class="material-icons">schedule</span>
                </div>
                <h2 id="ws-ts-popup-title">{{ $pendingTitle }}</h2>
                <p>{{ $pendingBody }}</p>
                <div class="ws-ts-popup-actions">
                    <a href="{{ $pendingTimesheetUrl }}">View pending timesheets</a>
                    <a class="is-secondary" href="{{ $timesheetUrl }}">Open timesheet</a>
                    <button type="button" data-ws-ts-dismiss>Remind me later</button>
                </div>
            </div>
        </div>

        <script>
            (function () {
                var popup = document.getElementById('ws-ts-popup');
                if (!popup) {
                    return;
                }

                var intervalSeconds = parseInt(popup.getAttribute('data-interval-seconds') || '30', 10);
                if (!intervalSeconds || intervalSeconds < 5) {
                    intervalSeconds = 30;
                }
                var INTERVAL_MS = intervalSeconds * 1000;
                var state = window.PanunPendingTimesheetPopup = window.PanunPendingTimesheetPopup || {};

                if (state.timer) {
                    clearInterval(state.timer);
                    state.timer = null;
                }

                function openPopup() {
                    popup.hidden = false;
                    popup.classList.add('is-open');
                    popup.setAttribute('aria-hidden', 'false');
                }

                function closePopup() {
                    popup.classList.remove('is-open');
                    popup.hidden = true;
                    popup.setAttribute('aria-hidden', 'true');
                }

                popup.querySelectorAll('[data-ws-ts-dismiss]').forEach(function (button) {
                    button.addEventListener('click', closePopup);
                });

                popup.querySelectorAll('a[href]').forEach(function (link) {
                    link.addEventListener('click', function () {
                        closePopup();
                        if (state.timer) {
                            clearInterval(state.timer);
                            state.timer = null;
                        }
                    });
                });

                popup.addEventListener('click', function (event) {
                    if (event.target === popup) {
                        closePopup();
                    }
                });

                openPopup();
                state.timer = setInterval(openPopup, INTERVAL_MS);
            })();
        </script>
    @else
        <script>
            (function () {
                var state = window.PanunPendingTimesheetPopup;
                if (state && state.timer) {
                    clearInterval(state.timer);
                    state.timer = null;
                }
                var stale = document.getElementById('ws-ts-popup');
                if (stale) {
                    stale.remove();
                }
            })();
        </script>
    @endif
@endif
