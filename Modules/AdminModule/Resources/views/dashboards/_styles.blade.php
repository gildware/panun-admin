<style>
    .ws-dash { display: flex; flex-direction: column; gap: 12px; }
    .ws-dash-head h1 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -.02em; color: #111827; }
    .ws-dash-head p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
    .ws-dash-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
    .ws-dash-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .ws-dash-split { display: grid; grid-template-columns: 1.2fr .8fr; gap: 10px; align-items: start; }
    .ws-dash-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; align-items: stretch; }
    .ws-dash-box {
        --box-accent: #43466e;
        --box-soft: #f8fafc;
        --box-line: #e5e7eb;
        height: 300px;
        min-height: 300px;
        max-height: 300px;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-top: 3px solid var(--box-accent);
        border-radius: 10px;
        overflow: hidden;
    }
    .ws-dash-box--birthday { --box-accent: #c2410c; --box-soft: #fff7ed; --box-line: #fed7aa; }
    .ws-dash-box--holiday { --box-accent: #1d4ed8; --box-soft: #eff6ff; --box-line: #bfdbfe; }
    .ws-dash-box-head {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        flex-shrink: 0; min-height: 40px; padding: 8px 12px;
        background: var(--box-soft); border-bottom: 1px solid var(--box-line);
    }
    .ws-dash-box-head h2 {
        display: flex; align-items: center; gap: 6px;
        margin: 0; font-size: 13px; font-weight: 700; color: #111827;
    }
    .ws-dash-box-head .material-icons { font-size: 18px; color: var(--box-accent); }
    .ws-dash-box-count {
        font-size: 11px; font-weight: 700; line-height: 1;
        padding: 4px 8px; border-radius: 999px;
        background: #fff; color: var(--box-accent); border: 1px solid var(--box-line);
    }
    .ws-dash-switch {
        flex: 0 0 auto; display: inline-flex; gap: 2px; padding: 2px;
        border-radius: 999px; background: #fff; border: 1px solid var(--box-line);
    }
    .ws-dash-box .ws-dash-switch button {
        appearance: none; margin: 0; border: 0; box-shadow: none;
        background: transparent; color: #64748b;
        font-size: 11px; font-weight: 700; line-height: 1.2;
        padding: 4px 8px; border-radius: 999px; cursor: pointer; white-space: nowrap;
    }
    .ws-dash-box .ws-dash-switch button span { font-weight: 700; opacity: .72; }
    .ws-dash-box .ws-dash-switch button.is-on {
        background: var(--box-accent); color: #fff;
    }
    .ws-dash-box .ws-dash-switch button.is-on span { opacity: .85; }
    .ws-dash-box-body[hidden] { display: none !important; }
    .ws-dash-box-body {
        flex: 1 1 auto; min-height: 0; overflow: auto;
        display: flex; flex-direction: column;
    }
    .ws-dash-box-list { margin: 0; padding: 6px; list-style: none; display: flex; flex-direction: column; gap: 2px; }
    .ws-dash-box-list li {
        display: flex; align-items: center; gap: 10px;
        padding: 7px 8px; border-radius: 8px;
    }
    .ws-dash-box-list li:hover { background: #f8fafc; }
    .ws-dash-box-list li.is-today { background: var(--box-soft); }
    .ws-dash-date {
        flex: 0 0 42px; width: 42px; text-align: center;
        padding: 4px 0 3px; border-radius: 8px;
        background: var(--box-soft); border: 1px solid var(--box-line); line-height: 1.05;
    }
    .ws-dash-date b { display: block; font-size: 15px; font-weight: 750; color: #111827; }
    .ws-dash-date i {
        display: block; margin-top: 1px;
        font-style: normal; font-size: 10px; font-weight: 700;
        letter-spacing: .04em; text-transform: uppercase; color: var(--box-accent);
    }
    .ws-dash-date small { display: block; margin-top: 1px; font-size: 9px; color: #94a3b8; }
    .ws-dash-box-copy { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
    .ws-dash-box-copy strong {
        font-size: 13px; font-weight: 650; color: #111827;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .ws-dash-box-copy em {
        font-style: normal; font-size: 11px; color: #94a3b8;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .ws-dash-pill {
        flex: 0 0 auto; font-size: 11px; font-weight: 650; color: #64748b;
        background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 999px;
        padding: 3px 8px; white-space: nowrap;
    }
    .ws-dash-box-list li.is-today .ws-dash-pill {
        color: var(--box-accent); background: #fff; border-color: var(--box-line);
    }
    .ws-dash-box .ws-dash-empty {
        flex: 1 1 auto; display: flex; align-items: center; justify-content: center;
        margin: 0; padding: 24px 16px; text-align: center;
    }
    .ws-dash-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-top: 3px solid var(--bs-primary, #25274d);
        border-radius: 10px;
        padding: 12px 14px;
        min-width: 0;
    }
    .ws-dash-card h2 { margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #374151; }
    .ws-dash-stat .label { color: #64748b; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .ws-dash-stat .value { margin-top: 6px; font-size: 24px; font-weight: 750; line-height: 1.1; color: #111827; }
    .ws-dash-stat .sub { margin-top: 4px; color: #64748b; font-size: 12px; }
    .ws-dash-list { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 8px; }
    .ws-dash-list li, .ws-dash-row {
        display: flex; align-items: baseline; justify-content: space-between; gap: 10px;
        font-size: 13px; color: #1f2937;
    }
    .ws-dash-list li span { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
    .ws-dash-list li em { font-style: normal; color: #94a3b8; font-size: 12px; }
    .ws-dash-list li.is-today { background: #fff7ed; border-radius: 8px; margin: 0 -6px; padding: 6px 6px; }
    .ws-dash-list li.is-today small { color: #c2410c; font-weight: 700; }
    .ws-dash-list small, .ws-dash-row small { color: #64748b; white-space: nowrap; }
    .ws-dash-links { display: flex; flex-wrap: wrap; gap: 6px; }
    .ws-dash-links a {
        display: inline-flex; align-items: center;
        border: 1px solid #e5e7eb; border-radius: 999px;
        padding: 4px 10px; font-size: 12px; font-weight: 650; color: var(--bs-primary, #25274d);
        text-decoration: none; background: #fff;
    }
    .ws-dash-links a:hover { background: var(--bs-primary-light-bg, #f8fafc); }
    .ws-dash-guide { display: flex; flex-direction: column; gap: 8px; min-height: 100%; }
    .ws-dash-guide p { margin: 0; color: #64748b; font-size: 12px; line-height: 1.4; flex: 1; }
    .ws-dash-guide a {
        align-self: flex-start; margin-top: 4px;
        color: #fff; background: var(--bs-primary, #25274d);
        border-radius: 999px; padding: 5px 12px; font-size: 12px; font-weight: 700; text-decoration: none;
    }
    .ws-dash-empty { margin: 0; color: #94a3b8; font-size: 13px; }
    @media (max-width: 1100px) {
        .ws-dash-grid, .ws-dash-grid--3, .ws-dash-split, .ws-dash-pair { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 700px) {
        .ws-dash-grid, .ws-dash-grid--3, .ws-dash-split, .ws-dash-pair { grid-template-columns: 1fr; }
    }
</style>
