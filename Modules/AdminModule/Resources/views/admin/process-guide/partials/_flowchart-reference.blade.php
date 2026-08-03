@php
    $flowchartsClass = $flowchartsClass ?? \Modules\AdminModule\Support\BookingFollowupTrainingFlowcharts::class;
    $charts = method_exists($flowchartsClass, 'referenceCharts')
        ? $flowchartsClass::referenceCharts()
        : [];
@endphp

<div class="pg-text-guide pg-flowchart-reference" id="pg-flowchart-reference">
    <div class="pg-text-guide-header">
        <h3>{{ $title ?? 'Process flow' }}</h3>
        <p class="pg-flow-sub">Reference flowcharts — same diagrams used in the training slides.</p>
    </div>

    <nav class="pg-text-toc" aria-label="Flowchart sections">
        @foreach ($charts as $i => $chart)
            <a href="#pg-flowchart-ref-{{ $i }}" class="pg-text-toc-link">{{ $chart['title'] }}</a>
        @endforeach
    </nav>

    <div class="pg-text-body">
        @foreach ($charts as $i => $chart)
            <section class="pg-text-section" id="pg-flowchart-ref-{{ $i }}">
                <h4 class="pg-text-section-title">{{ $chart['title'] }}</h4>
                @include('adminmodule::admin.process-guide.partials._training-flowchart', [
                    'flowchartKey' => $chart['key'],
                    'flowchartsClass' => $flowchartsClass,
                ])
            </section>
        @endforeach
    </div>
</div>
