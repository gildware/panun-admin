@php
    $a = $analytics ?? [];
    $summary = $a['summary'] ?? [];
    $deep = $a['deep'] ?? [];
    $parentLabel = $a['parent_label'] ?? translate('Reason');
    $isInvalid = ($a['lead_type'] ?? '') === 'invalid';
@endphp

<div class="reason-lead-analytics mb-4">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100 border-start border-4 border-primary">
                <div class="card-body py-3">
                    <span class="fz-12 text-muted">{{ translate('Total_Leads_in_Range') }}</span>
                    <h3 class="mb-0 mt-1">{{ $summary['total'] ?? 0 }}</h3>
                    <span class="fz-12">{{ $isInvalid ? translate('Invalid_Lead_Reports') : translate('Future_Customer_Lead_Reports') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 border-start border-4 border-warning">
                <div class="card-body py-3">
                    <span class="fz-12 text-muted">{{ translate('Missing_reason') }}</span>
                    <h3 class="mb-0 mt-1">{{ $summary['missing_reason'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <p class="section-title mb-1">{{ $parentLabel }}</p>
            <p class="text-muted fz-12 mb-3">{{ $isInvalid ? translate('Invalid_reason_report_help') : translate('Future_customer_reason_report_help') }}</p>
            @include('leadmanagement::admin.reports.partials._customer-tab-charts-row', [
                'charts' => [
                    ['chartId' => 'typed-reason-chart', 'title' => $parentLabel],
                    ['chartId' => 'typed-area-chart', 'title' => translate('Area_Wise')],
                    ['chartId' => 'typed-source-chart', 'title' => translate('Source')],
                ],
            ])
        </div>
    </div>

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
                'title' => $parentLabel.' × '.translate('Area'),
                'subtitle' => translate('Reason_area_matrix_help'),
                'parentLabel' => $parentLabel,
                'childLabel' => translate('Area'),
                'rows' => $deep['reason_area_matrix'] ?? [],
            ])
            @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
                'title' => $parentLabel.' × '.translate('Source'),
                'subtitle' => translate('Reason_source_matrix_help'),
                'parentLabel' => $parentLabel,
                'childLabel' => translate('Source'),
                'rows' => $deep['reason_source_matrix'] ?? [],
            ])

            @if(!empty($deep['remarks']))
                <div class="mt-4">
                    <h5 class="fz-14 mb-2">{{ translate('Remarks') }}</h5>
                    <p class="text-muted fz-12 mb-2">{{ $isInvalid ? translate('Invalid_remarks_help') : translate('Future_customer_remarks_help') }}</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>{{ $parentLabel }}</th>
                                <th>{{ translate('Area') }}</th>
                                <th>{{ translate('Source') }}</th>
                                <th>{{ translate('Remarks') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($deep['remarks'] as $remark)
                                <tr>
                                    <td>{{ $remark['category'] ?? '—' }}</td>
                                    <td>{{ $remark['zone'] ?? '—' }}</td>
                                    <td>{{ $remark['reason'] ?? '—' }}</td>
                                    <td class="text-wrap" style="max-width: 280px;">{{ $remark['text'] ?? '' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('leadmanagement::admin.reports.partials.customer-leads-table', [
        'title' => $isInvalid ? translate('Invalid_Leads') : translate('Future_Customer_Lead'),
        'subtitle' => $isInvalid ? translate('Invalid_leads_table_help') : translate('Future_customer_leads_table_help'),
        'rows' => $a['rows'] ?? [],
        'columns' => ['id', 'name', 'phone', 'type_reason', 'status_remarks', 'area', 'handled_by', 'source', 'received_at'],
    ])
</div>
