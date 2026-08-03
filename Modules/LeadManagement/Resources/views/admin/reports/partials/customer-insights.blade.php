@php
    $a = $analytics ?? [];
    $summary = $a['summary'] ?? [];
    $tabCounts = $a['tab_counts'] ?? [];
    $activeTab = $customerStatusTab ?? 'overview';
    $statusTabs = [
        'overview' => ['label' => translate('Overview'), 'count' => $summary['total'] ?? 0, 'class' => ''],
        'booked' => ['label' => translate('Booked'), 'count' => $tabCounts['booked'] ?? 0, 'class' => 'text-success'],
        'cancelled' => ['label' => translate('Cancelled'), 'count' => $tabCounts['cancelled'] ?? 0, 'class' => 'text-danger'],
        'hold' => ['label' => translate('Hold'), 'count' => $tabCounts['hold'] ?? 0, 'class' => 'text-info'],
        'pending' => ['label' => translate('Pending'), 'count' => $tabCounts['pending'] ?? 0, 'class' => 'text-warning'],
    ];
@endphp

@push('css_or_js')
    <style>
        .customer-lead-analytics .customer-report-chart-card { background: #fafbfc; }
        .customer-lead-analytics .customer-donut-chart .apexcharts-legend {
            padding-top: 4px !important; overflow-y: auto !important; overflow-x: hidden; align-content: flex-start;
        }
        .customer-lead-analytics .customer-donut-chart .apexcharts-legend.apexcharts-align-left {
            flex-direction: column !important; flex-wrap: nowrap !important;
            justify-content: flex-start !important; align-items: flex-start !important;
        }
        .customer-lead-analytics .customer-donut-chart .apexcharts-legend-text { font-size: 11px !important; }
        .customer-lead-analytics .section-title { font-size: 1rem; font-weight: 600; }
        .customer-lead-analytics .chart-empty-msg {
            display: flex; align-items: center; justify-content: center; min-height: 180px;
            color: #6c757d; font-size: 12px; text-align: center; padding: 1rem;
        }
        .customer-status-tab-link .badge { font-size: 11px; }
        .customer-lead-analytics .customer-breakdown-charts .customer-report-chart-card {
            min-height: 100%;
        }
        .customer-lead-analytics .customer-breakdown-charts .customer-donut-chart .apexcharts-legend {
            max-height: 220px;
        }
        @media (min-width: 992px) {
            .customer-lead-analytics .customer-breakdown-charts > [class*="col-lg-4"] {
                display: flex;
            }
            .customer-lead-analytics .customer-breakdown-charts > [class*="col-lg-4"] > .card {
                width: 100%;
            }
        }
    </style>
@endpush

<div class="customer-lead-analytics mb-4">
    <ul class="nav nav--tabs mb-3 flex-wrap">
        @foreach($statusTabs as $tabKey => $tabMeta)
            <li class="nav-item">
                <a class="nav-link customer-status-tab-link {{ $activeTab === $tabKey ? 'active' : '' }} {{ $tabMeta['class'] }}"
                   href="{{ route('admin.lead.reports.inbound', array_merge($queryParams ?? ['inbound_report' => 'customer'], ['inbound_report' => 'customer', 'customer_status_tab' => $tabKey])) }}">
                    {{ $tabMeta['label'] }}
                    <span class="badge bg-light text-dark border ms-1">{{ $tabMeta['count'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    @if($activeTab === 'overview')
        @include('leadmanagement::admin.reports.partials.customer-tab-overview', ['a' => $a])
    @elseif($activeTab === 'booked')
        @include('leadmanagement::admin.reports.partials.customer-tab-booked', ['a' => $a])
    @elseif($activeTab === 'cancelled')
        @include('leadmanagement::admin.reports.partials.customer-tab-cancelled', ['a' => $a])
    @elseif($activeTab === 'hold')
        @include('leadmanagement::admin.reports.partials.customer-tab-hold', ['a' => $a])
    @elseif($activeTab === 'pending')
        @include('leadmanagement::admin.reports.partials.customer-tab-pending', ['a' => $a])
    @endif
</div>
