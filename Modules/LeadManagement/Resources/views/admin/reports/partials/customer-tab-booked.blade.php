<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <p class="section-title mb-1 text-success">{{ translate('Booked') }}</p>
        <p class="text-muted fz-12 mb-3">{{ translate('Booked_breakdown_help') }}</p>
        @include('leadmanagement::admin.reports.partials._customer-tab-charts-row', [
            'charts' => [
                ['chartId' => 'customer-booked-category-chart', 'title' => translate('Category_Wise')],
                ['chartId' => 'customer-booked-zone-chart', 'title' => translate('Zone_Wise')],
                ['chartId' => 'customer-booked-subcategory-chart', 'title' => translate('Sub_Category')],
            ],
        ])
    </div>
</div>

@include('leadmanagement::admin.reports.partials.customer-leads-table', [
    'title' => translate('Booked_Leads'),
    'subtitle' => translate('Booked_leads_table_help'),
    'rows' => $a['leads_by_tab']['booked'] ?? [],
    'columns' => ['id', 'name', 'phone', 'category', 'zone', 'handled_by', 'source', 'received_at', 'followups', 'first_contact'],
])
