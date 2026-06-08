@php
    $dispatchUrl = route('admin.voice-call.cron-jobs.runs.dispatch', $run);
@endphp

<form method="POST" action="{{ $dispatchUrl }}" id="voice-cron-dispatch-form">
    @csrf
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="text-muted small mb-0">
            {{ translate('Voice_cron_dispatch_modal_hint') }}
        </p>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="voice-cron-dispatch-select-all">
                {{ translate('Select_All') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="voice-cron-dispatch-select-none">
                {{ translate('Deselect_All') }}
            </button>
        </div>
    </div>

    <div class="voice-cron-dispatch-list" style="max-height:60vh; overflow-y:auto;">
        @include('leadmanagement::admin.voice-calls._voice_cron_candidates_compact_list', [
            'candidates' => $candidates,
            'listId' => 'dispatch-' . $run->id,
            'showCheckboxes' => true,
            'callReasonLabels' => $callReasonLabels ?? [],
            'contextKeys' => $contextKeys ?? [],
        ])
    </div>

    @if(!$candidates->isEmpty())
        <p class="small text-muted mt-2 mb-0" id="voice-cron-dispatch-selected-count">
            {{ translate('Selected') }}: <span class="voice-cron-dispatch-count-num">{{ $candidates->count() }}</span> / {{ $candidates->count() }}
        </p>
    @endif
</form>
