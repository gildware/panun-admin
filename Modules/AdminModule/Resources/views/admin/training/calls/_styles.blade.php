<style>
    .call-picks { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .call-picks label {
        display: flex; gap: 8px; align-items: flex-start; margin: 0; border: 1px solid var(--lms-line);
        border-radius: 8px; padding: 8px 10px; background: #fff; font-weight: 500; cursor: pointer;
    }
    .call-picks input { margin-top: 2px; }
    .call-picks b { display: block; font-size: .82rem; color: var(--lms-ink); }
    .call-picks span { display: block; color: var(--lms-muted); font-size: .72rem; font-weight: 500; line-height: 1.35; }
    .call-layout { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 12px; align-items: stretch; }
    .call-phone {
        background: #111827; color: #fff; border-radius: 16px; padding: 22px 18px 16px;
        display: flex; flex-direction: column; min-height: 460px;
    }
    .call-kicker { color: #9ca3af; font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; font-weight: 650; }
    .call-phone h2 { color: #fff; font-size: 1.25rem; margin: 8px 0 0; text-transform: none; }
    .call-avatar {
        width: 76px; height: 76px; border-radius: 99px; margin: 22px auto 10px; display: grid; place-items: center;
        background: #25274d; font-size: 1.4rem; font-weight: 700;
    }
    .call-avatar.is-talking { box-shadow: 0 0 0 10px rgba(255,255,255,.08); }
    .call-avatar.is-listen { box-shadow: 0 0 0 10px rgba(34,197,94,.18); }
    .call-status {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        margin: 10px 0 12px; min-height: 1.5rem; font-size: .8rem; font-weight: 650; color: #e5e7eb;
    }
    .call-status::before {
        content: ''; width: 8px; height: 8px; border-radius: 99px; background: #9ca3af; flex: 0 0 auto;
    }
    .call-status.is-listen { color: #86efac; }
    .call-status.is-listen::before { background: #22c55e; animation: call-pulse 1.3s ease-out infinite; }
    .call-status.is-talk { color: #fff; }
    .call-status.is-talk::before { background: #fff; animation: call-pulse 1s ease-out infinite; }
    .call-status.is-wait::before {
        width: 12px; height: 12px; border: 2px solid rgba(255,255,255,.28); border-top-color: #fff;
        background: transparent; animation: call-spin .7s linear infinite;
    }
    .call-bubble.is-pending { opacity: .72; }
    @keyframes call-pulse {
        0% { box-shadow: 0 0 0 0 rgba(255,255,255,.45); }
        100% { box-shadow: 0 0 0 8px rgba(255,255,255,0); }
    }
    @keyframes call-spin { to { transform: rotate(360deg); } }
    .call-timer { text-align: center; font-variant-numeric: tabular-nums; font-size: 1.35rem; letter-spacing: .04em; }
    .call-note { margin: 0 0 10px; text-align: center; color: #fcd34d; font-size: .72rem; line-height: 1.35; }
    .call-langs { display: flex; gap: 6px; justify-content: center; margin: 0 0 14px; }
    .call-langs button { border: 1px solid rgba(255,255,255,.22); background: transparent; color: #e5e7eb; border-radius: 999px; padding: 5px 10px; font-size: .72rem; font-weight: 650; }
    .call-langs button.is-on { background: #fff; color: #111827; }
    .call-phone .btn-end {
        width: 100%; height: 2.4rem; border: 0; border-radius: 999px; background: #b91c1c; color: #fff; font-weight: 700;
    }
    .call-phone .btn-end:disabled { opacity: .55; }
    .call-log { background: #fff; border: 1px solid var(--lms-line); border-radius: 12px; display: flex; flex-direction: column; min-height: 460px; }
    .call-lines { flex: 1; overflow: auto; max-height: 520px; padding: 14px; display: flex; flex-direction: column; gap: 8px; }
    .call-bubble { max-width: 86%; padding: 8px 10px; border-radius: 12px; font-size: .84rem; line-height: 1.45; }
    .call-bubble.is-customer { background: #f3f4f6; color: #111827; }
    .call-bubble.is-employee { background: #25274d; color: #fff; margin-left: auto; }
    .call-bubble small { display: block; font-size: .68rem; opacity: .7; margin-bottom: 2px; }
    .call-compose { display: flex; gap: 8px; padding: 10px; border-top: 1px solid var(--lms-line); }
    .call-compose input { flex: 1; }
    .call-heard { padding: 0 14px 8px; color: var(--lms-muted); font-size: .75rem; min-height: 1.2em; }
    .call-score { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .call-score p { margin: 2px 0 0; }
    .call-box-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; }
    .call-box-head .trn-meta { margin: 0; }
    .call-details { display: grid; grid-template-columns: minmax(0, 1.6fr) 180px; gap: 10px; align-items: end; }
    .call-setup-split { display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px; align-items: stretch; }
    .call-scenario-card { display: flex; flex-direction: column; min-height: 560px; }
    .call-scenario-card > .card-body { flex: 1; }
    .call-scenario-card .call-scroll { display: flex; }
    .call-scenario-card .call-scenario-prompt { flex: 1; }
    .call-scenario-card .call-scenario-prompt textarea { flex: 1; min-height: 240px; resize: vertical; }
    .call-fixed { height: 560px; display: flex; flex-direction: column; overflow: hidden; }
    .call-fixed-body { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
    .call-scroll { flex: 1; min-height: 0; overflow-y: auto; margin-top: 10px; padding-right: 2px; }
    .call-pass-row {
        display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 12px; align-items: end;
        border-top: 1px solid var(--lms-line); margin-top: 12px; padding-top: 12px;
    }
    .call-pass-row .trn-meta { margin: 0 0 8px; }
    .call-settings { display: grid; grid-template-columns: repeat(5, minmax(140px, 1fr)); gap: 10px; }
    .call-add { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .call-add select { min-width: 0; flex: 1; }
    .call-added { display: flex; flex-direction: column; gap: 8px; }
    .call-added:empty { display: none; }
    .call-added-row {
        display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; align-items: center;
        border: 1px solid var(--lms-line); border-radius: 8px; padding: 8px 10px; background: #fff;
    }
    .call-added-row.is-skill { grid-template-columns: minmax(0, 1fr) 110px auto; }
    .call-added-row.is-scenario { display: flex; flex-direction: column; align-items: stretch; gap: 8px; }
    .call-scenario-top { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; align-items: start; }
    .call-scenario-prompt { display: flex; flex-direction: column; gap: 4px; margin: 0; font-size: .72rem; font-weight: 650; color: var(--lms-muted); }
    .call-scenario-prompt textarea { font-weight: 500; color: var(--lms-ink); min-height: 8rem; font-size: .8rem; line-height: 1.45; }
    .call-added-row b { display: block; font-size: .82rem; }
    .call-added-row span { display: block; color: var(--lms-muted); font-size: .72rem; line-height: 1.35; }
    .call-marks { display: flex; flex-direction: column; gap: 2px; margin: 0; font-size: .68rem; color: var(--lms-muted); font-weight: 650; }
    .call-marks input { height: 2rem; }
    @media (max-width: 860px) {
        .call-picks, .call-layout, .call-settings, .call-details, .call-setup-split, .call-pass-row { grid-template-columns: 1fr; }
        .call-fixed { height: 640px; }
    }
</style>
