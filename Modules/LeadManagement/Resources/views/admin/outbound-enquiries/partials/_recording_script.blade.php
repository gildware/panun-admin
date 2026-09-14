@push('script')
    <script>
        "use strict";
        (function ($) {
            function transcriptLineClass(line) {
                if (/^user\s*:/i.test(line) || /^customer\s*:/i.test(line)) {
                    return 'voice-call-transcript-line--user';
                }
                if (/^support\s*:/i.test(line) || /^agent\s*:/i.test(line)) {
                    return 'voice-call-transcript-line--llm';
                }
                return '';
            }

            function buildTranscriptHtml(transcript) {
                return String(transcript || '').split(/\n+/).map(function (line) {
                    line = String(line || '').trim();
                    if (!line) {
                        return '';
                    }
                    return '<div class="voice-call-transcript-line ' + transcriptLineClass(line) + '">' + $('<div>').text(line).html() + '</div>';
                }).join('');
            }

            function formatTranscribedAt(value) {
                if (!value) {
                    return '';
                }
                var date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return value;
                }
                return date.toLocaleString();
            }

            $(document).on('click', '.outbound-enquiry-recording-toggle', function () {
                var $btn = $(this);
                var target = $btn.data('target');
                var $inline = $(target);
                if (!$inline.length) {
                    return;
                }
                var isHidden = $inline.hasClass('d-none');
                $inline.toggleClass('d-none', !isHidden);
                $btn.text(isHidden ? @json(translate('Hide')) : @json(translate('View')));
            });

            $(document).on('click', '.js-transcribe-outbound-enquiry-recording', function () {
                var $btn = $(this);
                var enquiryId = $btn.data('enquiry-id');
                var url = $btn.data('url');
                var force = String($btn.data('force')) === '1';
                var $panel = $('#outbound-enquiry-transcript-panel-' + enquiryId);
                var csrfToken = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
                var originalHtml = $btn.html();

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + @json(translate('Transcribing')));

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { _token: csrfToken, force: force ? '1' : '0' }
                }).done(function (response) {
                    if (!response || !response.success) {
                        toastr.error((response && response.message) ? response.message : @json(translate('Failed_to_transcribe_recording')));
                        return;
                    }

                    $panel.find('.outbound-enquiry-recording-summary').text(response.summary || @json(translate('No_call_summary_available')));
                    $panel.find('.js-transcribe-outbound-enquiry-recording[data-has-transcript="0"]').remove();

                    var transcriptHtml = buildTranscriptHtml(response.transcript || '');
                    $panel.find('.outbound-enquiry-transcription-card .card-body').html(
                        '<div class="voice-call-transcript outbound-enquiry-recording-transcript-wrap p-3">' + transcriptHtml + '</div>' +
                        '<div class="outbound-enquiry-transcript-meta small text-muted px-3 pb-3">' +
                        @json(translate('Transcribed_by')) + ' ' + @json(translate('Google_Gemini_AI')) +
                        (response.transcribed_at ? ' · ' + formatTranscribedAt(response.transcribed_at) : '') +
                        '</div>'
                    );

                    if (!$panel.find('.js-transcribe-outbound-enquiry-recording[data-force="1"]').length && response.transcript && url) {
                        var $regenerateBtn = $('<button type="button" class="btn btn-sm btn-outline-secondary js-transcribe-outbound-enquiry-recording"></button>')
                            .attr('data-enquiry-id', enquiryId)
                            .attr('data-url', url)
                            .attr('data-force', '1')
                            .attr('data-has-transcript', '1')
                            .text(@json(translate('Regenerate')));
                        $panel.find('.outbound-enquiry-transcription-card .voice-call-detail-box__header .d-flex').append($regenerateBtn);
                    }

                    if (force || !response.from_cache) {
                        toastr.success(response.message || @json(translate('Recording_transcribed_successfully')));
                    }
                }).fail(function (xhr) {
                    var message = @json(translate('Failed_to_transcribe_recording'));
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    toastr.error(message);
                }).always(function () {
                    $btn.prop('disabled', false).html(originalHtml);
                });
            });
        })(jQuery);
    </script>
@endpush
