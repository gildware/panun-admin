@can('booking_delete')
    <div class="modal fade" id="bookingDeleteModal--{{ $booking['id'] }}" tabindex="-1"
         aria-labelledby="bookingDeleteModalLabel--{{ $booking['id'] }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body pt-5 p-md-5">
                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>

                    <div class="d-flex justify-content-center mb-4">
                        <img width="75" height="75"
                             src="{{ asset('assets/admin-module/img/media/delete.png') }}"
                             class="rounded-circle" alt="">
                    </div>

                    <h3 class="text-center mb-2 fw-medium">
                        {{ translate('Are_you_sure_you_want_to_delete_this_booking?') }}
                    </h3>
                    <p class="text-center fs-12 fw-medium text-muted">
                        {{ translate('This_action_will_permanently_remove_the_booking_and_its_related_data.') }}
                    </p>

                    <form method="POST"
                          action="{{ route('admin.booking.delete', [$booking->id]) }}">
                        @csrf
                        @method('DELETE')

                        <div class="d-flex justify-content-center gap-3 mt-3">
                            <button type="button"
                                    class="btn btn--secondary"
                                    data-bs-dismiss="modal">
                                {{ translate('cancel') }}
                            </button>
                            <button type="submit"
                                    class="btn btn-danger">
                                {{ translate('Delete') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
