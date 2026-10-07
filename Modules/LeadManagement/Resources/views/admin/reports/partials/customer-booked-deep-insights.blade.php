@php
    $deep = $a['booked_deep'] ?? [];
@endphp

<div class="card mb-3 border-0 shadow-sm border-start border-4 border-success">
    <div class="card-body">
        <h4 class="mb-1 text-success d-flex align-items-center gap-2">
            <span class="material-icons">analytics</span>
            {{ translate('Booked_Deep_Analysis') }}
        </h4>
        <p class="text-muted fz-12 mb-3">{{ translate('Booked_deep_analysis_help') }}</p>

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => translate('Category_x_Service'),
            'subtitle' => translate('Category_service_matrix_help'),
            'parentLabel' => translate('Category'),
            'childLabel' => translate('Service'),
            'rows' => $deep['category_service_matrix'] ?? [],
        ])

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => translate('Category_x_Area'),
            'subtitle' => translate('Category_area_matrix_help'),
            'parentLabel' => translate('Category'),
            'childLabel' => translate('Area'),
            'rows' => $deep['category_area_matrix'] ?? [],
        ])

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => translate('Zone_x_Area'),
            'subtitle' => translate('Zone_area_matrix_help'),
            'parentLabel' => translate('Zone'),
            'childLabel' => translate('Area'),
            'rows' => $deep['zone_area_matrix'] ?? [],
        ])

        @include('leadmanagement::admin.reports.partials._nested-matrix-table', [
            'title' => translate('Service_x_Area'),
            'subtitle' => translate('Service_area_matrix_help'),
            'parentLabel' => translate('Service'),
            'childLabel' => translate('Area'),
            'rows' => $deep['service_area_matrix'] ?? [],
        ])
    </div>
</div>
