@extends('adminmodule::layouts.new-master')

@section('title', translate('Process_Guides'))

@php
    $miroBoardUrl = 'https://miro.com/app/board/' . $miroBoardId . '/?share_link_id=' . $miroShareLinkId;
@endphp

@push('css_or_js')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Sans:wght@400;500;600&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .process-guide-page {
            --pg-line: #e2e8f0;
            --pg-radius: 16px;
            --pg-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.05);
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .main-area:has(.process-guide-page) { overflow: hidden; }
        .main-area:has(.process-guide-page.is-text-view) { overflow-y: auto; overflow-x: hidden; }
        .process-guide-page.is-text-view {
            overflow: visible;
            flex: 0 0 auto;
            min-height: auto;
        }
        .process-guide-page.is-text-view > .container-fluid {
            flex: 0 0 auto;
            min-height: auto;
        }
        .process-guide-page.is-text-view .pg-view-panel.is-active {
            flex: 0 0 auto;
            min-height: auto;
        }
        .process-guide-page.is-text-view .pg-text-guide {
            overflow: visible;
            flex: 0 0 auto;
            min-height: auto;
        }
        .process-guide-page.is-text-view .pg-text-body {
            overflow: visible;
            flex: 0 0 auto;
            min-height: auto;
        }
        .main-area:has(.process-guide-page) > footer.footer { flex-shrink: 0; }
        .process-guide-page > .container-fluid {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding-bottom: 0;
        }
        .process-guide-page .pg-flow-card {
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: var(--pg-radius);
            padding: 1rem 1.15rem;
            box-shadow: var(--pg-shadow);
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .process-guide-page .pg-flow-toolbar-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: .65rem;
            flex-shrink: 0;
        }
        .process-guide-page .pg-flow-toolbar-top h3 {
            font-family: Outfit, sans-serif;
            margin: 0 0 .2rem;
            font-size: 1.15rem;
            color: #0f172a;
        }
        .process-guide-page .pg-flow-sub {
            margin: 0;
            font-size: .82rem;
            color: #64748b;
            max-width: 40rem;
        }
        .process-guide-page .pg-miro-link {
            font-size: .78rem;
            font-weight: 700;
            color: #0f766e;
            text-decoration: none;
            white-space: nowrap;
        }
        .process-guide-page .pg-miro-link:hover { text-decoration: underline; }
        .process-guide-page .pg-flow {
            position: relative;
            border: 1px solid var(--pg-line);
            border-radius: 14px;
            background: #fafafa;
            overflow: hidden;
            flex: 1 1 auto;
            min-height: 520px;
            display: flex;
            flex-direction: column;
        }
        .process-guide-page .pg-flow-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .45rem;
            padding: .55rem .85rem;
            border-bottom: 1px solid var(--pg-line);
            background: #f8fafc;
            font-size: .72rem;
            font-weight: 700;
            color: #64748b;
            flex-shrink: 0;
        }
        .process-guide-page .pg-flow-toolbar-hint { font-weight: 600; }
        .process-guide-page .pg-flow-actions { display: flex; flex-wrap: wrap; gap: .25rem; align-items: center; }
        .process-guide-page .pg-flow-actions button {
            padding: .35rem .65rem;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            background: #fff;
            font-size: .72rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
        }
        .process-guide-page .pg-flow-actions button:hover { background: #f1f5f9; }
        .process-guide-page .pg-zoom-label { min-width: 2.5rem; text-align: center; font-variant-numeric: tabular-nums; }
        .process-guide-page .pg-flow-fallback { display: none; padding: 1.5rem; text-align: center; color: #64748b; }
        .process-guide-page .pg-flow.is-failed .pg-flow-fallback { display: block; }
        .process-guide-page .pg-flow.is-failed .pg-flow-viewport { display: none; }
        .process-guide-page .pg-flow-viewport {
            flex: 1 1 auto;
            min-height: 480px;
            overflow: hidden;
            cursor: grab;
            position: relative;
            background: #f2f2f2;
            touch-action: none;
            overscroll-behavior: contain;
            user-select: none;
        }
        .process-guide-page .pg-flow-viewport.is-dragging { cursor: grabbing; }
        .process-guide-page .pg-flow-viewport.is-loading::after {
            content: 'Loading board…';
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(242, 242, 242, 0.85);
            color: #64748b;
            font-weight: 600;
            font-size: .9rem;
            z-index: 1;
        }
        .process-guide-page .pg-flow-stage {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }
        .process-guide-page .pg-miro-svg { display: block; width: 100%; height: 100%; }
        .process-guide-page .pg-miro-label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            width: 100%;
            line-height: 1.18;
            font-family: "Noto Sans", "Plus Jakarta Sans", sans-serif;
            font-weight: 500;
            text-align: center;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            hyphens: auto;
            overflow: hidden;
            pointer-events: none;
            -webkit-font-smoothing: antialiased;
        }
        .process-guide-page .pg-miro-edge-label {
            fill: #1a1a1a;
            font-family: "Noto Sans", sans-serif;
            font-weight: 600;
            pointer-events: none;
        }
        .process-guide-page .pg-miro-node { cursor: pointer; }
        .process-guide-page .pg-miro-node.is-selected rect,
        .process-guide-page .pg-miro-node.is-selected polygon {
            stroke: #0f766e !important;
            stroke-width: 3px !important;
        }
        .process-guide-page .pg-flow:fullscreen,
        .process-guide-page .pg-flow:-webkit-full-screen {
            width: 100%; height: 100%; min-height: 100%; border-radius: 0;
        }
        @media (min-height: 700px) {
            .process-guide-page .pg-flow-viewport { min-height: calc(100vh - 220px); }
        }
        .process-guide-page .pg-view-tabs {
            display: flex;
            gap: .35rem;
            margin-bottom: .85rem;
            flex-shrink: 0;
        }
        .process-guide-page .pg-view-tab {
            padding: .45rem .9rem;
            border: 1px solid var(--pg-line);
            border-radius: 999px;
            background: #fff;
            font-size: .78rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
        }
        .process-guide-page .pg-view-tab.is-active {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
        }
        .process-guide-page .pg-view-panel { display: none; flex: 1 1 auto; min-height: 0; flex-direction: column; }
        .process-guide-page .pg-view-panel.is-active { display: flex; }
        .process-guide-page .pg-text-guide {
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: var(--pg-radius);
            box-shadow: var(--pg-shadow);
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .process-guide-page .pg-text-guide-header {
            padding: 1rem 1.15rem .75rem;
            border-bottom: 1px solid var(--pg-line);
            flex-shrink: 0;
        }
        .process-guide-page .pg-text-guide-header h3 {
            font-family: Outfit, sans-serif;
            margin: 0 0 .2rem;
            font-size: 1.15rem;
            color: #0f172a;
        }
        .process-guide-page .pg-text-toc {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            padding: .65rem 1.15rem;
            border-bottom: 1px solid var(--pg-line);
            background: #f8fafc;
            flex-shrink: 0;
        }
        .process-guide-page .pg-text-toc-link {
            font-size: .72rem;
            font-weight: 700;
            color: #0f766e;
            text-decoration: none;
            padding: .25rem .55rem;
            border-radius: 6px;
            background: #ecfdf5;
        }
        .process-guide-page .pg-text-toc-link:hover { background: #d1fae5; }
        .process-guide-page .pg-text-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding: 1rem 1.15rem 1.5rem;
        }
        .process-guide-page .pg-text-section + .pg-text-section {
            margin-top: 1.75rem;
            padding-top: 1.5rem;
            border-top: 1px dashed var(--pg-line);
        }
        .process-guide-page .pg-text-section-title {
            font-family: Outfit, sans-serif;
            font-size: 1rem;
            color: #0f172a;
            margin: 0 0 .65rem;
        }
        .process-guide-page .pg-text-intro {
            margin: 0 0 .85rem;
            color: #64748b;
            font-size: .86rem;
            line-height: 1.55;
        }
        .process-guide-page .pg-text-steps {
            margin: 0;
            padding-left: 1.25rem;
            color: #334155;
            font-size: .86rem;
            line-height: 1.55;
        }
        .process-guide-page .pg-text-step { margin-bottom: .85rem; }
        .process-guide-page .pg-text-step-title { font-weight: 700; color: #0f172a; margin-bottom: .25rem; }
        .process-guide-page .pg-text-step-body { margin: .25rem 0 .45rem; color: #475569; }
        .process-guide-page .pg-text-step-list {
            margin: .35rem 0 .5rem;
            padding-left: 1.1rem;
            color: #475569;
        }
        .process-guide-page .pg-text-branches {
            margin-top: .55rem;
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }
        .process-guide-page .pg-text-branch {
            border-left: 3px solid #99f6e4;
            padding-left: .75rem;
            margin-left: .15rem;
        }
        .process-guide-page .pg-text-branch-label {
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: #0f766e;
            margin-bottom: .35rem;
        }
        .process-guide-page .pg-text-branch .pg-text-steps { font-size: .84rem; }
    </style>
@endpush

@section('content')
    <div class="main-content process-guide-page">
        <div class="container-fluid">
            <div class="pg-view-tabs" role="tablist" aria-label="Process guide views">
                <button type="button" class="pg-view-tab is-active" data-pg-view-tab="flowchart" role="tab" aria-selected="true" aria-controls="pg-panel-flowchart">Flowchart</button>
                <button type="button" class="pg-view-tab" data-pg-view-tab="text" role="tab" aria-selected="false" aria-controls="pg-panel-text">Step-by-step guide</button>
            </div>

            <div class="pg-view-panel is-active" id="pg-panel-flowchart" data-pg-view-panel="flowchart" role="tabpanel">
                @include('adminmodule::admin.process-guide.partials._flow', [
                    'title' => $miroTitle,
                    'miroBoardUrl' => $miroBoardUrl,
                    'boardJsonUrl' => $boardJsonUrl,
                ])
            </div>

            <div class="pg-view-panel" id="pg-panel-text" data-pg-view-panel="text" role="tabpanel" hidden>
                @include('adminmodule::admin.process-guide.partials._text-guide', [
                    'title' => $miroTitle,
                ])
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('adminmodule::admin.process-guide.partials._scripts')
    <script>
    (function () {
        var root = document.querySelector('.process-guide-page');
        var tabs = document.querySelectorAll('[data-pg-view-tab]');
        var panels = document.querySelectorAll('[data-pg-view-panel]');
        if (!tabs.length || !root) return;

        function activate(name) {
            root.classList.toggle('is-text-view', name === 'text');
            tabs.forEach(function (tab) {
                var on = tab.getAttribute('data-pg-view-tab') === name;
                tab.classList.toggle('is-active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            panels.forEach(function (panel) {
                var on = panel.getAttribute('data-pg-view-panel') === name;
                panel.classList.toggle('is-active', on);
                if (on) panel.removeAttribute('hidden');
                else panel.setAttribute('hidden', '');
            });
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activate(tab.getAttribute('data-pg-view-tab'));
            });
        });
    })();
    </script>
@endpush
