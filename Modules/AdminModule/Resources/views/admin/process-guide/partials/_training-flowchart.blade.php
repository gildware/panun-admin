@php
    $flowchartsClass = $flowchartsClass ?? \Modules\AdminModule\Support\LeadQualificationTrainingFlowcharts::class;
    $nodes = is_string($flowchartKey ?? null)
        ? $flowchartsClass::get($flowchartKey)
        : ($flowchart ?? null);
@endphp

@if (!empty($nodes))
    <div class="pg-training-flowchart" aria-label="Flow diagram">
        @foreach ($nodes as $node)
            @if (($node['kind'] ?? '') === 'fork')
                <div class="pg-tf-fork">
                    @foreach ($node['branches'] ?? [] as $branch)
                        <div class="pg-tf-branch pg-tf-branch--{{ $branch['tone'] ?? 'neutral' }}">
                            <span class="pg-tf-branch-label">{{ $branch['label'] }}</span>
                            @if (!empty($branch['to']))
                                <span class="pg-tf-branch-to">{{ $branch['to'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="pg-tf-node pg-tf-node--{{ $node['kind'] ?? 'action' }} pg-tf-node--{{ $node['tone'] ?? 'default' }}">
                    {{ $node['label'] ?? '' }}
                </div>
            @endif
            @if (!in_array($node['kind'] ?? '', ['fork', 'end'], true) && !$loop->last)
                <div class="pg-tf-arrow" aria-hidden="true">↓</div>
            @endif
        @endforeach
    </div>
@endif
