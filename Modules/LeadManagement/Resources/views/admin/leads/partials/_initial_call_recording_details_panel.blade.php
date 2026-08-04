@php
    $lead = $lead ?? null;
    $transcript = trim((string) ($lead->initial_call_recording_transcript ?? ''));
    $summary = trim((string) ($lead->initial_call_recording_summary ?? ''));
    $hasRecording = $lead && $lead->hasInitialCallRecording() && $lead->initial_call_recording_url;
    $transcriptLines = \Modules\LeadManagement\Entities\LeadFollowup::parseTranscriptLines($transcript);
    $transcribeUrl = $hasRecording && $lead
        ? route('admin.lead.initial-call-recording.transcribe', ['lead' => $lead->id])
        : '';
@endphp

<div class="voice-call-details-panel p-3"
     id="initial-call-transcript-panel-{{ $lead->id }}"
     data-lead-id="{{ $lead->id }}"
     data-transcribe-url="{{ $transcribeUrl }}">
    <div class="row g-2 mb-2 voice-call-details-top-row">
        <div class="col-lg-3 d-flex">
            <div class="voice-call-left-stack d-flex flex-column gap-2 flex-grow-1">
                <div class="card voice-call-detail-box shadow-sm voice-call-recording-card">
                    <div class="card-header voice-call-detail-box__header">
                        <div class="voice-call-detail-box__header-title">
                            <span class="material-icons" aria-hidden="true">graphic_eq</span>
                            <span>{{ translate('Recording') }}</span>
                        </div>
                    </div>
                    <div class="card-body voice-call-recording-box">
                        @if($hasRecording)
                            <audio controls
                                   preload="none"
                                   class="w-100 voice-call-audio-player"
                                   src="{{ $lead->initial_call_recording_url }}">
                                <source src="{{ $lead->initial_call_recording_url }}" type="{{ $lead->initial_call_recording_mime ?: 'audio/mpeg' }}">
                            </audio>
                        @else
                            <p class="text-muted mb-0 small">{{ translate('No_recording_available') }}</p>
                        @endif
                    </div>
                </div>

                <div class="card voice-call-detail-box shadow-sm voice-call-summary-card">
                    <div class="card-header voice-call-detail-box__header">
                        <div class="voice-call-detail-box__header-title">
                            <span class="material-icons" aria-hidden="true">summarize</span>
                            <span>{{ translate('Call_Summary') }}</span>
                        </div>
                        @if($summary !== '')
                            <button type="button"
                                    class="voice-call-copy-btn"
                                    title="{{ translate('Copy') }}"
                                    data-copy-b64="{{ base64_encode($summary) }}">
                                <span class="material-icons" aria-hidden="true">content_copy</span>
                            </button>
                        @endif
                    </div>
                    <div class="card-body voice-call-summary-body">
                        @if($summary !== '')
                            <p class="text-muted mb-0 small initial-call-recording-summary">{{ $summary }}</p>
                        @else
                            <p class="text-muted mb-0 small initial-call-recording-summary">{{ translate('No_call_summary_available') }}</p>
                            @if($hasRecording && $transcribeUrl !== '')
                                <button type="button"
                                        class="btn btn-sm btn--primary mt-2 js-transcribe-initial-call-recording"
                                        data-lead-id="{{ $lead->id }}"
                                        data-url="{{ $transcribeUrl }}"
                                        data-has-transcript="0">
                                    <span class="js-transcribe-btn-label">{{ translate('Transcribe_Recording') }}</span>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9 d-flex">
            <div class="card voice-call-detail-box shadow-sm flex-grow-1 voice-call-extracted-card initial-call-transcription-card">
                <div class="card-header voice-call-detail-box__header">
                    <div class="voice-call-detail-box__header-title">
                        <span class="material-icons" aria-hidden="true">auto_awesome</span>
                        <span>{{ translate('AI_Call_Summary') }}</span>
                    </div>
                    @if($lead->hasInitialCallTranscript() && $transcribeUrl !== '')
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary js-transcribe-initial-call-recording"
                                data-lead-id="{{ $lead->id }}"
                                data-url="{{ $transcribeUrl }}"
                                data-force="1"
                                data-has-transcript="1">
                            {{ translate('Regenerate') }}
                        </button>
                    @endif
                </div>
                <div class="card-body voice-call-extracted-body">
                    @if($summary !== '')
                        <p class="text-muted mb-0 small">{{ $summary }}</p>
                    @else
                        <p class="text-muted mb-0 small">{{ translate('Transcribe_to_generate_summary') }}</p>
                    @endif
                    @if($lead->initial_call_recording_transcribed_at)
                        <div class="initial-call-transcript-meta small text-muted mt-3 pt-2 border-top">
                            {{ translate('Transcribed_by') }} {{ translate('Google_Gemini_AI') }}
                            · {{ $lead->initial_call_recording_transcribed_at->format('d M Y, h:i A') }}
                        </div>
                    @else
                        <div class="initial-call-transcript-meta small text-muted mt-3 pt-2 border-top d-none"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card voice-call-detail-box shadow-sm">
        <div class="card-header voice-call-detail-box__header">
            <div class="voice-call-detail-box__header-title">
                <span class="material-icons" aria-hidden="true">forum</span>
                <span>{{ translate('Transcript') }}</span>
            </div>
            <div class="d-flex align-items-center gap-1">
                @if($transcript !== '')
                    <button type="button"
                            class="voice-call-copy-btn voice-call-transcript-copy-btn"
                            title="{{ translate('Copy') }}"
                            data-copy-b64="{{ base64_encode($transcript) }}">
                        <span class="material-icons" aria-hidden="true">content_copy</span>
                    </button>
                @elseif($hasRecording && $transcribeUrl !== '')
                    <button type="button"
                            class="btn btn-sm btn--primary js-transcribe-initial-call-recording"
                            data-lead-id="{{ $lead->id }}"
                            data-url="{{ $transcribeUrl }}"
                            data-has-transcript="0">
                        <span class="js-transcribe-btn-label">{{ translate('Transcribe_Recording') }}</span>
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if($transcript !== '')
                <div class="voice-call-transcript initial-call-recording-transcript-wrap">
                    @foreach($transcriptLines as $line)
                        @php
                            $trimmedLine = trim((string) $line);
                            $lineClass = \Modules\LeadManagement\Entities\LeadFollowup::transcriptLineClass($trimmedLine);
                        @endphp
                        @if($trimmedLine !== '')
                            <div class="voice-call-transcript-line {{ $lineClass }}">{{ $trimmedLine }}</div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="p-3">
                    <p class="text-muted mb-0 small initial-call-recording-transcript">{{ translate('No_transcript_available') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
