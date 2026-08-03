<style>
    .booking-followup-cell {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
        padding: 0.35rem 0.5rem;
        border-radius: 0.375rem;
    }
    .booking-followup-cell--missed {
        background: #f8d7da;
        border: 1px solid #f1aeb5;
    }
    .booking-followup-cell--upcoming {
        background: #fff3cd;
        border: 1px solid #ffe082;
    }
    .booking-followup-cell__date {
        white-space: nowrap;
    }
    .booking-followup-badge {
        font-size: 0.68rem;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
    }
    .booking-followup-alert {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border-radius: 0.375rem;
        padding: 0.625rem 0.875rem;
        margin-bottom: 0.75rem;
        font-size: 0.8125rem;
        line-height: 1.45;
    }
    .booking-followup-alert--missed {
        background: #f8d7da;
        border: 1px solid #f1aeb5;
        color: #58151c;
    }
    .booking-followup-alert--pending {
        background: #fff3cd;
        border: 1px solid #ffe082;
        color: #664d03;
    }
    .booking-followup-alert__content {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        flex: 1;
        min-width: 0;
    }
    .booking-followup-alert__icon {
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .booking-followup-alert--missed .booking-followup-alert__icon {
        color: #dc3545;
    }
    .booking-followup-alert--pending .booking-followup-alert__icon {
        color: #ffc107;
    }
    .booking-followup-card--alert {
        border-width: 1px;
        border-style: solid;
    }
    .booking-followup-card--missed {
        border-color: #f1aeb5 !important;
    }
    .booking-followup-card--pending {
        border-color: #ffe082 !important;
    }
    .booking-detail-v2 .booking-subpage-panel .table-responsive {
        overflow-x: auto;
    }
    .booking-detail-v2 .booking-followups-table__action {
        position: sticky;
        right: 0;
        z-index: 2;
        background: #fff;
        box-shadow: -8px 0 8px -8px rgba(15, 23, 42, 0.12);
        min-width: 8.5rem;
        white-space: nowrap;
    }
    .booking-detail-v2 .booking-subpage-panel thead .booking-followups-table__action {
        background: #f8fafc;
    }
    .followup-modal-section {
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1rem;
        background: #fafbfc;
    }
    .followup-modal-section + .followup-modal-section {
        margin-top: 0.75rem;
    }
    .followup-modal-section-title {
        font-size: 0.8125rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #334155;
    }
    .followup-modal-section-help {
        font-size: 0.75rem;
        color: #64748b;
        margin: -0.35rem 0 0.75rem;
    }
    .booking-followup-history-table th {
        font-size: 0.6875rem;
        white-space: nowrap;
    }
    .booking-followup-history-table td {
        font-size: 0.8125rem;
        vertical-align: middle;
    }
    .booking-followup-history-table tbody tr.voice-call-details-row > td {
        background: #f8fafc;
    }
    .booking-followup-history-table tbody tr.booking-followup-row.is-open > td {
        background: #f1f5f9;
    }
    .voice-call-details-panel {
        background: #f8f9fb;
        border-top: 1px solid #e9ecef;
    }
    .voice-call-detail-box {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }
    .voice-call-detail-box__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 12px;
    }
    .voice-call-detail-box__header-title {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }
    .voice-call-detail-box__header .material-icons {
        font-size: 18px;
        color: #6c757d;
    }
    .voice-call-detail-box .card-body {
        padding: 12px;
    }
    .voice-call-copy-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border: none;
        background: transparent;
        color: #6c757d;
        border-radius: 6px;
        flex-shrink: 0;
    }
    .voice-call-copy-btn:hover {
        background: #f1f3f5;
        color: #495057;
    }
    .voice-call-transcript {
        max-height: 320px;
        overflow: auto;
        padding: 16px;
        font-size: 13px;
        line-height: 1.55;
        text-align: left;
        background: #fff;
        color: #212529;
    }
    .voice-call-transcript-line {
        margin-bottom: 6px;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .voice-call-transcript-line--user { color: #0d6efd; }
    .voice-call-transcript-line--llm { color: #495057; }
    .voice-call-details-top-row { align-items: stretch; }
    .voice-call-left-stack { min-height: 100%; }
    .voice-call-summary-card {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 180px;
    }
    .voice-call-summary-body {
        flex: 1 1 auto;
        min-height: 140px;
        overflow: auto;
    }
    .voice-call-extracted-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        align-content: start;
    }
    .voice-call-extracted-item {
        background: #f8f9fb;
        border: 1px solid #eef1f4;
        border-radius: 8px;
        padding: 10px 12px;
        min-width: 0;
    }
    .voice-call-extracted-item__label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6c757d;
        margin-bottom: 4px;
    }
    .voice-call-extracted-item__value {
        font-size: 14px;
        font-weight: 500;
        word-break: break-word;
    }
    .voice-call-recording-box .voice-call-audio-player { height: 36px; }
    .booking-detail-v2 .table-section-label {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--bk-faint, #64748b);
        padding: .75rem 1rem .5rem;
        margin: 0;
    }
    .booking-detail-v2 .activity-table-section .table-responsive {
        padding: 0 .5rem .75rem;
    }
    .booking-detail-v2 .ld-btn {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .75rem;
        font-weight: 600;
        padding: .4rem .75rem;
        border-radius: var(--bk-radius-sm, .375rem);
        border: 1px solid transparent;
        cursor: pointer;
        line-height: 1.3;
        white-space: nowrap;
        background: #fff;
    }
    .booking-detail-v2 .ld-btn-outline {
        color: var(--bk-muted, #64748b);
        border-color: var(--bk-border, #e2e8f0);
    }
    .booking-detail-v2 .ld-btn-outline:hover {
        background: var(--bk-accent-soft, #eff6ff);
        color: var(--bk-accent-dark, #1d4ed8);
        border-color: var(--bk-accent, #3b82f6);
    }
    .booking-detail-v2 .lead-followup-history-table th {
        font-size: 0.8125rem;
        white-space: nowrap;
    }
    .booking-detail-v2 .lead-followup-history-table td {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .booking-detail-v2 .lead-followup-history-table .ld-btn {
        padding: .35rem .65rem;
        font-size: .75rem;
    }
    .booking-detail-v2 .lead-followup-history-table tbody tr.lead-followup-row--alt td,
    .booking-detail-v2 .lead-followup-history-table tbody tr.booking-followup-row--alt td {
        background: #f3f4f6;
    }
    .booking-detail-v2 .lead-followup-history-table tbody tr.lead-followup-row.is-open td,
    .booking-detail-v2 .lead-followup-history-table tbody tr.booking-followup-row.is-open td {
        background: #e8ecf6;
    }
    .booking-detail-v2 .lead-followup-history-table tbody tr.voice-call-details-row > td,
    .booking-detail-v2 .booking-followup-history-table tbody tr.voice-call-details-row > td {
        box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.04);
        background: #f8f9fb !important;
    }
    .booking-detail-v2 .voice-call-extracted-card {
        display: flex;
        flex-direction: column;
    }
    .booking-detail-v2 .voice-call-extracted-body {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }
    @media (max-width: 1200px) {
        .booking-detail-v2 .voice-call-extracted-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 768px) {
        .booking-detail-v2 .voice-call-extracted-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
