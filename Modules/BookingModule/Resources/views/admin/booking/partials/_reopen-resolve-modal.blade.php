@php
    $mid = $modalId ?? 'reopenResolveModal';
    $fid = $formId ?? 'reopenResolveForm';
@endphp
@can('booking_can_manage_status')
    <div class="modal fade" id="{{ $mid }}" tabindex="-1" aria-labelledby="{{ $mid }}Label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="post" action="{{ $formAction }}" id="{{ $fid }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="{{ $mid }}Label">{{ translate('Mark_reopen_resolved') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">{{ translate('Reopen_resolve_remarks_help') }}</p>
                        <label class="form-label" for="reopen_resolve_remarks__{{ $mid }}">{{ translate('Reopen_resolve_remarks') }} <span class="text-danger">*</span></label>
                        <textarea id="reopen_resolve_remarks__{{ $mid }}" name="reopen_resolve_remarks" class="form-control" rows="4" required minlength="1" maxlength="5000"
                            placeholder="{{ translate('Reopen_resolve_remarks_placeholder') }}">{{ old('reopen_resolve_remarks') }}</textarea>
                        @error('reopen_resolve_remarks')
                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ translate('Mark_reopen_resolved') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
