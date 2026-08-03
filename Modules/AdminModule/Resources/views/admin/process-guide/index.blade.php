@extends('adminmodule::layouts.new-master')

@section('title', translate('Process_Guides'))

@php
    $activeGuideKey = $guide['key'] ?? 'lead-qualification';
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
        .main-area:has(.process-guide-page.is-scroll-view) { overflow-y: auto; overflow-x: hidden; }
        .main-area:has(.process-guide-page) > .main-content,
        .main-area:has(.process-guide-page) .admin-main-frame {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .main-content.process-guide-page {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .process-guide-page.is-scroll-view {
            overflow: visible;
            flex: 0 0 auto;
            min-height: auto;
        }
        .process-guide-page.is-scroll-view > .container-fluid {
            flex: 0 0 auto;
            min-height: auto;
        }
        .process-guide-page.is-scroll-view .pg-view-panel.is-active {
            flex: 0 0 auto;
            min-height: auto;
        }
        .process-guide-page.is-scroll-view .pg-text-guide {
            overflow: visible;
            flex: 0 0 auto;
            min-height: auto;
        }
        .process-guide-page.is-scroll-view .pg-text-body {
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
            font-family: "Noto Sans", sans-serif;
            font-weight: 600;
            pointer-events: none;
        }
        .process-guide-page .pg-miro-edge { pointer-events: none; }
        .process-guide-page .pg-miro-node { cursor: pointer; transition: filter .12s ease; }
        .process-guide-page .pg-miro-node:hover { filter: brightness(0.97); }
        .process-guide-page .pg-miro-node.is-selected { filter: drop-shadow(0 0 6px rgba(15, 118, 110, 0.35)); }
        .process-guide-page .pg-miro-node.is-selected rect,
        .process-guide-page .pg-miro-node.is-selected polygon {
            stroke-width: 0.14 !important;
        }
        .process-guide-page .pg-flow-legend {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .65rem;
            margin-bottom: .75rem;
            padding: .55rem .75rem;
            border: 1px solid var(--pg-line);
            border-radius: 10px;
            background: #f8fafc;
            flex-shrink: 0;
        }
        .process-guide-page .pg-legend-item {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .68rem;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
        }
        .process-guide-page .pg-legend-item i {
            display: inline-block;
            width: .85rem;
            height: .85rem;
            border-radius: 3px;
            border: 2px solid transparent;
            flex-shrink: 0;
        }
        .process-guide-page .pg-legend-channel i { background: #F5F3FF; border-color: #7C3AED; }
        .process-guide-page .pg-legend-action i { background: #EFF6FF; border-color: #3B82F6; }
        .process-guide-page .pg-legend-decision i {
            background: #FEF9C3;
            border-color: #D97706;
            transform: rotate(45deg);
            width: .65rem;
            height: .65rem;
            margin: 0 .1rem;
        }
        .process-guide-page .pg-legend-message i { background: #D1FAE5; border-color: #10B981; border-radius: 2px 2px 6px 2px; }
        .process-guide-page .pg-legend-end-state i { background: #ECFDF5; border-color: #059669; border-radius: 999px; }
        .process-guide-page .pg-legend-end-terminal i { background: #FEE2E2; border-color: #DC2626; border-radius: 999px; }
        .process-guide-page .pg-legend-arrow i {
            background: transparent;
            border: none;
            width: 1.1rem;
            height: 0;
            border-top: 2px solid #475569;
            position: relative;
        }
        .process-guide-page .pg-legend-arrow i::after {
            content: '';
            position: absolute;
            right: 0;
            top: -4px;
            border: 4px solid transparent;
            border-left-color: #475569;
        }
        .process-guide-page .pg-legend-group i {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #7C3AED;
            border-radius: 8px;
            box-shadow: inset 0 2px 0 #EDE9FE;
        }
        .process-guide-page .pg-flow-group-box { pointer-events: none; }
        .process-guide-page .pg-flow-group-content-bg { pointer-events: none; }
        .process-guide-page .pg-flow-group-header-bg { pointer-events: none; }
        .process-guide-page .pg-flow-group-title {
            fill: #ffffff;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            font-weight: 700;
            pointer-events: none;
            user-select: none;
        }
        .process-guide-page .pg-flow-group-info-btn {
            pointer-events: all;
            cursor: pointer;
        }
        .process-guide-page .pg-flow-group-info-btn:hover circle {
            fill: rgba(255, 255, 255, 0.3);
        }
        .process-guide-page .pg-flow-group.is-active .pg-flow-group-box {
            fill: rgba(237, 233, 254, 0.62);
            stroke: #5B21B6;
            stroke-width: 0.2;
        }
        .process-guide-page .pg-flow-group.is-active .pg-flow-group-content-bg {
            fill: rgba(255, 255, 255, 0.5);
            stroke: rgba(91, 33, 182, 0.35);
        }
        .process-guide-page .pg-flow-group.is-active .pg-flow-group-header-bg {
            fill: #5B21B6;
        }
        .process-guide-page .pg-group-detail {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            width: min(22rem, calc(100% - 1.5rem));
            max-height: calc(100% - 1.5rem);
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.14);
            z-index: 6;
            overflow: hidden;
        }
        .process-guide-page .pg-group-detail[hidden] { display: none !important; }
        .process-guide-page .pg-group-detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1.15rem 1.25rem 1rem;
            border-bottom: 1px solid var(--pg-line);
            background: linear-gradient(180deg, #F5F3FF 0%, #fff 100%);
            flex-shrink: 0;
        }
        .process-guide-page .pg-group-detail-step {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #6D28D9;
            background: #EDE9FE;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
            margin-bottom: 0.35rem;
        }
        .process-guide-page .pg-group-detail-title {
            margin: 0;
            font-family: Outfit, sans-serif;
            font-size: 1.12rem;
            color: #0f172a;
        }
        .process-guide-page .pg-group-detail-sub {
            margin: 0.2rem 0 0;
            font-size: 0.78rem;
            color: #64748b;
        }
        .process-guide-page .pg-group-detail-close {
            border: none;
            background: #fff;
            border: 1px solid var(--pg-line);
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 8px;
            font-size: 1.1rem;
            line-height: 1;
            color: #64748b;
            cursor: pointer;
            flex-shrink: 0;
        }
        .process-guide-page .pg-group-detail-close:hover { background: #f8fafc; color: #0f172a; }
        .process-guide-page .pg-group-detail-body {
            padding: 1.15rem 1.25rem 1.35rem;
            overflow: auto;
            font-size: 0.86rem;
            line-height: 1.6;
            color: #334155;
        }
        .process-guide-page .pg-detail-intro {
            margin: 0 0 1rem;
            color: #475569;
        }
        .process-guide-page .pg-detail-section + .pg-detail-section { margin-top: 1rem; }
        .process-guide-page .pg-detail-section h5,
        .process-guide-page .pg-detail-notes h5 {
            margin: 0 0 0.5rem;
            font-size: 0.82rem;
            font-weight: 800;
            color: #0f172a;
        }
        .process-guide-page .pg-detail-section ul,
        .process-guide-page .pg-detail-notes ul {
            margin: 0;
            padding-left: 1.15rem;
        }
        .process-guide-page .pg-detail-section li + li,
        .process-guide-page .pg-detail-notes li + li { margin-top: 0.35rem; }
        .process-guide-page .pg-detail-notes {
            margin-top: 1.1rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--pg-line);
        }
        .process-guide-page .pg-miro-node.is-group-active rect,
        .process-guide-page .pg-miro-node.is-group-active polygon {
            stroke: #5B21B6 !important;
        }
        .process-guide-page .pg-flow:fullscreen,
        .process-guide-page .pg-flow:-webkit-full-screen {
            width: 100%; height: 100%; min-height: 100%; border-radius: 0;
        }
        @media (min-height: 700px) {
            .process-guide-page .pg-flow-viewport { min-height: calc(100vh - 220px); }
        }
        .process-guide-page .pg-guide-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-bottom: 0;
            flex-shrink: 0;
            flex: 1 1 auto;
            min-width: 0;
        }
        .process-guide-page .pg-guide-tabs-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .65rem;
            flex-shrink: 0;
        }
        .process-guide-page .pg-guide-search {
            position: relative;
            flex: 0 1 320px;
            width: 100%;
            max-width: 360px;
            margin-left: auto;
        }
        .process-guide-page .pg-guide-search-input-wrap {
            display: flex;
            align-items: center;
            gap: .35rem;
            padding: .45rem .65rem;
            border: 1px solid var(--pg-line);
            border-radius: 10px;
            background: #fff;
            box-shadow: var(--pg-shadow);
        }
        .process-guide-page .pg-guide-search-input-wrap:focus-within {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
        }
        .process-guide-page .pg-guide-search-icon {
            font-size: 1.05rem;
            color: #94a3b8;
            flex-shrink: 0;
        }
        .process-guide-page .pg-guide-search-input {
            flex: 1 1 auto;
            min-width: 0;
            border: 0;
            background: transparent;
            font-size: .82rem;
            font-weight: 600;
            color: #0f172a;
            outline: none;
        }
        .process-guide-page .pg-guide-search-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }
        .process-guide-page .pg-guide-search-clear {
            display: none;
            align-items: center;
            justify-content: center;
            width: 1.35rem;
            height: 1.35rem;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: #e2e8f0;
            color: #64748b;
            cursor: pointer;
            flex-shrink: 0;
        }
        .process-guide-page .pg-guide-search-clear.is-visible { display: inline-flex; }
        .process-guide-page .pg-guide-search-clear .material-icons { font-size: .95rem; }
        .process-guide-page .pg-guide-search-dropdown {
            position: absolute;
            top: calc(100% + .35rem);
            left: 0;
            right: 0;
            z-index: 40;
            max-height: min(420px, 50vh);
            overflow: auto;
            border: 1px solid var(--pg-line);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
        }
        .process-guide-page .pg-guide-search-dropdown[hidden] { display: none; }
        .process-guide-page .pg-guide-search-empty,
        .process-guide-page .pg-guide-search-hint {
            padding: .85rem 1rem;
            font-size: .78rem;
            font-weight: 600;
            color: #64748b;
        }
        .process-guide-page .pg-guide-search-result {
            display: block;
            width: 100%;
            padding: .7rem .85rem;
            border: 0;
            border-bottom: 1px solid #f1f5f9;
            background: #fff;
            text-align: left;
            cursor: pointer;
        }
        .process-guide-page .pg-guide-search-result:last-child { border-bottom: 0; }
        .process-guide-page .pg-guide-search-result:hover,
        .process-guide-page .pg-guide-search-result.is-active {
            background: #f0fdfa;
        }
        .process-guide-page .pg-guide-search-result-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: .2rem;
        }
        .process-guide-page .pg-guide-search-result-title {
            font-size: .8rem;
            font-weight: 700;
            color: #0f172a;
        }
        .process-guide-page .pg-guide-search-result-badge {
            font-size: .65rem;
            font-weight: 700;
            color: #0f766e;
            background: #ecfdf5;
            border-radius: 999px;
            padding: .15rem .45rem;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .process-guide-page .pg-guide-search-result-meta {
            font-size: .68rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: .25rem;
        }
        .process-guide-page .pg-guide-search-result-snippet {
            font-size: .72rem;
            line-height: 1.45;
            color: #475569;
        }
        .process-guide-page .pg-guide-search-result-snippet mark {
            background: #fef08a;
            color: inherit;
            padding: 0 .1rem;
            border-radius: 2px;
        }
        @media (max-width: 768px) {
            .process-guide-page .pg-guide-tabs-row {
                flex-direction: column;
                align-items: stretch;
            }
            .process-guide-page .pg-guide-search {
                flex: 1 1 auto;
                max-width: none;
                margin-left: 0;
            }
        }
        .process-guide-page .pg-guide-tab {
            padding: .5rem 1rem;
            border: 1px solid var(--pg-line);
            border-radius: 10px;
            background: #fff;
            font-size: .82rem;
            font-weight: 700;
            color: #334155;
            text-decoration: none;
            box-shadow: var(--pg-shadow);
        }
        .process-guide-page .pg-guide-tab:hover {
            background: #f8fafc;
            color: #0f766e;
        }
        .process-guide-page .pg-guide-tab.is-active {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }
        .process-guide-page .pg-flowchart-reference .pg-text-section {
            margin-bottom: 2rem;
        }
        .process-guide-page .pg-flowchart-reference .pg-training-flowchart {
            margin-top: .75rem;
        }
        .process-guide-page .pg-training-panel {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
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

        .process-guide-page .pg-training-guide {
            background: #fff;
            border: 1px solid var(--pg-line);
            border-radius: var(--pg-radius);
            box-shadow: var(--pg-shadow);
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .process-guide-page .pg-training-guide .pg-text-guide-header {
            padding: .6rem .85rem .45rem;
            border-bottom: 1px solid var(--pg-line);
            flex-shrink: 0;
        }
        .process-guide-page .pg-training-header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }
        .process-guide-page .pg-training-header-copy {
            flex: 1 1 auto;
            min-width: 0;
        }
        .process-guide-page .pg-training-present-btn {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .45rem .75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #fff;
            font-size: .78rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            font-family: inherit;
        }
        .process-guide-page .pg-training-present-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        .process-guide-page .pg-training-present-icon {
            font-size: 1.1rem;
            line-height: 1;
        }
        .process-guide-page .pg-training-layout:fullscreen,
        .process-guide-page .pg-training-layout:-webkit-full-screen {
            display: flex;
            width: 100%;
            height: 100%;
            min-height: 100%;
            background: #fff;
        }
        .process-guide-page .pg-training-layout:fullscreen .pg-training-main,
        .process-guide-page .pg-training-layout:-webkit-full-screen .pg-training-main {
            flex: 1 1 auto;
            min-height: 0;
        }
        .process-guide-page .pg-training-guide.is-presentation .pg-training-sidebar {
            width: min(16rem, 28vw);
        }
        .process-guide-page .pg-training-nav-center {
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .process-guide-page .pg-training-nav-btn--icon {
            min-width: auto;
            padding: .5rem .65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .process-guide-page .pg-training-nav-btn--icon .material-icons {
            font-size: 1.15rem;
            line-height: 1;
        }
        .process-guide-page .pg-training-guide .pg-text-guide-header h3 {
            font-size: .95rem;
            margin: 0 0 .15rem;
        }
        .process-guide-page .pg-training-guide .pg-flow-sub {
            margin: 0;
            font-size: .72rem;
            max-width: none;
        }
        .process-guide-page .pg-training-layout {
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
            display: flex;
            align-items: stretch;
            overflow: hidden;
        }
        .process-guide-page .pg-training-sidebar {
            width: min(15.5rem, 34vw);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
            overflow: hidden;
            border-right: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .process-guide-page .pg-training-sidebar-head {
            padding: .75rem .85rem .55rem;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        .process-guide-page .pg-training-toc {
            display: flex;
            flex-direction: column;
            gap: .3rem;
            padding: .65rem .65rem .85rem;
            overflow-x: hidden;
            overflow-y: auto;
            flex: 1 1 auto;
            min-height: 0;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
        .process-guide-page .pg-training-toc-link {
            display: flex;
            align-items: flex-start;
            gap: .45rem;
            width: 100%;
            text-align: left;
            font-size: .72rem;
            font-weight: 600;
            line-height: 1.35;
            color: #334155;
            padding: .45rem .55rem;
            border-radius: 6px;
            background: #fff;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            font-family: inherit;
        }
        .process-guide-page .pg-training-toc-label {
            flex: 1 1 auto;
            min-width: 0;
        }
        .process-guide-page .pg-training-toc-link:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .process-guide-page .pg-training-toc-link.is-active {
            background: #1e293b;
            border-color: #1e293b;
            color: #fff;
        }
        .process-guide-page .pg-training-toc-link.is-active .pg-training-toc-num {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }
        .process-guide-page .pg-training-toc-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.35rem;
            border-radius: 4px;
            background: #e2e8f0;
            color: #475569;
            font-size: .62rem;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: .05rem;
        }
        .process-guide-page .pg-training-main {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .process-guide-page .pg-training-stage {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 24px rgba(15, 23, 42, .08);
            border: 1px solid #e2e8f0;
        }
        .process-guide-page .pg-training-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding: 0;
            position: relative;
        }
        .process-guide-page .pg-training-slide {
            display: none;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            border: none;
            border-radius: 0;
            background: #fff;
            overflow: hidden;
            box-shadow: none;
        }
        .process-guide-page .pg-training-slide.is-active {
            display: flex;
            min-height: 100%;
        }
        .process-guide-page .pg-training-slide--title {
            background: linear-gradient(160deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            border: none;
            color: #fff;
        }
        .process-guide-page .pg-training-slide--title .pg-training-slide-header {
            border-bottom: none;
            background: transparent;
            padding: 1.25rem 1rem;
            text-align: center;
        }
        .process-guide-page .pg-training-slide--title .pg-training-slide-badge {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }
        .process-guide-page .pg-training-slide--title .pg-training-slide-title,
        .process-guide-page .pg-training-slide--title .pg-training-slide-sub,
        .process-guide-page .pg-training-slide--title .pg-training-slide-tagline,
        .process-guide-page .pg-training-slide--title .pg-training-slide-footer {
            color: #fff;
        }
        .process-guide-page .pg-training-slide--title .pg-training-slide-body { display: none; }
        .process-guide-page .pg-training-slide--title.is-active .pg-training-slide-header {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: auto;
        }
        @media (max-width: 768px) {
            .process-guide-page .pg-training-layout {
                flex-direction: column;
            }
            .process-guide-page .pg-training-sidebar {
                width: 100%;
                max-height: 9rem;
                border-right: none;
                border-bottom: 1px solid var(--pg-line);
            }
            .process-guide-page .pg-training-toc {
                flex-direction: row;
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
            }
            .process-guide-page .pg-training-toc-link {
                width: auto;
                min-width: 10rem;
                flex-shrink: 0;
            }
        }
        .process-guide-page .pg-training-slide-header {
            padding: .65rem 1.1rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        }
        .process-guide-page .pg-training-slide-header-row {
            display: flex;
            align-items: center;
            gap: .65rem;
            min-width: 0;
        }
        .process-guide-page .pg-training-slide-icon {
            flex-shrink: 0;
            font-size: 1.35rem;
            color: #1e293b;
            line-height: 1;
        }
        .process-guide-page .pg-training-slide-index {
            flex-shrink: 0;
            font-family: Outfit, sans-serif;
            font-size: .75rem;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: .06em;
        }
        .process-guide-page .pg-training-slide-badge {
            flex-shrink: 0;
            margin-left: auto;
            font-size: .58rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #475569;
            background: #e2e8f0;
            padding: .2rem .45rem;
            border-radius: 4px;
        }
        .process-guide-page .pg-training-slide-title {
            flex: 1 1 auto;
            min-width: 0;
            font-family: Outfit, sans-serif;
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
        }
        .process-guide-page .pg-training-slide-sub {
            margin: .25rem 0 0 3.5rem;
            font-size: .78rem;
            color: #64748b;
            line-height: 1.35;
        }
        .process-guide-page .pg-training-overview {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            margin: 0 0 1rem;
            padding: .75rem .85rem;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #1e293b;
        }
        .process-guide-page .pg-training-overview-icon {
            flex-shrink: 0;
            font-size: 1.15rem;
            color: #475569;
            margin-top: .1rem;
        }
        .process-guide-page .pg-training-overview-body {
            flex: 1 1 auto;
            min-width: 0;
        }
        .process-guide-page .pg-training-overview-label {
            display: block;
            margin-bottom: .2rem;
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }
        .process-guide-page .pg-training-overview-text {
            margin: 0;
            font-size: .84rem;
            line-height: 1.45;
            color: #334155;
            font-weight: 500;
        }
        .process-guide-page .pg-training-toc-icon {
            flex-shrink: 0;
            font-size: .95rem;
            color: #94a3b8;
            line-height: 1.25rem;
        }
        .process-guide-page .pg-training-toc-link.is-active .pg-training-toc-icon {
            color: rgba(255, 255, 255, 0.85);
        }
        .process-guide-page .pg-training-block-icon {
            font-size: .95rem;
            vertical-align: middle;
            margin-right: .25rem;
            color: #64748b;
        }
        .process-guide-page .pg-training-follow-title,
        .process-guide-page .pg-training-quick-rules-label {
            display: flex;
            align-items: center;
            gap: .25rem;
        }
        .process-guide-page .pg-training-type-icon {
            display: block;
            font-size: 1.35rem;
            color: #475569;
            margin-bottom: .4rem;
        }
        .process-guide-page .pg-training-type-card--customer .pg-training-type-icon { color: #059669; }
        .process-guide-page .pg-training-type-card--provider .pg-training-type-icon { color: #2563eb; }
        .process-guide-page .pg-training-type-card--unknown .pg-training-type-icon { color: #d97706; }
        .process-guide-page .pg-training-type-card--future .pg-training-type-icon { color: #7c3aed; }
        .process-guide-page .pg-training-type-card--invalid .pg-training-type-icon { color: #dc2626; }
        .process-guide-page .pg-training-type-card--source .pg-training-type-icon { color: #0d9488; }
        .process-guide-page .pg-training-scenario-title {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin: 0 0 .5rem;
            font-size: .82rem;
            font-weight: 700;
            color: #0f172a;
        }
        .process-guide-page .pg-training-scenario-title-icon {
            font-size: 1rem;
            color: #475569;
        }
        .process-guide-page .pg-training-scenario-label {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            min-width: 5.5rem;
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            vertical-align: top;
        }
        .process-guide-page .pg-training-scenario-label .material-icons {
            font-size: .85rem;
        }
        .process-guide-page .pg-training-scenario-row {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            margin: .35rem 0 0;
            font-size: .8rem;
            color: #475569;
            line-height: 1.45;
        }
        .process-guide-page .pg-training-slide-tagline {
            margin: .65rem 0 0;
            font-size: .9rem;
            font-weight: 600;
            color: #334155;
        }
        .process-guide-page .pg-training-slide-footer {
            margin: .5rem 0 0;
            font-size: .72rem;
            color: #94a3b8;
        }
        .process-guide-page .pg-training-slide-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding: 1rem 1.15rem 1.25rem;
            font-size: .86rem;
            line-height: 1.55;
            color: #334155;
        }
        .process-guide-page .pg-training-intro,
        .process-guide-page .pg-training-note {
            margin: 0 0 .85rem;
            color: #475569;
        }
        .process-guide-page .pg-training-note {
            padding: .6rem .85rem;
            border-radius: 8px;
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            font-weight: 600;
            color: #1e3a8a;
            line-height: 1.5;
        }
        .process-guide-page .pg-training-important {
            margin: 0 0 .85rem;
            padding: .6rem .8rem;
            border-radius: 8px;
            background: #FEE2E2;
            border-left: 4px solid #DC2626;
            font-size: .82rem;
            font-weight: 700;
            color: #991B1B;
            line-height: 1.45;
        }
        .process-guide-page .pg-training-warning,
        .process-guide-page .pg-training-warning--inline {
            margin: .5rem 0 0;
            padding: .5rem .65rem;
            border-radius: 8px;
            background: #FFFBEB;
            border-left: 4px solid #F59E0B;
            font-size: .78rem;
            font-weight: 700;
            color: #92400E;
            line-height: 1.4;
        }
        .process-guide-page .pg-training-warning--inline { margin-top: .45rem; }
        .process-guide-page .pg-training-wa-badge {
            display: inline-block;
            margin-bottom: .35rem;
            padding: .15rem .45rem;
            border-radius: 4px;
            background: #25D366;
            color: #fff;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .process-guide-page .pg-training-playbook-block--wa {
            margin-top: .5rem;
            padding: .55rem .65rem;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .process-guide-page .pg-training-wa-accordion {
            margin-top: .5rem;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .process-guide-page .pg-training-wa-accordion-summary {
            cursor: pointer;
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .35rem .5rem;
            padding: .55rem .65rem;
            user-select: none;
        }
        .process-guide-page .pg-training-wa-accordion-summary::-webkit-details-marker {
            display: none;
        }
        .process-guide-page .pg-training-wa-summary-label {
            flex: 1 1 auto;
            font-size: .78rem;
            font-weight: 600;
            color: #334155;
            min-width: 0;
        }
        .process-guide-page .pg-training-wa-summary-hint {
            font-size: .68rem;
            font-weight: 600;
            color: #64748b;
            opacity: .85;
        }
        .process-guide-page .pg-training-wa-accordion[open] .pg-training-wa-summary-hint {
            display: none;
        }
        .process-guide-page .pg-training-wa-accordion-body {
            padding: 0 .65rem .55rem;
            border-top: 1px solid #e2e8f0;
        }
        .process-guide-page .pg-training-wa-accordion-body .pg-training-msg-block {
            margin-top: .45rem;
        }
        .process-guide-page .pg-training-msg-tag {
            display: block;
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: .25rem;
        }
        .process-guide-page .pg-training-playbook--onboarding {
            margin-top: .75rem;
        }
        .process-guide-page .pg-training-pipeline {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }
        .process-guide-page .pg-training-pipeline-item {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            padding: .65rem .75rem;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--pg-line);
        }
        .process-guide-page .pg-training-pipeline-step {
            flex-shrink: 0;
            min-width: 1.75rem;
            height: 1.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #0f766e;
            color: #fff;
            font-size: .72rem;
            font-weight: 800;
        }
        .process-guide-page .pg-training-pipeline-item strong {
            display: block;
            color: #0f172a;
            margin-bottom: .15rem;
        }
        .process-guide-page .pg-training-pipeline-item span { color: #64748b; font-size: .82rem; }
        .process-guide-page .pg-training-formatted .pg-txt-em,
        .process-guide-page .pg-txt-em {
            color: #0f766e;
            font-weight: 700;
        }
        .process-guide-page .pg-training-tab-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .process-guide-page .pg-training-tab-grid-group-label {
            margin: 0 0 .5rem;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
        }
        .process-guide-page .pg-training-tab-grid-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(11.5rem, 1fr));
            gap: .5rem;
        }
        .process-guide-page .pg-training-tab-badge {
            border: 1px solid var(--pg-line);
            border-radius: 10px;
            padding: .55rem .65rem;
            background: #fff;
            border-left-width: 3px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .process-guide-page .pg-training-tab-badge-name {
            display: block;
            font-size: .78rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: .2rem;
        }
        .process-guide-page .pg-training-tab-badge-desc {
            display: block;
            font-size: .72rem;
            color: #64748b;
            line-height: 1.4;
        }
        .process-guide-page .pg-training-tab-badge--neutral { border-left-color: #64748b; background: #f8fafc; }
        .process-guide-page .pg-training-tab-badge--pending { border-left-color: #d97706; background: #fffbeb; }
        .process-guide-page .pg-training-tab-badge--accepted { border-left-color: #059669; background: #ecfdf5; }
        .process-guide-page .pg-training-tab-badge--ongoing { border-left-color: #2563eb; background: #eff6ff; }
        .process-guide-page .pg-training-tab-badge--completed { border-left-color: #0d9488; background: #f0fdfa; }
        .process-guide-page .pg-training-tab-badge--canceled { border-left-color: #dc2626; background: #fef2f2; }
        .process-guide-page .pg-training-tab-badge--reopen { border-left-color: #7c3aed; background: #f5f3ff; }
        .process-guide-page .pg-training-tab-badge--dispute { border-left-color: #be123c; background: #fff1f2; }
        .process-guide-page .pg-training-tab-badge--hold { border-left-color: #ea580c; background: #fff7ed; }
        .process-guide-page .pg-training-tab-badge--settlement { border-left-color: #0891b2; background: #ecfeff; }
        .process-guide-page .pg-training-tab-badge--loss { border-left-color: #b45309; background: #fef3c7; }
        .process-guide-page .pg-training-pipeline-item--phase {
            background: linear-gradient(135deg, #f0fdfa 0%, #f8fafc 100%);
            border-color: #99f6e4;
        }
        .process-guide-page .pg-training-pipeline-item--phase .pg-training-pipeline-step {
            background: #0f766e;
            min-width: 2rem;
            height: 2rem;
            font-size: .8rem;
        }
        .process-guide-page .pg-training-flowchart-block--after-pipeline {
            margin-top: 1rem;
        }
        .process-guide-page .pg-training-legend {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr));
            gap: .55rem;
        }
        .process-guide-page .pg-training-legend-item {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem .65rem;
            border-radius: 8px;
            background: #f8fafc;
            font-size: .78rem;
            font-weight: 700;
            color: #475569;
        }
        .process-guide-page .pg-training-legend-item i {
            width: .85rem;
            height: .85rem;
            border-radius: 3px;
            border: 2px solid;
            flex-shrink: 0;
        }
        .process-guide-page .pg-training-point-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .55rem;
        }
        .process-guide-page .pg-training-point-cards {
            position: relative;
        }
        .process-guide-page .pg-training-point-hint {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin: 0 0 .65rem;
            font-size: .72rem;
            color: #64748b;
        }
        .process-guide-page .pg-training-point-hint .material-icons {
            font-size: 1rem;
            color: #94a3b8;
        }
        @media (max-width: 1100px) {
            .process-guide-page .pg-training-point-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 520px) {
            .process-guide-page .pg-training-point-grid {
                grid-template-columns: 1fr;
            }
        }
        .process-guide-page .pg-training-point-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            text-align: left;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .05);
            cursor: pointer;
            padding: 0;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }
        .process-guide-page .pg-training-point-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
        }
        .process-guide-page .pg-training-point-card.is-selected {
            border-color: #1e40af;
            box-shadow: 0 0 0 2px rgba(30, 64, 175, .15);
        }
        .process-guide-page .pg-training-point-card:focus-visible {
            outline: 2px solid #1e40af;
            outline-offset: 2px;
        }
        .process-guide-page .pg-training-point-card-media {
            aspect-ratio: 16 / 10;
            background: #f1f5f9;
            overflow: hidden;
        }
        .process-guide-page .pg-training-point-card-media img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .process-guide-page .pg-training-point-card-media--icon {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        }
        .process-guide-page .pg-training-point-card-media--icon .material-icons {
            font-size: 2.75rem;
            color: #475569;
        }
        .process-guide-page .pg-training-point-card-body {
            padding: .5rem .6rem .6rem;
            flex: 1 1 auto;
        }
        .process-guide-page .pg-training-point-card-title {
            margin: 0 0 .25rem;
            font-size: .78rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        .process-guide-page .pg-training-point-card-desc {
            margin: 0;
            font-size: .7rem;
            line-height: 1.35;
            color: #64748b;
        }
        .process-guide-page .pg-training-point-card-more {
            display: inline-flex;
            align-items: center;
            margin-top: .45rem;
            font-size: .68rem;
            font-weight: 600;
            color: #1e40af;
        }
        .process-guide-page .pg-training-point-drawer-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1040;
            background: rgba(15, 23, 42, .35);
        }
        .process-guide-page .pg-training-point-drawer {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            width: min(480px, 100vw);
            max-width: 100%;
            height: 100vh;
            background: #fff;
            border-left: 1px solid #e2e8f0;
            box-shadow: -8px 0 24px rgba(15, 23, 42, .12);
            transform: translateX(100%);
            transition: transform .22s ease;
        }
        .process-guide-page .pg-training-point-drawer.is-open {
            transform: translateX(0);
        }
        .process-guide-page .pg-training-point-drawer-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .5rem;
            padding: .85rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .process-guide-page .pg-training-point-drawer-head-main {
            display: flex;
            align-items: flex-start;
            gap: .45rem;
            min-width: 0;
        }
        .process-guide-page .pg-training-point-drawer-icon {
            font-size: 1.35rem;
            color: #1e40af;
            flex-shrink: 0;
        }
        .process-guide-page .pg-training-point-drawer-title {
            margin: 0;
            font-size: .92rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }
        .process-guide-page .pg-training-point-drawer-close {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: none;
            border-radius: 6px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            flex-shrink: 0;
        }
        .process-guide-page .pg-training-point-drawer-close:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .process-guide-page .pg-training-point-drawer-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: .85rem 1rem 1.25rem;
        }
        .process-guide-page .pg-training-point-drawer-hero {
            margin: 0 auto .65rem;
            max-width: 140px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
        }
        .process-guide-page .pg-training-point-drawer-hero img {
            display: block;
            width: 100%;
            height: auto;
            max-height: 88px;
            aspect-ratio: 4 / 3;
            object-fit: cover;
        }
        .process-guide-page .pg-training-point-drawer-detail-block {
            margin-bottom: .85rem;
        }
        .process-guide-page .pg-training-point-drawer-detail {
            margin: 0 0 .5rem;
            font-size: .78rem;
            line-height: 1.55;
            color: #334155;
        }
        .process-guide-page .pg-training-point-drawer-detail-points {
            margin: 0;
            padding-left: 1.1rem;
            font-size: .74rem;
            line-height: 1.5;
            color: #475569;
        }
        .process-guide-page .pg-training-point-drawer-detail-points li + li {
            margin-top: .3rem;
        }
        .process-guide-page .pg-training-point-drawer-section {
            margin-bottom: .85rem;
            padding: .65rem .75rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .process-guide-page .pg-training-point-drawer-section--good {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }
        .process-guide-page .pg-training-point-drawer-section--avoid {
            border-color: #fecaca;
            background: #fef2f2;
        }
        .process-guide-page .pg-training-point-drawer-section--examples {
            border-color: #bfdbfe;
            background: #f8fafc;
        }
        .process-guide-page .pg-training-point-drawer-examples {
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }
        .process-guide-page .pg-training-point-example {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .process-guide-page .pg-training-point-example--good {
            border-color: #bbf7d0;
        }
        .process-guide-page .pg-training-point-example--bad {
            border-color: #fecaca;
        }
        .process-guide-page .pg-training-point-example-media {
            flex: 0 0 88px;
            width: 88px;
            min-height: 66px;
            background: #f1f5f9;
            overflow: hidden;
        }
        .process-guide-page .pg-training-point-example-media img {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 66px;
            max-height: 72px;
            object-fit: cover;
        }
        .process-guide-page .pg-training-point-example-body {
            flex: 1 1 auto;
            min-width: 0;
            padding: .45rem .55rem .5rem;
        }
        .process-guide-page .pg-training-point-example-label {
            display: inline-block;
            margin-bottom: .3rem;
            padding: .12rem .4rem;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            background: #e2e8f0;
            color: #475569;
        }
        .process-guide-page .pg-training-point-example--good .pg-training-point-example-label {
            background: #dcfce7;
            color: #166534;
        }
        .process-guide-page .pg-training-point-example--bad .pg-training-point-example-label {
            background: #fee2e2;
            color: #991b1b;
        }
        .process-guide-page .pg-training-point-example-text {
            margin: 0;
            font-size: .72rem;
            line-height: 1.5;
            color: #334155;
        }
        .process-guide-page .pg-training-point-drawer-section-title {
            display: flex;
            align-items: center;
            gap: .3rem;
            margin: 0 0 .45rem;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #475569;
        }
        .process-guide-page .pg-training-point-drawer-section-title .material-icons {
            font-size: .95rem;
        }
        .process-guide-page .pg-training-point-drawer-section--good .pg-training-point-drawer-section-title {
            color: #166534;
        }
        .process-guide-page .pg-training-point-drawer-section--avoid .pg-training-point-drawer-section-title {
            color: #991b1b;
        }
        .process-guide-page .pg-training-point-drawer-list {
            margin: 0;
            padding-left: 1.1rem;
            font-size: .74rem;
            line-height: 1.5;
            color: #334155;
        }
        .process-guide-page .pg-training-point-drawer-list li + li {
            margin-top: .35rem;
        }
        .process-guide-page .pg-training-deck-guide {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .process-guide-page .pg-training-deck-section {
            padding: .75rem .85rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .process-guide-page .pg-training-deck-section-title {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin: 0 0 .5rem;
            padding-bottom: .45rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #1e293b;
        }
        .process-guide-page .pg-training-deck-section-hint {
            margin: 0 0 .55rem;
            font-size: .74rem;
            color: #64748b;
            line-height: 1.4;
        }
        .process-guide-page .pg-training-type-grid--legend {
            margin-bottom: 0;
        }
        .process-guide-page .pg-training-deck-terms {
            margin: 0;
            display: grid;
            gap: .45rem;
        }
        .process-guide-page .pg-training-deck-term {
            display: grid;
            grid-template-columns: minmax(6.5rem, 8.5rem) 1fr;
            gap: .5rem .75rem;
            padding: .4rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: .78rem;
            line-height: 1.4;
        }
        .process-guide-page .pg-training-deck-term:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .process-guide-page .pg-training-deck-term dt {
            margin: 0;
            font-weight: 700;
            color: #0f172a;
        }
        .process-guide-page .pg-training-deck-term dd {
            margin: 0;
            color: #475569;
        }
        .process-guide-page .pg-training-deck-shapes {
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }
        .process-guide-page .pg-training-deck-shape-row {
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .process-guide-page .pg-training-deck-shape-sample {
            flex-shrink: 0;
            min-width: 6.5rem;
            max-width: 7.5rem;
            margin: 0;
            text-align: center;
            font-size: .68rem;
            padding: .4rem .5rem;
        }
        .process-guide-page .pg-training-deck-shape-desc {
            display: flex;
            flex-direction: column;
            gap: .1rem;
            font-size: .76rem;
            line-height: 1.35;
        }
        .process-guide-page .pg-training-deck-shape-desc strong {
            color: #0f172a;
        }
        .process-guide-page .pg-training-deck-shape-desc span {
            color: #64748b;
        }
        .process-guide-page .pg-training-deck-branches {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
            gap: .45rem;
        }
        .process-guide-page .pg-training-deck-branch-sample {
            text-align: left;
            padding: .5rem .6rem;
        }
        .process-guide-page .pg-training-deck-branch-desc {
            display: block;
            margin-top: .2rem;
            font-size: .64rem;
            font-weight: 400;
            color: #64748b;
            line-height: 1.35;
        }
        .process-guide-page .pg-training-deck-annotations {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }
        .process-guide-page .pg-training-deck-annotation {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            padding: .5rem .6rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: .76rem;
            line-height: 1.35;
        }
        .process-guide-page .pg-training-deck-annotation-icon {
            flex-shrink: 0;
            font-size: 1rem;
            color: #64748b;
            margin-top: .05rem;
        }
        .process-guide-page .pg-training-deck-annotation strong {
            display: block;
            color: #0f172a;
            margin-bottom: .1rem;
        }
        .process-guide-page .pg-training-deck-annotation span {
            color: #64748b;
        }
        .process-guide-page .pg-training-deck-annotation--overview {
            border-left: 3px solid #1e293b;
        }
        .process-guide-page .pg-training-deck-annotation--do {
            border-left: 3px solid #059669;
        }
        .process-guide-page .pg-training-deck-annotation--dont {
            border-left: 3px solid #dc2626;
        }
        .process-guide-page .pg-training-deck-annotation--steps {
            border-left: 3px solid #475569;
        }
        .process-guide-page .pg-training-deck-annotation--wa {
            border-left: 3px solid #25D366;
        }
        @media (max-width: 520px) {
            .process-guide-page .pg-training-deck-term {
                grid-template-columns: 1fr;
                gap: .15rem;
            }
            .process-guide-page .pg-training-deck-shape-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        .process-guide-page .pg-training-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
            gap: .75rem;
        }
        .process-guide-page .pg-training-column {
            padding: .85rem;
            border-radius: 10px;
            border: 1px solid var(--pg-line);
            background: #fafafa;
        }
        .process-guide-page .pg-training-column h5 {
            margin: 0 0 .5rem;
            font-size: .82rem;
            color: #1e293b;
        }
        .process-guide-page .pg-training-column ul {
            margin: 0 0 .55rem;
            padding-left: 1.1rem;
            font-size: .82rem;
        }
        .process-guide-page .pg-training-column-path {
            margin: 0;
            font-size: .76rem;
            color: #64748b;
            padding-top: .45rem;
            border-top: 1px dashed var(--pg-line);
        }
        .process-guide-page .pg-training-decision-q {
            display: inline-block;
            margin: 0 0 .85rem;
            padding: .45rem .75rem;
            border-radius: 8px;
            background: #FEF9C3;
            border: 1px solid #FDE047;
            font-size: .78rem;
            font-weight: 800;
            color: #92400E;
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .process-guide-page .pg-training-paths {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
            gap: .65rem;
            margin-bottom: .85rem;
        }
        .process-guide-page .pg-training-path-card {
            display: block;
            width: 100%;
            text-align: left;
            padding: .75rem;
            border-radius: 10px;
            border: 1px solid #99f6e4;
            background: #ecfdf5;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            font-family: inherit;
        }
        .process-guide-page .pg-training-path-card:hover { background: #d1fae5; }
        .process-guide-page .pg-training-path-label {
            display: block;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f766e;
            margin-bottom: .25rem;
        }
        .process-guide-page .pg-training-bullets,
        .process-guide-page .pg-training-steps,
        .process-guide-page .pg-training-substeps,
        .process-guide-page .pg-training-rules {
            margin: 0;
            padding-left: 1.2rem;
        }
        .process-guide-page .pg-training-steps li + li,
        .process-guide-page .pg-training-substeps li + li { margin-top: .45rem; }
        .process-guide-page .pg-training-branches {
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }
        .process-guide-page .pg-training-branch {
            border-left: 3px solid #99f6e4;
            padding-left: .75rem;
        }
        .process-guide-page .pg-training-branch-label {
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f766e;
            margin-bottom: .35rem;
        }
        .process-guide-page .pg-training-branch-action {
            margin: 0 0 .35rem;
            font-weight: 600;
            color: #0f172a;
        }
        .process-guide-page .pg-training-branch ul {
            margin: 0;
            padding-left: 1.1rem;
            font-size: .84rem;
        }
        .process-guide-page .pg-training-template-inline,
        .process-guide-page .pg-training-template-card blockquote {
            margin: .45rem 0 0;
            padding: .65rem .75rem;
            border-radius: 8px;
            background: #D1FAE5;
            border-left: 3px solid #10B981;
            font-size: .82rem;
            font-style: normal;
            color: #065F46;
        }
        .process-guide-page .pg-training-templates {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }
        .process-guide-page .pg-training-template-card h5 {
            margin: 0 0 .35rem;
            font-size: .8rem;
            color: #0f172a;
        }
        .process-guide-page .pg-training-phases {
            list-style: none;
            margin: .85rem 0 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }
        .process-guide-page .pg-training-phase {
            display: flex;
            gap: .75rem;
            padding: .75rem;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--pg-line);
        }
        .process-guide-page .pg-training-phase-step {
            flex-shrink: 0;
            font-size: .68rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #fff;
            background: #3B82F6;
            padding: .25rem .45rem;
            border-radius: 6px;
            height: fit-content;
        }
        .process-guide-page .pg-training-phase strong { display: block; color: #0f172a; margin-bottom: .2rem; }
        .process-guide-page .pg-training-phase p { margin: 0; font-size: .84rem; color: #475569; }
        .process-guide-page .pg-training-phase-note {
            margin-top: .35rem !important;
            font-size: .76rem !important;
            color: #b45309 !important;
            font-weight: 600;
        }
        .process-guide-page .pg-training-outcomes {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr));
            gap: .55rem;
        }
        .process-guide-page .pg-training-outcomes li {
            padding: .65rem .75rem;
            border-radius: 10px;
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
        }
        .process-guide-page .pg-training-outcomes strong {
            display: block;
            font-size: .78rem;
            color: #065F46;
            margin-bottom: .2rem;
        }
        .process-guide-page .pg-training-outcomes span { font-size: .8rem; color: #047857; }
        .process-guide-page .pg-training-rules li {
            margin-bottom: .45rem;
            padding: .55rem .75rem;
            border-radius: 8px;
            background: #fff7ed;
            border-left: 3px solid #f97316;
            list-style: none;
            margin-left: 0;
        }
        .process-guide-page .pg-training-rules { padding-left: 0; }
        .process-guide-page .pg-training-mindset {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }
        .process-guide-page .pg-training-mindset li {
            padding: .7rem .8rem;
            border-radius: 10px;
            background: #F0FDF4;
            border-left: 3px solid #0f766e;
        }
        .process-guide-page .pg-training-mindset strong {
            display: block;
            color: #065F46;
            margin-bottom: .2rem;
            font-size: .82rem;
        }
        .process-guide-page .pg-training-mindset span { font-size: .82rem; color: #047857; }
        .process-guide-page .pg-training-end-start {
            margin: 0 0 .85rem;
            padding: .55rem .75rem;
            border-radius: 8px;
            background: #EFF6FF;
            font-weight: 700;
            color: #1D4ED8;
            font-size: .82rem;
        }
        .process-guide-page .pg-training-end-group {
            margin-bottom: .75rem;
            padding: .65rem .75rem;
            border-radius: 10px;
            border: 1px solid var(--pg-line);
        }
        .process-guide-page .pg-training-end-group h5 {
            margin: 0 0 .45rem;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .process-guide-page .pg-training-end-group ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: .45rem;
        }
        .process-guide-page .pg-training-end-group li strong {
            display: block;
            font-size: .78rem;
            margin-bottom: .15rem;
        }
        .process-guide-page .pg-training-end-group li span { font-size: .8rem; color: #475569; }
        .process-guide-page .pg-training-end-group--success {
            background: #ECFDF5;
            border-color: #A7F3D0;
        }
        .process-guide-page .pg-training-end-group--success h5 { color: #065F46; }
        .process-guide-page .pg-training-end-group--nurture {
            background: #FFFBEB;
            border-color: #FDE68A;
        }
        .process-guide-page .pg-training-end-group--nurture h5 { color: #92400E; }
        .process-guide-page .pg-training-end-group--closure {
            background: #FEF2F2;
            border-color: #FECACA;
        }
        .process-guide-page .pg-training-end-group--closure h5 { color: #991B1B; }
        .process-guide-page .pg-training-checklist {
            margin: 0;
            padding-left: 1.2rem;
        }
        .process-guide-page .pg-training-checklist li {
            margin-bottom: .65rem;
        }
        .process-guide-page .pg-training-checklist strong {
            display: block;
            color: #0f172a;
            margin-bottom: .2rem;
        }
        .process-guide-page .pg-training-checklist p {
            margin: 0;
            font-size: .84rem;
            color: #475569;
        }
        .process-guide-page .pg-training-checklist-details {
            margin: .35rem 0 0;
            padding-left: 1.1rem;
            font-size: .8rem;
            color: #64748b;
        }
        .process-guide-page .pg-training-checklist-details li {
            margin-bottom: .2rem;
        }
        .process-guide-page .pg-training-slide-subtitle {
            margin: 0 0 .75rem;
            font-size: .86rem;
            color: #64748b;
        }
        /* Training slides — professional deck */
        .process-guide-page .pg-training-follow-block {
            margin: 0 0 .85rem;
            padding: .85rem 1rem;
            border-radius: 6px;
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }
        .process-guide-page .pg-training-follow-title {
            margin: 0 0 .65rem;
            padding-bottom: .45rem;
            border-bottom: 2px solid #1e293b;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #1e293b;
        }
        .process-guide-page .pg-training-follow-steps {
            list-style: none;
            margin: 0;
            padding: 0;
            counter-reset: pg-follow;
        }
        .process-guide-page .pg-training-follow-step {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            padding: .5rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: .84rem;
            line-height: 1.45;
            color: #334155;
        }
        .process-guide-page .pg-training-follow-step:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .process-guide-page .pg-training-follow-steps > .pg-training-follow-step {
            counter-increment: pg-follow;
        }
        .process-guide-page .pg-training-follow-steps > .pg-training-follow-step::before {
            content: counter(pg-follow);
            flex-shrink: 0;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 4px;
            background: #1e293b;
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .process-guide-page .pg-training-follow-step-text {
            flex: 1 1 auto;
            font-weight: 500;
        }
        .process-guide-page .pg-training-follow-step-body {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }
        .process-guide-page .pg-training-follow-step-detail {
            display: block;
            font-size: .74rem;
            font-weight: 400;
            color: #64748b;
            line-height: 1.4;
        }
        .process-guide-page .pg-training-follow-step-extra {
            display: block;
            margin-top: .35rem;
            padding: .4rem .55rem;
            border-radius: 4px;
            font-size: .72rem;
            line-height: 1.45;
        }
        .process-guide-page .pg-training-follow-step-extra-label {
            display: flex;
            align-items: center;
            gap: .25rem;
            margin-bottom: .15rem;
            font-size: .64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .process-guide-page .pg-training-follow-step-extra-label .material-icons {
            font-size: .85rem;
        }
        .process-guide-page .pg-training-follow-step-extra--collect {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
        }
        .process-guide-page .pg-training-follow-step-extra--example {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-style: italic;
        }
        .process-guide-page .pg-training-follow-step-extra--next {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .process-guide-page .pg-training-qualifier {
            margin-bottom: .85rem;
            padding: .65rem .75rem;
            border-radius: 6px;
            border: 1px solid #fcd34d;
            background: #fffbeb;
        }
        .process-guide-page .pg-training-qualifier-title {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin: 0 0 .5rem;
            font-size: .82rem;
            font-weight: 700;
            color: #92400e;
        }
        .process-guide-page .pg-training-qualifier-list {
            display: flex;
            flex-direction: column;
            gap: .45rem;
        }
        .process-guide-page .pg-training-qualifier-item {
            padding: .45rem .55rem;
            border-radius: 4px;
            background: #fff;
            border: 1px solid #fde68a;
        }
        .process-guide-page .pg-training-qualifier-question {
            margin: 0 0 .25rem;
            font-size: .76rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.4;
        }
        .process-guide-page .pg-training-qualifier-meta {
            margin: 0;
            font-size: .72rem;
            line-height: 1.4;
            color: #64748b;
        }
        .process-guide-page .pg-training-qualifier-type {
            display: inline-block;
            margin-right: .35rem;
            padding: .08rem .35rem;
            border-radius: 3px;
            background: #fef3c7;
            color: #92400e;
            font-weight: 700;
            font-size: .64rem;
            text-transform: uppercase;
        }
        .process-guide-page .pg-training-scenarios {
            display: flex;
            flex-direction: column;
            gap: .65rem;
            margin-bottom: .85rem;
        }
        .process-guide-page .pg-training-scenario-card {
            padding: .75rem .85rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
            border-left: 3px solid #1e293b;
        }
        .process-guide-page .pg-training-scenario-row--panel {
            margin-bottom: 0;
            padding-top: .35rem;
            border-top: 1px dashed #e2e8f0;
        }
        .process-guide-page .pg-training-path-steps-block {
            margin: 0 0 .75rem;
            padding: .75rem .85rem;
            border-radius: 6px;
            background: #f8fafc;
            border-left: 3px solid #475569;
        }
        .process-guide-page .pg-training-path-steps-title {
            margin: 0 0 .45rem;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #475569;
        }
        .process-guide-page .pg-training-follow-steps--path > .pg-training-follow-step::before {
            background: #475569;
        }
        .process-guide-page .pg-training-flowchart-block {
            margin-top: .75rem;
            padding: .75rem .85rem;
            border-radius: 6px;
            background: #fff;
            border: 1px solid #e2e8f0;
        }
        .process-guide-page .pg-training-flowchart-block-title {
            margin: 0 0 .55rem;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
        }
        .process-guide-page .pg-training-flowchart-block .pg-training-flowchart {
            margin: 0;
            padding: .65rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .process-guide-page .pg-training-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: .5rem;
            margin-bottom: .85rem;
        }
        .process-guide-page .pg-training-type-grid--detail {
            grid-template-columns: 1fr;
            gap: .65rem;
        }
        .process-guide-page .pg-training-type-grid--row-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .65rem;
            align-items: stretch;
        }
        .process-guide-page .pg-training-type-grid--row-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .55rem;
            align-items: stretch;
        }
        .process-guide-page .pg-training-type-grid--row-3 .pg-training-type-card,
        .process-guide-page .pg-training-type-grid--row-4 .pg-training-type-card {
            height: 100%;
        }
        @media (max-width: 960px) {
            .process-guide-page .pg-training-type-grid--row-3 {
                grid-template-columns: 1fr;
            }
            .process-guide-page .pg-training-type-grid--row-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 520px) {
            .process-guide-page .pg-training-type-grid--row-4 {
                grid-template-columns: 1fr;
            }
        }
        .process-guide-page .pg-training-type-grid--row-3 .pg-training-type-card--rich,
        .process-guide-page .pg-training-type-grid--row-4 .pg-training-type-card--rich {
            padding: .5rem .55rem;
        }
        .process-guide-page .pg-training-type-grid--row-3 .pg-training-type-card strong,
        .process-guide-page .pg-training-type-grid--row-4 .pg-training-type-card strong {
            font-size: .74rem;
        }
        .process-guide-page .pg-training-type-grid--row-3 .pg-training-type-card p,
        .process-guide-page .pg-training-type-grid--row-4 .pg-training-type-card p {
            font-size: .68rem;
            margin-bottom: .35rem;
        }
        .process-guide-page .pg-training-type-grid--row-3 .pg-training-type-points,
        .process-guide-page .pg-training-type-grid--row-4 .pg-training-type-points {
            font-size: .66rem;
            line-height: 1.35;
            padding-left: .95rem;
        }
        .process-guide-page .pg-training-type-grid--row-3 .pg-training-type-points li + li,
        .process-guide-page .pg-training-type-grid--row-4 .pg-training-type-points li + li {
            margin-top: .15rem;
        }
        .process-guide-page .pg-training-type-grid--row-3 .pg-training-type-icon,
        .process-guide-page .pg-training-type-grid--row-4 .pg-training-type-icon {
            font-size: 1.15rem;
            margin-bottom: .2rem;
        }
        .process-guide-page .pg-training-source-group {
            margin-bottom: .65rem;
            padding: .55rem .65rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .process-guide-page .pg-training-source-group .pg-training-type-grid {
            margin-bottom: 0;
        }
        .process-guide-page .pg-training-source-group-head {
            margin-bottom: .45rem;
        }
        .process-guide-page .pg-training-source-group-title {
            margin: 0 0 .15rem;
            font-size: .78rem;
            font-weight: 700;
            color: #0f172a;
        }
        .process-guide-page .pg-training-source-group-hint {
            margin: 0;
            font-size: .68rem;
            line-height: 1.4;
            color: #64748b;
        }
        .process-guide-page .pg-training-source-group--manual {
            border-left: 3px solid #d97706;
            background: #fffbeb;
        }
        .process-guide-page .pg-training-source-group--auto {
            border-left: 3px solid #059669;
            background: #f0fdf4;
        }
        .process-guide-page .pg-training-source-group--warn {
            border-left: 3px solid #f59e0b;
            background: #fffbeb;
        }
        .process-guide-page .pg-training-source-group--warn .pg-training-source-group-title {
            color: #92400e;
        }
        .process-guide-page .pg-training-source-group--danger {
            border-left: 3px solid #dc2626;
            background: #fef2f2;
        }
        .process-guide-page .pg-training-source-group--danger .pg-training-source-group-title {
            color: #991b1b;
        }
        .process-guide-page .pg-training-source-group--info {
            border-left: 3px solid #2563eb;
            background: #eff6ff;
        }
        .process-guide-page .pg-training-source-group--info .pg-training-source-group-title {
            color: #1e40af;
        }
        .process-guide-page .pg-training-shift-checklist {
            margin-bottom: .85rem;
            padding: .65rem .75rem;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
        }
        .process-guide-page .pg-training-shift-checklist-title {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin: 0 0 .45rem;
            font-size: .82rem;
            font-weight: 700;
            color: #1e3a8a;
        }
        .process-guide-page .pg-training-shift-checklist-list {
            margin: 0;
            padding-left: 1.15rem;
            font-size: .74rem;
            line-height: 1.5;
            color: #334155;
        }
        .process-guide-page .pg-training-shift-checklist-list li + li {
            margin-top: .25rem;
        }
        .process-guide-page .pg-training-panel-links {
            margin-bottom: .85rem;
            padding: .65rem .75rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .process-guide-page .pg-training-panel-links-title {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin: 0 0 .5rem;
            font-size: .82rem;
            font-weight: 700;
            color: #0f172a;
        }
        .process-guide-page .pg-training-panel-links-grid,
        .process-guide-page .pg-training-source-guide-links-grid {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        .process-guide-page .pg-training-panel-link {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .35rem .55rem;
            border-radius: 5px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: .72rem;
            font-weight: 600;
            text-decoration: none;
            line-height: 1.3;
        }
        .process-guide-page .pg-training-panel-link:hover {
            background: #dbeafe;
            color: #1e3a8a;
        }
        .process-guide-page .pg-training-panel-link .material-icons {
            font-size: .85rem;
        }
        .process-guide-page .pg-training-panel-link--sm {
            font-size: .68rem;
            padding: .28rem .45rem;
        }
        .process-guide-page .pg-training-source-guide-links {
            padding: .55rem .75rem .65rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .process-guide-page .pg-training-source-guide-links-label {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            margin-bottom: .4rem;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #64748b;
        }
        .process-guide-page .pg-training-source-guide-links-label .material-icons {
            font-size: .85rem;
        }
        .process-guide-page .pg-training-source-guides {
            display: flex;
            flex-direction: column;
            gap: .65rem;
            margin-bottom: .85rem;
        }
        .process-guide-page .pg-training-ui-maps {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            margin-bottom: .85rem;
        }
        .process-guide-page .pg-training-ui-map {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            padding: .85rem;
            display: grid;
            gap: .75rem;
        }
        @media (min-width: 900px) {
            .process-guide-page .pg-training-ui-map:has(.pg-training-ui-map-media) {
                grid-template-columns: minmax(220px, 42%) 1fr;
                grid-template-rows: auto auto 1fr;
                align-items: start;
            }
            .process-guide-page .pg-training-ui-map:has(.pg-training-ui-map-media) .pg-training-ui-map-title {
                grid-column: 1 / -1;
            }
            .process-guide-page .pg-training-ui-map:has(.pg-training-ui-map-media) .pg-training-ui-map-summary {
                grid-column: 1 / -1;
            }
            .process-guide-page .pg-training-ui-map:has(.pg-training-ui-map-media) .pg-training-ui-map-media {
                grid-column: 1;
                grid-row: 3;
                margin-bottom: 0;
            }
            .process-guide-page .pg-training-ui-map:has(.pg-training-ui-map-media) .pg-training-ui-map-steps {
                grid-column: 2;
                grid-row: 3;
                margin: 0;
            }
        }
        .process-guide-page .pg-training-ui-map-title {
            display: flex;
            align-items: center;
            gap: .35rem;
            font-size: .95rem;
            font-weight: 700;
            margin: 0 0 .35rem;
            color: #0f172a;
        }
        .process-guide-page .pg-training-ui-map-summary {
            margin: 0 0 .5rem;
            color: #475569;
            font-size: .875rem;
        }
        .process-guide-page .pg-training-ui-map-media {
            margin-bottom: .5rem;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            padding: .35rem;
        }
        .process-guide-page .pg-training-ui-map-media img {
            display: block;
            width: 100%;
            height: auto;
            max-height: min(420px, 55vh);
            object-fit: contain;
            object-position: top center;
        }
        .process-guide-page .pg-training-ui-map-steps {
            margin: 0;
            padding-left: 1.25rem;
            font-size: .875rem;
            color: #334155;
        }
        .process-guide-page .pg-training-ui-map-steps li + li {
            margin-top: .35rem;
        }
        .process-guide-page .pg-training-ui-map-steps strong {
            display: block;
            color: #0f172a;
        }
        .process-guide-page .pg-training-source-guide {
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
            overflow: hidden;
        }
        .process-guide-page .pg-training-source-guide--manual {
            border-left: 3px solid #d97706;
        }
        .process-guide-page .pg-training-source-guide--inbox {
            border-left: 3px solid #2563eb;
        }
        .process-guide-page .pg-training-source-guide--auto {
            border-left: 3px solid #059669;
        }
        .process-guide-page .pg-training-source-guide--live {
            border-left: 3px solid #dc2626;
        }
        .process-guide-page .pg-training-source-guide-head {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            padding: .65rem .75rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .process-guide-page .pg-training-source-guide-icon {
            font-size: 1.35rem;
            color: #475569;
            flex-shrink: 0;
        }
        .process-guide-page .pg-training-source-guide-head-text {
            flex: 1 1 auto;
            min-width: 0;
        }
        .process-guide-page .pg-training-source-guide-title {
            margin: 0 0 .2rem;
            font-size: .82rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }
        .process-guide-page .pg-training-source-guide-summary {
            margin: 0;
            font-size: .72rem;
            line-height: 1.45;
            color: #64748b;
        }
        .process-guide-page .pg-training-source-guide-badge {
            flex-shrink: 0;
            padding: .15rem .45rem;
            border-radius: 999px;
            font-size: .6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .process-guide-page .pg-training-source-guide-badge--manual {
            background: #fef3c7;
            color: #92400e;
        }
        .process-guide-page .pg-training-source-guide-badge--inbox {
            background: #dbeafe;
            color: #1e40af;
        }
        .process-guide-page .pg-training-source-guide-badge--auto {
            background: #dcfce7;
            color: #166534;
        }
        .process-guide-page .pg-training-source-guide-badge--live {
            background: #fee2e2;
            color: #991b1b;
        }
        .process-guide-page .pg-training-source-guide-cols {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .5rem;
            padding: .65rem .75rem .75rem;
        }
        @media (max-width: 900px) {
            .process-guide-page .pg-training-source-guide-cols {
                grid-template-columns: 1fr;
            }
        }
        .process-guide-page .pg-training-source-guide-col {
            padding: .55rem .6rem;
            border-radius: 5px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .process-guide-page .pg-training-source-guide-col--warn {
            border-color: #fde68a;
            background: #fffbeb;
        }
        .process-guide-page .pg-training-source-guide-col--good {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }
        .process-guide-page .pg-training-source-guide-col-title {
            display: flex;
            align-items: center;
            gap: .25rem;
            margin: 0 0 .4rem;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #475569;
        }
        .process-guide-page .pg-training-source-guide-col-title .material-icons {
            font-size: .9rem;
        }
        .process-guide-page .pg-training-source-guide-col ul {
            margin: 0;
            padding-left: 1rem;
            font-size: .71rem;
            line-height: 1.45;
            color: #334155;
        }
        .process-guide-page .pg-training-source-guide-col li + li {
            margin-top: .3rem;
        }
        .process-guide-page .pg-training-type-card {
            padding: .65rem .7rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
            border-top: 3px solid #cbd5e1;
        }
        .process-guide-page .pg-training-type-card--customer { border-top-color: #059669; }
        .process-guide-page .pg-training-type-card--provider { border-top-color: #2563eb; }
        .process-guide-page .pg-training-type-card--unknown { border-top-color: #d97706; }
        .process-guide-page .pg-training-type-card--future { border-top-color: #7c3aed; }
        .process-guide-page .pg-training-type-card--invalid { border-top-color: #dc2626; }
        .process-guide-page .pg-training-type-card--source { border-top-color: #0d9488; }
        .process-guide-page .pg-training-type-tag {
            display: inline-block;
            margin-bottom: .35rem;
            padding: .1rem .35rem;
            border-radius: 3px;
            background: #f1f5f9;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .04em;
            color: #475569;
        }
        .process-guide-page .pg-training-type-card strong {
            display: block;
            font-size: .78rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: .15rem;
        }
        .process-guide-page .pg-training-type-card p {
            margin: 0;
            font-size: .74rem;
            color: #64748b;
            line-height: 1.35;
        }
        .process-guide-page .pg-training-type-card--rich {
            padding: .75rem .85rem;
        }
        .process-guide-page .pg-training-type-card--rich p {
            margin-bottom: .5rem;
            line-height: 1.45;
        }
        .process-guide-page .pg-training-type-points {
            margin: 0;
            padding-left: 1.1rem;
            font-size: .72rem;
            line-height: 1.45;
            color: #334155;
        }
        .process-guide-page .pg-training-type-points li + li {
            margin-top: .25rem;
        }
        .process-guide-page .pg-training-quick-rules {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .55rem;
            margin-top: .5rem;
        }
        @media (max-width: 520px) {
            .process-guide-page .pg-training-quick-rules { grid-template-columns: 1fr; }
        }
        .process-guide-page .pg-training-quick-rules-col {
            padding: .65rem .75rem;
            border-radius: 6px;
            font-size: .78rem;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .process-guide-page .pg-training-quick-rules-col ul {
            margin: .25rem 0 0;
            padding-left: 1rem;
        }
        .process-guide-page .pg-training-quick-rules-col li { margin-bottom: .15rem; }
        .process-guide-page .pg-training-quick-rules-col--do {
            border-left: 3px solid #059669;
        }
        .process-guide-page .pg-training-quick-rules-col--dont {
            border-left: 3px solid #dc2626;
        }
        .process-guide-page .pg-training-quick-rules-label {
            display: block;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
            margin-bottom: .25rem;
        }
        .process-guide-page .pg-training-sections {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }
        .process-guide-page .pg-training-section-card {
            border: 1px solid var(--pg-line);
            border-radius: 10px;
            padding: .7rem .75rem;
            background: #fff;
        }
        .process-guide-page .pg-training-section-title {
            margin: 0 0 .55rem;
            font-size: .88rem;
            font-weight: 800;
            color: #0f172a;
        }
        .process-guide-page .pg-training-section-intro {
            margin: 0 0 .5rem;
            font-size: .82rem;
            color: #64748b;
        }
        .process-guide-page .pg-training-section-block {
            margin-top: .5rem;
            padding: .5rem .6rem;
            border-radius: 8px;
            font-size: .8rem;
        }
        .process-guide-page .pg-training-section-block-label {
            display: block;
            font-size: .62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: .3rem;
        }
        .process-guide-page .pg-training-section-block ul {
            margin: 0;
            padding-left: 1.1rem;
        }
        .process-guide-page .pg-training-section-block li {
            margin-bottom: .2rem;
        }
        .process-guide-page .pg-training-section-block--mandatory {
            background: #FEF2F2;
            border: 1px solid #FECACA;
        }
        .process-guide-page .pg-training-section-block--mandatory .pg-training-section-block-label { color: #991B1B; }
        .process-guide-page .pg-training-section-block--dont-miss {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
        }
        .process-guide-page .pg-training-section-block--dont-miss .pg-training-section-block-label { color: #92400E; }
        .process-guide-page .pg-training-section-block--roleplay {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
        }
        .process-guide-page .pg-training-section-block--roleplay .pg-training-section-block-label { color: #1D4ED8; }
        .process-guide-page .pg-training-section-block--examples {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
        }
        .process-guide-page .pg-training-section-block--examples .pg-training-section-block-label { color: #475569; }
        .process-guide-page .pg-training-example {
            margin-top: .4rem;
            padding-top: .4rem;
            border-top: 1px dashed #E2E8F0;
        }
        .process-guide-page .pg-training-example:first-of-type {
            margin-top: .25rem;
            padding-top: 0;
            border-top: none;
        }
        .process-guide-page .pg-training-example p {
            margin: .2rem 0 0;
            color: #475569;
        }
        .process-guide-page .pg-training-example-detail {
            margin: .3rem 0 0;
            padding-left: 1.1rem;
            font-size: .78rem;
            color: #64748b;
        }
        .process-guide-page .pg-training-section-card--definition {
            background: #FAFAFA;
        }
        .process-guide-page .pg-training-def-block {
            margin-top: .45rem;
            font-size: .82rem;
            color: #334155;
        }
        .process-guide-page .pg-training-def-block p {
            margin: .2rem 0 0;
        }
        .process-guide-page .pg-training-def-label {
            display: block;
            font-size: .62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: .15rem;
        }
        .process-guide-page .pg-training-def-block--not ul {
            margin: .2rem 0 0;
            padding-left: 1.1rem;
            color: #64748b;
        }
        .process-guide-page .pg-training-def-block--panel code {
            font-size: .78rem;
            background: #F1F5F9;
            padding: .1rem .35rem;
            border-radius: 4px;
        }
        .process-guide-page .pg-training-rp-when {
            margin: 0 0 .45rem;
            font-size: .8rem;
            color: #1e3a5f;
        }
        .process-guide-page .pg-training-rp-script {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            margin: .35rem 0;
        }
        .process-guide-page .pg-training-rp-line {
            display: flex;
            gap: .5rem;
            font-size: .78rem;
            line-height: 1.4;
            padding: .35rem .45rem;
            border-radius: 6px;
        }
        .process-guide-page .pg-training-rp-who {
            flex-shrink: 0;
            min-width: 4.5rem;
            font-weight: 800;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .process-guide-page .pg-training-rp-line--you,
        .process-guide-page .pg-training-rp-line--staff {
            background: #ECFDF5;
        }
        .process-guide-page .pg-training-rp-line--you .pg-training-rp-who,
        .process-guide-page .pg-training-rp-line--staff .pg-training-rp-who { color: #065F46; }
        .process-guide-page .pg-training-rp-line--customer,
        .process-guide-page .pg-training-rp-line--user,
        .process-guide-page .pg-training-rp-line--provider {
            background: #EFF6FF;
        }
        .process-guide-page .pg-training-rp-line--customer .pg-training-rp-who,
        .process-guide-page .pg-training-rp-line--user .pg-training-rp-who,
        .process-guide-page .pg-training-rp-line--provider .pg-training-rp-who { color: #1D4ED8; }
        .process-guide-page .pg-training-slide--definitions .pg-training-section-card {
            border-left: 3px solid #7C3AED;
        }
        .process-guide-page .pg-training-slide--flowchart-only .pg-training-slide-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 280px;
        }
        .process-guide-page .pg-training-flowchart-only {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: .75rem;
            overflow: auto;
            width: 100%;
        }
        .process-guide-page .pg-training-flowchart-only .pg-training-mini-flow {
            width: 100%;
            max-width: 100%;
        }
        .process-guide-page .pg-training-playbook {
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }
        .process-guide-page .pg-training-playbook-card {
            padding: .75rem .85rem;
            border-radius: 10px;
            border: 1px solid var(--pg-line);
            background: #fafafa;
        }
        .process-guide-page .pg-training-playbook-card h5 {
            margin: 0 0 .45rem;
            font-size: .86rem;
            color: #0f172a;
        }
        .process-guide-page .pg-training-playbook-goal {
            margin: 0 0 .5rem;
            font-size: .8rem;
            color: #475569;
        }
        .process-guide-page .pg-training-playbook-goal span {
            display: inline-block;
            font-size: .65rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f766e;
            background: #ecfdf5;
            padding: .12rem .4rem;
            border-radius: 4px;
            margin-right: .35rem;
        }
        .process-guide-page .pg-training-playbook-block {
            margin-top: .45rem;
        }
        .process-guide-page .pg-training-playbook-label {
            display: block;
            font-size: .65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: .25rem;
        }
        .process-guide-page .pg-training-playbook-block ul {
            margin: 0;
            padding-left: 1.1rem;
            font-size: .82rem;
        }
        .process-guide-page .pg-training-playbook-block--say blockquote,
        .process-guide-page .pg-training-playbook-block--panel {
            margin: 0;
        }
        .process-guide-page .pg-training-playbook-next {
            margin: .5rem 0 0;
            font-size: .78rem;
            font-weight: 700;
            color: #5B21B6;
        }
        .process-guide-page .pg-training-playbook-tip {
            margin: .45rem 0 0;
            padding: .45rem .6rem;
            border-radius: 6px;
            background: #FEF9C3;
            font-size: .78rem;
            font-weight: 600;
            color: #854D0E;
        }
        .process-guide-page .pg-training-script {
            margin: 0;
            padding-left: 1.2rem;
        }
        .process-guide-page .pg-training-script li {
            margin-bottom: .55rem;
            font-size: .84rem;
        }
        .process-guide-page .pg-training-script-arrow {
            display: block;
            margin-top: .2rem;
            font-size: .78rem;
            font-weight: 700;
            color: #0f766e;
        }
        .process-guide-page .pg-training-phase-tip {
            margin-top: .35rem !important;
            font-size: .76rem !important;
            color: #854D0E !important;
            font-weight: 600;
            background: #FEF9C3;
            padding: .35rem .5rem;
            border-radius: 6px;
        }
        .process-guide-page .pg-training-flowchart {
            margin: 0 0 1rem;
            padding: .75rem;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px dashed var(--pg-line);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .25rem;
        }
        .process-guide-page .pg-tf-node {
            width: 100%;
            max-width: 20rem;
            padding: .45rem .65rem;
            border-radius: 8px;
            font-size: .74rem;
            font-weight: 700;
            text-align: center;
            border: 2px solid var(--pg-line);
            background: #fff;
            color: #334155;
        }
        .process-guide-page .pg-tf-node--start,
        .process-guide-page .pg-tf-node--end {
            background: #1e293b;
            border-color: #1e293b;
            color: #fff;
            border-radius: 6px;
            font-weight: 600;
        }
        .process-guide-page .pg-tf-node--decision {
            background: #fff;
            border-color: #d97706;
            color: #92400e;
            max-width: 16rem;
            border-width: 2px;
        }
        .process-guide-page .pg-tf-node--action {
            background: #fff;
            border-color: #cbd5e1;
            color: #334155;
        }
        .process-guide-page .pg-tf-node--success { background: #f0fdf4; border-color: #059669; color: #065f46; }
        .process-guide-page .pg-tf-arrow {
            font-size: .85rem;
            color: #94a3b8;
            line-height: 1;
        }
        .process-guide-page .pg-tf-fork {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr));
            gap: .4rem;
        }
        .process-guide-page .pg-tf-branch {
            padding: .4rem .5rem;
            border-radius: 8px;
            border: 1px solid var(--pg-line);
            background: #fff;
            font-size: .68rem;
            text-align: center;
        }
        .process-guide-page .pg-tf-branch-label { display: block; font-weight: 800; color: #0f172a; }
        .process-guide-page .pg-tf-branch-to { display: block; margin-top: .15rem; color: #64748b; font-size: .64rem; }
        .process-guide-page .pg-tf-branch--success { background: #ECFDF5; border-color: #6EE7B7; }
        .process-guide-page .pg-tf-branch--warn { background: #FFFBEB; border-color: #FCD34D; }
        .process-guide-page .pg-tf-branch--danger { background: #FEF2F2; border-color: #FCA5A5; }
        .process-guide-page .pg-tf-branch--neutral { background: #F8FAFC; }
        .process-guide-page .pg-training-msg-formats,
        .process-guide-page .pg-training-roleplay {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }
        .process-guide-page .pg-training-msg-card,
        .process-guide-page .pg-training-roleplay-card {
            padding: .75rem .85rem;
            border-radius: 10px;
            border: 1px solid var(--pg-line);
            background: #fafafa;
        }
        .process-guide-page .pg-training-msg-card h5,
        .process-guide-page .pg-training-roleplay-card h5 {
            margin: 0 0 .45rem;
            font-size: .84rem;
            color: #0f172a;
        }
        .process-guide-page .pg-training-msg-when {
            margin: 0 0 .5rem;
            font-size: .78rem;
            color: #475569;
        }
        .process-guide-page .pg-training-msg-pre {
            margin: .3rem 0 0;
            padding: .65rem .75rem;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #E2E8F0;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .72rem;
            line-height: 1.45;
            white-space: pre-wrap;
            word-break: break-word;
            color: #1e293b;
        }
        .process-guide-page .pg-training-msg-block--example .pg-training-msg-pre {
            background: #D1FAE5;
            border-color: #6EE7B7;
        }
        .process-guide-page .pg-training-msg-tips {
            margin: .5rem 0 0;
            padding-left: 1.1rem;
            font-size: .76rem;
            color: #854D0E;
        }
        .process-guide-page .pg-training-roleplay-user {
            margin: .45rem 0;
            padding: .55rem .65rem;
            background: #F1F5F9;
            border-left: 3px solid #64748B;
            font-size: .82rem;
            font-style: normal;
        }
        .process-guide-page .pg-training-roleplay-good {
            margin: .45rem 0;
            padding: .55rem .65rem;
            background: #ECFDF5;
            border-left: 3px solid #10B981;
            font-size: .82rem;
            font-style: normal;
            color: #065F46;
        }
        .process-guide-page .pg-training-roleplay-panel {
            margin: .4rem 0 0;
            font-size: .78rem;
            color: #334155;
        }
        .process-guide-page .pg-training-roleplay-avoid {
            margin: .35rem 0 0;
            font-size: .76rem;
            color: #B91C1C;
            font-weight: 600;
        }
        .process-guide-page .pg-training-quiz {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }
        .process-guide-page .pg-training-quiz-q {
            padding: .85rem 1rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .process-guide-page .pg-training-quiz-question {
            margin: 0 0 .55rem;
            font-size: .84rem;
            font-weight: 600;
            color: #0f172a;
        }
        .process-guide-page .pg-training-quiz-question span {
            color: #64748b;
            margin-right: .25rem;
            font-weight: 700;
        }
        .process-guide-page .pg-training-quiz-options {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }
        .process-guide-page .pg-training-quiz-opt {
            text-align: left;
            padding: .5rem .7rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #f8fafc;
            font-size: .78rem;
            font-family: inherit;
            cursor: pointer;
            color: #334155;
        }
        .process-guide-page .pg-training-quiz-opt:hover:not(:disabled) { background: #f1f5f9; border-color: #cbd5e1; }
        .process-guide-page .pg-training-quiz-opt:disabled { cursor: default; opacity: .85; }
        .process-guide-page .pg-training-quiz-opt.is-correct {
            background: #ecfdf5;
            border-color: #059669;
            color: #065f46;
            font-weight: 600;
        }
        .process-guide-page .pg-training-quiz-opt.is-wrong {
            background: #fef2f2;
            border-color: #dc2626;
            color: #991b1b;
        }
        .process-guide-page .pg-training-quiz-explain {
            margin: .5rem 0 0;
            font-size: .76rem;
            color: #475569;
            padding: .5rem .6rem;
            background: #f8fafc;
            border-radius: 4px;
            border-left: 3px solid #475569;
        }
        .process-guide-page .pg-training-quiz-score {
            margin: .25rem 0 0;
            padding: .75rem .85rem;
            border-radius: 6px;
            background: #1e293b;
            font-weight: 600;
            font-size: .84rem;
            color: #fff;
            text-align: center;
        }
        .process-guide-page .pg-training-master-flow p {
            margin: 0 0 .45rem;
            padding: .55rem .75rem;
            border-radius: 8px;
            background: #f1f5f9;
            font-family: ui-monospace, monospace;
            font-size: .78rem;
            color: #334155;
        }
        .process-guide-page .pg-training-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .65rem .85rem;
            border-top: 1px solid var(--pg-line);
            background: #f8fafc;
            flex-shrink: 0;
        }
        .process-guide-page .pg-training-nav-btn {
            padding: .5rem 1rem;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            background: #fff;
            font-size: .78rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            font-family: inherit;
            min-width: 7rem;
        }
        .process-guide-page .pg-training-nav-btn:hover:not(:disabled) { background: #f1f5f9; }
        .process-guide-page .pg-training-nav-btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }
        .process-guide-page .pg-training-nav-btn--primary {
            background: #1e293b;
            border-color: #1e293b;
            color: #fff;
        }
        .process-guide-page .pg-training-nav-btn--primary:hover:not(:disabled) { background: #334155; }
        .process-guide-page .pg-training-nav-counter {
            font-size: .78rem;
            font-weight: 700;
            color: #64748b;
            font-variant-numeric: tabular-nums;
        }

        .process-guide-page .pg-flow-layout {
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            align-items: stretch;
        }
        .process-guide-page .pg-flow-layout .pg-flow {
            flex: 1 1 auto;
            min-width: 0;
        }
        .process-guide-page .pg-editor-panel {
            display: none;
            flex-direction: column;
            width: min(19rem, 38vw);
            flex-shrink: 0;
            margin-right: .75rem;
            border: 1px solid var(--pg-line);
            border-radius: 14px;
            background: #fff;
            box-shadow: var(--pg-shadow);
            overflow: hidden;
        }
        .process-guide-page .pg-flow-layout.is-editing .pg-editor-panel {
            display: flex;
        }
        .process-guide-page .pg-editor-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .5rem;
            padding: .85rem .9rem .65rem;
            border-bottom: 1px solid var(--pg-line);
            background: linear-gradient(180deg, #ecfdf5 0%, #fff 100%);
        }
        .process-guide-page .pg-editor-header h4 {
            margin: 0;
            font-family: Outfit, sans-serif;
            font-size: .95rem;
            color: #0f172a;
        }
        .process-guide-page .pg-editor-sub {
            margin: .2rem 0 0;
            font-size: .68rem;
            color: #64748b;
            line-height: 1.4;
        }
        .process-guide-page .pg-editor-close {
            border: 1px solid var(--pg-line);
            background: #fff;
            width: 1.65rem;
            height: 1.65rem;
            border-radius: 8px;
            cursor: pointer;
            color: #64748b;
            flex-shrink: 0;
        }
        .process-guide-page .pg-editor-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding: .65rem .75rem;
        }
        .process-guide-page .pg-editor-section + .pg-editor-section {
            margin-top: .85rem;
            padding-top: .75rem;
            border-top: 1px dashed var(--pg-line);
        }
        .process-guide-page .pg-editor-section h5 {
            margin: 0 0 .45rem;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #475569;
        }
        .process-guide-page .pg-editor-label {
            display: block;
            font-size: .68rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: .55rem;
        }
        .process-guide-page .pg-editor-label input,
        .process-guide-page .pg-editor-label textarea,
        .process-guide-page .pg-editor-label select {
            display: block;
            width: 100%;
            margin-top: .25rem;
            padding: .4rem .5rem;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            font-size: .78rem;
            font-family: inherit;
            color: #0f172a;
            background: #fff;
        }
        .process-guide-page .pg-editor-empty {
            margin: 0;
            font-size: .75rem;
            color: #94a3b8;
        }
        .process-guide-page .pg-editor-btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }
        .process-guide-page .pg-editor-btn-row button,
        .process-guide-page .pg-editor-danger {
            padding: .35rem .55rem;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            background: #fff;
            font-size: .72rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
        }
        .process-guide-page .pg-editor-btn-row button:hover { background: #f8fafc; }
        .process-guide-page .pg-editor-danger {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fef2f2;
            width: 100%;
            margin-top: .35rem;
        }
        .process-guide-page .pg-editor-groups {
            display: flex;
            flex-direction: column;
            gap: .3rem;
            margin-bottom: .45rem;
        }
        .process-guide-page .pg-editor-group-item {
            text-align: left;
            padding: .4rem .55rem;
            border: 1px solid var(--pg-line);
            border-radius: 8px;
            background: #f8fafc;
            font-size: .72rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
        }
        .process-guide-page .pg-editor-group-item.is-active {
            border-color: #7C3AED;
            background: #f5f3ff;
            color: #5B21B6;
        }
        .process-guide-page .pg-editor-footer {
            padding: .65rem .75rem;
            border-top: 1px solid var(--pg-line);
            background: #f8fafc;
            flex-shrink: 0;
        }
        .process-guide-page .pg-editor-save {
            width: 100%;
            padding: .5rem .75rem;
            border: none;
            border-radius: 8px;
            background: #0f766e;
            color: #fff;
            font-size: .78rem;
            font-weight: 800;
            cursor: pointer;
        }
        .process-guide-page .pg-editor-save:hover { background: #0d9488; }
        .process-guide-page .pg-editor-save:disabled { opacity: .6; cursor: wait; }
        .process-guide-page .pg-editor-status {
            display: block;
            margin-top: .35rem;
            font-size: .68rem;
            font-weight: 600;
            color: #64748b;
            min-height: 1rem;
        }
        .process-guide-page .pg-flow.is-edit-mode .pg-miro-node.is-editable { cursor: grab; }
        .process-guide-page .pg-flow.is-edit-mode .pg-miro-node.is-editable:active { cursor: grabbing; }
        .process-guide-page .pg-flow-actions [data-pg-edit-toggle].is-active {
            background: #0f766e;
            border-color: #0f766e;
            color: #fff;
        }
    </style>
@endpush

@section('content')
    <div class="main-content process-guide-page">
        <div class="container-fluid">
            <div class="pg-guide-tabs-row">
                <nav class="pg-guide-tabs" aria-label="Process guides">
                    @foreach ($guides ?? [] as $guideOption)
                        <a
                            href="{{ route('admin.process-guides.index', ['guide' => $guideOption['key']]) }}"
                            class="pg-guide-tab{{ ($guideOption['key'] ?? '') === $activeGuideKey ? ' is-active' : '' }}"
                        >{{ $guideOption['title'] ?? '' }}</a>
                    @endforeach
                </nav>

                @can('lead_view')
                    <a href="{{ route('admin.workflow.stuck') }}" class="btn btn-sm btn--secondary ms-auto flex-shrink-0">
                        <span class="material-icons align-middle" style="font-size:16px;">pending_actions</span>
                        {{ translate('Workflow_Stuck_Items') }}
                    </a>
                @endcan

                <div
                    class="pg-guide-search"
                    id="pg-guide-search"
                    data-pg-search-base="{{ route('admin.process-guides.index') }}"
                >
                    <script type="application/json" id="pg-guide-search-index">@json($trainingSearchIndex ?? [])</script>
                    <label class="visually-hidden" for="pg-guide-search-input">Search training</label>
                    <div class="pg-guide-search-input-wrap">
                        <span class="material-icons pg-guide-search-icon" aria-hidden="true">search</span>
                        <input
                            type="search"
                            id="pg-guide-search-input"
                            class="pg-guide-search-input"
                            placeholder="Search training… e.g. booking, follow up"
                            autocomplete="off"
                            spellcheck="false"
                            role="combobox"
                            aria-expanded="false"
                            aria-controls="pg-guide-search-dropdown"
                            aria-autocomplete="list"
                        >
                        <button type="button" class="pg-guide-search-clear" data-pg-search-clear aria-label="Clear search">
                            <span class="material-icons" aria-hidden="true">close</span>
                        </button>
                    </div>
                    <div class="pg-guide-search-dropdown" id="pg-guide-search-dropdown" hidden></div>
                </div>
            </div>

            <div class="pg-training-panel" id="pg-panel-training">
                @include('adminmodule::admin.process-guide.partials._training-guide', [
                    'title' => $miroTitle,
                    'trainingSubtitle' => $guide['training_subtitle'] ?? null,
                    'trainingGuideClass' => $guide['training_guide'] ?? null,
                    'flowchartsClass' => $guide['flowcharts'] ?? null,
                    'initialSlideId' => $initialSlideId ?? '',
                ])
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
(function () {
    var root = document.getElementById('pg-guide-search');
    if (!root) return;

    var input = document.getElementById('pg-guide-search-input');
    var dropdown = document.getElementById('pg-guide-search-dropdown');
    var clearBtn = root.querySelector('[data-pg-search-clear]');
    var baseUrl = root.getAttribute('data-pg-search-base') || '';
    var index = [];
    var activeResult = -1;
    var visibleResults = [];

    try {
        var indexEl = document.getElementById('pg-guide-search-index');
        index = JSON.parse(indexEl ? indexEl.textContent : '[]');
    } catch (e) {
        index = [];
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function highlightSnippet(snippet, terms) {
        var safe = escapeHtml(snippet || '');
        terms.forEach(function (term) {
            if (!term) return;
            var re = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
            safe = safe.replace(re, '<mark>$1</mark>');
        });
        return safe;
    }

    function scoreEntry(entry, terms) {
        var haystack = (entry.slideTitle + ' ' + entry.guideTitle + ' ' + entry.text).toLowerCase();
        var score = 0;
        terms.forEach(function (term) {
            if (!term) return;
            if (entry.slideTitle.toLowerCase().indexOf(term) !== -1) score += 12;
            if (entry.guideTitle.toLowerCase().indexOf(term) !== -1) score += 6;
            if (haystack.indexOf(term) !== -1) score += 2;
        });
        return score;
    }

    function search(query) {
        var terms = query.toLowerCase().trim().split(/\s+/).filter(Boolean);
        if (!terms.length) return [];

        return index
            .filter(function (entry) {
                var haystack = (entry.slideTitle + ' ' + entry.guideTitle + ' ' + entry.text).toLowerCase();
                return terms.every(function (term) {
                    return haystack.indexOf(term) !== -1;
                });
            })
            .sort(function (a, b) {
                return scoreEntry(b, terms) - scoreEntry(a, terms);
            })
            .slice(0, 12);
    }

    function resultUrl(entry) {
        var url = new URL(baseUrl, window.location.origin);
        url.searchParams.set('guide', entry.guideKey);
        if (entry.slideId) {
            url.searchParams.set('slide', entry.slideId);
        }
        return url.toString();
    }

    function renderResults(results, terms) {
        activeResult = -1;
        visibleResults = results;

        if (!terms.length) {
            dropdown.innerHTML = '<div class="pg-guide-search-hint">Type to search all training slides — booking, follow up, tabs, etc.</div>';
            dropdown.removeAttribute('hidden');
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        if (!results.length) {
            dropdown.innerHTML = '<div class="pg-guide-search-empty">No training matches for “' + escapeHtml(terms.join(' ')) + '”.</div>';
            dropdown.removeAttribute('hidden');
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        dropdown.innerHTML = results.map(function (entry, i) {
            return (
                '<button type="button" class="pg-guide-search-result" data-pg-search-result="' + i + '">' +
                    '<div class="pg-guide-search-result-head">' +
                        '<span class="pg-guide-search-result-title">' + escapeHtml(entry.slideTitle) + '</span>' +
                        '<span class="pg-guide-search-result-badge">Slide ' + escapeHtml(String(entry.slideNumber)) + '</span>' +
                    '</div>' +
                    '<div class="pg-guide-search-result-meta">' + escapeHtml(entry.guideTitle) + '</div>' +
                    '<div class="pg-guide-search-result-snippet">' + highlightSnippet(entry.snippet, terms) + '</div>' +
                '</button>'
            );
        }).join('');

        dropdown.removeAttribute('hidden');
        input.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
        dropdown.setAttribute('hidden', '');
        input.setAttribute('aria-expanded', 'false');
        activeResult = -1;
    }

    function setActiveResult(next) {
        var buttons = dropdown.querySelectorAll('[data-pg-search-result]');
        if (!buttons.length) return;
        activeResult = Math.max(0, Math.min(next, buttons.length - 1));
        buttons.forEach(function (btn, i) {
            btn.classList.toggle('is-active', i === activeResult);
        });
        buttons[activeResult].scrollIntoView({ block: 'nearest' });
    }

    function openResult(entry) {
        if (!entry) return;
        window.location.href = resultUrl(entry);
    }

    function syncClearButton() {
        if (!clearBtn) return;
        clearBtn.classList.toggle('is-visible', !!input.value.trim());
    }

    input.addEventListener('input', function () {
        syncClearButton();
        renderResults(search(input.value), input.value.toLowerCase().trim().split(/\s+/).filter(Boolean));
    });

    input.addEventListener('focus', function () {
        renderResults(search(input.value), input.value.toLowerCase().trim().split(/\s+/).filter(Boolean));
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDropdown();
            input.blur();
            return;
        }

        if (dropdown.hasAttribute('hidden')) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveResult(activeResult + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveResult(activeResult <= 0 ? 0 : activeResult - 1);
        } else if (e.key === 'Enter') {
            if (activeResult >= 0 && visibleResults[activeResult]) {
                e.preventDefault();
                openResult(visibleResults[activeResult]);
            } else if (visibleResults.length === 1) {
                e.preventDefault();
                openResult(visibleResults[0]);
            }
        }
    });

    dropdown.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-pg-search-result]');
        if (!btn) return;
        var idx = parseInt(btn.getAttribute('data-pg-search-result'), 10);
        openResult(visibleResults[idx]);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            input.value = '';
            syncClearButton();
            closeDropdown();
            input.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) {
            closeDropdown();
        }
    });

    syncClearButton();
})();
</script>
@endpush

