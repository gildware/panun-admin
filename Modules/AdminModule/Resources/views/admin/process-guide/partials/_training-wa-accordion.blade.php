@if (!empty($msg))
    <details class="pg-training-wa-accordion">
        <summary class="pg-training-wa-accordion-summary">
            @if (!empty($msg['mandatory']))
                <span class="pg-training-wa-badge">Mandatory</span>
            @endif
            <span class="pg-training-wa-summary-label">{{ $msg['label'] ?? 'WhatsApp' }}</span>
            <span class="pg-training-wa-summary-hint">Show format &amp; example</span>
        </summary>
        <div class="pg-training-wa-accordion-body">
            @if (!empty($msg['template']))
                <div class="pg-training-msg-block">
                    <span class="pg-training-msg-tag">General format</span>
                    <pre class="pg-training-msg-pre">{{ $msg['template'] }}</pre>
                </div>
            @endif
            @if (!empty($msg['example']))
                <div class="pg-training-msg-block pg-training-msg-block--example">
                    <span class="pg-training-msg-tag">Example</span>
                    <pre class="pg-training-msg-pre">{{ $msg['example'] }}</pre>
                </div>
            @endif
        </div>
    </details>
@endif
