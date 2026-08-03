<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <p class="section-title mb-1 text-danger">{{ translate('Cancelled') }}</p>
        <p class="text-muted fz-12 mb-3">{{ translate('Cancelled_breakdown_help') }}</p>
        @include('leadmanagement::admin.reports.partials._customer-tab-charts-row', [
            'charts' => [
                ['chartId' => 'customer-cancelled-category-chart', 'title' => translate('Category_Wise')],
                ['chartId' => 'customer-cancelled-zone-chart', 'title' => translate('Zone_Wise')],
                ['chartId' => 'customer-cancel-reason-chart', 'title' => translate('Cancellation_Reasons')],
            ],
        ])
    </div>
</div>

@include('leadmanagement::admin.reports.partials.customer-deep-insights', ['a' => $a])

@include('leadmanagement::admin.reports.partials.customer-leads-table', [
    'title' => translate('All_Cancelled_Leads'),
    'subtitle' => translate('Cancelled_leads_table_help'),
    'rows' => $a['leads_by_tab']['cancelled'] ?? [],
    'columns' => ['id', 'name', 'phone', 'category', 'zone', 'reason', 'remarks', 'handled_by', 'source', 'received_at', 'followups', 'first_contact', 'no_response'],
])
