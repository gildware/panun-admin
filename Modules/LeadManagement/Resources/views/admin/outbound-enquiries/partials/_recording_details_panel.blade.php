@php
    $enquiry = $enquiry ?? null;
    $transcript = trim((string) ($enquiry->recording_transcript ?? ''));
    $summary = trim((string) ($enquiry->recording_summary ?? ''));
    $hasRecording = $enquiry && $enquiry->hasRecording() && $enquiry->recording_url;
    $transcriptLines = \Modules\LeadManagement\Entities\LeadFollowup::parseTranscriptLines($transcript);
    $transcribeUrl = $hasRecording
        ? route('admin.lead.outbound-enquiry.transcribe', $enquiry)
        : '';
@endphp

<div class="voice-call-details-panel p-3 outbound-enquiry-transcript-panel"
     id="outbound-enquiry-transcript-panel-{{ $enquiry->id }}"
     data-enquiry-id="{{ $enquiry->id }}"
     data-transcribe-url="{{ $transcribeUrl }}">
    <div class="row g-2 mb-2">
        <div class="col-lg-4">
            <div class="card voice-call-detail-box shadow-sm mb-2">
                <div class="card-header voice-call-detail-box__header">
                    <div class="voice-call-detail-box__header-title">
                        <span class="material-icons" aria-hidden="true">graphic_eq</span>
                        <span>{{ translate('Recording') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($hasRecording)
                        <audio controls preload="none" class="w-100 voice-call-audio-player" src="{{ $enquiry->recording_url }}">
                            <source src="{{ $enquiry->recording_url }}" type="{{ $enquiry->recording_mime ?: 'audio/mpeg' }}">
                        </audio>
                    @else
                        <p class="text-muted mb-0 small">{{ translate('No_recording_available') }}</p>
                    @endif
                </div>
            </div>
            <div class="card voice-call-detail-box shadow-sm">
                <div class="card-header voice-call-detail-box__header">
                    <div class="voice-call-detail-box__header-title">
                        <span class="material-icons" aria-hidden="true">summarize</span>
                        <span>{{ translate('Call_Summary') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($summary !== '')
                        <p class="text-muted mb-0 small outbound-enquiry-recording-summary">{{ $summary }}</p>
                    @else
                        <p class="text-muted mb-0 small outbound-enquiry-recording-summary">{{ translate('No_call_summary_available') }}</p>
                        @if($hasRecording && $transcribeUrl !== '')
                            <button type="button"
                                    class="btn btn-sm btn--primary mt-2 js-transcribe-outbound-enquiry-recording"
                                    data-enquiry-id="{{ $enquiry->id }}"
                                    data-url="{{ $transcribeUrl }}"
                                    data-has-transcript="0">
                                <span class="js-transcribe-btn-label">{{ translate('Transcribe_Recording') }}</span>
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card voice-call-detail-box shadow-sm outbound-enquiry-transcription-card">
                <div class="card-header voice-call-detail-box__header">
                    <div class="voice-call-detail-box__header-title">
                        <span class="material-icons" aria-hidden="true">forum</span>
                        <span>{{ translate('Transcript') }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        @if($enquiry->hasTranscript() && $transcribeUrl !== '')
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary js-transcribe-outbound-enquiry-recording"
                                    data-enquiry-id="{{ $enquiry->id }}"
                                    data-url="{{ $transcribeUrl }}"
                                    data-force="1"
                                    data-has-transcript="1">
                                {{ translate('Regenerate') }}
                            </button>
                        @elseif($hasRecording && $transcribeUrl !== '')
                            <button type="button"
                                    class="btn btn-sm btn--primary js-transcribe-outbound-enquiry-recording"
                                    data-enquiry-id="{{ $enquiry->id }}"
                                    data-url="{{ $transcribeUrl }}"
                                    data-has-transcript="0">
                                <span class="js-transcribe-btn-label">{{ translate('Transcribe_Recording') }}</span>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($transcript !== '')
                        <div class="voice-call-transcript outbound-enquiry-recording-transcript-wrap p-3">
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
                            <p class="text-muted mb-0 small outbound-enquiry-recording-transcript">{{ translate('No_transcript_available') }}</p>
                        </div>
                    @endif
                    @if($enquiry->transcribed_at)
                        <div class="outbound-enquiry-transcript-meta small text-muted px-3 pb-3">
                            {{ translate('Transcribed_by') }} {{ translate('Google_Gemini_AI') }}
                            · {{ $enquiry->transcribed_at->format('d M Y, h:i A') }}
                        </div>
                    @else
                        <div class="outbound-enquiry-transcript-meta small text-muted px-3 pb-3 d-none"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
