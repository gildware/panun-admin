@php
    $call = is_array($call ?? null) ? $call : [];
    $dispatchContext = is_array($dispatchContext ?? null) ? $dispatchContext : [];
    $extracted = is_array($call['extracted_variables'] ?? null) ? $call['extracted_variables'] : [];
    if ($extracted !== []) {
        $extracted = collect($extracted)->sortBy(function ($value, $key) {
            $text = trim((string) $value);
            $isFilled = $text !== ''
                && !in_array(strtolower($text), ['—', '-', 'n/a', 'na', 'none', 'null'], true);

            return [$isFilled ? 0 : 1, strtolower((string) $key)];
        })->all();
    }
    $transcript = trim((string) ($call['transcript'] ?? ''));
    $storedTransliteration = trim((string) ($call['transcript_transliterated'] ?? ''));
    $summary = trim((string) ($call['sentiment_analysis_details'] ?? ''));
    $hasRecording = !empty($call['recording_url']) && (int) ($call['id'] ?? 0) > 0;
    $callLogId = (int) ($call['id'] ?? 0);
    $extractedIsFilled = function ($value): bool {
        $text = trim((string) $value);
        if ($text === '') {
            return false;
        }

        return !in_array(strtolower($text), ['—', '-', 'n/a', 'na', 'none', 'null'], true);
    };
    $extractedCopyLines = [];
    $hasEmptyExtracted = false;
    foreach ($extracted as $varKey => $varValue) {
        if ($extractedIsFilled($varValue)) {
            $extractedCopyLines[] = str_replace('_', ' ', ucfirst($varKey)) . ': ' . $varValue;
        } else {
            $hasEmptyExtracted = true;
        }
    }
    $extractedCopyText = implode("\n", $extractedCopyLines);
    $transcriptLines = $transcript !== '' ? preg_split('/\r\n|\r|\n/', $transcript) : [];
    $transcriptHasDevanagari = $transcript !== '' && preg_match('/[\x{0900}-\x{097F}]/u', $transcript) === 1;
@endphp
<div class="voice-call-details-panel p-3">
    @if($dispatchContext !== [])
        <div class="voice-call-dispatch-chips">
            @foreach($dispatchContext as $varKey => $varValue)
                <div class="voice-call-dispatch-chip">
                    <span class="voice-call-dispatch-chip__label">
                        @if($varKey === 'call_reason')
                            {{ translate('Call_Reason') }}
                        @else
                            {{ str_replace('_', ' ', ucfirst($varKey)) }}
                        @endif
                    </span>
                    <span class="voice-call-dispatch-chip__value">
                        @if($varKey === 'call_reason')
                            @include('leadmanagement::admin.voice-calls._call_reason_badge', [
                                'reason' => $varValue,
                                'callReasonLabels' => $callReasonLabels ?? [],
                            ])
                        @else
                            {{ $varValue }}
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif

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
                                   data-play-url="{{ route('admin.voice-call.recording', $callLogId) }}"></audio>
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
                            <p class="text-muted mb-0 small">{{ $summary }}</p>
                        @else
                            <p class="text-muted mb-0 small">{{ translate('No_call_summary_available') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9 d-flex">
            <div class="card voice-call-detail-box shadow-sm flex-grow-1 voice-call-extracted-card">
                <div class="card-header voice-call-detail-box__header">
                    <div class="voice-call-detail-box__header-title">
                        <span class="material-icons" aria-hidden="true">data_object</span>
                        <span>{{ translate('Extracted_Data') }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        @if($hasEmptyExtracted)
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary voice-call-extracted-view-all">
                                {{ translate('view_all') }}
                            </button>
                        @endif
                        @if($extractedCopyText !== '')
                            <button type="button"
                                    class="voice-call-copy-btn"
                                    title="{{ translate('Copy') }}"
                                    data-copy-b64="{{ base64_encode($extractedCopyText) }}">
                                <span class="material-icons" aria-hidden="true">content_copy</span>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body voice-call-extracted-body">
                    @if($extracted !== [])
                        <div class="voice-call-extracted-grid">
                            @foreach($extracted as $varKey => $varValue)
                                @php $isFilledExtracted = $extractedIsFilled($varValue); @endphp
                                <div class="voice-call-extracted-item {{ $isFilledExtracted ? '' : 'voice-call-extracted-item--empty' }}">
                                    <div class="voice-call-extracted-item__label">
                                        {{ str_replace('_', ' ', ucfirst($varKey)) }}
                                    </div>
                                    <div class="voice-call-extracted-item__value">
                                        {{ $isFilledExtracted ? $varValue : '—' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0 small">{{ translate('No_extracted_data_available') }}</p>
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
                @if($transcriptHasDevanagari && $callLogId > 0)
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary voice-call-transcript-hinglish-toggle"
                            data-call-id="{{ $callLogId }}"
                            data-original-b64="{{ base64_encode($transcript) }}"
                            data-showing="original">
                        {{ translate('Show_Hinglish') }}
                    </button>
                @endif
                @if($transcript !== '')
                    <button type="button"
                            class="voice-call-copy-btn voice-call-transcript-copy-btn"
                            title="{{ translate('Copy') }}"
                            data-copy-b64="{{ base64_encode($transcript) }}">
                        <span class="material-icons" aria-hidden="true">content_copy</span>
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if($transcript !== '')
                <div class="voice-call-transcript"
                     @if($callLogId > 0) data-call-id="{{ $callLogId }}" @endif
                     data-original-b64="{{ base64_encode($transcript) }}"
                     @if($storedTransliteration !== '')
                         data-transliterated-b64="{{ base64_encode($storedTransliteration) }}"
                     @endif>
                    @foreach($transcriptLines as $line)
                        @php
                            $trimmedLine = trim((string) $line);
                            $lineClass = '';
                            if (stripos($trimmedLine, 'User:') === 0) {
                                $lineClass = 'voice-call-transcript-line--user';
                            } elseif (stripos($trimmedLine, 'LLM:') === 0) {
                                $lineClass = 'voice-call-transcript-line--llm';
                            }
                        @endphp
                        @if($trimmedLine !== '')
                            <div class="voice-call-transcript-line {{ $lineClass }}">{{ $trimmedLine }}</div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="p-3">
                    <p class="text-muted mb-0 small">{{ translate('No_transcript_available') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
