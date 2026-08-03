@php
    /** @var array<int, array> $steps */
    /** @var string $prefix */
@endphp

<ol class="pg-text-steps">
    @foreach ($steps as $j => $step)
        <li class="pg-text-step">
            <div class="pg-text-step-title">{{ $step['title'] }}</div>

            @if (!empty($step['body']))
                <p class="pg-text-step-body">{{ $step['body'] }}</p>
            @endif

            @if (!empty($step['items']))
                <ul class="pg-text-step-list">
                    @foreach ($step['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif

            @if (!empty($step['branches']))
                <div class="pg-text-branches">
                    @foreach ($step['branches'] as $branch)
                        <div class="pg-text-branch">
                            <div class="pg-text-branch-label">{{ $branch['label'] }}</div>
                            @include('adminmodule::admin.process-guide.partials._text-guide-steps', [
                                'steps' => $branch['steps'],
                                'prefix' => $prefix . '.' . ($j + 1),
                            ])
                        </div>
                    @endforeach
                </div>
            @endif
        </li>
    @endforeach
</ol>
