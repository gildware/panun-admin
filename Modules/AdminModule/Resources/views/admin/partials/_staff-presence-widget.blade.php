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
<div class="card dashboard-widget-staff-presence dashboard-collapsible-widget" id="staff-presence-widget">
    <div class="card-header d-flex justify-content-between align-items-center gap-10 flex-wrap">
        <h5 class="dashboard-widget-title mb-0">
            <span class="material-symbols-outlined dashboard-widget-title__icon text-primary" aria-hidden="true">badge</span>
            {{ translate('Employee_Status') }}
            <span class="text-muted fs-13">({{ $staffList->count() }})</span>
        </h5>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button"
                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                    id="staff-presence-history-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#staffPresenceHistoryModal">
                <span class="material-symbols-outlined fs-16" aria-hidden="true">history</span>
                {{ translate('View_History') }}
            </button>
            <div class="d-flex flex-wrap gap-2 fs-12" id="staff-presence-summary">
            <span class="badge bg-success">{{ translate('Online') }}: <span data-summary="online">{{ $summary['online'] }}</span></span>
            <span class="badge bg-warning text-dark">{{ translate('Away') }}: <span data-summary="away">{{ $summary['away'] }}</span></span>
            <span class="badge bg-info text-dark">{{ translate('On_Break') }}: <span data-summary="on_break">{{ $summary['on_break'] }}</span></span>
            <span class="badge bg-secondary">{{ translate('Offline') }}: <span data-summary="offline">{{ $summary['offline'] }}</span></span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        @if($staffList->isNotEmpty())
            <div class="table-responsive px-3 overflow-auto">
                <table class="table table-hover align-middle mb-0 fs-13 text-nowrap">
                    <thead class="text-secondary border-bottom">
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
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative flex-shrink-0 staff-presence-avatar-wrap">
                                            <img src="{{ $member['profile_image'] }}" alt="" class="avatar rounded-circle staff-presence-avatar" width="36" height="36">
                                            <span class="position-absolute bottom-0 end-0 rounded-circle border border-white staff-presence-dot {{ $presenceService->statusDotClass($member['presence_status']) }}" style="width:10px;height:10px;"></span>
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $member['name'] }}</div>
                                            <div class="small text-muted">{{ $member['email'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill staff-presence-badge {{ $presenceService->statusBadgeClass($member['presence_status']) }}">{{ $member['presence_label'] }}</span>
                                </td>
                                <td class="staff-last-visited-page text-muted" title="{{ $member['last_visited_page'] ?? '' }}">
                                    {{ $member['last_visited_page_label'] }}
                                </td>
                                <td class="staff-last-seen text-muted">
                                    @if($member['last_seen_at'])
                                        {{ \Carbon\Carbon::parse($member['last_seen_at'])->diffForHumans() }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="staff-last-offline-today text-muted">{{ $member['last_offline_period_today'] }}</td>
                                <td class="staff-total-offline-today text-muted">{{ $member['total_offline_today'] }}</td>
                                <td class="staff-last-away-today text-muted">{{ $member['last_away_period_today'] }}</td>
                                <td class="staff-total-away-today text-muted">{{ $member['total_away_today'] }}</td>
                                <td class="staff-last-break-today text-muted">{{ $member['last_break_period_today'] }}</td>
                                <td class="staff-total-break-today text-muted">{{ $member['total_break_today'] }}</td>
                                <td class="staff-total-online-today text-muted">{{ $member['total_online_today'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="d-flex align-items-center justify-content-center p-4">
                <span class="opacity-50">{{ translate('No_employees_found') }}</span>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="staffPresenceHistoryModal" tabindex="-1" aria-labelledby="staffPresenceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staffPresenceHistoryModalLabel">{{ translate('Employee_Status_History') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <label for="staff-presence-history-date" class="form-label mb-0 fw-medium">{{ translate('Select_Date') }}</label>
                    <select id="staff-presence-history-date" class="form-select form-select-sm w-auto" disabled>
                        <option value="">{{ translate('Loading') }}...</option>
                    </select>
                </div>
                <div id="staff-presence-history-empty" class="text-center text-muted py-4 d-none">
                    {{ translate('No_presence_history_available') }}
                </div>
                <div id="staff-presence-history-loading" class="text-center text-muted py-4 d-none">
                    {{ translate('Loading') }}...
                </div>
                <div class="table-responsive overflow-auto d-none" id="staff-presence-history-table-wrap">
                    <table class="table table-hover align-middle mb-0 fs-13 text-nowrap">
                        <thead class="text-secondary border-bottom">
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
