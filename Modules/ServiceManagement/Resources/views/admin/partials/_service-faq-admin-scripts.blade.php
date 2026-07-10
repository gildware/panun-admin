<script>
    "use strict";

    (function () {
        let faqSubmitting = false;
        let faqDragItem = null;
        let faqReorderSaving = false;

        function getFaqReorderUrl() {
            return $('#faq-list').data('reorder-url') || '';
        }

        function collectFaqOrder() {
            return $('#faqAccordionList .service-faq-item').map(function () {
                return $(this).data('faq-id');
            }).get().filter(Boolean);
        }

        function saveFaqOrder() {
            const url = getFaqReorderUrl();
            const order = collectFaqOrder();
            if (!url || order.length < 1 || faqReorderSaving) {
                return;
            }

            faqReorderSaving = true;
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.post({
                url: url,
                data: { order: order },
                success: function () {
                    toastr.success('{{ translate('successfully_updated') }}');
                },
                error: function () {
                    toastr.error('{{ translate('something_went_wrong') }}');
                },
                complete: function () {
                    faqReorderSaving = false;
                }
            });
        }

        function initFaqSortable() {
            const list = document.getElementById('faqAccordionList');
            if (!list) {
                return;
            }
            list.dataset.faqSortInit = '1';

            list.querySelectorAll('.service-faq-drag-handle').forEach(function (handle) {
                if (handle.dataset.faqDragInit === '1') {
                    return;
                }
                handle.dataset.faqDragInit = '1';

                handle.addEventListener('dragstart', function (e) {
                    faqDragItem = handle.closest('.service-faq-item');
                    if (!faqDragItem) {
                        return;
                    }
                    faqDragItem.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    try {
                        e.dataTransfer.setData('text/plain', faqDragItem.dataset.faqId || '');
                    } catch (err) {}
                    e.stopPropagation();
                });

                handle.addEventListener('dragend', function () {
                    if (faqDragItem) {
                        faqDragItem.classList.remove('is-dragging');
                    }
                    list.querySelectorAll('.service-faq-item.is-drag-over').forEach(function (el) {
                        el.classList.remove('is-drag-over');
                    });
                    faqDragItem = null;
                    saveFaqOrder();
                });

                handle.addEventListener('mousedown', function (e) {
                    e.stopPropagation();
                });
                handle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            if (list.dataset.faqListDragInit === '1') {
                return;
            }
            list.dataset.faqListDragInit = '1';

            list.addEventListener('dragover', function (e) {
                e.preventDefault();
                const target = e.target.closest('.service-faq-item');
                if (!faqDragItem || !target || target === faqDragItem || !list.contains(target)) {
                    return;
                }

                list.querySelectorAll('.service-faq-item.is-drag-over').forEach(function (el) {
                    if (el !== target) {
                        el.classList.remove('is-drag-over');
                    }
                });
                target.classList.add('is-drag-over');

                const rect = target.getBoundingClientRect();
                const before = (e.clientY - rect.top) < (rect.height / 2);
                if (before) {
                    list.insertBefore(faqDragItem, target);
                } else {
                    list.insertBefore(faqDragItem, target.nextSibling);
                }
            });

            list.addEventListener('drop', function (e) {
                e.preventDefault();
                list.querySelectorAll('.service-faq-item.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
            });
        }

        function setFaqSubmitLoading(isLoading) {
            const $btn = $('#faq-submit-btn');
            const idleLabel = $btn.data('label-idle') || '{{ translate('add_faq') }}';
            const loadingLabel = $btn.data('label-loading') || '{{ translate('Loading') }}...';

            faqSubmitting = isLoading;
            $btn.prop('disabled', isLoading);
            $btn.find('.faq-submit-label').text(isLoading ? loadingLabel : idleLabel);
            $btn.find('.spinner-border').toggleClass('d-none', !isLoading);
            $('#faq-form').find('input[name="question"], textarea[name="answer"]').prop('disabled', isLoading);
        }

        $('#faq-form').on('submit', function (e) {
            e.preventDefault();

            const form = this;
            const question = (form.question.value || '').trim();
            const answer = (form.answer.value || '').trim();

            if (!question || !answer) {
                form.reportValidity();
                toastr.error('{{ translate('Please_complete_all_required_fields_before_proceeding') }}');
                return;
            }

            if (faqSubmitting) {
                return;
            }

            form.question.value = question;
            form.answer.value = answer;
            setFaqSubmitLoading(true);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const data = new FormData();
            data.append('_token', $('meta[name="csrf-token"]').attr('content') || form._token?.value || '');
            data.append('question', question);
            data.append('answer', answer);

            $.post({
                url: '{{ route('admin.faq.store', [$service->id]) }}',
                data: data,
                processData: false,
                contentType: false,
                cache: false,
                timeout: 800000,
                success: function (response) {
                    $('#faq-list').empty().html(response.template);
                    form.reset();
                    toastr.success('{{ translate('successfully_added') }}');
                    initFaqSortable();
                },
                error: function () {
                    toastr.error('{{ translate('something_went_wrong') }}');
                },
                complete: function () {
                    setFaqSubmitLoading(false);
                }
            });
        });

        $('#faq-list').on('click', '.service-faq-update', function () {
            let id = $(this).data('id');
            ajax_post(id, this);
        });

        function ajax_post(form_id, triggerBtn) {
            const $btn = $(triggerBtn);
            if ($btn.data('busy')) {
                return;
            }

            const form = $('#' + form_id)[0];
            if (!form) {
                return;
            }

            const question = (form.question?.value || '').trim();
            const answer = (form.answer?.value || '').trim();
            if (!question || !answer) {
                form.reportValidity();
                toastr.error('{{ translate('Please_complete_all_required_fields_before_proceeding') }}');
                return;
            }

            form.question.value = question;
            form.answer.value = answer;

            $btn.data('busy', true).prop('disabled', true);
            const originalHtml = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>{{ translate('Loading') }}...');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.post({
                url: $('#' + form_id).attr('action'),
                data: new FormData(form),
                processData: false,
                contentType: false,
                cache: false,
                timeout: 800000,
                success: function (response) {
                    $('#faq-list').empty().html(response.template);
                    toastr.success('{{ translate('successfully_updated') }}');
                    initFaqSortable();
                },
                error: function () {
                    toastr.error('{{ translate('something_went_wrong') }}');
                    $btn.data('busy', false).prop('disabled', false).html(originalHtml);
                }
            });
        }

        $('#faq-list').on('click', '.faq-list-ajax-delete', function () {
            let route = $(this).data('route');
            ajax_delete(route);
        });

        $('#faq-list').on('click', '.show-service-edit-section', function () {
            let id = $(this).data('id');
            $(`#edit-${id}`).toggle();
        });

        function ajax_delete(route) {
            Swal.fire({
                title: "{{ translate('are_you_sure') }}?",
                text: '{{ translate('want_to_delete_this_faq') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: route,
                        dataType: 'json',
                        data: {},
                        success: function (response) {
                            $('#faq-list').empty().html(response.template);
                            toastr.success('{{ translate('successfully_deleted') }}');
                            initFaqSortable();
                        },
                    });
                }
            });
        }

        $('#faq-list').on('click', '.service-ajax-status-update', function () {
            let route = $(this).data('route');
            ajax_status_update(route);
        });

        function ajax_status_update(route) {
            Swal.fire({
                title: "{{ translate('are_you_sure') }}?",
                text: '{{ translate('want_to_update_status_of_this_faq') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Yes',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.get({
                        url: route,
                        dataType: 'json',
                        data: {},
                        success: function () {
                            toastr.success('{{ translate('successfully_updated') }}');
                        },
                    });
                }
            });
        }

        initFaqSortable();
    })();
</script>
