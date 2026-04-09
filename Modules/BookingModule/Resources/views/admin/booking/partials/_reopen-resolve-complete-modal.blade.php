{{-- Resolve reopen: complete booking + close reopen case (notes required, confirm on submit) --}}
@can('booking_can_manage_status')
    <div class="modal fade" id="reopenResolveCompleteModal--{{ $booking->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.booking.reopen-resolve-complete', $booking->id) }}" id="reopenResolveCompleteForm--{{ $booking->id }}"
                    onsubmit="return confirm(@json(translate('Confirm_resolve_reopen_booking')));">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Resolve_booking') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">{{ translate('Resolve_reopen_complete_help') }}</p>
                        <label class="form-label" for="reopen_resolve_complete_remarks--{{ $booking->id }}">{{ translate('Reopen_resolve_remarks') }} <span class="text-danger">*</span></label>
                        <textarea id="reopen_resolve_complete_remarks--{{ $booking->id }}" name="reopen_resolve_complete_remarks" class="form-control" rows="4" required minlength="1" maxlength="5000"
                            placeholder="{{ translate('Reopen_resolve_remarks_placeholder') }}">{{ old('reopen_resolve_complete_remarks') }}</textarea>
                        @error('reopen_resolve_complete_remarks')
                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('Confirm') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
