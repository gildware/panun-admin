@php
    $deep = $a['cancelled_deep'] ?? [];
    $staff = $a['staff_performance'] ?? [];
    $engagement = $a['engagement'] ?? [];
    $engSummary = $engagement['summary'] ?? [];
    $noResp = $engagement['no_response_analysis'] ?? [];
    $noRespCompare = $noResp['comparison'] ?? [];
@endphp

<div class="card mb-3 border-0 shadow-sm border-start border-4 border-danger">
    <div class="card-body">
        <h4 class="mb-1 text-danger d-flex align-items-center gap-2">
            <span class="material-icons">analytics</span>
            {{ translate('Cancelled_Deep_Analysis') }}
        </h4>
        <p class="text-muted fz-12 mb-3">{{ translate('Cancelled_deep_analysis_help') }}</p>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 bg-light">
                    <span class="fz-12 text-muted d-block">{{ translate('Cancelled_never_followed_up') }}</span>
                    <strong class="fs-5">{{ $engSummary['cancelled_never_followed_up'] ?? 0 }}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 bg-light">
                    <span class="fz-12 text-muted d-block">{{ translate('Cancelled_delayed_first_contact') }}</span>
                    <strong class="fs-5">{{ $engSummary['cancelled_delayed_first_contact'] ?? 0 }}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 bg-light">
                    <span class="fz-12 text-muted d-block">{{ translate('No_response_cancellations') }}</span>
                    <strong class="fs-5">{{ $noResp['cancelled_count'] ?? 0 }}</strong>
                </div>
            </div>
        </div>

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => translate('Category_x_Cancellation_Reason'),
            'subtitle' => translate('Category_reason_matrix_help'),
            'parentLabel' => translate('Category'),
            'childLabel' => translate('Cancellation_Reason'),
            'rows' => $deep['category_reason_matrix'] ?? [],
        ])

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => translate('Category_x_Zone'),
            'subtitle' => translate('Category_zone_matrix_help'),
            'parentLabel' => translate('Category'),
            'childLabel' => translate('Zone'),
            'rows' => $deep['category_zone_matrix'] ?? [],
        ])

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => translate('Reason_x_Zone'),
            'subtitle' => translate('Reason_zone_matrix_help'),
            'parentLabel' => translate('Cancellation_Reason'),
            'childLabel' => translate('Zone'),
            'rows' => $deep['reason_zone_matrix'] ?? [],
        ])

        @if(!empty($deep['remarks']))
            <div class="mt-4">
                <h5 class="fz-14 mb-2">{{ translate('Cancellation_Remarks') }}</h5>
                <p class="text-muted fz-12 mb-2">{{ translate('Cancellation_remarks_help') }}</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>{{ translate('Category') }}</th>
                            <th>{{ translate('Zone') }}</th>
                            <th>{{ translate('Reason') }}</th>
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
    </div>
</div>

<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <h4 class="mb-1 d-flex align-items-center gap-2">
            <span class="material-icons text-primary">support_agent</span>
            {{ translate('Staff_Response_and_Followup_Analysis') }}
        </h4>
        <p class="text-muted fz-12 mb-3">{{ translate('Staff_response_analysis_help') }}</p>

        <div class="row g-3 mb-3">
            <div class="col-md-3 col-sm-6">
                <div class="border rounded p-3 h-100">
                    <span class="fz-12 text-muted">{{ translate('Median_hours_to_first_followup') }}</span>
                    <h4 class="mb-0 mt-1">{{ isset($engSummary['median_hours_to_first_followup']) ? $engSummary['median_hours_to_first_followup'] . 'h' : '—' }}</h4>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded p-3 h-100">
                    <span class="fz-12 text-muted">{{ translate('Followups_on_time_rate') }}</span>
                    <h4 class="mb-0 mt-1">{{ isset($engSummary['followup_on_time_rate']) ? $engSummary['followup_on_time_rate'] . '%' : '—' }}</h4>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded p-3 h-100">
                    <span class="fz-12 text-muted">{{ translate('Booked_median_first_followup') }}</span>
                    <h4 class="mb-0 mt-1">{{ isset($noRespCompare['booked_median_hours']) ? $noRespCompare['booked_median_hours'] . 'h' : '—' }}</h4>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="border rounded p-3 h-100">
                    <span class="fz-12 text-muted">{{ translate('No_response_median_first_followup') }}</span>
                    <h4 class="mb-0 mt-1">{{ isset($noRespCompare['cancelled_no_response_median_hours']) ? $noRespCompare['cancelled_no_response_median_hours'] . 'h' : '—' }}</h4>
                </div>
            </div>
        </div>

        @if(!empty($engagement['insights']))
            <ul class="list-unstyled mb-3 d-flex flex-column gap-2">
                @foreach($engagement['insights'] as $insight)
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

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>{{ translate('Handled_By') }}</th>
                    <th class="text-end">{{ translate('Total') }}</th>
                    <th class="text-end">{{ translate('Booked') }}</th>
                    <th class="text-end">{{ translate('Cancelled') }}</th>
                    <th class="text-end">{{ translate('conversion') }} %</th>
                    <th class="text-end">{{ translate('cancellation_rate') }} %</th>
                    <th class="text-end">{{ translate('Avg_followups') }}</th>
                    <th class="text-end">{{ translate('Median_first_followup') }}</th>
                    <th class="text-end">{{ translate('First_followup_on_time') }} %</th>
                    <th class="text-end">{{ translate('Cancelled_zero_followup') }}</th>
                    <th class="text-end">{{ translate('Cancelled_delayed_contact') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($staff as $row)
                    <tr>
                        <td>{{ $row['label'] ?? '—' }}</td>
                        <td class="text-end">{{ $row['total'] ?? 0 }}</td>
                        <td class="text-end text-success">{{ $row['booked'] ?? 0 }}</td>
                        <td class="text-end text-danger">{{ $row['cancelled'] ?? 0 }}</td>
                        <td class="text-end">{{ $row['conversion_rate'] ?? 0 }}%</td>
                        <td class="text-end">{{ $row['cancel_rate'] ?? 0 }}%</td>
                        <td class="text-end">{{ $row['avg_followups_per_lead'] ?? 0 }}</td>
                        <td class="text-end">{{ isset($row['median_hours_to_first_followup']) ? $row['median_hours_to_first_followup'] . 'h' : '—' }}</td>
                        <td class="text-end">{{ isset($row['first_followup_on_time_rate']) ? $row['first_followup_on_time_rate'] . '%' : '—' }}</td>
                        <td class="text-end">{{ $row['cancelled_zero_followup'] ?? 0 }}</td>
                        <td class="text-end">{{ $row['cancelled_delayed_first_contact'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-3">{{ translate('Data_not_available') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
