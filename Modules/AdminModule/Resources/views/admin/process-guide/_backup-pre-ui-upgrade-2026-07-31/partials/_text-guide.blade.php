@php
    use Modules\AdminModule\Support\LeadQualificationTextGuide;
    $sections = LeadQualificationTextGuide::sections();
@endphp

<div class="pg-text-guide" id="pg-text-guide">
    <div class="pg-text-guide-header">
        <h3>{{ $title ?? 'Lead Qualification Flow' }}</h3>
        <p class="pg-flow-sub">Step-by-step text guide — same process as the flowchart, written for daily use on calls and in the panel.</p>
    </div>

    <nav class="pg-text-toc" aria-label="Guide sections">
        @foreach ($sections as $i => $section)
            <a href="#pg-section-{{ $i }}" class="pg-text-toc-link">{{ $section['title'] }}</a>
        @endforeach
    </nav>

    <div class="pg-text-body">
        @foreach ($sections as $i => $section)
            <section class="pg-text-section" id="pg-section-{{ $i }}">
                <h4 class="pg-text-section-title">{{ $section['title'] }}</h4>
                @if (!empty($section['intro']))
                    <p class="pg-text-intro">{{ $section['intro'] }}</p>
                @endif

                @include('adminmodule::admin.process-guide.partials._text-guide-steps', [
                    'steps' => $section['steps'],
                    'prefix' => (string) ($i + 1),
                ])
            </section>
        @endforeach
    </div>
</div>
