@extends('adminmodule::layouts.new-master')

@section('title', translate('Task_Board'))

@push('css_or_js')
    <style>
        .staff-chat-entity-link { display: inline-flex; align-items: center; gap: .15rem; }
        .staff-chat-entity-type { font-size: .7rem; text-transform: uppercase; letter-spacing: .02em; }
        .task-board-page { --tb-col-min: 360px; }
        .task-board-toolbar {
            background: transparent;
            border: 0;
            border-radius: 0;
            padding: 0;
            margin-bottom: 0;
        }
        .task-board-header-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }
        .task-board-header-search {
            min-width: 180px;
            width: 220px;
        }
        .task-board-assignee-avatars {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0;
        }
        .day-detail-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #fff;
            margin-left: -8px;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e9ecef;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 0 0 1px #dee2e6;
            position: relative;
            flex-shrink: 0;
            transition: transform .15s ease, box-shadow .15s ease;
            cursor: pointer;
            padding: 0;
        }
        .day-detail-avatar:first-child { margin-left: 0; }
        .day-detail-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .day-detail-avatar:hover {
            transform: translateY(-2px);
            z-index: 2;
            color: #fff;
        }
        .day-detail-avatar.is-active {
            box-shadow: 0 0 0 2px var(--bs-primary, #0d6efd);
            z-index: 3;
        }
        .day-detail-avatar.is-all {
            background: #212529;
            color: #fff;
            font-size: 10px;
            width: auto;
            min-width: 36px;
            padding: 0 .55rem;
            border-radius: 999px;
        }
        .day-detail-avatar.is-more {
            background: #e9ecef;
            color: #212529;
        }
        .day-detail-mini-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dee2e6;
            font-size: 11px;
            font-weight: 600;
            color: #495057;
        }
        .day-detail-avatar-letter-a, .day-detail-mini-avatar.day-detail-avatar-letter-a { background: #e53935; color: #fff; }
        .day-detail-avatar-letter-b, .day-detail-mini-avatar.day-detail-avatar-letter-b { background: #d81b60; color: #fff; }
        .day-detail-avatar-letter-c, .day-detail-mini-avatar.day-detail-avatar-letter-c { background: #8e24aa; color: #fff; }
        .day-detail-avatar-letter-d, .day-detail-mini-avatar.day-detail-avatar-letter-d { background: #5e35b1; color: #fff; }
        .day-detail-avatar-letter-e, .day-detail-mini-avatar.day-detail-avatar-letter-e { background: #3949ab; color: #fff; }
        .day-detail-avatar-letter-f, .day-detail-mini-avatar.day-detail-avatar-letter-f { background: #1e88e5; color: #fff; }
        .day-detail-avatar-letter-g, .day-detail-mini-avatar.day-detail-avatar-letter-g { background: #039be5; color: #fff; }
        .day-detail-avatar-letter-h, .day-detail-mini-avatar.day-detail-avatar-letter-h { background: #00acc1; color: #fff; }
        .day-detail-avatar-letter-i, .day-detail-mini-avatar.day-detail-avatar-letter-i { background: #00897b; color: #fff; }
        .day-detail-avatar-letter-j, .day-detail-mini-avatar.day-detail-avatar-letter-j { background: #43a047; color: #fff; }
        .day-detail-avatar-letter-k, .day-detail-mini-avatar.day-detail-avatar-letter-k { background: #7cb342; color: #fff; }
        .day-detail-avatar-letter-l, .day-detail-mini-avatar.day-detail-avatar-letter-l { background: #c0ca33; color: #212529; }
        .day-detail-avatar-letter-m, .day-detail-mini-avatar.day-detail-avatar-letter-m { background: #fdd835; color: #212529; }
        .day-detail-avatar-letter-n, .day-detail-mini-avatar.day-detail-avatar-letter-n { background: #ffb300; color: #212529; }
        .day-detail-avatar-letter-o, .day-detail-mini-avatar.day-detail-avatar-letter-o { background: #fb8c00; color: #fff; }
        .day-detail-avatar-letter-p, .day-detail-mini-avatar.day-detail-avatar-letter-p { background: #f4511e; color: #fff; }
        .day-detail-avatar-letter-q, .day-detail-mini-avatar.day-detail-avatar-letter-q { background: #6d4c41; color: #fff; }
        .day-detail-avatar-letter-r, .day-detail-mini-avatar.day-detail-avatar-letter-r { background: #546e7a; color: #fff; }
        .day-detail-avatar-letter-s, .day-detail-mini-avatar.day-detail-avatar-letter-s { background: #00838f; color: #fff; }
        .day-detail-avatar-letter-t, .day-detail-mini-avatar.day-detail-avatar-letter-t { background: #2e7d32; color: #fff; }
        .day-detail-avatar-letter-u, .day-detail-mini-avatar.day-detail-avatar-letter-u { background: #1565c0; color: #fff; }
        .day-detail-avatar-letter-v, .day-detail-mini-avatar.day-detail-avatar-letter-v { background: #6a1b9a; color: #fff; }
        .day-detail-avatar-letter-w, .day-detail-mini-avatar.day-detail-avatar-letter-w { background: #ad1457; color: #fff; }
        .day-detail-avatar-letter-x, .day-detail-mini-avatar.day-detail-avatar-letter-x { background: #c62828; color: #fff; }
        .day-detail-avatar-letter-y, .day-detail-mini-avatar.day-detail-avatar-letter-y { background: #ef6c00; color: #fff; }
        .day-detail-avatar-letter-z, .day-detail-mini-avatar.day-detail-avatar-letter-z { background: #455a64; color: #fff; }
        .day-detail-more-menu {
            min-width: 220px;
            max-height: 280px;
            overflow: auto;
        }
        .day-detail-more-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .45rem .85rem;
            cursor: pointer;
        }
        .day-detail-more-menu .dropdown-item img,
        .day-detail-more-menu .day-detail-mini-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .task-board-icon-btn {
            position: relative;
            width: 34px;
            height: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d7dde5;
            background: #fff;
            color: #475569;
            border-radius: 8px;
        }
        .task-board-icon-btn:hover,
        .task-board-icon-btn.show,
        .task-board-icon-btn.is-active {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .task-board-icon-btn .material-symbols-outlined {
            font-size: 18px;
        }
        .task-board-icon-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            line-height: 16px;
            text-align: center;
            pointer-events: none;
            box-shadow: 0 0 0 2px #fff;
        }
        .task-board-sort-menu,
        .task-board-filter-menu {
            min-width: 260px;
            padding: .5rem 0;
        }
        .task-board-filter-menu {
            min-width: 300px;
            padding: .85rem;
        }
        .task-board-sort-menu .dropdown-item.active {
            font-weight: 600;
        }
        @media (max-width: 991.98px) {
            .task-board-header-search {
                width: 100%;
                min-width: 0;
            }
        }
        .task-board-scroller {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            min-height: 70vh;
            align-items: flex-start;
        }
        .task-column {
            --column-color: #64748b;
            flex: 0 0 var(--tb-col-min);
            width: var(--tb-col-min);
            background: color-mix(in srgb, var(--column-color) 14%, #ffffff);
            border: 1px solid color-mix(in srgb, var(--column-color) 28%, #e2e8f0);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 220px);
        }
        .task-column-header {
            padding: .85rem 1rem;
            border-bottom: 1px solid color-mix(in srgb, var(--column-color) 22%, #e2e8f0);
            background: color-mix(in srgb, var(--column-color) 10%, transparent);
            display: flex;
            align-items: center;
            gap: .5rem;
            cursor: grab;
            border-radius: 12px 12px 0 0;
        }
        .task-column-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            background: var(--column-color);
        }
        .task-column-title { font-weight: 700; font-size: .95rem; margin: 0; flex: 1; }
        .task-column-count {
            font-size: .75rem;
            background: color-mix(in srgb, var(--column-color) 18%, #ffffff);
            border: 1px solid color-mix(in srgb, var(--column-color) 30%, transparent);
            border-radius: 999px;
            padding: .1rem .45rem;
            color: #334155;
        }
        .task-column-body {
            padding: .75rem;
            overflow-y: auto;
            flex: 1;
            min-height: 120px;
        }
        .task-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: .75rem;
            margin-bottom: .65rem;
            cursor: grab;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            transition: box-shadow .15s ease, transform .15s ease;
            display: flex;
            flex-direction: column;
            gap: .45rem;
        }
        .task-card:hover { box-shadow: 0 6px 16px rgba(15, 23, 42, .08); }
        .task-card.sortable-ghost { opacity: .45; }
        .task-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .5rem;
        }
        .task-card-title {
            font-weight: 650;
            font-size: .9rem;
            margin: 0;
            color: #0f172a;
            line-height: 1.35;
            min-width: 0;
            flex: 1 1 auto;
            padding-right: .15rem;
        }
        .task-card-assignees {
            display: flex;
            align-items: center;
            flex-direction: row-reverse;
            flex-shrink: 0;
            margin-top: .05rem;
        }
        .task-card-creator {
            display: flex;
            align-items: center;
            gap: .4rem;
            min-width: 0;
        }
        .task-card-creator-avatar {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid #fff;
            box-shadow: 0 0 0 1px #e2e8f0;
        }
        img.task-card-creator-avatar {
            background: #e2e8f0;
        }
        .task-card-creator-avatar.task-card-avatar-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .5rem;
            font-weight: 700;
            color: #fff;
            line-height: 1;
            background: #64748b;
        }
        .task-card-creator-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
            line-height: 1.15;
        }
        .task-card-creator-label {
            font-size: .58rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #94a3b8;
        }
        .task-card-creator-name {
            font-size: .72rem;
            color: #475569;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .task-card-links { display: flex; flex-wrap: wrap; gap: .25rem; }
        .task-card-dates {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .4rem;
        }
        .task-date-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .12rem;
            max-width: calc(50% - .2rem);
            min-width: 0;
        }
        .task-date-block:last-child { margin-left: auto; }
        .task-date-pill-label {
            font-size: .52rem;
            font-weight: 500;
            color: #94a3b8;
            letter-spacing: .01em;
            white-space: nowrap;
            text-align: center;
            line-height: 1;
        }
        .task-date-pill {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            max-width: 100%;
            padding: .2rem .5rem .2rem .38rem;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: .65rem;
            font-weight: 500;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .task-date-pill .material-symbols-outlined {
            font-size: 12px;
            line-height: 1;
            color: inherit;
            flex-shrink: 0;
        }
        .task-date-pill.is-today {
            background: #fef3c7;
            border-color: #fde68a;
            color: #b45309;
        }
        .task-date-pill.is-overdue {
            background: #fee2e2;
            border-color: #fecaca;
            color: #dc2626;
        }
        .task-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-top: .15rem;
            padding-top: .35rem;
        }
        .task-card-code {
            font-size: .72rem;
            font-weight: 600;
            color: #64748b;
            letter-spacing: .02em;
        }
        .task-card-footer-right {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-left: auto;
        }
        .task-card-stat {
            display: inline-flex;
            align-items: center;
            gap: .1rem;
            font-size: .68rem;
            color: #94a3b8;
        }
        .task-card-stat .material-symbols-outlined {
            font-size: 15px;
            color: #94a3b8;
        }
        .task-card-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            margin-left: -7px;
            box-shadow: 0 0 0 1px #e2e8f0;
            flex-shrink: 0;
        }
        img.task-card-avatar {
            background: #e2e8f0;
        }
        .task-card-assignees .task-card-avatar:last-child {
            margin-left: 0;
        }
        .task-card-avatar-fallback,
        .task-card-avatar-more {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .58rem;
            font-weight: 700;
            color: #fff;
            line-height: 1;
            background: #64748b;
        }
        .task-card-avatar-fallback.day-detail-avatar-letter-a,
        .task-card-creator-avatar.day-detail-avatar-letter-a,
        .ticket-created-by-avatar.day-detail-avatar-letter-a { background: #e53935; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-b,
        .task-card-creator-avatar.day-detail-avatar-letter-b,
        .ticket-created-by-avatar.day-detail-avatar-letter-b { background: #d81b60; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-c,
        .task-card-creator-avatar.day-detail-avatar-letter-c,
        .ticket-created-by-avatar.day-detail-avatar-letter-c { background: #8e24aa; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-d,
        .task-card-creator-avatar.day-detail-avatar-letter-d,
        .ticket-created-by-avatar.day-detail-avatar-letter-d { background: #5e35b1; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-e,
        .task-card-creator-avatar.day-detail-avatar-letter-e,
        .ticket-created-by-avatar.day-detail-avatar-letter-e { background: #3949ab; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-f,
        .task-card-creator-avatar.day-detail-avatar-letter-f,
        .ticket-created-by-avatar.day-detail-avatar-letter-f { background: #1e88e5; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-g,
        .task-card-creator-avatar.day-detail-avatar-letter-g,
        .ticket-created-by-avatar.day-detail-avatar-letter-g { background: #039be5; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-h,
        .task-card-creator-avatar.day-detail-avatar-letter-h,
        .ticket-created-by-avatar.day-detail-avatar-letter-h { background: #00acc1; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-i,
        .task-card-creator-avatar.day-detail-avatar-letter-i,
        .ticket-created-by-avatar.day-detail-avatar-letter-i { background: #00897b; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-j,
        .task-card-creator-avatar.day-detail-avatar-letter-j,
        .ticket-created-by-avatar.day-detail-avatar-letter-j { background: #43a047; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-k,
        .task-card-creator-avatar.day-detail-avatar-letter-k,
        .ticket-created-by-avatar.day-detail-avatar-letter-k { background: #7cb342; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-l,
        .task-card-creator-avatar.day-detail-avatar-letter-l,
        .ticket-created-by-avatar.day-detail-avatar-letter-l { background: #c0ca33; color: #212529; }
        .task-card-avatar-fallback.day-detail-avatar-letter-m,
        .task-card-creator-avatar.day-detail-avatar-letter-m,
        .ticket-created-by-avatar.day-detail-avatar-letter-m { background: #fdd835; color: #212529; }
        .task-card-avatar-fallback.day-detail-avatar-letter-n,
        .task-card-creator-avatar.day-detail-avatar-letter-n,
        .ticket-created-by-avatar.day-detail-avatar-letter-n { background: #ffb300; color: #212529; }
        .task-card-avatar-fallback.day-detail-avatar-letter-o,
        .task-card-creator-avatar.day-detail-avatar-letter-o,
        .ticket-created-by-avatar.day-detail-avatar-letter-o { background: #fb8c00; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-p,
        .task-card-creator-avatar.day-detail-avatar-letter-p,
        .ticket-created-by-avatar.day-detail-avatar-letter-p { background: #f4511e; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-q,
        .task-card-creator-avatar.day-detail-avatar-letter-q,
        .ticket-created-by-avatar.day-detail-avatar-letter-q { background: #6d4c41; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-r,
        .task-card-creator-avatar.day-detail-avatar-letter-r,
        .ticket-created-by-avatar.day-detail-avatar-letter-r { background: #546e7a; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-s,
        .task-card-creator-avatar.day-detail-avatar-letter-s,
        .ticket-created-by-avatar.day-detail-avatar-letter-s { background: #00838f; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-t,
        .task-card-creator-avatar.day-detail-avatar-letter-t,
        .ticket-created-by-avatar.day-detail-avatar-letter-t { background: #2e7d32; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-u,
        .task-card-creator-avatar.day-detail-avatar-letter-u,
        .ticket-created-by-avatar.day-detail-avatar-letter-u { background: #1565c0; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-v,
        .task-card-creator-avatar.day-detail-avatar-letter-v,
        .ticket-created-by-avatar.day-detail-avatar-letter-v { background: #6a1b9a; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-w,
        .task-card-creator-avatar.day-detail-avatar-letter-w,
        .ticket-created-by-avatar.day-detail-avatar-letter-w { background: #ad1457; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-x,
        .task-card-creator-avatar.day-detail-avatar-letter-x,
        .ticket-created-by-avatar.day-detail-avatar-letter-x { background: #c62828; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-y,
        .task-card-creator-avatar.day-detail-avatar-letter-y,
        .ticket-created-by-avatar.day-detail-avatar-letter-y { background: #ef6c00; color: #fff; }
        .task-card-avatar-fallback.day-detail-avatar-letter-z,
        .task-card-creator-avatar.day-detail-avatar-letter-z,
        .ticket-created-by-avatar.day-detail-avatar-letter-z { background: #455a64; color: #fff; }
        .task-card-avatar-more {
            color: #475569;
            background: #e2e8f0;
        }
        .task-card-overdue { border-color: #fecaca; }
        .task-board-ghost-col {
            flex: 0 0 var(--tb-col-min);
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            min-height: 200px;
            background: #f1f5f9;
        }
        #ticketModal .modal-dialog {
            max-width: 1100px;
            margin: .75rem auto;
            max-height: calc(100vh - 1.5rem);
            height: calc(100vh - 1.5rem);
        }
        #ticketModal .modal-content,
        #ticketModal .ticket-modal-content {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            max-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .ticket-modal-topbar {
            display: block;
            padding: .85rem 1.15rem .95rem;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
            flex-shrink: 0;
        }
        .ticket-modal-topbar-main {
            min-width: 0;
        }
        .ticket-modal-topbar-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .35rem;
        }
        .ticket-modal-key {
            font-size: .82rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: .02em;
        }
        .ticket-modal-topbar-right {
            display: flex;
            align-items: center;
            gap: .45rem;
            flex-shrink: 0;
        }
        .ticket-title-input {
            width: 100%;
            border: 0;
            outline: none;
            box-shadow: none;
            font-size: 1.45rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            padding: .2rem .15rem;
            margin: 0;
            background: transparent;
            border-radius: 6px;
        }
        .ticket-title-input:focus {
            background: #f8fafc;
        }
        .ticket-title-input::placeholder {
            color: #94a3b8;
            font-weight: 600;
        }
        .ticket-modal-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }
        .ticket-modal-main {
            padding: 1.15rem 1.35rem 1.5rem;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            background: #fff;
            border-right: 1px solid #e2e8f0;
        }
        .ticket-modal-sidebar {
            padding: 1rem 1rem 1.25rem;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            background: #f8fafc;
        }
        .ticket-section {
            margin-bottom: 1.35rem;
        }
        .ticket-section-label,
        .ticket-side-label {
            display: block;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: .45rem;
        }
        .ticket-description-input {
            border-color: #e2e8f0;
            min-height: 160px;
            resize: vertical;
        }
        .ticket-comment-file {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: .25rem .45rem;
            background: #fff;
            text-decoration: none;
            color: #334155;
            font-size: .75rem;
        }
        .ticket-comment-file img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
        }
        .ticket-attach-btn {
            cursor: pointer;
            color: #64748b;
            display: inline-flex;
            align-items: center;
        }
        .ticket-attach-btn:hover { color: #0f172a; }
        .ticket-attach-btn .material-symbols-outlined { font-size: 20px; }
        .ticket-comment-files-preview .ticket-pending-file {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: .2rem .55rem;
            font-size: .72rem;
            color: #475569;
        }
        .ticket-comment-files-preview .ticket-pending-file button {
            border: 0;
            background: transparent;
            color: #94a3b8;
            line-height: 1;
            padding: 0;
        }
        .ticket-side-field {
            margin-bottom: 1rem;
        }
        .ticket-created-by {
            display: flex;
            align-items: center;
            gap: .55rem;
            min-height: 32px;
        }
        .ticket-created-by-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid #e2e8f0;
        }
        img.ticket-created-by-avatar {
            background: #e2e8f0;
        }
        .ticket-created-by-avatar.is-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .65rem;
            font-weight: 700;
            color: #fff;
            background: #64748b;
        }
        .ticket-created-by-name {
            font-size: .86rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.25;
            word-break: break-word;
        }
        .ticket-activity-section {
            border-top: 1px solid #e2e8f0;
            padding-top: 1rem;
        }
        .ticket-activity-tabs {
            display: flex;
            gap: .25rem;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1rem;
        }
        .ticket-activity-tab {
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: .86rem;
            font-weight: 600;
            padding: .45rem .7rem;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
        }
        .ticket-activity-tab.active {
            color: #0f172a;
            border-bottom-color: #2563eb;
        }
        .ticket-activity-panel { display: none; }
        .ticket-activity-panel.active { display: block; }
        .ticket-comments-list,
        .ticket-activity-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            margin-bottom: 1rem;
        }
        .ticket-comment-item,
        .task-activity-item {
            display: grid;
            grid-template-columns: 32px 1fr;
            gap: .65rem;
            align-items: start;
        }
        .ticket-comment-avatar,
        .ticket-activity-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: .7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .ticket-comment-card,
        .ticket-activity-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: .7rem .85rem;
        }
        .ticket-comment-meta,
        .ticket-activity-meta {
            font-size: .75rem;
            color: #64748b;
            margin-bottom: .35rem;
        }
        .ticket-comment-meta strong,
        .ticket-activity-meta strong {
            color: #0f172a;
            font-weight: 650;
        }
        .ticket-comment-body {
            font-size: .9rem;
            color: #1e293b;
            line-height: 1.45;
        }
        .ticket-comment-compose {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: .75rem;
            background: #fff;
        }
        .ticket-empty-state {
            color: #94a3b8;
            font-size: .85rem;
            padding: .5rem 0;
        }
        @media (max-width: 991.98px) {
            #ticketModal .modal-dialog {
                height: calc(100vh - 1rem);
                max-height: calc(100vh - 1rem);
                margin: .5rem;
            }
            .ticket-modal-body {
                grid-template-columns: 1fr;
                grid-template-rows: minmax(0, 1fr) auto;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .ticket-modal-main {
                border-right: 0;
                border-bottom: 1px solid #e2e8f0;
                overflow: visible;
            }
            .ticket-modal-sidebar {
                overflow: visible;
            }
        }
        .task-activity-item {
            border-left: 0;
            padding-left: 0;
            margin-bottom: 0;
        }
        .staff-chat-entity-picker { z-index: 1080; position: absolute; left: 0; right: 0; top: 100%; }
    </style>
@endpush

@section('content')
    <div class="main-content task-board-page">
        <div class="container-fluid">
            <form method="get" action="{{ route('admin.task-board.index') }}" class="task-board-toolbar" id="taskBoardFilterForm">
                @php
                    $currentSort = $filters['sort'] ?? 'position';
                    $sortOptions = [
                        'position' => translate('Manual'),
                        'newest' => translate('Newest'),
                        'oldest' => translate('Oldest'),
                        'due_date' => translate('Due_date'),
                        'title' => translate('Title'),
                    ];
                    $activeFilterCount = 0;
                    if (!empty($filters['overdue'])) {
                        $activeFilterCount++;
                    }
                    if (!empty($filters['start_date_from'])) {
                        $activeFilterCount++;
                    }
                    if (!empty($filters['start_date_to'])) {
                        $activeFilterCount++;
                    }
                    if (!empty($filters['end_date_from'])) {
                        $activeFilterCount++;
                    }
                    if (!empty($filters['end_date_to'])) {
                        $activeFilterCount++;
                    }
                    $hasExtraFilters = $activeFilterCount > 0;
                    $hasActiveSort = $currentSort !== 'position';
                    $activeSortCount = $hasActiveSort ? 1 : 0;
                @endphp
                <input type="hidden" name="sort" id="taskBoardSortValue" value="{{ $currentSort }}">

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="mb-1">{{ translate('Task_Board') }}</h2>
                        <p class="text-muted mb-0">{{ translate('Manage_team_tickets_on_a_single_board') }}</p>
                    </div>
                    <div class="task-board-header-filters">
                        <div class="task-board-header-search">
                            <input type="text"
                                   name="search"
                                   value="{{ $filters['search'] ?? '' }}"
                                   class="form-control form-control-sm"
                                   placeholder="{{ translate('Search') }}..."
                                   onkeydown="if(event.key==='Enter'){this.form.submit();}">
                        </div>
                        @include('taskboardmodule::admin.partials._assignee-avatar-filter', [
                            'employees' => $employees,
                            'filters' => $filters,
                        ])

                        <div class="dropdown">
                            <button type="button"
                                    class="task-board-icon-btn {{ $hasActiveSort ? 'is-active' : '' }}"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="true"
                                    aria-expanded="false"
                                    title="{{ translate('Sort') }}">
                                <span class="material-symbols-outlined">sort</span>
                                @if($activeSortCount > 0)
                                    <span class="task-board-icon-badge">{{ $activeSortCount }}</span>
                                @endif
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end task-board-sort-menu">
                                <li class="px-3 pb-1 small text-muted">{{ translate('Sort') }}</li>
                                @foreach($sortOptions as $value => $label)
                                    <li>
                                        <button type="button"
                                                class="dropdown-item task-board-sort-option {{ $currentSort === $value ? 'active' : '' }}"
                                                data-sort="{{ $value }}">
                                            {{ $label }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="dropdown">
                            <button type="button"
                                    class="task-board-icon-btn {{ $hasExtraFilters ? 'is-active' : '' }}"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false"
                                    title="{{ translate('Filter') }}">
                                <span class="material-symbols-outlined">filter_list</span>
                                @if($activeFilterCount > 0)
                                    <span class="task-board-icon-badge">{{ $activeFilterCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end task-board-filter-menu">
                                @if(!empty($filters['my_tickets']))
                                    <input type="hidden" name="my_tickets" value="1">
                                @endif

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="overdue" value="1" id="filterOverdue" @checked(!empty($filters['overdue']))>
                                        <label class="form-check-label" for="filterOverdue">{{ translate('Overdue') }}</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted mb-1">{{ translate('Start_date') }}</div>
                                    <div class="d-flex gap-2">
                                        <input type="date" name="start_date_from" class="form-control form-control-sm" value="{{ $filters['start_date_from'] ?? '' }}" title="{{ translate('From') }}">
                                        <input type="date" name="start_date_to" class="form-control form-control-sm" value="{{ $filters['start_date_to'] ?? '' }}" title="{{ translate('To') }}">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted mb-1">{{ translate('End_date') }}</div>
                                    <div class="d-flex gap-2">
                                        <input type="date" name="end_date_from" class="form-control form-control-sm" value="{{ $filters['end_date_from'] ?? '' }}" title="{{ translate('From') }}">
                                        <input type="date" name="end_date_to" class="form-control form-control-sm" value="{{ $filters['end_date_to'] ?? '' }}" title="{{ translate('To') }}">
                                    </div>
                                </div>

                                <div class="d-flex gap-2 justify-content-between">
                                    <a href="{{ route('admin.task-board.index') }}" class="btn btn-sm btn-outline-secondary">{{ translate('Reset') }}</a>
                                    <button type="submit" class="btn btn-sm btn-primary">{{ translate('Apply') }}</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#columnModal">
                            <span class="material-symbols-outlined align-middle" style="font-size:18px">view_column</span>
                            {{ translate('Add_Column') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnNewTicket">
                            <span class="material-symbols-outlined align-middle" style="font-size:18px">add</span>
                            {{ translate('Add_Ticket') }}
                        </button>
                        @if($canRestore)
                            <a href="{{ route('admin.task-board.trash') }}" class="btn btn-sm btn-outline-secondary">
                                <span class="material-symbols-outlined align-middle" style="font-size:18px">delete</span>
                                {{ translate('Trash') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="task-board-scroller" id="taskBoardColumns">
                @foreach($columns as $column)
                    @include('taskboardmodule::admin.partials._column', ['column' => $column])
                @endforeach
            </div>
        </div>
    </div>

    @include('taskboardmodule::admin.partials._column-modal')
    @include('taskboardmodule::admin.partials._ticket-modal', ['employees' => $employees, 'columns' => $columns])

    <div class="staff-chat-entity-picker card shadow-sm border d-none" id="staffChatEntityPicker" style="position:fixed;z-index:2000;width:min(360px,90vw);">
        <div class="card-body p-2">
            <input type="search" class="form-control form-control-sm mb-2" id="staffChatEntitySearchInput"
                   placeholder="{{ translate('Search_by_name_phone_or_id') }}" autocomplete="off">
            <div class="small text-muted mb-2" id="staffChatEntityPickerHint">{{ translate('Staff_chat_tag_hint') }}</div>
            <div class="staff-chat-entity-results list-group list-group-flush" id="staffChatEntityResults"></div>
        </div>
    </div>
@endsection

@push('script')
    @php
        $taskBoardEmployeesJson = $employees->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => trim($e->first_name.' '.$e->last_name),
            ];
        })->values();
        $taskBoardColumnsJson = $columns->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'color' => $c->color,
            ];
        })->values();
        $authUser = auth()->user();
        $authName = $authUser ? trim(($authUser->first_name ?? '').' '.($authUser->last_name ?? '')) : '';
        if ($authName === '' && $authUser) {
            $authName = (string) ($authUser->email ?? $authUser->id);
        }
        $authInitials = 'E';
        if ($authName !== '') {
            $words = preg_split('/\s+/u', $authName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($words) >= 2) {
                $authInitials = mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1));
            } elseif (count($words) === 1) {
                $authInitials = mb_strtoupper(mb_substr($words[0], 0, min(2, mb_strlen($words[0]))));
            }
        }
        $authPhoto = null;
        if ($authUser && trim((string) ($authUser->profile_image ?? '')) !== '') {
            $path = (string) ($authUser->profile_image_full_path ?? '');
            $pathLower = mb_strtolower($path);
            if (
                $path !== ''
                && ! str_contains($pathLower, 'placeholder')
                && ! str_contains($pathLower, '/customer.png')
                && ! str_contains($pathLower, '/user2x.png')
                && ! str_contains($pathLower, '/default.png')
            ) {
                $authPhoto = $path;
            }
        }
        $taskBoardCurrentUserJson = $authUser ? [
            'id' => $authUser->id,
            'name' => $authName,
            'photo' => $authPhoto,
            'initials' => $authInitials,
        ] : null;
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        window.staffChatEntitySearchUrl = @json(route('admin.chat.entity-search'));
        window.staffChatTypeLabels = {
            staff: @json(translate('Staff')),
            customer: @json(translate('customer')),
            provider: @json(translate('Provider')),
            booking: @json(translate('booking')),
            service: @json(translate('Service')),
            lead: @json(translate('Lead')),
        };
        window.taskBoardRoutes = {
            columnsReorder: @json(route('admin.task-board.columns.reorder')),
            columnsUpdate: @json(url('admin/task-board/columns')),
            columnsDestroy: @json(url('admin/task-board/columns')),
            ticketsStore: @json(route('admin.task-board.tickets.store')),
            ticketsShow: @json(url('admin/task-board/tickets')),
            ticketsUpdate: @json(url('admin/task-board/tickets')),
            ticketsMove: @json(url('admin/task-board/tickets')),
            ticketsDestroy: @json(url('admin/task-board/tickets')),
            commentsStore: @json(url('admin/task-board/tickets')),
            searchBookings: @json(route('admin.task-board.search-bookings')),
            searchLeads: @json(route('admin.task-board.search-leads')),
        };
        window.taskBoardCsrf = @json(csrf_token());
        window.taskBoardEmployees = @json($taskBoardEmployeesJson);
        window.taskBoardColumns = @json($taskBoardColumnsJson);
        window.taskBoardCurrentUser = @json($taskBoardCurrentUserJson);
    </script>
    <script src="{{ asset('assets/chatting-module/js/staff-chat-compose.js') }}"></script>
    <script src="{{ asset('assets/task-board-module/js/task-board.js') }}"></script>
@endpush
