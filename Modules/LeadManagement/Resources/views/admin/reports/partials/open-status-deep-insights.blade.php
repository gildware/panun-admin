@php
    $deep = $deep ?? [];
    $summary = $deep['summary'] ?? [];
    $mode = $mode ?? 'hold';
    $tone = $tone ?? ($mode === 'hold' ? 'info' : 'warning');
    $parentLabel = $parentLabel ?? translate('Category');
    $zoneLabel = $zoneLabel ?? translate('Zone');
    $reasonLabel = $reasonLabel ?? ($mode === 'hold' ? translate('Hold_reason') : translate('Pending_Reason'));
    $reasonMatrixTitle = $reasonMatrixTitle ?? ($mode === 'hold' ? translate('Category_x_Hold_Reason') : translate('Category_x_Pending_Reason'));
    $zoneMatrixTitle = $zoneMatrixTitle ?? translate('Category_x_Zone');
    $reasonZoneTitle = $reasonZoneTitle ?? ($mode === 'hold' ? translate('Hold_Reason_x_Zone') : translate('Pending_Reason_x_Zone'));
@endphp

<div class="card mb-3 border-0 shadow-sm border-start border-4 border-{{ $tone }}">
    <div class="card-body">
        <h4 class="mb-1 text-{{ $tone }} d-flex align-items-center gap-2">
            <span class="material-icons">analytics</span>
            {{ $title ?? '' }}
        </h4>
        <p class="text-muted fz-12 mb-3">{{ $help ?? '' }}</p>

        <div class="row g-3 mb-3">
            @if($mode === 'hold')
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <span class="fz-12 text-muted d-block">{{ translate('Hold_missing_reason') }}</span>
                        <strong class="fs-5">{{ $summary['missing_reason'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <span class="fz-12 text-muted d-block">{{ translate('Hold_never_followed_up') }}</span>
                        <strong class="fs-5">{{ $summary['never_followed_up'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <span class="fz-12 text-muted d-block">{{ translate('Hold_delayed_first_contact') }}</span>
                        <strong class="fs-5">{{ $summary['delayed_first_contact'] ?? 0 }}</strong>
                    </div>
                </div>
            @else
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <span class="fz-12 text-muted d-block">{{ translate('Pending_never_followed_up') }}</span>
                        <strong class="fs-5">{{ $summary['never_followed_up'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <span class="fz-12 text-muted d-block">{{ translate('Followup_overdue') }}</span>
                        <strong class="fs-5">{{ $summary['overdue'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <span class="fz-12 text-muted d-block">{{ translate('No_followup_scheduled') }}</span>
                        <strong class="fs-5">{{ $summary['no_schedule'] ?? 0 }}</strong>
                    </div>
                </div>
            @endif
        </div>

        @if(!empty($deep['insights']))
            <ul class="list-unstyled mb-3 d-flex flex-column gap-2">
                @foreach($deep['insights'] as $insight)
                    @php
                        $alertClass = match ($insight['type'] ?? 'info') {
                            'success' => 'alert-success',
                            'warning' => 'alert-warning',
                            'danger' => 'alert-danger',
                            default => 'alert-info',
                        };
                    @endphp
                    <li class="alert {{ $alertClass }} py-2 px-3 mb-0 fz-13">{{ $insight['text'] ?? '' }}</li>
                @endforeach
            </ul>
        @endif

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => $reasonMatrixTitle,
            'subtitle' => $mode === 'hold' ? translate('Hold_reason_matrix_help') : translate('Pending_reason_matrix_help'),
            'parentLabel' => $parentLabel,
            'childLabel' => $reasonLabel,
            'rows' => $deep['category_reason_matrix'] ?? [],
        ])

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => $zoneMatrixTitle,
            'subtitle' => translate('Open_category_zone_matrix_help'),
            'parentLabel' => $parentLabel,
            'childLabel' => $zoneLabel,
            'rows' => $deep['category_zone_matrix'] ?? [],
        ])

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => $reasonZoneTitle,
            'subtitle' => $mode === 'hold' ? translate('Hold_reason_zone_matrix_help') : translate('Pending_reason_zone_matrix_help'),
            'parentLabel' => $reasonLabel,
            'childLabel' => $zoneLabel,
            'rows' => $deep['reason_zone_matrix'] ?? [],
        ])

        @if(!empty($deep['remarks']))
            <div class="mt-4">
                <h5 class="fz-14 mb-2">{{ $mode === 'hold' ? translate('Hold_Remarks') : translate('Pending_Remarks') }}</h5>
                <p class="text-muted fz-12 mb-2">{{ $mode === 'hold' ? translate('Hold_remarks_help') : translate('Pending_remarks_help') }}</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>{{ $parentLabel }}</th>
                            <th>{{ $zoneLabel }}</th>
                            <th>{{ $reasonLabel }}</th>
                            <th>{{ translate('Remarks') }}</th>
                            <th class="text-end">{{ translate('Followups') }}</th>
                            <th class="text-end">{{ translate('Hours_to_first_followup') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($deep['remarks'] as $remark)
                            <tr>
                                <td>{{ $remark['category'] ?? '—' }}</td>
                                <td>{{ $remark['zone'] ?? '—' }}</td>
                                <td>{{ $remark['reason'] ?? '—' }}</td>
                                <td class="text-wrap" style="max-width: 280px;">{{ $remark['text'] ?? '' }}</td>
                                <td class="text-end">{{ $remark['followup_count'] ?? 0 }}</td>
                                <td class="text-end">{{ isset($remark['hours_to_first_followup']) ? $remark['hours_to_first_followup'] . 'h' : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mt-4">
            <h5 class="fz-14 mb-1">{{ translate('Staff_Response_and_Followup_Analysis') }}</h5>
            <p class="text-muted fz-12 mb-2">{{ translate('Open_status_staff_help') }}</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ translate('Handled_By') }}</th>
                        <th class="text-end">{{ translate('Total') }}</th>
                        @if($mode === 'hold')
                            <th class="text-end">{{ translate('Missing_hold_reason') }}</th>
                        @else
                            <th class="text-end">{{ translate('Followup_overdue') }}</th>
                            <th class="text-end">{{ translate('No_followup_scheduled') }}</th>
                        @endif
                        <th class="text-end">{{ translate('Never_followed_up') }}</th>
                        <th class="text-end">{{ translate('Avg_followups') }}</th>
                        <th class="text-end">{{ translate('Median_first_followup') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($deep['staff'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['label'] ?? '—' }}</td>
                            <td class="text-end">{{ $row['total'] ?? 0 }}</td>
                            @if($mode === 'hold')
                                <td class="text-end">{{ $row['missing_reason'] ?? 0 }}</td>
                            @else
                                <td class="text-end">{{ $row['overdue'] ?? 0 }}</td>
                                <td class="text-end">{{ $row['no_schedule'] ?? 0 }}</td>
                            @endif
                            <td class="text-end">{{ $row['never_followed_up'] ?? 0 }}</td>
                            <td class="text-end">{{ $row['avg_followups_per_lead'] ?? 0 }}</td>
                            <td class="text-end">{{ isset($row['median_hours_to_first_followup']) ? $row['median_hours_to_first_followup'] . 'h' : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $mode === 'hold' ? 6 : 7 }}" class="text-center text-muted py-3">{{ translate('Data_not_available') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
