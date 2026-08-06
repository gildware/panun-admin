<style>
    .emp-dash {
        --wq-brand: #43466e;
        --wq-brand-hover: #363856;
        --wq-brand-soft: #eef0f6;
        --wq-pickup: #78716c;
        --wq-pickup-soft: #f5f5f4;
        --wq-border: #e5e7eb;
        --wq-surface: #f8fafc;
        --wq-text: #1f2937;
        --wq-muted: #64748b;
        --wq-hot: #dc2626;
        --wq-hot-soft: #fef2f2;
        --wq-widget-height: 300px;
    }
    .emp-dash .tone-lead { --wq-tone: #2563eb; --wq-tone-soft: #eff6ff; --wq-tone-border: #bfdbfe; --wq-tone-header-text: #1e40af; }
    .emp-dash .tone-booking { --wq-tone: #0891b2; --wq-tone-soft: #ecfeff; --wq-tone-border: #a5f3fc; --wq-tone-header-text: #0e7490; }
    .emp-dash .tone-task { --wq-tone: #7c3aed; --wq-tone-soft: #f5f3ff; --wq-tone-border: #ddd6fe; --wq-tone-header-text: #5b21b6; }
    .emp-dash .tone-earning { --wq-tone: #15803d; --wq-tone-soft: #f0fdf4; --wq-tone-border: #bbf7d0; --wq-tone-header-text: #15803d; }
    .emp-dash .tone-wallet { --wq-tone: #b45309; --wq-tone-soft: #fffbeb; --wq-tone-border: #fde68a; --wq-tone-header-text: #b45309; }
    .emp-dash .tone-unassigned-lead { --wq-tone: #d97706; --wq-tone-soft: #fffbeb; --wq-tone-border: #fde68a; --wq-tone-header-text: #b45309; }
    .emp-dash .tone-unassigned-booking { --wq-tone: #e11d48; --wq-tone-soft: #fff1f2; --wq-tone-border: #fecdd3; --wq-tone-header-text: #be123c; }
    .emp-dash .tone-whatsapp { --wq-tone: #16a34a; --wq-tone-soft: #f0fdf4; --wq-tone-border: #bbf7d0; --wq-tone-header-text: #15803d; }
    .emp-dash .tone-whatsapp-unread { --wq-tone: #0d9488; --wq-tone-soft: #f0fdfa; --wq-tone-border: #99f6e4; --wq-tone-header-text: #0f766e; }
    .emp-dash .work-queue-split {
        display: flex; flex-direction: column; gap: 12px;
    }
    .emp-dash .work-queue-lane {
        display: flex; flex-direction: column; min-height: 0; width: 100%;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 10px 8px;
    }
    .emp-dash .work-queue-lane > .lane-boxes-row {
        flex: 1 1 auto;
    }
    .emp-dash .work-queue-lane--pending { border-top: 3px solid var(--wq-brand); }
    .emp-dash .work-queue-lane--pickup { border-top: 3px solid var(--wq-pickup); }
    .emp-dash .lane-header { margin-bottom: 10px; min-width: 0; }
    .emp-dash .lane-title {
        font-size: 13px; font-weight: 700; color: #111827; margin: 0;
        text-transform: uppercase; letter-spacing: .03em;
    }
    .emp-dash-topbar {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        margin-bottom: 12px; min-width: 0;
    }
    .emp-dash-topbar .admin-dashboard-switcher {
        margin-bottom: 0 !important;
        flex: 0 0 auto;
        width: fit-content;
    }
    .emp-dash .lane-boxes-row {
        display: flex; flex-direction: row; flex-wrap: nowrap; gap: 8px;
        align-items: stretch; overflow-x: auto; padding-bottom: 2px;
    }
    .emp-dash .lane-boxes-row > .work-queue-box {
        flex: 1 1 0; min-width: 240px;
        height: var(--wq-widget-height);
        min-height: var(--wq-widget-height);
        max-height: var(--wq-widget-height);
        align-self: stretch;
        box-sizing: border-box;
    }
    @media (max-width: 992px) {
        .emp-dash .lane-boxes-row { flex-wrap: wrap; overflow-x: visible; }
        .emp-dash .lane-boxes-row > .work-queue-box {
            flex: 1 1 calc(50% - 4px); min-width: min(100%, 280px);
        }
    }
    @media (max-width: 576px) {
        .emp-dash .lane-boxes-row > .work-queue-box { flex: 1 1 100%; }
    }
    .emp-dash .work-queue-box {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;
        display: flex; flex-direction: column; min-width: 0;
    }
    .emp-dash .work-queue-box-header {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        flex-wrap: nowrap; flex-shrink: 0;
        min-height: 32px; box-sizing: border-box;
        padding: 7px 10px; border-bottom: 1px solid var(--wq-tone-border, #eef0f3);
        background: var(--wq-tone-soft, var(--wq-surface));
    }
    .emp-dash .work-queue-box[class*="tone-"] {
        border-top: 2px solid var(--wq-tone, var(--wq-border));
    }
    .emp-dash .work-queue-box-title {
        display: flex; align-items: center; gap: 6px;
        flex: 1 1 auto; min-width: 0;
        font-size: 11px; font-weight: 700; color: var(--wq-tone-header-text, var(--wq-text));
    }
    .emp-dash .work-queue-box-title .material-symbols-outlined,
    .emp-dash .work-queue-box-title .material-icons {
        font-size: 16px; color: var(--wq-tone, var(--wq-muted));
    }
    .emp-dash .work-queue-box-title > span:last-child {
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .emp-dash .work-queue-count-badge {
        font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px;
        background: #fff; color: var(--wq-tone-header-text, #374151);
        border: 1px solid var(--wq-tone-border, #e5e7eb);
    }
    .emp-dash .work-queue-count-badge.is-hot { background: var(--wq-hot-soft); color: var(--wq-hot); }
    .emp-dash .work-queue-box-body { display: none; flex: 1 1 auto; flex-direction: column; min-height: 0; }
    .emp-dash .work-queue-box-body.active { display: flex; }
    .emp-dash .work-queue-box-content {
        flex: 1 1 auto; display: flex; flex-direction: column; min-height: 0; overflow: hidden;
    }
    .emp-dash .work-queue-table-wrap { flex: 1 1 auto; min-height: 0; overflow: auto; padding: 6px; }
    .emp-dash .work-queue-table {
        width: 100%; border-collapse: collapse; font-size: 11px; table-layout: fixed;
    }
    .emp-dash .work-queue-table thead th {
        position: sticky; top: 0; z-index: 1;
        background: #f1f5f9; color: #64748b; font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .02em;
        padding: 5px 4px; border-bottom: 1px solid #e2e8f0; white-space: nowrap;
    }
    .emp-dash .work-queue-table tbody td {
        padding: 4px 4px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .emp-dash .work-queue-table tbody tr:hover { background: #f8fafc; }
    .emp-dash .work-queue-table tbody tr.is-clickable { cursor: pointer; }
    .emp-dash .work-queue-table .col-name { width: 42%; }
    .emp-dash .work-queue-table .col-score { width: 18%; text-align: right; }
    .emp-dash .work-queue-table .col-bookings { width: 18%; text-align: right; }
    .emp-dash .work-queue-table .col-amount { width: 34%; }
    .emp-dash .work-queue-table .col-booking-ref { width: 28%; }
    .emp-dash .work-queue-table .col-datetime { width: 38%; }
    .emp-dash .cell-primary {
        display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2;
        font-weight: 600; color: #111827; line-height: 1.35;
        overflow: hidden; white-space: normal; word-break: break-word;
    }
    .emp-dash .cell-secondary {
        display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 1;
        font-size: 10px; color: #94a3b8; margin-top: 1px; line-height: 1.3;
        overflow: hidden; white-space: normal; word-break: break-word;
    }
    .emp-dash .datetime-main { color: #475569; white-space: nowrap; font-size: 10px; }
    .emp-dash .work-queue-empty {
        flex: 1 1 auto; display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 0; padding: 24px 16px; text-align: center; color: #94a3b8; font-size: 11px;
    }
    .emp-dash .work-queue-empty .material-symbols-outlined {
        font-size: 28px; color: #cbd5e1; margin-bottom: 6px;
    }
    .emp-dash .work-queue-box-footer {
        display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end;
        padding: 8px 10px; border-top: 1px solid #eef0f3; background: #fafafa;
        margin-top: auto; flex-shrink: 0;
    }
    .emp-dash .work-queue-footer-link {
        display: inline-flex; align-items: center; justify-content: center;
        flex: 0 0 auto; width: auto;
        font-size: 10px; font-weight: 600; text-decoration: none;
        padding: 5px 11px; border-radius: 6px; border: 1px solid transparent;
        line-height: 1.2; white-space: nowrap;
        transition: background .12s, border-color .12s, color .12s, box-shadow .12s;
    }
    .emp-dash .work-queue-footer-link.is-single {
        color: #475569; border-color: #cbd5e1; background: #fff;
    }
    .emp-dash .work-queue-footer-link.is-single:hover {
        color: var(--wq-text); border-color: #94a3b8; background: var(--wq-surface);
        text-decoration: none;
    }
    .emp-dash .rank-avatar {
        width: 20px; height: 20px; border-radius: 999px; object-fit: cover; flex-shrink: 0;
    }
    .emp-dash .rank-name-cell {
        display: flex; align-items: center; gap: 6px; min-width: 0;
    }
    .emp-dash .finance-amount { font-size: 10px; font-weight: 700; white-space: nowrap; }
    .emp-dash .finance-amount.is-credit { color: #15803d; }
    .emp-dash .finance-amount.is-debit { color: #b91c1c; }
    .emp-dash .finance-chart-wrap {
        flex: 1 1 auto; min-height: 0; padding: 2px 4px 0; overflow: hidden;
    }
    .emp-dash .finance-chart-wrap #apex_line-chart {
        min-height: 0; height: 100%; max-height: 100%;
    }
    .emp-dash .dashboard-earning-filter-wrap {
        display: flex; align-items: center; gap: 4px; flex-shrink: 0;
    }
    .emp-dash .dashboard-earning-filter-wrap .select-wrap { flex: 0 0 auto; }
    .emp-dash .dashboard-earning-filter-wrap .select-wrap:first-child .select2-container {
        min-width: 4.25rem !important; max-width: 4.75rem;
    }
    .emp-dash .dashboard-earning-filter-wrap .select-wrap:last-child .select2-container {
        min-width: 6.5rem !important; max-width: 7rem;
    }
    .emp-dash .dashboard-earning-filter-wrap .select2-container .select2-selection--single {
        min-height: 1.5rem; height: 1.5rem; border-color: var(--wq-tone-border, #d1d5db);
        background: #fff;
    }
    .emp-dash .dashboard-earning-filter-wrap .select2-container .select2-selection__rendered {
        font-size: 10px; line-height: 1.4rem; padding-left: 6px; padding-right: 1.1rem;
        color: var(--wq-tone-header-text, #475569); font-weight: 600;
    }
    .emp-dash .dashboard-earning-filter-wrap .select-wrap:last-child .select2-container .select2-selection__rendered {
        padding-right: 2.35rem;
    }
    .emp-dash .dashboard-earning-filter-wrap .select2-container .select2-selection__arrow {
        height: 1.4rem; right: 2px;
    }
    .emp-dash .dashboard-earning-filter-wrap .select-wrap:last-child .select2-container .select2-selection__clear {
        font-size: 0.85rem; line-height: 1.4rem; margin-right: 0.1rem;
    }
    .emp-dash .finance-ledger-summary {
        padding: 6px 8px 0; font-size: 10px; color: var(--wq-muted); line-height: 1.3;
    }
</style>
