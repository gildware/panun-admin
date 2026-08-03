<div class="pg-training-follow-step-body">
    <span class="pg-training-follow-step-text">@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $step['text'] ?? ''])</span>
    @if (!empty($step['detail']))
        <span class="pg-training-follow-step-detail">@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $step['detail']])</span>
    @endif
    @if (!empty($step['collect']))
        <span class="pg-training-follow-step-extra pg-training-follow-step-extra--collect">
            <span class="pg-training-follow-step-extra-label">
                <span class="material-icons" aria-hidden="true">fact_check</span>
                Details to get
            </span>
            {{ $step['collect'] }}
        </span>
    @endif
    @if (!empty($step['example']))
        <span class="pg-training-follow-step-extra pg-training-follow-step-extra--example">
            <span class="pg-training-follow-step-extra-label">
                <span class="material-icons" aria-hidden="true">format_quote</span>
                Example
            </span>
            {{ $step['example'] }}
        </span>
    @endif
    @if (!empty($step['next']))
        <span class="pg-training-follow-step-extra pg-training-follow-step-extra--next">
            <span class="pg-training-follow-step-extra-label">
                <span class="material-icons" aria-hidden="true">arrow_forward</span>
                What next
            </span>
            {{ $step['next'] }}
        </span>
    @endif
</div>
