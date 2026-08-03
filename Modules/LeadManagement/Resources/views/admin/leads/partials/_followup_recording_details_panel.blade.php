@php
    $followup = $followup ?? null;
    $lead = $lead ?? null;
    $transcript = trim((string) ($followup->recording_transcript ?? ''));
    $summary = trim((string) ($followup->recording_summary ?? ''));
    $hasRecording = $followup && $followup->hasRecording() && $followup->recording_url;
    $transcriptLines = \Modules\LeadManagement\Entities\LeadFollowup::parseTranscriptLines($transcript);
    $transcribeUrl = $hasRecording && $lead
        ? route('admin.lead.followups.transcribe', ['lead' => $lead->id, 'followup' => $followup->id])
        : '';
@endphp

<div class="voice-call-details-panel p-3"
     id="followup-transcript-panel-{{ $followup->id }}"
     data-followup-id="{{ $followup->id }}"
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
                                   src="{{ $followup->recording_url }}">
                                <source src="{{ $followup->recording_url }}" type="{{ $followup->recording_mime ?: 'audio/mpeg' }}">
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
                            <p class="text-muted mb-0 small followup-recording-summary">{{ $summary }}</p>
                        @else
                            <p class="text-muted mb-0 small followup-recording-summary">{{ translate('No_call_summary_available') }}</p>
                            @if($hasRecording && $transcribeUrl !== '')
                                <button type="button"
                                        class="btn btn-sm btn--primary mt-2 js-transcribe-followup-recording"
                                        data-followup-id="{{ $followup->id }}"
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
            <div class="card voice-call-detail-box shadow-sm flex-grow-1 voice-call-extracted-card">
                <div class="card-header voice-call-detail-box__header">
                    <div class="voice-call-detail-box__header-title">
                        <span class="material-icons" aria-hidden="true">info</span>
                        <span>{{ translate('Follow_up_Details') }}</span>
                    </div>
                    @if($followup->hasTranscript() && $transcribeUrl !== '')
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary js-transcribe-followup-recording"
                                data-followup-id="{{ $followup->id }}"
                                data-url="{{ $transcribeUrl }}"
                                data-force="1"
                                data-has-transcript="1">
                            {{ translate('Regenerate') }}
                        </button>
                    @endif
                </div>
                <div class="card-body voice-call-extracted-body">
                    <div class="voice-call-extracted-grid is-show-all">
                        <div class="voice-call-extracted-item">
                            <div class="voice-call-extracted-item__label">{{ translate('Follow_up_Taken_on') }}</div>
                            <div class="voice-call-extracted-item__value">{{ $followup->contactChannelLabel() ?? '—' }}</div>
                        </div>
                        <div class="voice-call-extracted-item">
                            <div class="voice-call-extracted-item__label">{{ translate('Date_Time') }}</div>
                            <div class="voice-call-extracted-item__value">
                                {{ $followup->followup_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>
                        </div>
                        <div class="voice-call-extracted-item">
                            <div class="voice-call-extracted-item__label">{{ translate('Status') }}</div>
                            <div class="voice-call-extracted-item__value">{{ $followup->followupStatusLabel() }}</div>
                        </div>
                        <div class="voice-call-extracted-item">
                            <div class="voice-call-extracted-item__label">{{ translate('Urgency') }}</div>
                            <div class="voice-call-extracted-item__value">{{ translate(ucfirst($followup->urgency ?: 'medium')) }}</div>
                        </div>
                        <div class="voice-call-extracted-item" style="grid-column: 1 / -1;">
                            <div class="voice-call-extracted-item__label">{{ translate('Remarks') }}</div>
                            <div class="voice-call-extracted-item__value">{{ $followup->remarks ?: '—' }}</div>
                        </div>
                    </div>
                    @if($followup->transcribed_at)
                        <div class="followup-transcript-meta small text-muted mt-3 pt-2 border-top">
                            {{ translate('Transcribed_by') }} {{ translate('Google_Gemini_AI') }}
                            · {{ $followup->transcribed_at->format('d M Y, h:i A') }}
                        </div>
                    @else
                        <div class="followup-transcript-meta small text-muted mt-3 pt-2 border-top d-none"></div>
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
                            class="btn btn-sm btn--primary js-transcribe-followup-recording"
                            data-followup-id="{{ $followup->id }}"
                            data-url="{{ $transcribeUrl }}"
                            data-has-transcript="0">
                        <span class="js-transcribe-btn-label">{{ translate('Transcribe_Recording') }}</span>
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if($transcript !== '')
                <div class="voice-call-transcript followup-recording-transcript-wrap">
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
                    <p class="text-muted mb-0 small followup-recording-transcript">{{ translate('No_transcript_available') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
