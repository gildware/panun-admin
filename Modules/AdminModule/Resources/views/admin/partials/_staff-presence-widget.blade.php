@php
    $presenceService = app(\Modules\AdminModule\Services\StaffPresenceService::class);
    $staffList = $presenceService->listStaffPresence();
    $summary = [
        'online' => $staffList->where('presence_status', 'online')->count(),
        'away' => $staffList->where('presence_status', 'away')->count(),
        'on_break' => $staffList->where('presence_status', 'on_break')->count(),
        'offline' => $staffList->where('presence_status', 'offline')->count(),
    ];
@endphp
<div class="staff-presence-panel" id="staff-presence-widget">
    <div class="staff-presence-panel__header">
        <div class="staff-presence-panel__title">
            <span class="material-symbols-outlined" aria-hidden="true">badge</span>
            <span>{{ translate('Employee_Status') }}</span>
            <span class="staff-presence-panel__count">{{ $staffList->count() }}</span>
        </div>
        <div class="staff-presence-panel__tools">
            <div class="staff-presence-summary" id="staff-presence-summary">
                <span class="staff-presence-chip staff-presence-chip--online">
                    {{ translate('Online') }} <strong data-summary="online">{{ $summary['online'] }}</strong>
                </span>
                <span class="staff-presence-chip staff-presence-chip--busy">
                    {{ translate('Busy') }} <strong data-summary="away">{{ $summary['away'] }}</strong>
                </span>
                <span class="staff-presence-chip staff-presence-chip--break">
                    {{ translate('On_Break') }} <strong data-summary="on_break">{{ $summary['on_break'] }}</strong>
                </span>
                <span class="staff-presence-chip staff-presence-chip--offline">
                    {{ translate('Offline') }} <strong data-summary="offline">{{ $summary['offline'] }}</strong>
                </span>
            </div>
            <button type="button"
                    class="staff-presence-history-btn"
                    id="staff-presence-history-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#staffPresenceHistoryModal">
                <span class="material-symbols-outlined" aria-hidden="true">history</span>
                {{ translate('View_History') }}
            </button>
        </div>
    </div>

    <div class="staff-presence-panel__body">
        @if($staffList->isNotEmpty())
            <div class="staff-presence-table-wrap">
                <table class="staff-presence-table">
                    <thead>
                        <tr>
                            <th class="staff-presence-employee-col">{{ translate('Employee') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Last_Visited_Page') }}</th>
                            <th>{{ translate('Last_Seen') }}</th>
                            <th>{{ translate('Last_Offline_Period_Today') }}</th>
                            <th>{{ translate('Total_Offline_Today') }}</th>
                            <th>{{ translate('Last_Away_Period_Today') }}</th>
                            <th>{{ translate('Total_Away_Today') }}</th>
                            <th>{{ translate('Last_Break_Period_Today') }}</th>
                            <th>{{ translate('Total_Break_Today') }}</th>
                            <th>{{ translate('Total_Online_Hours_Today') }}</th>
                        </tr>
                    </thead>
                    <tbody id="staff-presence-tbody">
                        @foreach($staffList as $member)
                            <tr data-staff-id="{{ $member['id'] }}">
                                <td class="staff-presence-employee-col">
                                    <div class="staff-presence-person">
                                        <div class="staff-presence-avatar-wrap">
                                            <img src="{{ $member['profile_image'] }}" alt="" class="staff-presence-avatar" width="28" height="28">
                                            <span class="staff-presence-dot {{ $presenceService->statusDotClass($member['presence_status']) }}"></span>
                                        </div>
                                        <div class="staff-presence-person__meta">
                                            <div class="staff-presence-person__name">{{ $member['name'] }}</div>
                                            <div class="staff-presence-person__email">{{ $member['email'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="staff-presence-badge {{ $presenceService->statusBadgeClass($member['presence_status']) }}">{{ $member['presence_label'] }}</span>
                                </td>
                                <td class="staff-last-visited-page" title="{{ $member['last_visited_page'] ?? '' }}">
                                    {{ $member['last_visited_page_label'] }}
                                </td>
                                <td class="staff-last-seen">
                                    @if($member['last_seen_at'])
                                        {{ \Carbon\Carbon::parse($member['last_seen_at'])->diffForHumans() }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="staff-last-offline-today">{{ $member['last_offline_period_today'] }}</td>
                                <td class="staff-total-offline-today">{{ $member['total_offline_today'] }}</td>
                                <td class="staff-last-away-today">{{ $member['last_away_period_today'] }}</td>
                                <td class="staff-total-away-today">{{ $member['total_away_today'] }}</td>
                                <td class="staff-last-break-today">{{ $member['last_break_period_today'] }}</td>
                                <td class="staff-total-break-today">{{ $member['total_break_today'] }}</td>
                                <td class="staff-total-online-today">{{ $member['total_online_today'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="staff-presence-empty">{{ translate('No_employees_found') }}</div>
        @endif
    </div>
</div>

<div class="modal fade" id="staffPresenceHistoryModal" tabindex="-1" aria-labelledby="staffPresenceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 staff-presence-history-header">
                <h5 class="modal-title fs-6 mb-0" id="staffPresenceHistoryModalLabel">{{ translate('Employee_Status_History') }}</h5>
                <div class="staff-presence-history-header__actions">
                    <label for="staff-presence-history-date" class="visually-hidden">{{ translate('Select_Date') }}</label>
                    <input type="date"
                           id="staff-presence-history-date"
                           class="form-control form-control-sm staff-presence-history-date"
                           max="{{ now()->toDateString() }}"
                           value="{{ now()->toDateString() }}">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
            </div>
            <div class="modal-body pt-2">
                <div id="staff-presence-history-empty" class="text-center text-muted py-4 d-none">
                    {{ translate('No_presence_history_available') }}
                </div>
                <div id="staff-presence-history-loading" class="text-center text-muted py-4 d-none">
                    {{ translate('Loading') }}...
                </div>
                <div class="table-responsive overflow-auto d-none" id="staff-presence-history-table-wrap">
                    <table class="staff-presence-table staff-presence-table--history">
                        <thead>
                            <tr>
                                <th class="staff-presence-employee-col">{{ translate('Employee') }}</th>
                                <th>{{ translate('Last_Offline_Period_Today') }}</th>
                                <th>{{ translate('Total_Offline_Today') }}</th>
                                <th>{{ translate('Last_Away_Period_Today') }}</th>
                                <th>{{ translate('Total_Away_Today') }}</th>
                                <th>{{ translate('Last_Break_Period_Today') }}</th>
                                <th>{{ translate('Total_Break_Today') }}</th>
                                <th>{{ translate('Total_Online_Hours_Today') }}</th>
                            </tr>
                        </thead>
                        <tbody id="staff-presence-history-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
