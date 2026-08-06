@can('booking_delete')
    <div class="modal fade" id="wbDeleteModal" tabindex="-1" aria-labelledby="wbDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body pt-5 p-md-5">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>

                    <div class="d-flex justify-content-center mb-4">
                        <img width="75" height="75"
                             src="{{ asset('assets/admin-module/img/media/delete.png') }}"
                             class="rounded-circle" alt="">
                    </div>

                    <h3 class="text-center mb-2 fw-medium" id="wbDeleteModalLabel">
                        {{ translate('Are_you_sure_you_want_to_delete_this_item') }}
                    </h3>
                    <p class="text-center fs-12 fw-medium text-muted mb-1" id="wbDeleteModalItem"></p>

                    <form method="POST" id="wbDeleteForm" action="#">
                        @csrf

                        <div class="d-flex justify-content-center gap-3 mt-3">
                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">
                                {{ translate('Cancel') }}
                            </button>
                            <button type="submit" class="btn btn-danger">
                                {{ translate('Delete') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            (function () {
                var deleteModal = document.getElementById('wbDeleteModal');
                var form = document.getElementById('wbDeleteForm');
                var labelEl = document.getElementById('wbDeleteModalItem');
                if (!deleteModal || !form) return;

                function applyDeleteTarget(button) {
                    if (!button) return;

                    form.action = button.getAttribute('data-wb-delete-url') || '#';
                    if (labelEl) {
                        labelEl.textContent = button.getAttribute('data-wb-delete-label') || '';
                    }
                }

                document.querySelectorAll('[data-wb-delete-url]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        applyDeleteTarget(button);
                    });
                });

                deleteModal.addEventListener('show.bs.modal', function (event) {
                    applyDeleteTarget(event.relatedTarget);
                });
            })();
        </script>
    @endpush
@endcan
