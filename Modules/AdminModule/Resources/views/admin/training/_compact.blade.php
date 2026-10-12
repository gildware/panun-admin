<style>
    .trn { --lms-line: #e5e7eb; --lms-muted: #6b7280; --lms-ink: #111827; --lms-soft: #f9fafb; --lms-brand: #25274d; }
    .trn .page-title { font-size: 1.15rem; margin: 0; line-height: 1.3; text-transform: none; color: var(--lms-ink); }
    .trn label, .trn .form-label, .trn .btn, .trn .page-title { text-transform: none; }
    .trn .card { margin-bottom: .75rem; border: 1px solid var(--lms-line); border-radius: 10px; box-shadow: none; }
    .trn .card-body { padding: .85rem 1rem; }
    .trn .form-label { font-size: .75rem; margin-bottom: .25rem; color: var(--lms-muted); font-weight: 650; display: block; }
    .trn .form-control, .trn .form-select {
        font-size: .8125rem; height: 2rem; min-height: 2rem; padding: .25rem .6rem;
        border: 1px solid #d1d5db; border-radius: .375rem; box-shadow: none;
    }
    .trn textarea.form-control { height: auto; min-height: 5.5rem; padding: .45rem .6rem; }
    .trn .btn {
        height: 2rem; min-height: 2rem; padding: 0 .7rem; font-size: .8125rem; line-height: 1;
        border-radius: .375rem; font-weight: 650; gap: .25rem;
    }
    .trn .btn-outline-secondary,
    .trn .btn-outline-secondary:hover,
    .trn .btn-outline-secondary:focus {
        border: 1px solid #d1d5db; background: #fff; color: #374151 !important;
    }
    .trn .btn-outline-secondary:hover,
    .trn .btn-outline-secondary:focus { background: var(--lms-soft); }
    .trn .btn--primary,
    .trn .btn--primary:hover,
    .trn .btn--primary:focus {
        color: #fff !important; background-color: var(--lms-brand) !important; border-color: var(--lms-brand);
    }
    .trn .btn--primary:hover,
    .trn .btn--primary:focus { background-color: #1b1d3a !important; }
    .trn .table { font-size: .8125rem; margin-bottom: 0; }
    .trn .table th {
        font-size: .68rem; letter-spacing: .04em; text-transform: uppercase; color: var(--lms-muted);
        font-weight: 650; padding: .45rem .7rem; border-bottom: 1px solid var(--lms-line); background: var(--lms-soft);
    }
    .trn .table td { padding: .55rem .7rem; vertical-align: middle; border-color: #f3f4f6; }
    .trn .badge {
        font-size: .68rem; font-weight: 650; text-transform: none; letter-spacing: 0;
        padding: .2rem .45rem; border-radius: 999px; vertical-align: middle;
    }
    .trn-tabs, .trn-filters { display: flex; gap: .25rem; flex-wrap: wrap; align-items: center; }
    .trn-tabs a, .trn-filters a {
        display: inline-flex; align-items: center; height: 1.75rem; padding: 0 .6rem; border-radius: 999px;
        border: 1px solid var(--lms-line); background: #fff; color: #374151; font-size: .75rem; font-weight: 650; text-decoration: none;
    }
    .trn-tabs a.is-on, .trn-filters a.is-on { background: var(--lms-brand); border-color: var(--lms-brand); color: #fff; }
    .trn-meta { color: var(--lms-muted); font-size: .75rem; line-height: 1.35; }
    .lms-pagehead { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
    .lms-pagehead-actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
    .lms-toolbar, .lms-assign {
        display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(140px, .7fr) auto; gap: 10px; align-items: end;
    }
    .lms-assign { grid-template-columns: minmax(160px, 1.3fr) auto minmax(180px, 1fr) minmax(140px, .75fr) auto; }
    .lms-scope { display: flex; gap: 6px; height: 2rem; align-items: center; }
    .lms-scope label {
        display: inline-flex; align-items: center; height: 2rem; margin: 0; padding: 0 .7rem; border-radius: 999px;
        border: 1px solid var(--lms-line); background: #fff; color: #374151; font-size: .75rem; font-weight: 650; cursor: pointer;
    }
    .lms-scope input { position: absolute; opacity: 0; pointer-events: none; }
    .lms-scope label.is-on { background: var(--lms-brand); border-color: var(--lms-brand); color: #fff; }
    .lms-stats { display: flex; gap: 8px; flex-wrap: wrap; }
    .lms-stat { min-width: 96px; padding: 8px 10px; border: 1px solid var(--lms-line); border-radius: 8px; background: var(--lms-soft); }
    .lms-stat b { display: block; font-size: 1.05rem; line-height: 1.2; color: var(--lms-ink); }
    .lms-stat span { color: var(--lms-muted); font-size: .68rem; font-weight: 650; }
    .lms-stat.is-ok b { color: #166534; }
    .lms-stat.is-bad b { color: #b91c1c; }
    .lms-bar { width: 88px; height: 6px; border-radius: 999px; background: #e5e7eb; overflow: hidden; }
    .lms-bar > span { display: block; height: 100%; background: var(--lms-brand); }
    .lms-bar.is-done > span { background: #166534; }
    .lms-progress-cell { display: flex; align-items: center; gap: 8px; white-space: nowrap; }
    .lms-one-line { display: flex; align-items: center; gap: 8px; white-space: nowrap; }
    .lms-courses, .lms-section { background: #fff; border: 1px solid var(--lms-line); border-radius: 10px; overflow: hidden; }
    .lms-courses { margin-bottom: 0; }
    .lms-course {
        display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; align-items: center;
        padding: 12px 14px; border-bottom: 1px solid #f3f4f6;
    }
    .lms-course:last-child { border-bottom: 0; }
    .lms-course-title { font-weight: 700; color: var(--lms-ink); font-size: .9rem; line-height: 1.3; }
    .lms-course-meta, .lms-course-actions, .lms-row-actions, .lms-editor-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .lms-course-meta { margin-top: 4px; }
    .lms-section { margin-bottom: 12px; }
    .lms-section-head {
        display: grid; grid-template-columns: 28px 28px minmax(0, 1fr) auto auto; gap: 10px; align-items: center;
        min-height: 52px; padding: 8px 12px; background: var(--lms-soft); border-bottom: 1px solid #f3f4f6;
    }
    .lms-index {
        width: 28px; height: 28px; border-radius: 999px; background: var(--lms-brand); color: #fff;
        font-size: .72rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center;
    }
    .lms-section-title { margin: 0; font-size: .9rem; font-weight: 700; color: var(--lms-ink); line-height: 1.3; }
    .lms-section-head .lms-rename { grid-column: 3 / -1; }
    .lms-rename { display: flex; align-items: center; gap: 6px; min-width: 0; }
    .lms-rename .form-control { flex: 1; }
    .lms-lesson {
        display: grid; grid-template-columns: 28px 28px minmax(0, 1fr) auto; gap: 10px; align-items: center;
        min-height: 48px; padding: 8px 12px; border-bottom: 1px solid #f3f4f6;
    }
    .lms-lesson.is-open { background: #f8fafc; }
    .lms-lesson-title { font-weight: 650; color: var(--lms-ink); font-size: .84rem; }
    .lms-lesson-meta { display: flex; align-items: center; gap: 6px; margin-top: 2px; flex-wrap: wrap; }
    .lms-pill {
        display: inline-flex; align-items: center; height: 1.25rem; padding: 0 .4rem; border-radius: 999px;
        background: #f3f4f6; color: #374151; font-size: .68rem; font-weight: 650;
    }
    .lms-pill.is-ready { background: #ecfdf3; color: #166534; }
    .lms-pill.is-empty { background: #fff7ed; color: #c2410c; }
    .lms-type {
        width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex: none;
        background: #f3f4f6; color: #374151;
    }
    .lms-type .material-icons { font-size: 16px; line-height: 1; }
    .lms-type--audio { background: #eff6ff; color: #1d4ed8; }
    .lms-type--video { background: #f5f3ff; color: #6d28d9; }
    .lms-type--quiz { background: #fff7ed; color: #c2410c; }
    .lms-type--ai { background: #ecfdf3; color: #166534; }
    .lms-drag { cursor: grab; }
    .lms-section.is-dragging, .lms-lesson.is-dragging { opacity: .55; }
    .lms-iconbtn {
        width: 28px; height: 28px; padding: 0; border: 1px solid var(--lms-line); border-radius: 6px; background: #fff;
        color: #374151; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
    }
    .lms-iconbtn .material-icons { font-size: 16px; line-height: 1; }
    .lms-iconbtn:hover { background: var(--lms-soft); color: var(--lms-ink); }
    .lms-iconbtn.is-danger { color: #b91c1c; }
    .lms-iconbtn:disabled { opacity: .35; }
    .lms-add, .lms-new-section {
        display: grid; grid-template-columns: minmax(0, 1fr) 150px auto auto; gap: 8px; align-items: center;
        padding: 10px 12px; background: #fff;
    }
    .lms-new-section { grid-template-columns: minmax(0, 1fr) auto auto; border: 1px dashed #d1d5db; border-radius: 10px; background: #fff; }
    .lms-add-btn, .lms-section-foot { padding: 8px 12px; }
    .lms-editor { margin: 0 12px 12px 50px; padding: 12px; background: #fff; border: 1px solid var(--lms-line); border-radius: 8px; }
    .trn input[type="file"].form-control { height: auto; min-height: 2rem; padding: .35rem .6rem; }
    .lms-source { display: flex; gap: 8px; margin-bottom: 8px; }
    .lms-source label { display: inline-flex; align-items: center; gap: 6px; height: 2rem; margin: 0; padding: 0 .7rem; border: 1px solid #d1d5db; border-radius: .375rem; font-size: .8125rem; font-weight: 650; cursor: pointer; }
    .lms-source label:has(input:checked) { background: var(--lms-brand); border-color: var(--lms-brand); color: #fff; }
    .lms-source input { margin: 0; }
    .lms-questions { margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--lms-line); }
    .lms-lesson-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
    .lms-lesson-head h3 { margin-bottom: 0; }
    .lms-player-main .card-body:has(.lms-stage) { display: flex; flex-direction: column; }
    .lms-stage { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(240px, .72fr); gap: 16px; align-items: stretch; flex: 1 1 auto; min-height: 0; }
    .lms-stage:has(audio) { grid-template-columns: minmax(0, 1fr); grid-template-rows: auto minmax(0, 1fr); }
    .lms-stage:has(audio) .lms-stage-side { height: auto; min-height: 0; }
    .lms-stage.is-single { grid-template-columns: minmax(0, 1fr); }
    .lms-stage-main { min-width: 0; min-height: 0; display: flex; flex-direction: column; }
    .lms-play { width: 100%; position: relative; }
    .lms-play:has(video) { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
    .lms-play:has(video) .lms-frame { border-radius: 8px 8px 0 0; flex: 1 1 auto; min-height: 180px; aspect-ratio: auto; }
    .lms-frame {
        position: relative; width: 100%; aspect-ratio: 16 / 9; height: auto; padding-top: 0;
        background: #111; border-radius: 8px; overflow: hidden;
    }
    .lms-stage-side {
        background: var(--lms-soft); border: 1px solid var(--lms-line); border-radius: 10px; padding: 14px; height: 100%;
        min-height: 0; display: flex; flex-direction: column; overflow: auto;
    }
    .lms-stage-side .trn-prose { margin: 0; }
    .lms-frame iframe, .lms-frame video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; border: 0; background: #111; }
    .lms-play audio { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .trn .lms-play-bar {
        display: flex; align-items: center; gap: 2px; margin: 0; padding: 6px 8px;
        background: #1a1c38; color: #fff;
    }
    .lms-play:has(video) .lms-play-bar { border-radius: 0 0 8px 8px; }
    .lms-play:has(audio) .lms-play-bar { border-radius: 10px; }
    .trn .lms-play-btn {
        width: 32px; height: 32px; min-height: 32px; padding: 0; border: 0; border-radius: 6px;
        background: transparent; color: #fff; display: grid; place-items: center; line-height: 1;
    }
    .trn .lms-play-btn:hover { background: rgba(255, 255, 255, .14); color: #fff; }
    .trn .lms-play-btn .material-icons { font-size: 22px; }
    .lms-play-track { flex: 1; height: 4px; margin: 0 8px; background: rgba(255, 255, 255, .28); border-radius: 999px; overflow: hidden; cursor: pointer; }
    .lms-play-track span { display: block; height: 100%; width: 0; background: #fff; pointer-events: none; }
    .lms-play-clock { min-width: 76px; font-size: 12px; font-variant-numeric: tabular-nums; text-align: right; color: #fff; }
    .trn .lms-play-volume { width: 72px; height: 4px; margin: 0 4px; accent-color: #fff; cursor: pointer; }
    .trn .lms-play-status { color: #fecaca; font-size: 12px; font-weight: 650; white-space: nowrap; }
    .trn .lms-play-bar.is-loading .lms-play-status { color: #e4e7ec; }
    .lms-play-choice { margin-top: 8px; padding: 12px; border: 1px solid var(--lms-line); border-radius: 10px; background: var(--lms-soft); }
    .lms-player-main .card-body:has(.lms-score):not(:has(form)) { display: flex; align-items: center; justify-content: center; }
    .lms-player-main .card-body:has(.lq-miss) { flex-direction: column; align-items: stretch; justify-content: flex-start; }
    .lms-score {
        width: 100%; min-height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center; gap: 8px; padding: 28px 16px;
    }
    .lms-score-gif { width: 120px; height: 120px; object-fit: contain; }
    .lms-score-icon { font-size: 42px; line-height: 1; }
    .lms-score.is-pass .lms-score-icon { color: #166534; }
    .lms-score.is-fail .lms-score-icon { color: #c2410c; }
    .lms-score-line { font-size: 1.25rem; font-weight: 700; line-height: 1.4; }
    .lms-score-actions { margin-top: 8px; }
    .trn audio { display: block; width: 100%; }
    .trn-prose { font-size: .92rem; line-height: 1.65; color: var(--lms-ink); }
    .trn-prose p { margin: 0 0 .7rem; }
    .trn-prose ul, .trn-prose ol { margin: 0 0 .7rem; padding-left: 1.2rem; }
    .trn-prose h3 { font-size: 1rem; margin: 1.1rem 0 .45rem; }
    .lq-lead { font-size: 1rem; color: var(--lms-ink); }
    .lq-block { border: 1px solid var(--lms-line); border-radius: 12px; padding: 12px 14px; margin: 0 0 12px; background: #fff; }
    .lq-kicker { display: flex; align-items: center; gap: 8px; font-weight: 700; margin: 0 0 6px; }
    .lq-kicker .material-icons, .lq-row .material-icons { font-size: 20px; color: var(--lms-brand, #1f6f5b); }
    .lq-row { display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: 8px; margin: 0 0 10px; }
    .lq-label { font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--lms-muted); margin: 8px 0 2px; }
    .lq-figure { margin: 8px 0 12px; }
    .lq-figure img { width: 100%; max-width: 720px; border-radius: 10px; display: block; background: #f8faf9; }
    .lq-figure figcaption { font-size: .78rem; color: var(--lms-muted); margin-top: 4px; }
    .lq-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0; }
    .lq-do, .lq-dont { border-radius: 10px; padding: 10px 12px; }
    .lq-do { background: #f0fdf4; }
    .lq-dont { background: #fef2f2; }
    .lq-pill { display: inline-block; font-size: .72rem; font-weight: 700; border-radius: 999px; padding: 2px 8px; margin-right: 6px; }
    .lq-pill.is-good { background: #dcfce7; color: #166534; }
    .lq-pill.is-bad { background: #fee2e2; color: #991b1b; }
    .lq-wa pre, .lq-example { white-space: pre-wrap; background: #f8fafc; border-radius: 8px; padding: 10px 12px; font-family: inherit; font-size: .88rem; }
    .lq-miss { text-align: left; margin-top: 16px; }
    .lq-miss li { margin: 0 0 10px; }
    @media (max-width: 800px) { .lq-pair { grid-template-columns: 1fr; } }
    .lms-editor-actions { margin-top: 10px; }
    .lms-q, .lms-point { border: 1px solid #f3f4f6; border-radius: 8px; padding: 8px; margin-bottom: 8px; background: var(--lms-soft); }
    .lms-q-top, .lms-point { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: 8px; align-items: center; }
    .lms-point { grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr) auto; background: #fff; }
    .trn .lms-choice {
        display: flex; align-items: flex-start; gap: 8px; width: 100%; margin: 0 0 8px;
    }
    .trn .lms-choice input[type="radio"],
    .trn .lms-option input[type="radio"] {
        position: static; inset: auto; width: 16px; height: 16px; margin: 2px 0 0; flex: none; appearance: auto;
    }
    .trn .lms-choice span, .trn .lms-option span { flex: 1; min-width: 0; line-height: 1.45; }
    .lms-step-q { border: 0; margin: 0; padding: 0; min-inline-size: 0; }
    .lms-step-q legend { float: none; width: 100%; font-size: 1.05rem; font-weight: 700; line-height: 1.4; margin: 0 0 12px; padding: 0; }
    .lms-step-bar { height: 4px; background: #e5e7eb; border-radius: 999px; overflow: hidden; margin: 8px 0 16px; }
    .lms-step-bar span { display: block; height: 100%; width: 0; background: var(--lms-brand); }
    .trn .lms-option {
        display: flex; align-items: flex-start; gap: 10px; width: 100%; margin: 0 0 8px; padding: 12px 14px;
        border: 1px solid var(--lms-line); border-radius: 8px; background: #fff; cursor: pointer;
    }
    .trn .lms-option:has(input:checked) { border-color: var(--lms-brand); background: #eef2ff; }
    .lms-step-q.is-needed .lms-option { border-color: #fdba74; }
    .lms-step-nav { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 8px; }
    .lms-step-nav .d-flex { margin-left: auto; }
    .lms-side { background: #fff; border: 1px solid var(--lms-line); border-radius: 10px; padding: 12px; }
    .lms-side dt { color: var(--lms-muted); font-size: .72rem; font-weight: 650; margin: 0; }
    .lms-side dd { margin: 0 0 10px; font-size: .84rem; font-weight: 650; color: var(--lms-ink); }
    .main-area:has(.lms-player),
    body.nav-top .main-area:has(.lms-player) {
        height: 100dvh !important; max-height: 100dvh !important; overflow: hidden !important;
    }
    .main-area:has(.lms-player) > .admin-main-frame,
    .main-area:has(.lms-player) .main-content,
    .main-area:has(.lms-player) .main-content > .container-fluid {
        display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; overflow: hidden;
    }
    .lms-player {
        display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 10px;
        flex: 1 1 auto; min-height: 0; align-items: stretch; overflow: hidden;
    }
    .lms-player-side, .lms-player-main { min-width: 0; min-height: 0; overflow: hidden; display: flex; flex-direction: column; }
    .lms-player .card { margin-bottom: 0; flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
    .lms-player .card-body { flex: 1 1 auto; min-height: 0; overflow: auto; overscroll-behavior: contain; }
    .lms-outline-section { font-size: .72rem; font-weight: 700; color: var(--lms-muted); text-transform: uppercase; letter-spacing: .04em; margin: 8px 0 4px; }
    .lms-outline-section:first-child { margin-top: 0; }
    .lms-outline a, .lms-outline .is-locked {
        display: grid; grid-template-columns: 22px minmax(0, 1fr) auto; gap: 8px; align-items: center;
        min-height: 36px; padding: 4px 6px; border-radius: 6px; color: inherit; text-decoration: none;
    }
    .lms-outline a > span:nth-child(2), .lms-outline .is-locked > span:nth-child(2) {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        line-height: 1.35; overflow-wrap: break-word; word-break: normal;
    }
    .lms-outline a > span:nth-child(3), .lms-outline .is-locked > span:nth-child(3) { justify-self: end; white-space: nowrap; }
    .lms-outline a.is-current { background: #eef2ff; }
    .lms-outline .is-locked { color: #9ca3af; }
    .lms-outline .lms-type { width: 22px; height: 22px; }
    .lms-outline .lms-type .material-icons { font-size: 14px; }
    .lms-empty { padding: 28px 16px; text-align: center; color: var(--lms-muted); font-size: .84rem; }
    .trn-q { border: 1px solid var(--lms-line); border-radius: .5rem; padding: .55rem .65rem; margin-bottom: .5rem; }
    @media (max-width: 800px) {
        .main-area:has(.lms-player),
        body.nav-top .main-area:has(.lms-player) { height: auto !important; max-height: none !important; overflow: visible !important; }
        .main-area:has(.lms-player) > .admin-main-frame,
        .main-area:has(.lms-player) .main-content,
        .main-area:has(.lms-player) .main-content > .container-fluid { overflow: visible; }
        .lms-player, .lms-stage { grid-template-columns: 1fr; overflow: visible; }
        .lms-player-side .card-body { max-height: 280px; }
        .lms-toolbar, .lms-assign, .lms-add, .lms-new-section, .lms-course, .lms-section-head { grid-template-columns: 1fr; }
        .lms-editor { margin-left: 12px; }
        .lms-pagehead-actions, .lms-course-actions { justify-content: flex-start; }
    }
</style>
