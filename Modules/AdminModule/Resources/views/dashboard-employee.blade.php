@extends('adminmodule::layouts.new-master')

@section('title', translate('dashboard'))

@push('css_or_js')
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
    .emp-dash-employee-filter {
        flex: 0 0 auto; min-width: 0; margin-left: auto;
    }
    .emp-dash-employee-filter .form-select {
        border: 1px solid #d1d5db; background: #fff; color: #475569;
        font-size: 10px; font-weight: 600;
        padding: 0 1.5rem 0 8px;
        min-width: 120px; max-width: min(100vw - 2rem, 200px);
        box-shadow: none; border-radius: 4px;
        height: 1.5rem; min-height: 1.5rem; line-height: 1.5rem;
    }
    .emp-dash-employee-filter .form-select:focus {
        border-color: #43466e; box-shadow: 0 0 0 2px rgba(67, 70, 110, .1);
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
    .emp-dash .work-queue-box-title .material-symbols-outlined {
        font-size: 16px; color: var(--wq-tone, var(--wq-muted));
    }
    .emp-dash .work-queue-box-title > span:last-child {
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .emp-dash .work-queue-tabs {
        display: inline-flex; align-items: center; gap: 4px;
        flex: 0 0 auto; width: auto; max-width: 100%; flex-shrink: 0;
    }
    .emp-dash .work-queue-tab {
        flex: 0 0 auto; width: auto; max-width: 8.5rem;
        border: 1px solid #d1d5db; background: #fff; color: #475569;
        padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; cursor: pointer;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .emp-dash .work-queue-tab--employee {
        display: inline-flex; align-items: center; gap: 3px;
        max-width: 10rem;
    }
    .emp-dash .work-queue-tab-count { font-weight: 700; flex-shrink: 0; }
    .emp-dash .work-queue-tab-text {
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        min-width: 0;
    }
    .emp-dash .work-queue-tab.active {
        background: var(--wq-tone, var(--wq-brand)); color: #fff;
        border-color: var(--wq-tone, var(--wq-brand));
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
    .emp-dash .work-queue-table-wrap { flex: 1 1 auto; min-height: 0; overflow: auto; }
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
        padding: 4px 4px; border-bottom: 1px solid #f1f5f9; vertical-align: top;
    }
    .emp-dash .work-queue-table tbody tr:hover { background: #f8fafc; }
    .emp-dash .work-queue-table tbody tr.is-overdue { background: rgba(254, 226, 226, .35); }
    .emp-dash .work-queue-table .col-name { width: 30%; }
    .emp-dash .work-queue-table .col-assignee { width: 16%; }
    .emp-dash .work-queue-table .col-type { width: 14%; }
    .emp-dash .work-queue-table .col-datetime { width: 24%; }
    .emp-dash .work-queue-table .col-urgency { width: 16%; }
    .emp-dash .assignee-pill {
        display: inline-block; max-width: 100%;
        padding: 2px 5px; border-radius: 999px;
        background: #eef0f6; color: #43466e;
        font-size: 9px; font-weight: 600;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .emp-dash .work-queue-row-link {
        display: block; text-decoration: none; color: inherit; min-width: 0;
    }
    .emp-dash .work-queue-row-link:hover .cell-primary { color: var(--wq-tone, var(--wq-brand)); }
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
    .emp-dash .type-pill {
        display: inline-block; max-width: 100%;
        padding: 2px 5px; border-radius: 999px;
        background: var(--wq-tone-soft, var(--wq-brand-soft));
        color: var(--wq-tone-header-text, var(--wq-brand));
        font-size: 9px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .emp-dash .datetime-main { color: #475569; white-space: nowrap; font-size: 10px; }
    .emp-dash .urgency-pill {
        display: inline-block; padding: 2px 6px; border-radius: 999px;
        font-size: 9px; font-weight: 700; text-transform: capitalize; white-space: nowrap;
    }
    .emp-dash .urgency-pill.urgency-high { background: #fee2e2; color: #b91c1c; }
    .emp-dash .urgency-pill.urgency-medium { background: #fef3c7; color: #b45309; }
    .emp-dash .urgency-pill.urgency-low { background: #f1f5f9; color: #64748b; }
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
    .emp-dash .work-queue-footer-link.is-primary {
        color: var(--wq-tone-header-text, var(--wq-brand));
        border-color: var(--wq-tone-border, #c7cbe0);
        background: var(--wq-tone-soft, var(--wq-brand-soft));
    }
    .emp-dash .work-queue-footer-link.is-primary:hover {
        color: #fff; border-color: var(--wq-tone, var(--wq-brand));
        background: var(--wq-tone, var(--wq-brand));
        text-decoration: none; box-shadow: 0 1px 2px color-mix(in srgb, var(--wq-tone, #43466e) 22%, transparent);
    }
    .emp-dash .work-queue-footer-link.is-all {
        color: var(--wq-muted); border-color: var(--wq-border); background: #fff;
    }
    .emp-dash .work-queue-footer-link.is-all:hover {
        color: var(--wq-text); border-color: #cbd5e1; background: var(--wq-surface);
        text-decoration: none;
    }
    .emp-dash .work-queue-footer-link.is-single {
        color: #475569; border-color: #cbd5e1; background: #fff;
    }
    .emp-dash .work-queue-footer-link.is-single:hover {
        color: var(--wq-text); border-color: #94a3b8; background: var(--wq-surface);
        text-decoration: none;
    }
    .emp-dash .work-queue-card-list {
        display: flex; flex-direction: column; gap: 6px;
        flex: 1 1 auto; min-height: 0; padding: 8px; overflow-y: auto;
    }
    .emp-dash .work-queue-item-card {
        display: block; text-decoration: none; color: inherit;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
        padding: 8px 10px; transition: border-color .12s, box-shadow .12s, background .12s;
    }
    .emp-dash .work-queue-item-card:hover {
        border-color: var(--wq-tone-border, #c7cbe0);
        background: var(--wq-tone-soft, var(--wq-brand-soft)); color: inherit;
        box-shadow: 0 1px 3px color-mix(in srgb, var(--wq-tone, #43466e) 10%, transparent);
    }
    .emp-dash .work-queue-item-card.is-overdue {
        border-color: #fecaca; background: #fef2f2;
    }
    .emp-dash .work-queue-item-card.is-overdue:hover {
        border-color: #f87171; background: #fee2e2;
    }
    .emp-dash .work-queue-item-card-top {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 6px;
    }
    .emp-dash .work-queue-item-card-top-right {
        display: flex; align-items: center; justify-content: flex-end; gap: 4px;
        flex-shrink: 0; max-width: 55%;
    }
    .emp-dash .work-queue-item-card-title {
        font-size: 11px; font-weight: 700; color: #111827; line-height: 1.35;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .emp-dash .work-queue-item-card-meta {
        display: flex; align-items: center; justify-content: space-between; gap: 6px;
    }
    .emp-dash .work-queue-item-card-date {
        font-size: 10px; color: #64748b; white-space: nowrap;
    }
    .emp-dash .work-queue-whatsapp-card-list {
        display: flex; flex-direction: column; gap: 4px;
        flex: 1 1 auto; min-height: 0; padding: 6px; overflow-y: auto;
    }
    .emp-dash .work-queue-whatsapp-card {
        display: block; text-decoration: none; color: inherit;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;
        padding: 5px 7px; transition: border-color .12s, box-shadow .12s, background .12s;
    }
    .emp-dash .work-queue-whatsapp-card:hover {
        border-color: #cbd5e1; background: var(--wq-surface); color: inherit;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
    }
    .emp-dash .work-queue-whatsapp-card.has-unread {
        border-color: var(--wq-tone-border, #c7cbe0);
        background: var(--wq-tone-soft, var(--wq-brand-soft));
    }
    .emp-dash .work-queue-whatsapp-card-head {
        display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 3px;
    }
    .emp-dash .work-queue-whatsapp-card-user {
        display: flex; align-items: center; gap: 6px; min-width: 0;
    }
    .emp-dash .work-queue-whatsapp-avatar {
        flex-shrink: 0; width: 22px; height: 22px; border-radius: 999px;
        background: var(--wq-tone-soft, var(--wq-brand-soft));
        color: var(--wq-tone, var(--wq-brand)); font-size: 14px;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .emp-dash .work-queue-whatsapp-user-text { min-width: 0; line-height: 1.2; }
    .emp-dash .work-queue-whatsapp-name {
        display: block; font-size: 10px; font-weight: 700; color: #111827;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .emp-dash .work-queue-whatsapp-phone {
        display: block; font-size: 9px; color: #64748b;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .emp-dash .work-queue-whatsapp-card-meta {
        display: flex; align-items: center; gap: 3px; flex-shrink: 0;
    }
    .emp-dash .work-queue-whatsapp-time {
        font-size: 9px; color: #94a3b8; white-space: nowrap;
    }
    .emp-dash .work-queue-whatsapp-unread {
        min-width: 15px; height: 15px; padding: 0 4px; border-radius: 999px;
        background: var(--wq-tone, var(--wq-brand)); color: #fff; font-size: 9px; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .emp-dash .work-queue-whatsapp-handler {
        font-size: 8px; font-weight: 600; color: var(--wq-muted); background: #eef0f3;
        border-radius: 999px; padding: 1px 5px; white-space: nowrap;
    }
    .emp-dash .work-queue-whatsapp-message {
        margin: 0; font-size: 9px; color: #475569; line-height: 1.35;
        display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;
    }
    .emp-dash .work-queue-whatsapp-tags {
        display: flex; flex-wrap: wrap; gap: 3px; margin-top: 3px;
    }
    .emp-dash .work-queue-whatsapp-status,
    .emp-dash .work-queue-whatsapp-tag {
        font-size: 8px; font-weight: 600; border-radius: 999px; padding: 1px 5px; white-space: nowrap;
    }
    .emp-dash .work-queue-whatsapp-status {
        color: var(--wq-tone-header-text, var(--wq-brand));
        background: var(--wq-tone-soft, var(--wq-brand-soft));
    }
    .emp-dash .work-queue-whatsapp-tag {
        color: var(--tag-color, #64748b);
        background: color-mix(in srgb, var(--tag-color, #64748b) 14%, #fff);
        border: 1px solid color-mix(in srgb, var(--tag-color, #64748b) 28%, #fff);
    }
    .emp-dash .section-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin-bottom: 12px; }
    .emp-dash .progress-section { margin-bottom: 1rem; }
    .emp-dash .progress-shell {
        background: #fff; border: 1px solid var(--wq-border); border-radius: 10px;
        border-top: 3px solid var(--wq-brand); overflow: hidden; margin-bottom: 0.75rem;
    }
    .emp-dash .progress-shell-header {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px;
        padding: 8px 12px; background: var(--wq-surface); border-bottom: 1px solid #eef0f3;
    }
    .emp-dash .progress-shell-title {
        font-size: 13px; font-weight: 700; color: var(--wq-text); margin: 0;
        text-transform: uppercase; letter-spacing: .03em;
    }
    .emp-dash .progress-shell-sub {
        display: block; font-size: 9px; color: var(--wq-muted); margin-top: 1px; line-height: 1.2;
    }
    .emp-dash .progress-view-report-btn {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10px; font-weight: 600; text-decoration: none;
        padding: 4px 10px; border-radius: 6px;
        color: var(--wq-brand); border: 1px solid #c7cbe0; background: var(--wq-brand-soft);
        transition: background .12s, color .12s, border-color .12s;
    }
    .emp-dash .progress-view-report-btn:hover {
        color: #fff; background: var(--wq-brand); border-color: var(--wq-brand);
    }
    .emp-dash .progress-view-report-btn .material-symbols-outlined { font-size: 14px; }
    .emp-dash .progress-shell-body { padding: 8px 10px 10px; }
    .emp-dash .progress-shell-body > .row { align-items: stretch; margin: 0; }
    .emp-dash .progress-shell-body > .row > [class*="col-"] {
        display: flex; flex-direction: column; padding-top: 0; padding-bottom: 0;
    }
    .emp-dash .progress-card--today {
        --pc-tone: #2563eb; --pc-tone-soft: #eff6ff; --pc-tone-border: #bfdbfe; --pc-tone-text: #1e40af;
    }
    .emp-dash .progress-card--month {
        --pc-tone: #7c3aed; --pc-tone-soft: #f5f3ff; --pc-tone-border: #ddd6fe; --pc-tone-text: #5b21b6;
    }
    .emp-dash .progress-card--contribution {
        --pc-tone: #0891b2; --pc-tone-soft: #ecfeff; --pc-tone-border: #a5f3fc; --pc-tone-text: #0e7490;
    }
    .emp-dash .progress-card--today,
    .emp-dash .progress-card--month,
    .emp-dash .progress-card--contribution {
        border-top: 2px solid var(--pc-tone);
    }
    .emp-dash .progress-card--today .progress-card-header,
    .emp-dash .progress-card--month .progress-card-header,
    .emp-dash .progress-card--contribution .progress-card-header {
        background: var(--pc-tone-soft);
        border-bottom-color: var(--pc-tone-border);
        height: 36px;
        min-height: 36px;
        max-height: 36px;
        padding: 0 8px;
    }
    .emp-dash .progress-card--today .progress-card-title,
    .emp-dash .progress-card--month .progress-card-title,
    .emp-dash .progress-card--contribution .progress-card-title {
        color: var(--pc-tone-text);
    }
    .emp-dash .progress-card--today .progress-summary-badge.is-active {
        background: #fff; color: var(--pc-tone);
        border: 1px solid var(--pc-tone-border);
    }
    .emp-dash .progress-card--contribution .progress-tab.active {
        background: var(--pc-tone); border-color: var(--pc-tone); color: #fff;
    }
    .emp-dash .progress-card--contribution .contribution-row-label .material-symbols-outlined,
    .emp-dash .progress-card--contribution .contribution-row-pct {
        color: var(--pc-tone);
    }
    .emp-dash .progress-card--contribution .contribution-bar > span {
        background: var(--pc-tone);
    }
    .emp-dash .progress-stat-tile--compact.tone-lead { --pt-soft: #eff6ff; --pt-border: #bfdbfe; --pt-text: #1e40af; }
    .emp-dash .progress-stat-tile--compact.tone-booking { --pt-soft: #ecfeff; --pt-border: #a5f3fc; --pt-text: #0e7490; }
    .emp-dash .progress-stat-tile--compact.tone-task { --pt-soft: #f5f3ff; --pt-border: #ddd6fe; --pt-text: #5b21b6; }
    .emp-dash .progress-stat-tile--compact.tone-brand { --pt-soft: #eef0f6; --pt-border: #c7cbe0; --pt-text: #43466e; }
    .emp-dash .progress-stat-tile--compact.tone-outbound { --pt-soft: #fffbeb; --pt-border: #fde68a; --pt-text: #b45309; }
    .emp-dash .progress-stat-tile--compact.tone-whatsapp { --pt-soft: #f0fdf4; --pt-border: #bbf7d0; --pt-text: #15803d; }
    .emp-dash .progress-stat-tile--compact.tone-whatsapp-closed { --pt-soft: #f0fdfa; --pt-border: #99f6e4; --pt-text: #0f766e; }
    .emp-dash .progress-stat-tile--compact.tone-sync { --pt-soft: #eef2ff; --pt-border: #c7d2fe; --pt-text: #4338ca; }
    .emp-dash .progress-stat-tile--compact.tone-good { --pt-soft: #f0fdf4; --pt-border: #bbf7d0; --pt-text: #15803d; }
    .emp-dash .progress-stat-tile--compact.tone-warn { --pt-soft: #fef2f2; --pt-border: #fecaca; --pt-text: #b91c1c; }
    .emp-dash .progress-stat-tile--compact.tone-neutral { --pt-soft: #f8fafc; --pt-border: #e2e8f0; --pt-text: #475569; }
    .emp-dash .progress-stat-tile--compact[class*="tone-"]:not(.is-zero) {
        background: var(--pt-soft);
        border-color: var(--pt-border);
    }
    .emp-dash .progress-stat-tile--compact[class*="tone-"]:not(.is-zero) .progress-stat-icon,
    .emp-dash .progress-stat-tile--compact[class*="tone-"]:not(.is-zero) .progress-stat-val {
        color: var(--pt-text);
    }
    .emp-dash .progress-stat-tile--compact.is-zero {
        background: #f8fafc;
        border-color: #e5e7eb;
        opacity: .82;
    }
    .emp-dash .progress-stat-tile--compact.is-zero .progress-stat-icon,
    .emp-dash .progress-stat-tile--compact.is-zero .progress-stat-val {
        color: var(--wq-muted);
    }
    .emp-dash .progress-stat-tile--compact.progress-stat-tile--spacer {
        visibility: hidden;
        pointer-events: none;
        border-color: transparent;
        background: transparent;
    }
    .emp-dash .progress-stat-tile--compact .progress-stat-sub {
        font-size: 8px;
        color: var(--wq-hot);
        margin: 0;
        line-height: 1.15;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 1;
        overflow: hidden;
    }
    .emp-dash .progress-section-head { margin-bottom: 10px; }
    .emp-dash .progress-section-title {
        font-size: 13px; font-weight: 700; color: var(--wq-text); margin: 0;
        text-transform: uppercase; letter-spacing: .03em;
    }
    .emp-dash .progress-card {
        background: #fff; border: 1px solid var(--wq-border); border-radius: 10px;
        overflow: hidden; display: flex; flex-direction: column;
        width: 100%;
    }
    .emp-dash .progress-card--compact {
        flex: 1 1 auto;
        height: 100%;
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }
    .emp-dash .progress-card-header {
        display: flex; align-items: center; justify-content: space-between; gap: 6px;
        padding: 6px 10px; background: var(--wq-surface); border-bottom: 1px solid #eef0f3;
        box-sizing: border-box;
    }
    .emp-dash .progress-card-header-main {
        flex: 1 1 auto; min-width: 0;
    }
    .emp-dash .progress-card-header-action {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        min-height: 24px;
    }
    .emp-dash .progress-card-header-action--spacer {
        min-width: 72px;
        visibility: hidden;
    }
    .emp-dash .progress-card-title {
        display: block; font-size: 10px; font-weight: 700; color: var(--wq-text); line-height: 1.2;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .emp-dash .progress-card-sub {
        display: none;
    }
    .emp-dash .progress-card--compact .progress-card-title { font-size: 10px; }
    .emp-dash .progress-summary-badge {
        flex-shrink: 0; min-width: 24px; height: 24px; border-radius: 999px;
        background: #eef0f3; color: var(--wq-muted);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 700;
    }
    .emp-dash .progress-summary-badge.is-active {
        background: var(--wq-brand-soft); color: var(--wq-brand);
    }
    .emp-dash .progress-card-body {
        padding: 6px 8px 8px;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }
    .emp-dash .progress-card--compact .progress-card-body {
        padding: 6px 8px 8px;
        min-height: 240px;
    }
    .emp-dash .progress-card--today .progress-stat-grid--compact,
    .emp-dash .progress-card--month .progress-stat-grid--compact {
        flex: 1 1 auto;
        height: 100%;
        min-height: 0;
        grid-auto-rows: 1fr;
        gap: 5px;
        align-content: stretch;
    }
    .emp-dash .progress-stat-grid,
    .emp-dash .progress-stat-grid--compact {
        display: grid;
    }
    .emp-dash .progress-stat-grid--compact {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 5px;
    }
    .emp-dash .progress-card--contribution .contribution-panel.active {
        flex: 1 1 auto;
        align-content: start;
    }
    .emp-dash .progress-stat-tile--compact {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 5px;
        padding: 6px 8px;
        min-height: 48px;
        height: 100%;
        border: 1px solid var(--wq-border);
        border-radius: 6px;
        background: #fff;
        min-width: 0;
        box-sizing: border-box;
    }
    .emp-dash .progress-stat-tile--compact .progress-stat-icon {
        flex-shrink: 0;
        font-size: 13px;
        line-height: 1;
        margin: 0;
    }
    .emp-dash .progress-stat-tile--compact .progress-stat-tile-content {
        display: flex;
        flex-direction: column;
        gap: 0;
        min-width: 0;
        flex: 1 1 auto;
    }
    .emp-dash .progress-stat-tile--compact .progress-stat-val {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.15;
        word-break: break-word;
    }
    .emp-dash .progress-stat-tile--compact .progress-stat-label {
        font-size: 9px;
        font-weight: 600;
        line-height: 1.2;
        color: var(--wq-muted);
    }
    @media (max-width: 576px) {
        .emp-dash .progress-stat-grid--compact { grid-template-columns: 1fr; }
    }
    .emp-dash .progress-stat-tile:not(.progress-stat-tile--compact) {
        display: flex; flex-direction: column; gap: 2px;
        border: 1px solid var(--wq-border); border-radius: 8px; padding: 8px 9px;
        background: #fff; text-decoration: none; color: inherit;
        transition: border-color .12s, background .12s, box-shadow .12s;
    }
    .emp-dash a.progress-stat-tile:hover {
        border-color: #c7cbe0; background: var(--wq-brand-soft); color: inherit;
        box-shadow: 0 1px 2px rgba(67, 70, 110, .08);
    }
    .emp-dash .progress-stat-tile.is-zero { opacity: .72; }
    .emp-dash .progress-stat-tile.is-zero .progress-stat-val { color: var(--wq-muted); }
    .emp-dash .progress-stat-icon {
        font-size: 15px; color: var(--wq-brand); line-height: 1;
    }
    .emp-dash .progress-stat-tile.is-zero .progress-stat-icon { color: #94a3b8; }
    .emp-dash .progress-stat-val {
        font-size: 16px; font-weight: 700; color: var(--wq-brand); line-height: 1.2;
    }
    .emp-dash .progress-stat-label {
        font-size: 9px; font-weight: 600; color: var(--wq-muted); line-height: 1.3;
    }
    .emp-dash .progress-stat-sub {
        font-size: 9px; color: var(--wq-hot); margin-top: 2px; line-height: 1.2;
    }
    .emp-dash .progress-tabs {
        display: inline-flex; align-items: center; gap: 3px; flex-shrink: 0;
        min-height: 24px;
    }
    .emp-dash .progress-tab {
        border: 1px solid var(--wq-border); background: #fff; color: var(--wq-muted);
        padding: 2px 7px; border-radius: 999px; font-size: 9px; font-weight: 600; cursor: pointer;
        line-height: 1.2;
    }
    .emp-dash .progress-tab.active {
        background: var(--wq-brand); color: #fff; border-color: var(--wq-brand);
    }
    .emp-dash .contribution-row { margin-bottom: 6px; }
    .emp-dash .contribution-row:last-child { margin-bottom: 0; }
    .emp-dash .contribution-row-head {
        display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 1px;
    }
    .emp-dash .contribution-row-label {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 9px; font-weight: 600; color: var(--wq-text); min-width: 0; line-height: 1.2;
    }
    .emp-dash .contribution-row-label .material-symbols-outlined {
        font-size: 13px; color: var(--wq-brand); flex-shrink: 0;
    }
    .emp-dash .contribution-row-pct {
        font-size: 10px; font-weight: 700; color: var(--wq-brand); flex-shrink: 0;
    }
    .emp-dash .contribution-row-meta {
        font-size: 8px; color: var(--wq-muted); margin-bottom: 3px; line-height: 1.2;
    }
    .emp-dash .contribution-bar {
        height: 4px; background: #eef0f3; border-radius: 999px; overflow: hidden;
    }
    .emp-dash .contribution-bar > span {
        display: block; height: 100%; background: var(--wq-brand); border-radius: 999px;
    }
    .emp-dash .progress-empty {
        text-align: center; color: var(--wq-muted); font-size: 10px; padding: 8px 6px;
    }
    .emp-dash .card > .card-header { background: #43466e; color: #fff; border: 0; }
    .emp-dash .card > .card-header.light { background: #f8fafc; color: #1f2937; border-bottom: 1px solid #e5e7eb; }
    .emp-dash .contribution-panel { display: none; }
    .emp-dash .contribution-panel.active { display: block; }
    .emp-dash :target { scroll-margin-top: 80px; }
    .emp-dash #priority-inbox { scroll-margin-top: 88px; }
    .emp-dash #what-needs-attention { scroll-margin-top: 88px; }
</style>
@endpush

@section('content')
@php
    $monthly = $employeeData['monthly'] ?? [];
    $contributionVsAll = $employeeData['contribution_vs_all'] ?? [];
    $contributionToday = $contributionVsAll['today'] ?? (is_array($contributionVsAll) && ! isset($contributionVsAll['today']) ? $contributionVsAll : []);
    $contributionMonthly = $contributionVsAll['monthly'] ?? [];
    $todayDone = $employeeData['today_done'] ?? [];
    $workQueue = $employeeData['work_queue'] ?? [];
    $dashboardEmployees = $employeeData['dashboard_employees'] ?? [];
    $defaultEmployeeId = $employeeData['default_employee_id'] ?? '';
    $defaultDashboardScope = $employeeData['default_dashboard_scope'] ?? '__all__';
@endphp

<div class="main-content emp-dash">
    <div class="container-fluid">
        @if(! is_admin_employee())
            <div class="emp-dash-topbar">
                @include('adminmodule::partials._admin-dashboard-switcher', ['active' => 'work'])
                @if($dashboardEmployees !== [])
                    <div class="emp-dash-employee-filter">
                        <label class="visually-hidden" for="dashboard-employee-select">{{ translate('Select_employee') }}</label>
                        <select id="dashboard-employee-select"
                                class="form-select form-select-sm js-dashboard-employee-select"
                                data-default-scope="{{ $defaultDashboardScope }}">
                            <option value="__all__" selected>{{ translate('All') }}</option>
                            @foreach($dashboardEmployees as $employee)
                                <option value="{{ $employee['id'] }}">{{ $employee['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        @endif

        {{-- Work queue: pending vs new to pick up --}}
        <div id="what-needs-attention" class="mb-3">
            <div class="work-queue-split">
                @foreach($workQueue as $laneKey => $lane)
                    <div class="work-queue-lane work-queue-lane--{{ $laneKey }}" id="work-queue-{{ $laneKey }}">
                        <div class="lane-header">
                            <h5 class="lane-title">{{ $lane['title'] ?? '' }}</h5>
                        </div>

                        <div class="lane-boxes-row" id="priority-inbox-{{ $laneKey }}">
                            @foreach($lane['boxes'] ?? [] as $box)
                                @if(! empty($box['requires_permission']) && ! Gate::check($box['requires_permission']))
                                    @continue
                                @endif
                                @include('adminmodule::partials._employee-work-queue-box', ['box' => $box])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @php
            $progressScopes = $employeeData['progress_scopes'] ?? [];
        @endphp

        @if($progressScopes !== [])
            <div id="section-progress" class="js-progress-scope-wrapper">
                @foreach($progressScopes as $scopeId => $scope)
                    <div class="js-progress-scope-panel {{ $scopeId !== '__all__' ? 'd-none' : '' }}"
                         data-scope-id="{{ $scopeId }}">
                        @include('adminmodule::partials._employee-progress', [
                            'todayDone' => $scope['today_done'] ?? [],
                            'monthly' => $scope['monthly'] ?? [],
                            'contributionToday' => $scope['contribution_today'] ?? [],
                            'contributionMonthly' => $scope['contribution_monthly'] ?? [],
                            'progressTitle' => $scope['title'] ?? translate('Team_Progress'),
                            'progressSubtitle' => $scope['subtitle'] ?? translate('Team_progress_sub'),
                            'monthTitle' => $scope['month_title'] ?? translate('Team_Month_Report'),
                            'contributionTitle' => $scope['contribution_title'] ?? translate('Team_activity_by_employee'),
                            'contributionSubtitle' => $scope['contribution_subtitle'] ?? translate('Team_activity_by_employee_sub'),
                            'viewReportUrl' => $scope['view_report_url'] ?? route('admin.report.daily-employee'),
                        ])
                    </div>
                @endforeach
            </div>
        @elseif($showEmployeeProgress ?? is_admin_employee())
            <div id="section-progress">
                @include('adminmodule::partials._employee-progress', [
                    'todayDone' => $todayDone,
                    'monthly' => $monthly,
                    'contributionToday' => $contributionToday,
                    'contributionMonthly' => $contributionMonthly,
                ])
            </div>
        @endif

    </div>
</div>
@endsection

@push('script')
<script>
    'use strict';

    document.querySelectorAll('[data-tabs]').forEach(function (group) {
        var container = group.closest('.work-queue-box, .card, .progress-card, .progress-shell');
        group.querySelectorAll('.work-queue-tab, .progress-tab, .tab-btn, .tab-btn-light').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-tab');
                group.querySelectorAll('.work-queue-tab, .progress-tab, .tab-btn, .tab-btn-light').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                container.querySelectorAll('[data-panel]').forEach(function (panel) {
                    panel.classList.toggle('active', panel.getAttribute('data-panel') === tab);
                });
            });
        });
    });

    function activateWorkQueueTab(box, tabKey) {
        var tabsGroup = box.querySelector('[data-tabs]');
        if (! tabsGroup) {
            return;
        }

        var targetTab = box.querySelector('[data-tab$="-' + tabKey + '"]');
        if (! targetTab) {
            return;
        }

        tabsGroup.querySelectorAll('.work-queue-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn === targetTab);
        });

        box.querySelectorAll('[data-panel]').forEach(function (panel) {
            panel.classList.toggle('active', panel.getAttribute('data-panel') === targetTab.getAttribute('data-tab'));
        });
    }

    function formatEmployeeFooterLabel(template, employeeName) {
        return template.replace(/:name/g, employeeName);
    }

    function setDashboardScope(scopeValue) {
        var select = document.getElementById('dashboard-employee-select');
        var isAll = scopeValue === '__all__' || scopeValue === '';
        var employeeName = '';

        if (select) {
            if (select.value !== scopeValue) {
                select.value = scopeValue;
            }

            if (! isAll && select.selectedIndex >= 0) {
                employeeName = select.options[select.selectedIndex].text;
            }
        }

        document.querySelectorAll('[data-has-employee-tab]').forEach(function (box) {
            var footerEmployeeLink = box.querySelector('.js-work-queue-employee-footer-link');
            var employeeTabBtn = box.querySelector('.js-work-queue-employee-tab');
            var tabCountEl = box.querySelector('.js-work-queue-employee-tab-count');
            var labelEl = box.querySelector('.js-work-queue-employee-tab-label');
            var defaultLabel = labelEl ? (labelEl.getAttribute('data-default-label') || 'Employee') : 'Employee';

            if (isAll) {
                activateWorkQueueTab(box, 'all');

                if (employeeTabBtn) {
                    employeeTabBtn.classList.add('d-none');
                }

                if (labelEl) {
                    labelEl.textContent = defaultLabel;
                }

                if (footerEmployeeLink) {
                    footerEmployeeLink.classList.add('d-none');
                    footerEmployeeLink.textContent = footerEmployeeLink.getAttribute('data-default-label') || '';
                }

                box.querySelectorAll('.js-work-queue-employee-panel').forEach(function (panel) {
                    panel.classList.add('d-none');
                });

                return;
            }

            if (employeeTabBtn) {
                employeeTabBtn.classList.remove('d-none');
            }

            activateWorkQueueTab(box, 'employee');

            if (labelEl) {
                labelEl.textContent = employeeName || defaultLabel;
            }

            if (footerEmployeeLink) {
                footerEmployeeLink.classList.remove('d-none');
                var footerTemplate = footerEmployeeLink.getAttribute('data-employee-label-template');
                var footerDefaultLabel = footerEmployeeLink.getAttribute('data-default-label') || '';
                footerEmployeeLink.textContent = footerTemplate
                    ? formatEmployeeFooterLabel(footerTemplate, employeeName || defaultLabel)
                    : footerDefaultLabel;
            }

            var activePanel = null;

            box.querySelectorAll('.js-work-queue-employee-panel').forEach(function (panel) {
                var isActive = panel.getAttribute('data-employee-id') === scopeValue;
                panel.classList.toggle('d-none', ! isActive);
                if (isActive) {
                    activePanel = panel;
                }
            });

            if (activePanel) {
                var total = Number(activePanel.getAttribute('data-total') || 0);
                var viewAllUrl = activePanel.getAttribute('data-view-all-url') || '#';

                if (tabCountEl) {
                    tabCountEl.textContent = '(' + total + ')';
                }

                if (footerEmployeeLink) {
                    footerEmployeeLink.setAttribute('href', viewAllUrl);
                }
            }
        });

        document.querySelectorAll('.js-progress-scope-panel').forEach(function (panel) {
            var panelScope = panel.getAttribute('data-scope-id') || '';
            var showPanel = isAll ? panelScope === '__all__' : panelScope === scopeValue;
            panel.classList.toggle('d-none', ! showPanel);
        });

        try {
            localStorage.setItem('admin_dashboard_scope', scopeValue);
        } catch (error) {}
    }

    document.querySelectorAll('.js-dashboard-employee-select').forEach(function (select) {
        select.addEventListener('change', function () {
            setDashboardScope(select.value);
        });
        select.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    (function restoreDashboardScope() {
        var select = document.getElementById('dashboard-employee-select');
        if (! select) {
            return;
        }

        var storedScope = null;
        try {
            storedScope = localStorage.getItem('admin_dashboard_scope')
                || localStorage.getItem('admin_dashboard_employee_id');
        } catch (error) {}

        var scopeValue = storedScope || select.getAttribute('data-default-scope') || '__all__';
        setDashboardScope(scopeValue);
    })();
</script>
@endpush
