<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <p class="section-title mb-1 text-info">{{ translate('Hold') }}</p>
        <p class="text-muted fz-12 mb-3">{{ translate('Hold_leads_tab_help') }}</p>
        @include('leadmanagement::admin.reports.partials._customer-tab-charts-row', [
            'charts' => [
                ['chartId' => 'customer-hold-category-chart', 'title' => translate('Category_Wise')],
                ['chartId' => 'customer-hold-zone-chart', 'title' => translate('Zone_Wise')],
                ['chartId' => 'customer-hold-subcategory-chart', 'title' => translate('Sub_Category')],
            ],
        ])
    </div>
</div>

@include('leadmanagement::admin.reports.partials.customer-leads-table', [
    'title' => translate('Hold_Leads'),
    'subtitle' => translate('Hold_leads_table_help'),
    'rows' => $a['leads_by_tab']['hold'] ?? [],
    'columns' => ['id', 'name', 'phone', 'category', 'zone', 'handled_by', 'source', 'received_at', 'next_followup', 'followups'],
])
