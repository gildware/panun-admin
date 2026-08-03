@if(!empty($pipelineSteps) && count($pipelineSteps) > 1)
    <section class="pipeline" aria-label="{{ translate('Status') }}">
        <div class="pipeline__track">
            @foreach($pipelineSteps as $index => $step)
                @if($index > 0)
                    <div class="pipeline__line {{ $index <= $pipelineCurrentIndex ? 'is-done' : '' }}"></div>
                @endif
                <div class="pipeline__step {{ $index < $pipelineCurrentIndex ? 'is-done' : ($index === $pipelineCurrentIndex ? 'is-current' : '') }}">
                    <span class="pipeline__dot"></span>
                    {{ $step['label'] }}
                </div>
            @endforeach
        </div>
    </section>
@endif
