@push('script')
<script>
    (function ($) {
        'use strict';

        var providerSearchUrl = @json(route('admin.lead.search-providers'));

        $(function () {
            var $callLogModal = $('#addCallLogModal');
            if (!$callLogModal.length) {
                return;
            }

            var $callLogForm = $('#add-call-log-form');
            var $callLogProviderSelect = $('#call-log-provider-select');
            var $callLogProviderPreview = $('#call-log-provider-preview');
            var $callLogCurrentRecording = $('#call-log-current-recording');
            var callLogProviderData = {};
            var callLogLabels = {
                addTitle: @json(translate('Add_Call_Log')),
                editTitle: @json(translate('Edit_Call_Log')),
                addSubmit: @json(translate('Add_Call_Log')),
                editSubmit: @json(translate('Update')),
                currentRecording: @json(translate('Current_recording')),
                replaceRecording: @json(translate('Upload_new_recording_to_replace')),
            };

            function destroyCallLogProviderSelect2() {
                if ($callLogProviderSelect.length && $callLogProviderSelect.data('select2')) {
                    $callLogProviderSelect.select2('destroy');
                }
            }

            function updateCallLogProviderPreview() {
                var selectedId = $callLogProviderSelect.val();
                var selected = selectedId ? callLogProviderData[String(selectedId)] : null;

                if (!selected || (!selected.name && !selected.phone)) {
                    $callLogProviderPreview.addClass('d-none').text('');
                    return;
                }

                var parts = [];
                if (selected.name) {
                    parts.push(selected.name);
                }
                if (selected.phone) {
                    parts.push(selected.phone);
                }

                $callLogProviderPreview.removeClass('d-none').text(parts.join(' · '));
            }

            function initCallLogProviderSelect2() {
                if (!$callLogProviderSelect.length) {
                    return;
                }

                destroyCallLogProviderSelect2();

                var selected = $callLogProviderSelect.data('selected') || '';
                $callLogProviderSelect.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $callLogProviderSelect.data('placeholder') || '',
                    ajax: {
                        url: providerSearchUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term || '',
                            };
                        },
                        processResults: function (data) {
                            (data.results || []).forEach(function (item) {
                                callLogProviderData[String(item.id)] = {
                                    name: item.name || '',
                                    phone: item.phone || '',
                                };
                            });

                            return data;
                        },
                        cache: true,
                    },
                    minimumInputLength: 0,
                    dropdownParent: $(document.body),
                });

                $callLogProviderSelect.off('change.callLogProvider').on('change.callLogProvider', updateCallLogProviderPreview);

                if (selected) {
                    $.get(providerSearchUrl, { selected: selected }, function (data) {
                        var match = (data.results || []).find(function (item) {
                            return String(item.id) === String(selected);
                        });

                        if (match) {
                            callLogProviderData[String(match.id)] = {
                                name: match.name || '',
                                phone: match.phone || '',
                            };
                            var option = new Option(match.text, match.id, true, true);
                            $callLogProviderSelect.append(option).trigger('change');
                        }
                    });
                }
            }

            function toggleCallLogPartyPanels() {
                var partyType = $('input[name="called_party_type"]:checked').val() || 'customer';

                $('.call-log-party-panel').addClass('d-none');
                $('.call-log-party-panel--' + partyType).removeClass('d-none');

                if (partyType === 'provider') {
                    window.setTimeout(initCallLogProviderSelect2, 0);
                } else {
                    destroyCallLogProviderSelect2();
                    $callLogProviderPreview.addClass('d-none').text('');
                }
            }

            function setCallLogCurrentRecording(hasRecording, recordingName) {
                if (!hasRecording) {
                    $callLogCurrentRecording.addClass('d-none').text('');
                    return;
                }

                var label = callLogLabels.currentRecording;
                if (recordingName) {
                    label += ': ' + recordingName;
                }
                label += '. ' + callLogLabels.replaceRecording;
                $callLogCurrentRecording.removeClass('d-none').text(label);
            }

            window.configureCallLogModal = function (mode, followupId, payload) {
                mode = mode || 'add';
                payload = payload || {};

                var isEdit = mode === 'edit' && followupId;
                var storeUrl = $callLogForm.data('store-url');
                var updateTemplate = $callLogForm.data('update-url-template') || '';

                $('#call-log-mode-input').val(mode);
                $('#call-log-followup-id-input').val(isEdit ? followupId : '');
                $('#call-log-method-input').val('PUT').prop('disabled', !isEdit);
                $callLogForm.attr('action', isEdit ? updateTemplate.replace('__FOLLOWUP__', followupId) : storeUrl);
                $('#addCallLogModalLabel').text(isEdit ? callLogLabels.editTitle : callLogLabels.addTitle);
                $('#call-log-submit-btn').text(isEdit ? callLogLabels.editSubmit : callLogLabels.addSubmit);

                if (!isEdit) {
                    if ($callLogForm[0]) {
                        $callLogForm[0].reset();
                    }
                    $('#call-log-mode-input').val('add');
                    $('#call-log-method-input').prop('disabled', true);
                    $callLogForm.attr('action', storeUrl);
                    $('input[name="called_party_type"][value="customer"]').prop('checked', true);
                    $('#call-log-other-name').val('');
                    $('#call-log-other-number').val('');
                    $callLogProviderSelect.data('selected', '');
                    destroyCallLogProviderSelect2();
                    $callLogProviderSelect.val('').trigger('change');
                    setCallLogCurrentRecording(false);
                    return;
                }

                var partyType = payload.partyType || 'customer';
                $('input[name="called_party_type"][value="' + partyType + '"]').prop('checked', true);
                $('#call-log-called-at-input').val(payload.calledAt || '');
                $callLogForm.find('textarea[name="remarks"]').val(payload.remarks || '');

                if (partyType === 'other') {
                    $('#call-log-other-name').val(payload.calledName || '');
                    $('#call-log-other-number').val(payload.calledNumber || '');
                } else {
                    $('#call-log-other-name').val('');
                    $('#call-log-other-number').val('');
                }

                $callLogProviderSelect.data('selected', partyType === 'provider' ? (payload.providerId || '') : '');
                setCallLogCurrentRecording(payload.hasRecording === '1' || payload.hasRecording === true, payload.recordingName || '');
                toggleCallLogPartyPanels();
            };

            $(document).on('change', '.js-call-log-party-type', toggleCallLogPartyPanels);

            $(document).on('click', '.js-add-call-log-btn', function () {
                configureCallLogModal('add');
            });

            $(document).on('click', '.js-edit-call-log-btn', function () {
                var $btn = $(this);
                configureCallLogModal('edit', $btn.data('followup-id'), {
                    partyType: $btn.data('party-type'),
                    providerId: $btn.data('provider-id'),
                    calledName: $btn.data('called-name'),
                    calledNumber: $btn.data('called-number'),
                    calledAt: $btn.data('called-at'),
                    remarks: $btn.data('remarks'),
                    hasRecording: String($btn.data('has-recording') || '0'),
                    recordingName: $btn.data('recording-name') || '',
                });
            });

            $(document).on('click', '.js-delete-call-log-btn', function () {
                var $btn = $(this);
                if (!confirm(@json(translate('Are_you_sure')))) {
                    return;
                }

                var url = $btn.data('url');
                if (!url) {
                    return;
                }

                $btn.prop('disabled', true);
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('delete failed');
                        }
                        window.location.reload();
                    })
                    .catch(function () {
                        $btn.prop('disabled', false);
                        if (typeof toastr !== 'undefined') {
                            toastr.error(@json(translate('Failed_to_update')));
                        }
                    });
            });

            $callLogModal.on('shown.bs.modal', function () {
                toggleCallLogPartyPanels();
            });

            $callLogModal.on('hidden.bs.modal', function () {
                destroyCallLogProviderSelect2();
                $callLogProviderPreview.addClass('d-none').text('');
                configureCallLogModal('add');
            });

            @if($errors->any() && old('call_log_form'))
            configureCallLogModal(@json(old('call_log_mode', 'add')), @json(old('call_log_followup_id')));
            $callLogModal.modal('show');
            @endif

            toggleCallLogPartyPanels();
        });
    })(jQuery);
</script>
@endpush
