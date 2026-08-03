<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <p class="section-title mb-1 text-warning">{{ translate('Pending') }}</p>
        <p class="text-muted fz-12 mb-3">{{ translate('Pending_leads_tab_help') }}</p>
        @include('leadmanagement::admin.reports.partials._customer-tab-charts-row', [
            'charts' => [
                ['chartId' => 'customer-pending-category-chart', 'title' => translate('Category_Wise')],
                ['chartId' => 'customer-pending-zone-chart', 'title' => translate('Zone_Wise')],
                ['chartId' => 'customer-pending-subcategory-chart', 'title' => translate('Sub_Category')],
            ],
        ])
    </div>
</div>

@include('leadmanagement::admin.reports.partials.customer-leads-table', [
    'title' => translate('Pending_Leads'),
    'subtitle' => translate('Pending_leads_table_help'),
    'rows' => $a['leads_by_tab']['pending'] ?? [],
    'columns' => ['id', 'name', 'phone', 'category', 'zone', 'handled_by', 'source', 'received_at', 'next_followup', 'followups', 'first_contact'],
])
