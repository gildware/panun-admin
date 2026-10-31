{{-- Lazy-load eligible providers when the reassign modal opens (avoids heavy work on page load). --}}
<script>
    $(document).ready(function() {
        const availableProviderRoute = '{{ url('admin/provider/available-provider') }}';
        const bookingIdForProviders = @json((string) ($booking->id ?? ''));
        let providerModalRequest = null;

        function currentProviderSortOption() {
            const checked = document.querySelector('#providerModal input[name="sort"]:checked');
            return checked ? checked.value : 'default';
        }

        function loadAvailableProviders(extraData) {
            if (!bookingIdForProviders) {
                return;
            }
            if (providerModalRequest) {
                providerModalRequest.abort();
            }

            providerModalRequest = $.get({
                url: availableProviderRoute,
                dataType: 'json',
                data: Object.assign({
                    sort_by: currentProviderSortOption(),
                    booking_id: bookingIdForProviders
                }, extraData || {}),
                success: function(response) {
                    $('.modal-content-data').html(response.view);
                },
                error: function(xhr) {
                    if (xhr.statusText === 'abort') {
                        return;
                    }
                    toastr.error('{{ translate('Failed to load') }}');
                },
                complete: function() {
                    providerModalRequest = null;
                }
            });
        }

        $('#providerModal').on('show.bs.modal', function() {
            const $content = $('#providerModal .modal-content-data');
            $content.html(
                '<div class="p-5 text-center text-muted" data-provider-modal-placeholder="1">' +
                '<div class="spinner-border spinner-border-sm text-secondary" role="status" aria-hidden="true"></div>' +
                '<div class="mt-2 fs-12">{{ translate('Loading') }}...</div></div>'
            );
            loadAvailableProviders();
        });

        $(document).on('click', '#providerModal .sort-by-class', function() {
            loadAvailableProviders({
                sort_by: currentProviderSortOption()
            });
        });

        $(document).on('keyup', '#providerModal .search-form-input', function() {
            const searchTerm = $(this).val();
            loadAvailableProviders({
                search: searchTerm
            });
            setTimeout(function() {
                const input = $('#providerModal .search-form-input').get(0);
                if (!input) {
                    return;
                }
                const cursorPosition = String(searchTerm).length;
                $('#providerModal .search-form-input').focus();
                input.setSelectionRange(cursorPosition, cursorPosition);
            }, 0);
        });
    });
</script>
