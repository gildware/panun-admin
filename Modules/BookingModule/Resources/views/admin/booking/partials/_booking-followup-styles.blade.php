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
</style>
