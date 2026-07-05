<div class="data-reset-progress mt-3 d-none" id="data-reset-progress-{{ $id }}" aria-live="polite">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="fw-semibold small data-reset-progress-title">{{ translate('Processing') }}...</span>
        <span class="badge bg-secondary data-reset-progress-count">0 / 0</span>
    </div>
    <div class="progress mb-3" style="height: 8px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger data-reset-progress-bar"
             role="progressbar"
             style="width: 0%"
             aria-valuenow="0"
             aria-valuemin="0"
             aria-valuemax="100"></div>
    </div>
    <ul class="data-reset-timeline list-unstyled mb-0 small border rounded p-2 bg-light"
        style="max-height: 220px; overflow-y: auto;"></ul>
    <p class="data-reset-status text-muted small mb-0 mt-2"></p>
</div>
