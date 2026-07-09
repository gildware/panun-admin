<style>
    .service-overview-readonly,
    .service-overview-mobile-preview__screen {
        font-size: 13px;
        line-height: 1.45;
        color: #1e293b;
    }
    .service-overview-readonly { padding: 4px 0; }
    .sov-intro { margin: 0 0 12px; color: #334155; }
    .sov-title { font-size: 15px; font-weight: 700; margin: 16px 0 8px; color: #0f172a; }
    .sov-top-icons { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 4px; }
    @media (min-width: 768px) {
        .sov-top-icons { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .sov-top-icon {
        display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 14px;
        border: 1px solid color-mix(in srgb, var(--sov-accent) 20%, white);
        background: color-mix(in srgb, var(--sov-accent) 8%, white);
    }
    .sov-top-icon .material-icons { font-size: 18px; color: var(--sov-accent); }
    .sov-process-row { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
    .sov-process-step { flex: 0 0 130px; text-align: center; }
    .sov-process-step img, .sov-process-placeholder {
        width: 130px; height: 86px; border-radius: 12px; object-fit: cover; display: block; margin: 0 auto 6px;
    }
    .sov-process-placeholder {
        background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #3b82f6;
    }
    .sov-step-no {
        display: inline-flex; width: 24px; height: 24px; align-items: center; justify-content: center;
        border-radius: 999px; background: #25274d; color: #fff; font-size: 11px; font-weight: 700;
    }
    .sov-step-label { display: block; margin-top: 6px; font-weight: 600; font-size: 12px; }
    .sov-step-desc { display: block; margin-top: 4px; font-size: 11px; color: #64748b; line-height: 1.35; }
    .sov-included-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    @media (min-width: 992px) {
        .sov-included-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .sov-included-item {
        border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 6px; text-align: center; min-height: 78px;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
    }
    .sov-included-item .material-icons { font-size: 18px; color: #64748b; }
    .sov-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .sov-chip {
        display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border-radius: 999px;
        background: rgba(37, 39, 77, 0.07); color: #25274d; font-weight: 600; font-size: 12px;
    }
    .sov-chip .material-icons { font-size: 14px; }
    .sov-info-stack { display: flex; flex-direction: column; gap: 8px; }
    .sov-info-card { border-radius: 14px; padding: 12px; }
    .sov-info-card--good { background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.18); }
    .sov-info-card--bad { background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.18); }
    .sov-info-card--neutral { background: rgba(100, 116, 139, 0.06); border: 1px solid rgba(100, 116, 139, 0.18); }
    .sov-info-head { display: flex; align-items: center; gap: 6px; font-weight: 700; margin-bottom: 8px; font-size: 13px; }
    .sov-info-card--good .sov-info-head, .sov-info-card--good .sov-info-line .material-icons { color: #16a34a; }
    .sov-info-card--bad .sov-info-head, .sov-info-card--bad .sov-info-line .material-icons { color: #dc2626; }
    .sov-info-card--neutral .sov-info-head, .sov-info-card--neutral .sov-info-line .sov-info-bullet { color: #64748b; }
    .sov-info-line { display: flex; gap: 6px; margin-bottom: 6px; }
    .sov-info-line .material-icons { font-size: 14px; margin-top: 1px; }
    .sov-info-bullet {
        width: 6px; height: 6px; border-radius: 999px; background: currentColor;
        margin-top: 6px; flex: 0 0 6px;
    }
    .sov-why-row { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; }
    .sov-why-card {
        flex: 0 0 180px; border-radius: 14px; padding: 12px;
        background: color-mix(in srgb, var(--sov-accent) 10%, white);
        border: 1px solid color-mix(in srgb, var(--sov-accent) 18%, white);
    }
    .sov-why-card .material-icons { color: var(--sov-accent); font-size: 20px; }
    .sov-why-card strong { display: block; margin: 8px 0 4px; font-size: 13px; }
    .sov-why-card span:last-child { color: #64748b; font-size: 12px; }
    .service-detail-overview-empty {
        border: 1px dashed var(--bs-border-color);
        border-radius: 12px;
        padding: 1.25rem;
        text-align: center;
        color: var(--bs-secondary-color);
        font-size: 13px;
    }
</style>
