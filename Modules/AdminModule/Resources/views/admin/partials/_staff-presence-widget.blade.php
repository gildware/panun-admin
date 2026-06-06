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
<div class="card dashboard-widget-staff-presence" id="staff-presence-widget">
    <div class="card-header d-flex justify-content-between align-items-center gap-10 flex-wrap">
        <h5 class="dashboard-widget-title mb-0">
            <span class="material-symbols-outlined dashboard-widget-title__icon text-primary" aria-hidden="true">badge</span>
            {{ translate('Employee_Status') }}
            <span class="text-muted fs-13">({{ $staffList->count() }})</span>
        </h5>
        <div class="d-flex flex-wrap gap-2 fs-12" id="staff-presence-summary">
            <span class="badge bg-success">{{ translate('Online') }}: <span data-summary="online">{{ $summary['online'] }}</span></span>
            <span class="badge bg-warning text-dark">{{ translate('Away') }}: <span data-summary="away">{{ $summary['away'] }}</span></span>
            <span class="badge bg-info text-dark">{{ translate('On_Break') }}: <span data-summary="on_break">{{ $summary['on_break'] }}</span></span>
            <span class="badge bg-secondary">{{ translate('Offline') }}: <span data-summary="offline">{{ $summary['offline'] }}</span></span>
        </div>
    </div>
    <div class="card-body p-0">
        @if($staffList->isNotEmpty())
            <div class="table-responsive px-3">
                <table class="table table-hover align-middle mb-0 fs-13">
                    <thead class="text-secondary border-bottom">
                        <tr>
                            <th>{{ translate('Employee') }}</th>
                            <th>{{ translate('Role') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Last_Visited_Page') }}</th>
                            <th>{{ translate('Last_Seen') }}</th>
                        </tr>
                    </thead>
                    <tbody id="staff-presence-tbody">
                        @foreach($staffList as $member)
                            <tr data-staff-id="{{ $member['id'] }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative">
                                            <img src="{{ $member['profile_image'] }}" alt="" class="avatar rounded-circle" width="36" height="36">
                                            <span class="position-absolute bottom-0 end-0 rounded-circle border border-white staff-presence-dot {{ $presenceService->statusDotClass($member['presence_status']) }}" style="width:10px;height:10px;"></span>
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $member['name'] }}</div>
                                            <div class="small text-muted">{{ $member['email'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-capitalize">{{ str_replace('-', ' ', $member['user_type']) }}</td>
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
