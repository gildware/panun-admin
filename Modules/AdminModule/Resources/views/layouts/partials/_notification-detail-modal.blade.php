<div class="modal fade" id="adminNotificationDetailModal" tabindex="-1" aria-labelledby="adminNotificationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="adminNotificationDetailModalLabel">{{ translate('Notification_Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body js-notification-detail-body pt-3">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ translate('Loading') }}...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
