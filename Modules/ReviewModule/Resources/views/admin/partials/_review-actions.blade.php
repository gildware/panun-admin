@if($showActions ?? true)
    <div class="d-inline-flex flex-nowrap align-items-center gap-1 text-nowrap review-actions-cell">
        @if($isApproved)
            <span class="badge badge-success text-capitalize mb-0">{{ translate('approved') }}</span>
        @else
            <span class="badge badge-info text-capitalize mb-0">{{ translate('pending') }}</span>
            @if(!empty($approveRoute))
                <button type="button"
                        class="action-btn btn--success route-alert-reload"
                        style="--size: 28px"
                        data-route="{{ $approveRoute }}"
                        data-message="{{ translate('want_to_approve_review') }}"
                        title="{{ translate('approve') }}">
                    <span class="material-icons" style="font-size: 16px">check</span>
                </button>
            @endif
        @endif

        @if(!empty($deleteRoute))
            <button type="button"
                    class="action-btn btn--danger route-alert-reload"
                    style="--size: 28px"
                    data-route="{{ $deleteRoute }}"
                    data-message="{{ translate('want_to_delete_review') }}"
                    title="{{ translate('delete') }}">
                <span class="material-icons" style="font-size: 16px">delete</span>
            </button>
        @endif
    </div>
@endif
