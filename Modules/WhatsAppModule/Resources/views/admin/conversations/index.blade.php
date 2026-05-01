@extends($layout ?? 'adminmodule::layouts.master')

@section('title', translate('social_inbox_page_title'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/select2/select2.min.css') }}"/>
    <style>
        /* WABA template picker when session is closed — Select2 searchable dropdown (aligned with message templates UI) */
        #wa-waba-template-panel .select2-container {
            min-width: 0;
            width: 100% !important;
            max-width: 100% !important;
        }
        #wa-waba-template-panel .select2-container--open {
            z-index: 2005;
        }
        body > .select2-container .select2-dropdown.select2-wa-session-tpl-dd {
            z-index: 2010 !important;
            max-width: min(36rem, calc(100vw - 1rem));
            box-sizing: border-box;
        }
        .select2-wa-session-tpl-dd .select2-search--dropdown {
            padding: 0.5rem 0.5rem 0.25rem;
        }
        .select2-wa-session-tpl-dd .select2-search__field {
            width: 100% !important;
            min-height: 2.25rem;
        }
        .select2-wa-session-tpl-dd .select2-results > .select2-results__options {
            max-height: 12.5rem !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }
    </style>
    <?php if (in_array(($tab ?? ''), ['chats', 'human_support', 'users'], true)): ?>
        <style>
            .wa-msg-selected > .wa-msg-bubble {
                outline: 3px solid var(--bs-warning, #ffc107);
                outline-offset: 3px;
                box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.06);
            }
            /* Split layout: list + chat share one viewport-based height; scroll inside panes (no dead gap under list). */
            .wa-min-h-0 {
                min-height: 0;
            }
            /*
             * .main-area is flex column + 100vh; first child must shrink/scroll so <footer> stays visible.
             */
            .wa-whatsapp-chats-split-page.main-content {
                flex: 1 1 auto;
                min-height: 0;
                max-height: 100%;
                overflow-x: hidden;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 20px;
            }
            .main-area > footer.footer {
                flex-shrink: 0;
            }
            /* Space below the split row (chats + conversation) inside the scrollable page. */
            .wa-whatsapp-chats-split-page .wa-chats-split-layout {
                --wa-chats-pane-bottom-gap: 20px;
                margin-bottom: var(--wa-chats-pane-bottom-gap);
            }
            @media (min-width: 768px) {
                /*
                 * Fixed 900px row: clip overflow; columns/cards must not grow with content.
                 * Scroll lives only inside .wa-active-chat-list-scroll and #whatsapp-chat-messages.
                 */
                .wa-whatsapp-chats-split-page .wa-chats-split-layout {
                    --wa-chats-pane-h: 900px;
                    display: flex;
                    flex-direction: row;
                    flex-wrap: nowrap;
                    align-items: stretch;
                    height: var(--wa-chats-pane-h);
                    min-height: var(--wa-chats-pane-h);
                    max-height: var(--wa-chats-pane-h);
                    overflow: hidden;
                }
                .wa-whatsapp-chats-split-page .wa-chats-split-layout > .wa-chats-split-col {
                    display: flex;
                    flex-direction: column;
                    align-self: stretch;
                    min-width: 0;
                    min-height: 0;
                    max-height: 100%;
                    overflow: hidden;
                }
                .wa-whatsapp-chats-split-page .wa-chats-split-layout > .wa-chats-split-col > .card {
                    flex: 1 1 0%;
                    min-width: 0;
                    min-height: 0;
                    height: 100%;
                    max-height: 100%;
                    overflow: hidden;
                }
                /*
                 * height:0 + flex:1 is the standard pattern so flex children scroll instead of
                 * expanding the parent (min-height:auto would follow content size).
                 */
                .wa-whatsapp-chats-split-page .wa-active-chat-list-scroll {
                    flex: 1 1 0%;
                    height: 0;
                    min-height: 0;
                    overflow-x: hidden;
                    overflow-y: auto;
                    -webkit-overflow-scrolling: touch;
                }
                .wa-whatsapp-chats-split-page .wa-chat-main-panel #whatsapp-chat-panel {
                    flex: 1 1 0%;
                    height: 0;
                    min-height: 0;
                    overflow: hidden;
                }
                .wa-whatsapp-chats-split-page .wa-chat-main-panel #whatsapp-chat-messages {
                    flex: 1 1 0%;
                    height: 0;
                    min-height: 0;
                    overflow-x: hidden;
                    overflow-y: auto;
                    -webkit-overflow-scrolling: touch;
                    overscroll-behavior-y: contain;
                    touch-action: pan-y;
                    position: relative;
                }
            }
            .wa-active-chat-list-scroll {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .wa-chats-filter-select2 {
                min-height: 2.75rem;
            }
            #wa-chats-filters-offcanvas .select2-container {
                width: 100% !important;
            }
            .whatsapp-active-list-container .card-header {
                min-width: 0;
            }
            .whatsapp-active-list-container .wa-chat-list-filter-btn {
                white-space: nowrap;
            }
            .wa-sys-pill {
                display: inline-flex;
                align-items: center;
                font-size: 0.65rem;
                font-weight: 600;
                line-height: 1.2;
                padding: 0.2rem 0.55rem;
                border-radius: 999px;
                border: 1px solid transparent;
                white-space: nowrap;
            }
            .wa-sys-pill--customer {
                background: #e7f1ff;
                color: #0b5ed7;
                border-color: #b6d4fe;
            }
            .wa-sys-pill--provider {
                background: #f3e8ff;
                color: #6f42c1;
                border-color: #e0c3fc;
            }
            .wa-sys-pill--none {
                background: #f1f3f5;
                color: #6c757d;
                border-color: #dee2e6;
            }
            .wa-sys-name-link {
                font-weight: 500;
                max-width: 9rem;
            }
            .wa-sys-pills:not(.wa-sys-pills--on-unread) .wa-sys-name-link {
                color: #0d6efd;
            }
            .wa-sys-pills:not(.wa-sys-pills--on-unread) .wa-sys-pill--provider ~ .wa-sys-name-link,
            .wa-sys-pills:not(.wa-sys-pills--on-unread) a.wa-sys-pill--provider + a.wa-sys-name-link {
                color: #6f42c1;
            }
            .wa-hand-pill {
                display: inline-flex;
                align-items: center;
                font-size: 0.65rem;
                font-weight: 600;
                line-height: 1.2;
                padding: 0.2rem 0.55rem;
                border-radius: 999px;
                border: 1px solid transparent;
                white-space: nowrap;
            }
            .wa-hand-pill--ai {
                background: #d1e7dd;
                color: #0f5132;
                border-color: #badbcc;
            }
            .wa-hand-pill--agent {
                background: #fff3cd;
                color: #664d03;
                border-color: #ffecb5;
            }
            .wa-sys-pills--on-unread .wa-sys-pill--customer {
                background: rgba(255, 255, 255, 0.22);
                color: #fff;
                border-color: rgba(255, 255, 255, 0.45);
            }
            .wa-sys-pills--on-unread .wa-sys-pill--provider {
                background: rgba(255, 255, 255, 0.18);
                color: #fff;
                border-color: rgba(255, 255, 255, 0.4);
            }
            .wa-sys-pills--on-unread .wa-sys-pill--none {
                background: rgba(255, 255, 255, 0.15);
                color: rgba(255, 255, 255, 0.92);
                border-color: rgba(255, 255, 255, 0.35);
            }
            .wa-sys-pills--on-unread .wa-sys-name-link {
                color: #fff !important;
                text-decoration: underline !important;
                text-underline-offset: 2px;
            }
            .wa-hand-pill--on-unread.wa-hand-pill--ai {
                background: rgba(255, 255, 255, 0.22);
                color: #fff;
                border-color: rgba(255, 255, 255, 0.45);
            }
            .wa-hand-pill--on-unread.wa-hand-pill--agent {
                background: rgba(255, 255, 255, 0.2);
                color: #fff;
                border-color: rgba(255, 255, 255, 0.4);
            }
            .wa-chat-preview {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                line-height: 1.35;
                word-break: break-word;
            }
            .wa-chat-item-row3-time {
                font-size: 0.7rem;
                white-space: nowrap;
            }
            .wa-conversation-header {
                border-bottom: 1px solid var(--bs-border-color, #dee2e6);
            }
            .wa-conversation-header .wa-header-title {
                max-width: 100%;
            }
            @media (min-width: 992px) {
                .wa-conversation-header .wa-header-title {
                    max-width: min(100%, 560px);
                }
            }
            /*
             * Session-closed UI (banner + WABA template) must not grow the split row: messages shrink + scroll;
             * footer blocks scroll internally if needed.
             */
            /* flex-basis must be 0% (not auto) or the panel's min size follows message content and kills inner scroll. */
            .wa-chat-main-panel #whatsapp-chat-panel {
                flex: 1 1 0%;
                min-height: 0;
                overflow: hidden;
            }
            /*
             * Scroll container: use block layout here — display:flex on the scrollport often breaks
             * overflow scrolling when a flex child has min-height:auto (content-sized).
             */
            .wa-chat-main-panel #whatsapp-chat-messages {
                flex: 1 1 0%;
                min-height: 0;
                overflow-x: hidden;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior-y: contain;
                touch-action: pan-y;
                position: relative;
                display: block;
            }
            /* Stacked layout: row height is not capped like the md+ split row; bound the thread so it scrolls inside. */
            @media (max-width: 767.98px) {
                .wa-whatsapp-chats-split-page .wa-chat-main-panel #whatsapp-chat-messages {
                    max-height: min(70vh, 560px);
                }
            }
            /* Short threads: spacer grows so newest messages sit toward the bottom; long threads: spacer collapses. */
            .wa-chat-main-panel #whatsapp-chat-messages > .wa-chat-messages-inner {
                display: flex;
                flex-direction: column;
                min-height: 100%;
                width: 100%;
                box-sizing: border-box;
                padding-bottom: 0.25rem;
            }
            .wa-chat-main-panel #whatsapp-chat-messages > .wa-chat-messages-inner::before {
                content: '';
                flex: 1 1 0;
                min-height: 0;
            }
            .wa-chat-main-panel #whatsapp-chat-panel > .card-footer {
                flex: 0 0 auto;
            }
            .wa-chat-main-panel #wa-session-window-banner {
                max-height: 7.5rem;
                overflow-y: auto;
            }
            .wa-chat-main-panel #wa-waba-template-panel {
                max-height: min(14rem, 32vh);
                overflow-y: auto;
            }
            /* Empty state fills the same vertical space as the open chat panel (no extra min-height mismatch). */
            .wa-chat-main-panel #whatsapp-chat-placeholder {
                flex: 1 1 auto;
                min-height: 0;
            }
            .wa-chat-column {
                min-width: 0;
                flex: 1 1 0%;
            }
            @media (min-width: 768px) {
                .wa-chat-column {
                    min-width: 280px;
                }
            }
            .wa-tpl-suggest {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 100%;
                margin-bottom: 4px;
                max-height: 240px;
                overflow-y: auto;
                z-index: 25;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.12);
                border-radius: 10px;
                background: #fff;
                border: 1px solid var(--bs-border-color, #dee2e6);
            }
            .wa-tpl-suggest-item {
                padding: 0.45rem 0.75rem;
                cursor: pointer;
                border-bottom: 1px solid var(--bs-border-color-translucent, rgba(0, 0, 0, 0.06));
                text-align: left;
                width: 100%;
                display: block;
                background: transparent;
                border-left: 0;
                border-right: 0;
                border-top: 0;
            }
            .wa-tpl-suggest-item:last-child {
                border-bottom: 0;
            }
            .wa-tpl-suggest-item:hover,
            .wa-tpl-suggest-item.wa-tpl-suggest-item--active {
                background: var(--bs-primary-bg-subtle, #e7f1ff);
            }
            .wa-tpl-suggest-title {
                font-weight: 600;
                font-size: 0.8rem;
                line-height: 1.2;
            }
            .wa-tpl-suggest-preview {
                font-size: 0.72rem;
                color: var(--bs-secondary, #6c757d);
                margin-top: 0.15rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .wa-msg-bubble {
                max-width: 94%;
            }
            /* Strips sit flush with the bubble edge (out = right, in = left), not the far row corner. */
            .wa-msg-react-strip,
            .wa-msg-bottom-strip {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 2px;
                width: fit-content;
                max-width: 94%;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.14s ease, visibility 0.14s ease;
                pointer-events: none;
            }
            .wa-msg-react-strip--out,
            .wa-msg-bottom-strip--out {
                align-self: flex-end;
                justify-content: flex-end;
            }
            .wa-msg-react-strip--out {
                margin-bottom: 6px;
            }
            .wa-msg-bottom-strip--out {
                margin-top: 6px;
            }
            .wa-msg-react-strip--in,
            .wa-msg-bottom-strip--in {
                align-self: flex-start;
                justify-content: flex-start;
            }
            .wa-msg-react-strip--in {
                margin-bottom: 6px;
            }
            .wa-msg-bottom-strip--in {
                margin-top: 6px;
            }
            .wa-msg-row:hover .wa-msg-react-strip,
            .wa-msg-row:hover .wa-msg-bottom-strip,
            .wa-msg-row:focus-within .wa-msg-react-strip,
            .wa-msg-row:focus-within .wa-msg-bottom-strip {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }
            @media (hover: none) {
                .wa-msg-react-strip,
                .wa-msg-bottom-strip {
                    opacity: 1;
                    visibility: visible;
                    pointer-events: auto;
                }
            }
            .wa-msg-action-icon {
                line-height: 1;
                min-width: 30px;
                min-height: 30px;
                padding: 2px !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 6px;
                text-decoration: none !important;
                color: #5c636a;
            }
            .wa-msg-action-icon .material-icons {
                font-size: 18px;
            }
            .wa-msg-action-icon:hover {
                background: rgba(0, 0, 0, 0.07);
                color: #343a40;
            }
            .wa-msg-action-icon.wa-send-reaction {
                font-size: 1rem;
                min-width: 28px;
                min-height: 28px;
            }
            .wa-reply-preview-jump {
                cursor: pointer;
                display: block;
                width: 100%;
                border: 0;
                background: transparent;
                font: inherit;
                color: inherit;
                text-align: start;
            }
            .wa-reply-preview-jump:hover {
                filter: brightness(0.97);
            }
            .wa-send-reaction {
                text-decoration: none !important;
            }
            .wa-reply-quote-snippet {
                border: 1px solid rgba(202, 138, 4, 0.55);
                background: rgba(255, 251, 235, 0.95);
                border-left-width: 3px;
                border-left-color: #ca8a04;
                color: #1c1917;
            }
            .wa-msg-bubble.bg-primary .wa-reply-quote-snippet {
                border-color: rgba(250, 204, 21, 0.85);
                background: rgba(254, 252, 232, 0.96);
                color: #1c1917;
            }
            .wa-msg-row--out .wa-reply-quote-snippet--out-hover {
                display: none !important;
            }
            .wa-msg-row--out:hover .wa-reply-quote-snippet--out-hover,
            .wa-msg-row--out:focus-within .wa-reply-quote-snippet--out-hover {
                display: block !important;
            }
            .wa-conv-tpl-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
            }
            .wa-msg-bubble.wa-msg-jump-flash {
                animation: wa-msg-jump-highlight 1.25s ease;
            }
            @keyframes wa-msg-jump-highlight {
                0%, 100% { box-shadow: none; }
                20%, 80% { box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.75); }
            }
            .wa-msg-staff-note {
                max-width: 94%;
                font-size: 0.75rem;
                line-height: 1.4;
                color: var(--bs-secondary-color, #6c757d);
                border-left: 3px solid rgba(108, 117, 125, 0.45);
                padding-left: 0.6rem;
                margin-top: 0.35rem;
            }
            .wa-msg-row--out .wa-msg-staff-note {
                align-self: flex-end;
            }
            .wa-msg-row--in .wa-msg-staff-note {
                align-self: flex-start;
            }
            .wa-msg-staff-note__label {
                font-size: 0.65rem;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                opacity: 0.85;
                margin-bottom: 0.15rem;
            }
            /* Fullscreen inbox (new tab): fill viewport; list + conversation use remaining height */
            body.wa-social-inbox-fullscreen-body .wa-si-fs-main .wa-social-inbox-fs-page.main-content {
                flex: 1 1 auto;
                min-height: 0;
                display: flex;
                flex-direction: column;
            }
            .wa-social-inbox-fs-page.wa-whatsapp-chats-split-page.main-content {
                overflow-y: hidden;
                padding-bottom: 0;
            }
            @media (min-width: 768px) {
                .wa-social-inbox-fs-page.wa-whatsapp-chats-split-page .wa-chats-split-layout {
                    --wa-chats-pane-h: calc(100vh - 12rem);
                }
            }
            body.wa-social-inbox-fullscreen-body .wa-social-inbox-fs-page > .container-fluid {
                flex: 1 1 auto;
                min-height: 0;
                display: flex;
                flex-direction: column;
            }
            body.wa-social-inbox-fullscreen-body .wa-social-inbox-fs-page .wa-chats-split-layout {
                flex: 1 1 auto;
                min-height: 0;
            }
        </style>
    <?php endif; ?>
        @include('whatsappmodule::admin.partials.social-inbox-page-surface-css')
@endpush

@section('content')
    @php
        $waFs = !empty($fullscreen);
        $waCh = request()->route('channel') ?? 'whatsapp';
        $waFsQuery = $waFs ? ['fullscreen' => 1] : [];
        $waExitQ = collect(request()->query())->except('fullscreen')->all();
        $waExitFullscreenUrl = route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh], $waExitQ));
        // UI only: show last 10 digits. Full number stays in data-phone / hidden input for send + API.
        $displayPhone = function ($phone) {
            $digits = preg_replace('/\D+/', '', (string) $phone);
            if (!$digits) {
                return '—';
            }
            return strlen($digits) > 10 ? substr($digits, -10) : $digits;
        };
        $waInboxCh = request()->route('channel') ?? 'whatsapp';
        $socialInboxChannel = $waInboxCh;
    @endphp
    <div class="main-content social-inbox-page social-inbox-page--{{ $socialInboxChannel }} {{ $waFs ? 'wa-social-inbox-fs-page' : '' }} {{ in_array(($tab ?? ''), ['chats', 'human_support'], true) ? 'wa-whatsapp-chats-split-page' : '' }}">
        <div class="container-fluid {{ $waFs ? 'py-2' : '' }}">
            @unless($waFs)
                <div class="page-title-wrap mb-3">
                    <h2 class="page-title d-flex gap-3 align-items-center flex-wrap">
                        <span class="material-icons">chat</span>
                        {{ translate('social_inbox_page_title') }}
                        @if(in_array(($tab ?? ''), ['chats', 'human_support'], true))
                            <a href="{{ request()->fullUrlWithQuery(['fullscreen' => '1']) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-sm btn-outline-secondary ms-md-auto d-inline-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:18px;">open_in_new</span>
                                {{ translate('whatsapp_fullscreen_chat') }}
                            </a>
                        @endif
                    </h2>
                </div>

                <div class="card card-body mb-3">
                    <ul class="nav nav--tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ ($tab ?? '') === 'chats' ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'chats'], $waFsQuery)) }}">
                                {{ translate('Active Chats') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ ($tab ?? '') === 'human_support' ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'human_support'], $waFsQuery)) }}">
                                {{ translate('Human support') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ ($tab ?? '') === 'leads' ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'leads'], $waFsQuery)) }}">
                                {{ translate('Provider Leads') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ ($tab ?? '') === 'bookings' ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'bookings'], $waFsQuery)) }}">
                                {{ translate('Bookings') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ ($tab ?? '') === 'users' ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'users'], $waFsQuery)) }}">
                                {{ translate('WhatsApp Users') }}
                            </a>
                        </li>
                        @can('whatsapp_message_template_update')
                            <li class="nav-item">
                                <a class="nav-link {{ ($tab ?? '') === 'quick_replies' ? 'active' : '' }}"
                                   href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'quick_replies'], $waFsQuery)) }}">
                                    {{ translate('WhatsApp_quick_replies_tab') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ ($tab ?? '') === 'chat_config' ? 'active' : '' }}"
                                   href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'chat_config'], $waFsQuery)) }}">
                                    {{ translate('whatsapp_chat_configuration') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            @else
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 wa-si-fs-toolbar">
                    <ul class="nav nav-pills wa-si-fs-pills mb-0">
                        <li class="nav-item">
                            <a class="nav-link py-1 px-3 {{ ($tab ?? '') === 'chats' ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'chats'], $waFsQuery)) }}">
                                {{ translate('Active Chats') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-3 {{ ($tab ?? '') === 'human_support' ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => $waCh, 'tab' => 'human_support'], $waFsQuery)) }}">
                                {{ translate('Human support') }}
                            </a>
                        </li>
                    </ul>
                    <a href="{{ $waExitFullscreenUrl }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <span class="material-icons" style="font-size:18px;">fullscreen_exit</span>
                        {{ translate('whatsapp_exit_fullscreen') }}
                    </a>
                </div>
            @endunless

            @php
                $waSearchableTabs = ['chats', 'human_support', 'leads', 'bookings', 'users', 'quick_replies', 'chat_config'];
                $waFacetCount = 0;
                if (in_array($tab ?? '', ['chats', 'human_support'], true)) {
                    $_hf = $handlerFilters ?? [];
                    $_st = $chatStatusIdsFilter ?? [];
                    $_ur = array_unique($unreadStateFilter ?? []);
                    $_sk = $systemKindsFilter ?? [];
                    $waFacetCount = (int) (count($_hf) > 0)
                        + (int) (count($_st) > 0)
                        + (int) (count($chatTagIdsFilter ?? []) > 0)
                        + (int) (count($_ur) === 1)
                        + (int) (count($_sk) > 0)
                        + (int) (request()->filled('last_inbound_from') || request()->filled('last_inbound_to'))
                        + (int) (request()->filled('chat_started_from') || request()->filled('chat_started_to'));
                }
            @endphp
            @if(in_array($tab ?? '', $waSearchableTabs, true))
                <div class="card card-body mb-3 py-3">
                    <label for="wa-global-search" class="form-label mb-2">{{ translate('Search here') }}</label>
                    <div class="d-flex flex-nowrap align-items-start justify-content-between w-100 wa-global-search-toolbar"
                         style="gap: clamp(0.5rem, 2vw, 1rem);">
                        <div class="position-relative min-w-0 flex-grow-1" style="max-width: 100%;">
                            <input type="search"
                                   id="wa-global-search"
                                   class="form-control"
                                   placeholder="{{ translate('Search name, number, or message') }}…"
                                   autocomplete="off"
                                   aria-autocomplete="list"
                                   aria-controls="wa-global-search-dropdown">
                            <div id="wa-global-search-dropdown"
                                 class="list-group position-absolute w-100 shadow-sm mt-1 rounded border bg-white"
                                 style="z-index: 25; max-height: 420px; overflow-y: auto; display: none;"
                                 role="listbox"></div>
                        </div>
                    </div>
                </div>
                @if(in_array($tab ?? '', ['chats', 'human_support'], true))
                    <input type="hidden" id="wa-initial-open-phone" value="{{ e(request()->query('phone', '')) }}">
                    <input type="hidden" id="wa-initial-focus-message-id" value="{{ e(request()->query('focus_message_id', '')) }}">

                    @php
                        $chatStatusesForFilter = $chatStatusesForFilter ?? collect();
                        $chatTagsForFilter = $chatTagsForFilter ?? collect();
                        $chatTagIdsFilter = $chatTagIdsFilter ?? [];
                        $chatStatusIdsFilter = $chatStatusIdsFilter ?? [];
                        $handlerFilters = $handlerFilters ?? [];
                        $unreadStateFilter = $unreadStateFilter ?? [];
                        $systemKindsFilter = $systemKindsFilter ?? [];
                    @endphp

                    <div class="offcanvas offcanvas-end border-start shadow-sm"
                         tabindex="-1"
                         id="wa-chats-filters-offcanvas"
                         aria-labelledby="wa-chats-filters-offcanvas-label"
                         style="width: min(100vw, 440px); max-width: 100%;">
                        <div class="offcanvas-header border-bottom">
                            <h5 class="offcanvas-title mb-0" id="wa-chats-filters-offcanvas-label">{{ translate('Filters') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body d-flex flex-column">
                            <form method="get" action="{{ route('admin.whatsapp.conversations.index', ['channel' => request()->route('channel') ?? 'whatsapp']) }}" class="d-flex flex-column flex-grow-1 gap-3">
                                <input type="hidden" name="tab" value="{{ $tab ?? 'chats' }}">
                                @if($waFs)
                                    <input type="hidden" name="fullscreen" value="1">
                                @endif

                                <div>
                                    <label class="form-label fw-semibold" for="wa-filter-assignee">{{ translate('Assignee') }}</label>
                                    <p class="small text-muted mb-2">{{ translate('whatsapp_chat_filters_assignee_hint') }}</p>
                                    <select class="form-select wa-chats-filter-select2"
                                            id="wa-filter-assignee"
                                            name="handlers[]"
                                            multiple
                                            data-placeholder="{{ translate('All') }}"
                                            data-allow-clear="1">
                                        @foreach(($chatHandlers ?? []) as $h)
                                            @if(($h['key'] ?? '') === 'all')
                                                @continue
                                            @endif
                                            <option value="{{ e($h['key']) }}" {{ in_array((string) ($h['key'] ?? ''), $handlerFilters, true) ? 'selected' : '' }}>
                                                {{ $h['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label fw-semibold" for="wa-filter-system">{{ translate('whatsapp_chat_filters_system_label') }}</label>
                                    <p class="small text-muted mb-2">{{ translate('whatsapp_chat_filters_system_hint') }}</p>
                                    <select class="form-select wa-chats-filter-select2"
                                            id="wa-filter-system"
                                            name="system_kinds[]"
                                            multiple
                                            data-placeholder="{{ translate('All') }}"
                                            data-allow-clear="1">
                                        <option value="none" {{ in_array('none', $systemKindsFilter, true) ? 'selected' : '' }}>{{ translate('whatsapp_filter_system_none') }}</option>
                                        <option value="customer" {{ in_array('customer', $systemKindsFilter, true) ? 'selected' : '' }}>{{ translate('whatsapp_filter_system_customer') }}</option>
                                        <option value="provider" {{ in_array('provider', $systemKindsFilter, true) ? 'selected' : '' }}>{{ translate('whatsapp_filter_system_provider') }}</option>
                                        <option value="both" {{ in_array('both', $systemKindsFilter, true) ? 'selected' : '' }}>{{ translate('whatsapp_filter_system_both') }}</option>
                                    </select>
                                </div>

                                <div>
                                    <span class="form-label fw-semibold d-block">{{ translate('whatsapp_chat_filters_last_inbound') }}</span>
                                    <p class="small text-muted mb-2">{{ translate('whatsapp_chat_filters_last_inbound_hint') }}</p>
                                    <div class="row g-2">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label small mb-0" for="wa-filter-last-in-from">{{ translate('whatsapp_date_from') }}</label>
                                            <input type="date"
                                                   class="form-control form-control-sm"
                                                   id="wa-filter-last-in-from"
                                                   name="last_inbound_from"
                                                   value="{{ e(request('last_inbound_from', '')) }}">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label small mb-0" for="wa-filter-last-in-to">{{ translate('whatsapp_date_to') }}</label>
                                            <input type="date"
                                                   class="form-control form-control-sm"
                                                   id="wa-filter-last-in-to"
                                                   name="last_inbound_to"
                                                   value="{{ e(request('last_inbound_to', '')) }}">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <span class="form-label fw-semibold d-block">{{ translate('whatsapp_chat_filters_chat_started') }}</span>
                                    <p class="small text-muted mb-2">{{ translate('whatsapp_chat_filters_chat_started_hint') }}</p>
                                    <div class="row g-2">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label small mb-0" for="wa-filter-started-from">{{ translate('whatsapp_date_from') }}</label>
                                            <input type="date"
                                                   class="form-control form-control-sm"
                                                   id="wa-filter-started-from"
                                                   name="chat_started_from"
                                                   value="{{ e(request('chat_started_from', '')) }}">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label small mb-0" for="wa-filter-started-to">{{ translate('whatsapp_date_to') }}</label>
                                            <input type="date"
                                                   class="form-control form-control-sm"
                                                   id="wa-filter-started-to"
                                                   name="chat_started_to"
                                                   value="{{ e(request('chat_started_to', '')) }}">
                                        </div>
                                    </div>
                                </div>

                                @if($chatStatusesForFilter->isNotEmpty())
                                    <div>
                                        <label class="form-label fw-semibold" for="wa-filter-status">{{ translate('whatsapp_chat_status') }}</label>
                                        <p class="small text-muted mb-2">{{ translate('whatsapp_chat_filters_status_hint') }}</p>
                                        <select class="form-select wa-chats-filter-select2"
                                                id="wa-filter-status"
                                                name="chat_status_ids[]"
                                                multiple
                                                data-placeholder="{{ translate('All') }}"
                                                data-allow-clear="1">
                                            @foreach($chatStatusesForFilter as $st)
                                                <option value="{{ (int) $st->id }}"
                                                    {{ in_array((int) $st->id, $chatStatusIdsFilter, true) ? 'selected' : '' }}>
                                                    {{ e($st->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div>
                                    <label class="form-label fw-semibold" for="wa-filter-unread">{{ translate('whatsapp_chat_filters_read_state') }}</label>
                                    <p class="small text-muted mb-2">{{ translate('whatsapp_chat_filters_read_hint') }}</p>
                                    <select class="form-select wa-chats-filter-select2"
                                            id="wa-filter-unread"
                                            name="unread_state[]"
                                            multiple
                                            data-placeholder="{{ translate('All') }}"
                                            data-allow-clear="1">
                                        <option value="unread" {{ in_array('unread', $unreadStateFilter, true) ? 'selected' : '' }}>{{ translate('whatsapp_filter_has_unread') }}</option>
                                        <option value="read" {{ in_array('read', $unreadStateFilter, true) ? 'selected' : '' }}>{{ translate('whatsapp_filter_no_unread') }}</option>
                                    </select>
                                </div>

                                @if($chatTagsForFilter->isNotEmpty())
                                    <div class="flex-grow-1 d-flex flex-column min-h-0">
                                        <label class="form-label fw-semibold" for="wa-filter-tags">{{ translate('whatsapp_chat_tags_label') }}</label>
                                        <p class="small text-muted mb-2">{{ translate('whatsapp_chat_filters_tags_hint') }}</p>
                                        <select class="form-select wa-chats-filter-select2"
                                                id="wa-filter-tags"
                                                name="chat_tag_ids[]"
                                                multiple
                                                data-placeholder="{{ translate('All') }}"
                                                data-allow-clear="1">
                                            @foreach($chatTagsForFilter as $tg)
                                                @php $tid = (int) $tg->id; @endphp
                                                <option value="{{ $tid }}" {{ in_array($tid, $chatTagIdsFilter, true) ? 'selected' : '' }}>
                                                    {{ e($tg->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if($chatStatusesForFilter->isEmpty() && $chatTagsForFilter->isEmpty())
                                    <p class="text-muted small mb-0">{{ translate('whatsapp_chat_filters_unconfigured') }}</p>
                                @endif

                                <div class="mt-auto d-flex flex-wrap gap-2 pt-2 border-top">
                                    <button type="submit" class="btn btn--primary">{{ translate('apply') }}</button>
                                    <a href="{{ route('admin.whatsapp.conversations.index', array_merge(['channel' => request()->route('channel') ?? 'whatsapp', 'tab' => $tab ?? 'chats'], $waFsQuery)) }}"
                                       class="btn btn-outline-secondary">{{ translate('Clear_all_Filter') }}</a>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Tab: Active Chats / Human support — left: scrollable list, right: open chat --}}
            <?php if (($tab ?? '') === 'chats' || ($tab ?? '') === 'human_support'): ?>
                <div class="row g-3 align-items-stretch wa-chats-split-layout">
                    <div class="col-12 col-md-5 col-lg-4 col-xl-4 whatsapp-active-list-container wa-chats-split-col">
                        <div class="card h-100 d-flex flex-column wa-min-h-0">
                            <div class="card-header py-2 d-flex align-items-center justify-content-between gap-2 min-w-0 flex-wrap">
                                <strong class="flex-shrink-0 me-1">{{ !empty($humanSupportTab ?? false) ? translate('Human support requests') : translate('Chats') }}</strong>
                                <button type="button"
                                        class="btn btn-outline-primary btn-sm wa-chat-list-filter-btn d-inline-flex align-items-center gap-1 flex-shrink-0"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#wa-chats-filters-offcanvas"
                                        aria-controls="wa-chats-filters-offcanvas">
                                    {{ translate('Filters') }}
                                    @if(($waFacetCount ?? 0) > 0)
                                        <span class="badge bg-primary rounded-pill">{{ $waFacetCount }}</span>
                                    @endif
                                </button>
                            </div>
                            <div class="card-body p-0 wa-active-chat-list-scroll">
                                <?php $chatCollection = $chats ?? collect(); ?>
                                <div id="wa-active-chat-items">
                                <?php if ($chatCollection->isNotEmpty()): ?>
                                    <?php foreach ($chatCollection as $chat): ?>
                                        <?php
                                            $created = $chat->created_at ?? null;
                                            $phone = $chat->phone ?? '';
                                            $name = trim($chat->name ?? '');
                                            $phoneDisplay = $displayPhone($phone);
                                            $displayLine = $chat->display_line ?? ($name !== '' ? $name . ' (' . $phoneDisplay . ')' : $phoneDisplay);
                                            $direction = strtoupper($chat->direction ?? '');
                                            $status = strtolower($chat->status ?? '');
                                            $statusIcon = '';
                                            $hasUnread = !empty($chat->unread_count);
                                            if ($direction === 'OUT') {
                                                if ($status === 'sent') {
                                                    $statusIcon = '✓';
                                                } elseif ($status === 'delivered') {
                                                    $statusIcon = '✓✓';
                                                } elseif ($status === 'read') {
                                                    $statusIcon = '✓✓';
                                                }
                                            }
                                        ?>
                                        <?php
                                            $handledByLabel = $chat->handled_by_label ?? 'AI';
                                            $handledByKey = $chat->handled_by_key ?? 'AI';
                                            $lastMessageAt = \Modules\WhatsAppModule\Support\WhatsAppMessageTime::formatListLabel($created);
                                        ?>
                                        <?php
                                            $chatSt = isset($chat->chat_status) && is_array($chat->chat_status) ? $chat->chat_status : null;
                                            $chatTagList = isset($chat->chat_tags) && is_array($chat->chat_tags) ? $chat->chat_tags : [];
                                        ?>
                                        <div class="whatsapp-chat-item border-bottom p-3 cursor-pointer{{ $hasUnread ? ' bg-primary text-white' : '' }}"
                                             data-phone="{{ e($phone) }}"
                                             data-wa-display-line="{{ e($displayLine) }}"
                                             title="{{ e($phone) }}"
                                             role="button">
                                            {{-- Row 1: name (number) | system type pills --}}
                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                <strong class="text-truncate min-w-0{{ $hasUnread ? ' text-white' : '' }}" title="{{ e($displayLine) }}">{{ $displayLine }}</strong>
                                                <div class="flex-shrink-0">
                                                    @include('whatsappmodule::admin.conversations.partials.system-link-pills', [
                                                        'systemLink' => $chat->system_link ?? [],
                                                        'onUnread' => $hasUnread,
                                                        'showNames' => false,
                                                    ])
                                                </div>
                                            </div>
                                            {{-- Row 2: message (max 2 lines) | unread + status --}}
                                            <div class="d-flex justify-content-between align-items-start gap-2 mt-2">
                                                <div class="wa-chat-preview fz-12 flex-grow-1 min-w-0{{ $hasUnread ? ' text-white' : ' text-muted' }}">
                                                    {{ $chat->message_text ?? '' }}
                                                </div>
                                                <div class="flex-shrink-0 d-flex align-items-center gap-1 pt-0">
                                                    <?php if (!empty($chat->unread_count)): ?>
                                                        <span class="badge wa-unread-count-badge {{ $hasUnread ? 'bg-light text-primary' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                                                            {{ (int) $chat->unread_count }}
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($statusIcon): ?>
                                                        <span class="fz-12 {{ $hasUnread ? 'text-white' : ($status === 'read' ? 'text-primary' : 'text-muted') }}">{{ $statusIcon }}</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            {{-- Row 3: handled by | last message time --}}
                                            <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                                                <div class="min-w-0">
                                                    @include('whatsappmodule::admin.conversations.partials.handled-by-pill', [
                                                        'handledByKey' => $handledByKey,
                                                        'handledByLabel' => $handledByLabel,
                                                        'onUnread' => $hasUnread,
                                                    ])
                                                </div>
                                                <span class="wa-chat-item-row3-time {{ $hasUnread ? 'text-white-50' : 'text-muted' }}">{{ $lastMessageAt }}</span>
                                            </div>
                                            <?php if (!empty($chat->human_support_requested_at) && empty($humanSupportTab ?? false)): ?>
                                                <div class="fz-11 mt-1">
                                                    <span class="badge bg-warning text-dark">{{ translate('Wants human') }}</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($chatSt || !empty($chatTagList)): ?>
                                                <div class="wa-chat-item-meta mt-2 d-flex flex-wrap align-items-center gap-1">
                                                    <?php if ($chatSt): ?>
                                                        <?php $bucket = $chatSt['bucket'] ?? 'open'; ?>
                                                        <span class="badge fz-11 {{ $bucket === 'closed' ? 'bg-secondary' : 'bg-success' }}{{ $hasUnread ? ' text-white' : '' }}">{{ e($chatSt['name'] ?? '') }}</span>
                                                    <?php endif; ?>
                                                    <?php foreach ($chatTagList as $tg): ?>
                                                        <?php $tc = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($tg['color'] ?? '')) ? $tg['color'] : '#6c757d'; ?>
                                                        <span class="badge fz-11 wa-chat-tag-pill" style="background:{{ e($tc) }};color:#fff;">{{ e($tg['name'] ?? '') }}</span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-muted wa-no-chats-msg">{{ !empty($humanSupportTab ?? false) ? translate('No human support requests') : translate('No active chats') }}</div>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-7 col-lg-8 col-xl-8 wa-chat-column wa-chats-split-col">
                        <div class="card h-100 d-flex flex-column wa-chat-main-panel wa-min-h-0">
                            <div id="whatsapp-chat-placeholder" class="card-body d-flex align-items-center justify-content-center flex-grow-1 text-muted wa-min-h-0">
                                <span>{{ translate('Select a chat') }}</span>
                            </div>
                            <div id="whatsapp-chat-panel" class="d-none flex-column flex-grow-1 w-100 wa-min-h-0">
                                <div class="card-header wa-conversation-header">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2 min-w-0 flex-grow-1">
                                            <strong id="whatsapp-chat-phone-line" class="mb-0 text-truncate wa-header-title"></strong>
                                            <span id="whatsapp-chat-system-pills" class="d-flex flex-wrap align-items-center gap-1 min-w-0"></span>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 flex-shrink-0">
                                            <span id="whatsapp-chat-handled-pill" class="flex-shrink-0"></span>
                                            <span id="whatsapp-chat-view-leads-slot" class="flex-shrink-0"></span>
                                            <span id="whatsapp-chat-override-slot"></span>
                                            <span id="whatsapp-chat-status-slot" class="flex-shrink-0"></span>
                                            <span id="whatsapp-chat-delete-slot"></span>
                                            <div id="whatsapp-chat-actions" class="d-flex flex-wrap align-items-center gap-1"></div>
                                        </div>
                                    </div>
                                    <div class="wa-conversation-header-row2 d-none border-top mt-2 pt-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2 w-100">
                                            <span class="small text-muted flex-shrink-0">{{ translate('whatsapp_chat_tags_label') }}</span>
                                            <div id="whatsapp-chat-tags-row" class="d-flex flex-wrap align-items-center gap-1 flex-grow-1 min-w-0"></div>
                                            <div class="flex-shrink-0 position-relative wa-manage-tags-wrap">
                                                <button type="button" id="wa-manage-tags-btn" class="btn btn-sm btn-outline-secondary d-none">{{ translate('whatsapp_manage_tags') }}</button>
                                                <div id="wa-manage-tags-panel" class="d-none border rounded bg-white shadow-sm p-2 position-absolute end-0 mt-1" style="z-index: 40; min-width: 260px; max-height: 320px;">
                                                    <div class="form-label small mb-1" id="wa-manage-tags-field-label">{{ translate('whatsapp_manage_tags') }}</div>
                                                    <div id="wa-manage-tags-checkboxes" class="d-flex flex-column gap-1 border rounded p-2 bg-light" style="max-height: 220px; overflow-y: auto;" role="group" aria-labelledby="wa-manage-tags-field-label"></div>
                                                    <div class="d-flex justify-content-end gap-1 mt-2">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="wa-manage-tags-cancel">{{ translate('Cancel') }}</button>
                                                        <button type="button" class="btn btn-sm btn--primary" id="wa-manage-tags-save">{{ translate('save') }}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="whatsapp-chat-messages" class="card-body flex-grow-1 wa-min-h-0 p-0">
                                    <div class="wa-chat-messages-inner px-3 pt-2"></div>
                                </div>
                                <?php if(auth()->check() && auth()->user()->can('whatsapp_chat_reply')): ?>
                                    <div class="card-footer border-top">
                                        <div id="wa-session-window-banner" class="alert alert-warning py-2 px-3 mb-2 d-none small" role="status"></div>
                                        <div id="wa-waba-template-panel" class="border rounded p-2 mb-2 bg-body-secondary d-none">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label small mb-0" for="wa-waba-template-select">{{ translate('whatsapp_session_window_select_template') }}</label>
                                                    <select id="wa-waba-template-select" class="form-select form-select-sm">
                                                        <option value="">{{ translate('whatsapp_session_window_select_template') }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-6 d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                                                    <button type="button" class="btn btn-sm btn--primary" id="wa-waba-template-send-btn">{{ translate('whatsapp_session_window_send_template') }}</button>
                                                </div>
                                            </div>
                                            <div id="wa-waba-template-params" class="mt-2 row g-2"></div>
                                        </div>
                                        <div id="wa-reply-session-open-block">
                                        <div id="wa-conv-tpl-wrap" class="mb-2 px-1 wa-conv-tpl-row d-none">
                                            <div id="wa-conv-tpl-chips" class="d-flex flex-wrap align-items-center gap-1"></div>
                                        </div>
                                        <form id="whatsapp-reply-form" class="d-flex flex-column w-100">
                                            @csrf
                                            <input type="hidden" name="phone" id="whatsapp-reply-phone" value="">
                                            <input type="hidden" name="reply_to_wa_message_id" id="wa-reply-to-wa-id" value="">
                                            <div id="wa-reply-quote-bar" class="mb-2 d-none w-100 small border-start border-3 border-primary ps-2 py-1 bg-light rounded-end">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div class="min-w-0 flex-grow-1">
                                                        <div class="text-muted fz-11">{{ translate('WhatsApp_replying_to') }}</div>
                                                        <div id="wa-reply-quote-text" class="text-break"></div>
                                                    </div>
                                                    <button type="button" class="btn-close btn-sm flex-shrink-0 mt-0" id="wa-reply-quote-clear" aria-label="{{ translate('close') }}"></button>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 w-100">
                                            <button type="button"
                                                    class="btn btn-link text-decoration-none p-0 d-flex align-items-center justify-content-center"
                                                    id="wa-emoji-toggle"
                                                    style="width: 40px; height: 40px;"
                                                    title="{{ translate('Insert emoji') }}">
                                                😊
                                            </button>

                                            <label class="btn btn-link text-decoration-none p-0 d-flex align-items-center justify-content-center mb-0"
                                                   for="wa-attachment-input"
                                                   style="width: 40px; height: 40px;"
                                                   title="{{ translate('Attach file') }}">
                                                📎
                                            </label>
                                            <input type="file" id="wa-attachment-input" name="attachments[]" class="d-none" accept="image/*,application/pdf,video/*,audio/*" multiple>

                                            <div class="flex-grow-1 position-relative">
                                                <div id="wa-attachment-preview" class="mb-1 d-none d-flex flex-wrap gap-2"></div>
                                                <div id="wa-tpl-suggest" class="wa-tpl-suggest d-none" role="listbox" aria-label="{{ translate('WhatsApp_quick_reply_suggestions') }}"></div>
                                                <textarea name="body"
                                                          id="wa-reply-body"
                                                          class="form-control rounded-pill ps-3 pe-3"
                                                          rows="1"
                                                          autocomplete="off"
                                                          style="resize:none; min-height: 40px; max-height: 120px; line-height: 1.4; padding-top: 8px; padding-bottom: 8px; overflow-y:auto;"
                                                          placeholder="{{ translate('Type a message') }}"></textarea>
                                                <div id="wa-emoji-panel"
                                                     class="border rounded bg-light d-none flex-wrap gap-1 p-2 position-absolute"
                                                     style="bottom: 120%; left: 0; max-width: 280px; font-size: 1.2rem; z-index: 5;">
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😀">😀</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😁">😁</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😂">😂</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="🤣">🤣</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😊">😊</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😍">😍</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😎">😎</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😢">😢</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😡">😡</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="👍">👍</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="🙏">🙏</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="👌">👌</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="🔥">🔥</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="🎉">🎉</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="❤️">❤️</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="💔">💔</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="🤔">🤔</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😮">😮</button>
                                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 wa-emoji-btn" data-emoji="😴">😴</button>
                                                </div>
                                            </div>

                                            <button type="submit"
                                                    class="btn btn--primary d-flex align-items-center justify-content-center p-0"
                                                    style="width: 40px; height: 40px; border-radius: 50%;"
                                                    disabled>
                                                <svg width="22" height="22" viewBox="0 0 512.001 512.001" aria-hidden="true">
                                                    <path fill="currentColor"
                                                          d="M483.927,212.664L66.967,25.834C30.95,9.695-7.905,42.023,1.398,80.368l21.593,89.001
                                                             c3.063,12.622,11.283,23.562,22.554,30.014l83.685,47.915c6.723,3.85,6.738,13.546,0,17.405l-83.684,47.915
                                                             c-11.271,6.452-19.491,17.393-22.554,30.015l-21.594,89c-9.283,38.257,29.506,70.691,65.569,54.534l416.961-186.83
                                                             C521.383,282.554,521.333,229.424,483.927,212.664z M359.268,273.093l-147.519,66.1c-9.44,4.228-20.521,0.009-24.752-9.435
                                                             c-4.231-9.44-0.006-20.523,9.434-24.752l109.37-49.006l-109.37-49.006c-9.44-4.231-13.665-15.313-9.434-24.752
                                                             c4.229-9.44,15.309-13.666,24.752-9.435l147.519,66.101C373.996,245.505,374.007,266.49,359.268,273.093z"/>
                                                </svg>
                                            </button>
                                            </div>
                                        </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                @if(auth()->check() && auth()->user()->can('whatsapp_chat_reply'))
                    <div class="modal fade" id="wa-forward-modal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ translate('WhatsApp_forward_message') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label small text-muted mb-1" for="wa-forward-modal-search">{{ translate('Search here') }}</label>
                                    <input type="search"
                                           class="form-control"
                                           id="wa-forward-modal-search"
                                           placeholder="{{ translate('WhatsApp_forward_search_placeholder') }}"
                                           autocomplete="off">
                                    <div id="wa-forward-modal-list"
                                         class="list-group list-group-flush mt-2 border rounded"
                                         style="max-height: 360px; overflow-y: auto;"></div>
                                </div>
                                <div class="modal-footer flex-column align-items-stretch gap-2">
                                    <div id="wa-forward-modal-selected-count" class="small text-muted text-center"></div>
                                    <button type="button"
                                            class="btn btn-primary w-100"
                                            id="wa-forward-modal-send"
                                            disabled>{{ translate('WhatsApp_forward_send') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="modal fade" id="wa-conversation-leads-modal" tabindex="-1" aria-labelledby="wa-conversation-leads-modal-label" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="wa-conversation-leads-modal-label">{{ translate('User leads') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                            </div>
                            <div class="modal-body" id="wa-conversation-leads-body">
                                <div class="text-center py-4 text-muted">{{ translate('Loading…') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @push('script')
                <script>
(function() {
    (function initWaChatsFilterSelect2() {
        var oc = document.getElementById('wa-chats-filters-offcanvas');
        if (!oc || typeof jQuery === 'undefined' || !jQuery.fn.select2) {
            return;
        }
        function bindWaFilterSelect2() {
            jQuery('.wa-chats-filter-select2').each(function () {
                var $el = jQuery(this);
                if ($el.data('select2')) {
                    return;
                }
                var ph = $el.attr('data-placeholder') || '';
                var ac = $el.attr('data-allow-clear') === '1' || $el.attr('data-allow-clear') === 'true';
                $el.select2({
                    width: '100%',
                    dropdownParent: jQuery('#wa-chats-filters-offcanvas'),
                    placeholder: ph,
                    allowClear: ac
                });
            });
        }
        oc.addEventListener('shown.bs.offcanvas', bindWaFilterSelect2);
    })();
    var waConvTemplates = @json($waQuickTplPayload ?? []);
    var waAgentName = @json($waAgentDisplayNameForTemplates ?? '');
    var waCustomerName = '';
    var waTplSuggestMax = 10;
    var waTplChipMax = 5;
    var waTplSuggestSelected = -1;
    var waTplSuggestMatches = [];
    var messagesUrl = '{{ route("admin.whatsapp.conversations.chat.messages", ['channel' => $waInboxCh]) }}';
    var waUserDetailsForLeadsUrl = '{{ route("admin.whatsapp.users.details", ['channel' => $waInboxCh]) }}';
    var replyUrl = '{{ route("admin.whatsapp.conversations.reply", ['channel' => $waInboxCh]) }}';
    var reactionUrl = '{{ route("admin.whatsapp.conversations.reaction", ['channel' => $waInboxCh]) }}';
    var handoffUrl = '{{ route("admin.whatsapp.conversations.handoff", ['channel' => $waInboxCh]) }}';
    var deleteHistoryUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_delete') ? route('admin.whatsapp.conversations.delete-history', ['channel' => $waInboxCh]) : '');
    var canDeleteChatHistory = @json((bool) (auth()->check() && auth()->user()->can('whatsapp_chat_delete')));
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var currentAdminId = '{{ (string) auth()->id() }}';
    var currentPhone = null;
    var pollTimer = null;
    var currentHandler = null;
    var activeListTimer = null;
    /** When set, loads a window around this message id and keeps it highlighted until another chat opens. */
    var stickyFocusMessageId = null;
    var strNoResults = {!! json_encode(translate('No results')) !!};
    var strWaCustomer = {!! json_encode(translate('whatsapp_system_customer')) !!};
    var strWaProvider = {!! json_encode(translate('whatsapp_system_provider')) !!};
    var strWaNone = {!! json_encode(translate('whatsapp_not_in_system')) !!};
    var strHandlerAiLabel = {!! json_encode(translate('AI')) !!};
    var strOverrideChat = {!! json_encode(translate('Override chat')) !!};
    var strViewLeads = {!! json_encode(translate('View leads')) !!};
    var strViewLeadLink = {!! json_encode(translate('View lead')) !!};
    var strConvLeadsEmpty = {!! json_encode(translate('No provider leads')) !!};
    var strLoadingEllipsis = {!! json_encode(translate('Loading…')) !!};
    var strAssignBackAi = {!! json_encode(translate('Assign back to AI')) !!};
    var strDeleteChatTitle = {!! json_encode(translate('delete_chat')) !!};
    var strReply = {!! json_encode(translate('Reply')) !!};
    var strReact = {!! json_encode(translate('WhatsApp_react')) !!};
    var strGoToReplied = {!! json_encode(translate('WhatsApp_go_to_replied_message')) !!};
    var strOutNotSent = {!! json_encode(translate('WhatsApp_out_not_sent_to_user')) !!};
    var strStaffHeading = {!! json_encode(translate('WhatsApp_chat_staff_note_heading')) !!};
    var strCopy = {!! json_encode(translate('WhatsApp_copy')) !!};
    var strForward = {!! json_encode(translate('WhatsApp_forward')) !!};
    var strCopied = {!! json_encode(translate('WhatsApp_copied')) !!};
    var strForwardPrefix = {!! json_encode(translate('WhatsApp_forward_prefix')) !!};
    var strForwardSent = {!! json_encode(translate('WhatsApp_forward_sent')) !!};
    var strForwardSentMultiple = {!! json_encode(translate('WhatsApp_forward_sent_multiple')) !!};
    var strForwardSelectedCount = {!! json_encode(translate('WhatsApp_forward_selected_count')) !!};
    var activeChatsForForwardUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_reply') ? route('admin.whatsapp.conversations.active-chats-forward', ['channel' => $waInboxCh]) : '');
    var wabaTemplatesUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_view') ? route('admin.whatsapp.conversations.chat.waba-templates', ['channel' => $waInboxCh]) : '');
    var sendTemplateUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_reply') ? route('admin.whatsapp.conversations.chat.send-template', ['channel' => $waInboxCh]) : '');
    var strSessionBanner = {!! json_encode(translate('whatsapp_session_window_banner')) !!};
    var strSessionTextareaPh = {!! json_encode(translate('whatsapp_session_window_textarea_placeholder')) !!};
    var strTplLoadFailed = {!! json_encode(translate('whatsapp_waba_templates_load_failed')) !!};
    var strTplSentOk = {!! json_encode(translate('whatsapp_template_sent_ok')) !!};
    var strTplSentFail = {!! json_encode(translate('whatsapp_template_send_failed')) !!};
    var strPlaceholderN = {!! json_encode(translate('whatsapp_session_window_placeholder_n')) !!};
    var strTplHeaderVars = {!! json_encode(translate('whatsapp_template_section_header_vars')) !!};
    var strTplBodyVars = {!! json_encode(translate('whatsapp_template_section_body_vars')) !!};
    var strTplMediaUrlLabel = {!! json_encode(translate('whatsapp_template_header_media_url_label')) !!};
    var strWaSessionTplSelectPlaceholder = {!! json_encode(translate('whatsapp_session_window_select_template')) !!};
    var waSessionWindowOpen = true;
    var waWabaTemplatesList = null;
    var waWabaTemplatesLoading = false;
    var threadStatusUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_reply') ? route('admin.whatsapp.conversations.thread-status', ['channel' => $waInboxCh]) : '');
    var threadTagsUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_reply') ? route('admin.whatsapp.conversations.thread-tags', ['channel' => $waInboxCh]) : '');
    var strChatStatus = {!! json_encode(translate('whatsapp_chat_status')) !!};
    var strManageTags = {!! json_encode(translate('whatsapp_manage_tags')) !!};
    var strSave = {!! json_encode(translate('save')) !!};
    var waForwardPayloadB64 = '';
    var waForwardChatsCache = [];
    var waForwardSelectedPhones = Object.create(null);
    var waMsgTimeZone = @json(config('whatsappmodule.message_timezone', 'Asia/Kolkata'));
    var waMsgTimeLocale = 'en-IN';
    function waFormatChatMessageTime(isoVal) {
        if (!isoVal) return '';
        var d = new Date(isoVal);
        if (isNaN(d.getTime())) return '';
        return d.toLocaleTimeString(waMsgTimeLocale, { timeZone: waMsgTimeZone, hour: 'numeric', minute: '2-digit', hour12: true });
    }
    function waFormatChatMessageDateTimeNow() {
        var d = new Date();
        return d.toLocaleString(waMsgTimeLocale, {
            timeZone: waMsgTimeZone,
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
    function waEscapeHtmlThenNl2br(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');
    }
    /** File name for document links — do not use caption HTML here (caption uses &lt;br&gt; otherwise). */
    function waBasenameFromMediaUrl(url) {
        if (!url) return 'Document';
        try {
            var path = String(url).split('?')[0];
            var name = path.split('/').pop() || '';
            name = decodeURIComponent(name);
            if (name && name.length <= 180) return name;
        } catch (e) { /* ignore */ }
        return 'Document';
    }
    var canWaThreadActions = @json(auth()->check() && auth()->user()->can('whatsapp_chat_reply'));
    var canWaHandoff = @json(auth()->check() && auth()->user()->can('whatsapp_chat_assign'));

    function waClearReplyTarget() {
        var hid = document.getElementById('wa-reply-to-wa-id');
        var bar = document.getElementById('wa-reply-quote-bar');
        var txt = document.getElementById('wa-reply-quote-text');
        if (hid) hid.value = '';
        if (bar) bar.classList.add('d-none');
        if (txt) txt.textContent = '';
    }
    function waSetReplyTarget(waMid, previewPlain) {
        var hid = document.getElementById('wa-reply-to-wa-id');
        var bar = document.getElementById('wa-reply-quote-bar');
        var txt = document.getElementById('wa-reply-quote-text');
        if (!hid || !waMid) return;
        hid.value = waMid;
        if (txt) txt.textContent = previewPlain || '';
        if (bar) bar.classList.remove('d-none');
        var ta = document.getElementById('wa-reply-body');
        if (ta) ta.focus();
    }

    function debounce(fn, wait) {
        var t = null;
        return function() {
            var ctx = this;
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function() { fn.apply(ctx, args); }, wait);
        };
    }

    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function waSyncThreadTags(phone, tagIds) {
        if (!threadTagsUrl || !phone) {
            return;
        }
        fetch(threadTagsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ phone: phone, tag_ids: tagIds }),
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.ok) {
                    return;
                }
                var panel = document.getElementById('wa-manage-tags-panel');
                if (panel) {
                    panel.classList.add('d-none');
                }
                waPatchListItemChatMeta(phone, data.chat_meta);
                if (currentPhone === phone) {
                    loadMessages(phone, false);
                }
            })
            .catch(function () {});
    }

    function waB64EncodeUtf8(str) {
        try {
            return btoa(unescape(encodeURIComponent(String(str || ''))));
        } catch (e) {
            return '';
        }
    }

    function waB64DecodeUtf8(b64) {
        try {
            return decodeURIComponent(escape(atob(String(b64 || ''))));
        } catch (e) {
            return '';
        }
    }

    function waRenderedSystemPills(link, compact) {
        var cust = link && link.customer;
        var prov = link && link.provider;
        compact = !!compact;
        var parts = [];
        if (cust && cust.url) {
            parts.push('<a href="' + escapeHtml(cust.url) + '" target="_blank" rel="noopener" class="wa-sys-pill wa-sys-pill--customer text-decoration-none">' + escapeHtml(strWaCustomer) + '</a>');
            if (!compact && cust.name) {
                parts.push('<a href="' + escapeHtml(cust.url) + '" target="_blank" rel="noopener" class="wa-sys-name-link small text-decoration-none text-truncate d-inline-block" style="max-width:9rem" title="' + escapeHtml(cust.name) + '">' + escapeHtml(cust.name) + '</a>');
            }
        }
        if (cust && cust.url && prov && prov.url) {
            parts.push('<span class="wa-sys-pill-sep text-muted px-0">|</span>');
        }
        if (prov && prov.url) {
            parts.push('<a href="' + escapeHtml(prov.url) + '" target="_blank" rel="noopener" class="wa-sys-pill wa-sys-pill--provider text-decoration-none">' + escapeHtml(strWaProvider) + '</a>');
            if (!compact && prov.name) {
                parts.push('<a href="' + escapeHtml(prov.url) + '" target="_blank" rel="noopener" class="wa-sys-name-link small text-decoration-none text-truncate d-inline-block" style="max-width:9rem" title="' + escapeHtml(prov.name) + '">' + escapeHtml(prov.name) + '</a>');
            }
        }
        if (!parts.length) {
            parts.push('<span class="wa-sys-pill wa-sys-pill--none">' + escapeHtml(strWaNone) + '</span>');
        }
        return '<span class="wa-sys-pills d-flex align-items-center gap-1 flex-wrap">' + parts.join('') + '</span>';
    }

    function waRenderedHandledPill(handler) {
        handler = handler || {};
        var isAi = (handler.type || '') === 'AI';
        var text = isAi ? strHandlerAiLabel : (handler.name || 'Agent');
        var cls = isAi ? 'wa-hand-pill wa-hand-pill--ai' : 'wa-hand-pill wa-hand-pill--agent';
        return '<span class="' + cls + '">' + escapeHtml(text) + '</span>';
    }

    function waChatItemMetaHtml(meta) {
        meta = meta || {};
        var st = meta.chat_status;
        var tags = meta.chat_tags || [];
        var h = '';
        if (st && st.name) {
            var b = (st.bucket || 'open') === 'closed' ? 'bg-secondary' : 'bg-success';
            h += '<span class="badge fz-11 ' + b + '">' + escapeHtml(st.name) + '</span>';
        }
        tags.forEach(function (t) {
            var c = /^#[0-9A-Fa-f]{6}$/.test(t.color || '') ? t.color : '#6c757d';
            h +=
                '<span class="badge fz-11 wa-chat-tag-pill" style="background:' +
                escapeHtml(c) +
                ';color:#fff">' +
                escapeHtml(t.name) +
                '</span>';
        });
        return h;
    }

    function waPatchListItemChatMeta(phone, meta) {
        if (!phone) return;
        var el = null;
        document.querySelectorAll('.whatsapp-chat-item').forEach(function (node) {
            if (node.getAttribute('data-phone') === phone) el = node;
        });
        if (!el) return;
        var h = waChatItemMetaHtml(meta);
        var host = el.querySelector('.wa-chat-item-meta');
        if (!h) {
            if (host) host.remove();
            return;
        }
        if (!host) {
            host = document.createElement('div');
            host.className = 'wa-chat-item-meta mt-2 d-flex flex-wrap align-items-center gap-1';
            el.appendChild(host);
        }
        host.innerHTML = h;
    }

    function waApplyChatHeaderMeta(phone, res) {
        var row2 = document.querySelector('.wa-conversation-header-row2');
        var tagsRow = document.getElementById('whatsapp-chat-tags-row');
        var manageBtn = document.getElementById('wa-manage-tags-btn');
        var panel = document.getElementById('wa-manage-tags-panel');
        var tagCheckboxHost = document.getElementById('wa-manage-tags-checkboxes');
        var stSlot = document.getElementById('whatsapp-chat-status-slot');
        if (!res.chat_statuses_all || !res.chat_statuses_all.length) {
            if (row2) row2.classList.add('d-none');
            if (stSlot) stSlot.innerHTML = '';
            if (tagsRow) tagsRow.innerHTML = '';
            if (manageBtn) manageBtn.classList.add('d-none');
            return;
        }
        if (row2) row2.classList.remove('d-none');
        var cur = res.chat_status || {};
        var applied = res.chat_status_applied_id != null ? String(res.chat_status_applied_id) : (cur.id != null ? String(cur.id) : '');
        var bucket = (cur.bucket || 'open') === 'closed' ? 'closed' : 'open';
        var badgeCls = bucket === 'closed' ? 'btn-secondary' : 'btn-success';
        if (stSlot) {
            if (canWaThreadActions) {
                var sb = '<span class="wa-chat-status-ctl d-inline-flex align-items-center gap-1">';
                sb += '<button type="button" class="btn btn-sm ' + badgeCls + ' text-white wa-chat-status-badge" id="wa-chat-status-badge">' + escapeHtml(cur.name || '') + '</button>';
                sb += '<select id="wa-chat-status-select" class="form-select form-select-sm d-none" style="max-width:12rem;" aria-label="' + escapeHtml(strChatStatus) + '">';
                res.chat_statuses_all.forEach(function (s) {
                    var sel = String(s.id) === applied ? ' selected' : '';
                    sb += '<option value="' + String(s.id) + '"' + sel + '>' + escapeHtml(s.name) + '</option>';
                });
                sb += '</select></span>';
                stSlot.innerHTML = sb;
                var badgeEl = document.getElementById('wa-chat-status-badge');
                var selEl = document.getElementById('wa-chat-status-select');
                if (badgeEl && selEl) {
                    badgeEl.onclick = function () {
                        badgeEl.classList.add('d-none');
                        selEl.classList.remove('d-none');
                        selEl.focus();
                    };
                    selEl.onblur = function () {
                        setTimeout(function () {
                            if (!selEl) return;
                            selEl.classList.add('d-none');
                            if (badgeEl) badgeEl.classList.remove('d-none');
                        }, 150);
                    };
                    selEl.onchange = function () {
                        var v = parseInt(selEl.value, 10);
                        if (!phone || isNaN(v)) return;
                        fetch(threadStatusUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ phone: phone, whatsapp_chat_status_id: v }),
                        })
                            .then(function (r) {
                                return r.json();
                            })
                            .then(function (data) {
                                if (!data.ok) return;
                                waPatchListItemChatMeta(phone, data.chat_meta);
                                loadMessages(phone, false);
                            });
                    };
                }
            } else if (cur.name) {
                stSlot.innerHTML =
                    '<span class="badge ' +
                    (bucket === 'closed' ? 'bg-secondary' : 'bg-success') +
                    '">' +
                    escapeHtml(cur.name) +
                    '</span>';
            } else {
                stSlot.innerHTML = '';
            }
        }
        var tagHtml = '';
        (res.chat_tags || []).forEach(function (t) {
            var c = /^#[0-9A-Fa-f]{6}$/.test(t.color || '') ? t.color : '#6c757d';
            tagHtml +=
                '<span class="badge fz-12 wa-chat-tag-pill" style="background:' +
                escapeHtml(c) +
                ';color:#fff">' +
                escapeHtml(t.name) +
                '</span>';
        });
        if (tagsRow) {
            tagsRow.innerHTML = tagHtml;
        }
        if (manageBtn) {
            if (canWaThreadActions && res.chat_tags_all && res.chat_tags_all.length) {
                manageBtn.classList.remove('d-none');
                manageBtn.textContent = strManageTags;
                manageBtn.onclick = function () {
                    if (!tagCheckboxHost || !panel) return;
                    tagCheckboxHost.innerHTML = '';
                    var selectedIds = {};
                    (res.chat_tags || []).forEach(function (t) {
                        selectedIds[String(t.id)] = true;
                    });
                    res.chat_tags_all.forEach(function (t) {
                        var idStr = String(t.id);
                        var row = document.createElement('div');
                        row.className = 'form-check mb-0';
                        var cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.className = 'form-check-input';
                        cb.setAttribute('data-wa-tag-id', idStr);
                        cb.id = 'wa-manage-tag-cb-' + idStr;
                        cb.checked = !!selectedIds[idStr];
                        var lab = document.createElement('label');
                        lab.className = 'form-check-label small';
                        lab.setAttribute('for', cb.id);
                        lab.textContent = t.name || '#' + idStr;
                        row.appendChild(cb);
                        row.appendChild(lab);
                        tagCheckboxHost.appendChild(row);
                    });
                    panel.classList.toggle('d-none');
                };
            } else {
                manageBtn.classList.add('d-none');
            }
        }
        var saveBtn = document.getElementById('wa-manage-tags-save');
        var cancelBtn = document.getElementById('wa-manage-tags-cancel');
        if (saveBtn) {
            saveBtn.textContent = strSave;
            saveBtn.onclick = function () {
                if (!tagCheckboxHost || !phone) return;
                var ids = [];
                tagCheckboxHost.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    if (!cb.checked) return;
                    var v = parseInt(cb.getAttribute('data-wa-tag-id'), 10);
                    if (!isNaN(v)) ids.push(v);
                });
                waSyncThreadTags(phone, ids);
            };
        }
        if (cancelBtn) {
            cancelBtn.onclick = function () {
                if (panel) panel.classList.add('d-none');
            };
        }
    }

    function bindActiveChatListClicks(scope) {
        var root = scope || document;
        root.querySelectorAll('.whatsapp-chat-item').forEach(function(el) {
            el.addEventListener('click', function() {
                openChat(this.getAttribute('data-phone'));
            });
        });
    }

    function formatPhoneDisplay(phone) {
        var digits = String(phone || '').replace(/\D+/g, '');
        if (!digits) return '—';
        return digits.length > 10 ? digits.slice(-10) : digits;
    }

    function waNormalizeWaDigits(p) {
        return String(p || '').replace(/\D+/g, '');
    }
    function waResolvePhoneToListKey(wantPhone) {
        var want = waNormalizeWaDigits(wantPhone);
        if (!want) {
            return wantPhone;
        }
        var found = null;
        document.querySelectorAll('.whatsapp-chat-item').forEach(function (el) {
            var p = el.getAttribute('data-phone') || '';
            var d = waNormalizeWaDigits(p);
            if (!d) {
                return;
            }
            if (d === want || d.slice(-10) === want.slice(-10) || want.endsWith(d) || d.endsWith(want)) {
                found = p;
            }
        });
        return found || wantPhone;
    }

    function openChat(phone, options) {
        options = options || {};
        if (!phone) return;
        var rawFocus = options.focusMessageId;
        var parsedFocus = rawFocus != null && String(rawFocus).trim() !== ''
            ? parseInt(String(rawFocus), 10)
            : NaN;
        stickyFocusMessageId = !isNaN(parsedFocus) ? parsedFocus : null;
        waClearReplyTarget();

        // Always store full DB phone for POST; header is display-only (last 10 digits).
        var replyPhoneEl = document.getElementById('whatsapp-reply-phone');
        if (replyPhoneEl) replyPhoneEl.value = phone;
        var headerLine = document.getElementById('whatsapp-chat-phone-line');
        var displayLine = '';
        document.querySelectorAll('.whatsapp-chat-item').forEach(function(el) {
            if (el.getAttribute('data-phone') === phone) {
                displayLine = el.getAttribute('data-wa-display-line') || '';
            }
        });
        if (headerLine) {
            headerLine.textContent = displayLine || formatPhoneDisplay(phone);
            headerLine.setAttribute('title', phone);
        }
        var phEl = document.getElementById('whatsapp-chat-placeholder');
        var chatPanel = document.getElementById('whatsapp-chat-panel');
        if (!phEl || !chatPanel) {
            return;
        }
        phEl.classList.add('d-none');
        chatPanel.classList.remove('d-none');
        chatPanel.classList.add('d-flex');
        // Clear unread styling immediately when opening this chat
        document.querySelectorAll('.whatsapp-chat-item').forEach(function(el) {
            var isCurrent = el.getAttribute('data-phone') === phone;
            if (!isCurrent) {
                el.classList.remove('bg-light');
            }
            if (isCurrent) {
                el.classList.remove('bg-primary', 'text-white');
                el.querySelectorAll('.text-white, .text-white-50').forEach(function(node) {
                    node.classList.remove('text-white', 'text-white-50');
                });
                el.querySelectorAll('.text-muted, .fz-12, .fz-11').forEach(function(node) {
                    // ensure normal muted styling
                    if (!node.classList.contains('fz-11') && !node.classList.contains('fz-12')) return;
                });
                var unreadBadge = el.querySelector('.wa-unread-count-badge');
                if (unreadBadge && unreadBadge.parentNode) {
                    unreadBadge.parentNode.removeChild(unreadBadge);
                }
            }
        });
        if (currentPhone !== phone) {
            waWabaTemplatesList = null;
            waWabaTemplatesLoading = false;
        }
        currentPhone = phone;
        waCustomerName = '';
        startPolling();
        loadMessages(phone, false);
        waInitConvTemplatePicker();
    }

    function waFormatReactionsStrip(isOut, rx) {
        rx = rx || {};
        var rc = rx.customer ? String(rx.customer) : '';
        var ra = rx.agent ? String(rx.agent) : '';
        if (!rc && !ra) {
            return '';
        }
        var bdr = isOut ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.08)';
        var h = '<div class="fz-11 mt-2 pt-1 wa-msg-reactions" style="opacity:0.92;border-top:1px solid ' + bdr + '">';
        if (rc) {
            h += '<span class="me-2" title="Customer">👤 ' + rc.replace(/</g, '&lt;') + '</span>';
        }
        if (ra) {
            h += '<span title="You">✓ ' + ra.replace(/</g, '&lt;') + '</span>';
        }
        h += '</div>';
        return h;
    }

    function waScrollToMessageByWaId(panel, waMid) {
        if (!panel || !waMid) {
            return;
        }
        var rows = panel.querySelectorAll('.wa-msg-row');
        var target = null;
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].getAttribute('data-wa-message-id') === waMid) {
                target = rows[i];
                break;
            }
        }
        if (!target) {
            if (typeof toastr !== 'undefined') {
                toastr.info({!! json_encode(translate('WhatsApp_reply_original_not_loaded')) !!});
            }
            return;
        }
        var bubble = target.querySelector('.wa-msg-bubble') || target;
        bubble.scrollIntoView({ behavior: 'smooth', block: 'center' });
        bubble.classList.add('wa-msg-jump-flash');
        setTimeout(function () {
            bubble.classList.remove('wa-msg-jump-flash');
        }, 1300);
    }

    function waForwardChatsFilter(items, q) {
        var needle = String(q || '').trim().toLowerCase();
        var dig = needle.replace(/\D+/g, '');
        if (!needle) {
            return items || [];
        }
        return (items || []).filter(function (c) {
            var line = String(c.display_line || '').toLowerCase();
            var ph = String(c.phone || '');
            var phLow = ph.toLowerCase();
            var phDig = ph.replace(/\D+/g, '');
            if (line.indexOf(needle) !== -1) {
                return true;
            }
            if (phLow.indexOf(needle) !== -1) {
                return true;
            }
            if (dig.length >= 3 && phDig.indexOf(dig) !== -1) {
                return true;
            }
            return false;
        });
    }

    function waUpdateForwardSendButton() {
        var btn = document.getElementById('wa-forward-modal-send');
        var countEl = document.getElementById('wa-forward-modal-selected-count');
        var n = Object.keys(waForwardSelectedPhones).length;
        if (btn) {
            btn.disabled = n === 0;
        }
        if (countEl) {
            countEl.textContent = n ? strForwardSelectedCount.replace(':count', String(n)) : '';
        }
    }

    function waRenderForwardList(items) {
        var listEl = document.getElementById('wa-forward-modal-list');
        if (!listEl) {
            return;
        }
        if (!items || !items.length) {
            listEl.innerHTML = '<div class="list-group-item text-muted small">' + escapeHtml(strNoResults) + '</div>';
            waUpdateForwardSendButton();
            return;
        }
        var h = '';
        items.forEach(function (c) {
            var phRaw = String(c.phone || '');
            var ph = escapeHtml(phRaw);
            var line = (c.display_line && String(c.display_line).trim()) ? String(c.display_line) : formatPhoneDisplay(c.phone);
            var checked = waForwardSelectedPhones[phRaw] ? ' checked' : '';
            h += '<label class="list-group-item list-group-item-action d-flex align-items-center gap-2 wa-forward-row py-2 mb-0 cursor-pointer">';
            h += '<input type="checkbox" class="form-check-input flex-shrink-0 mt-0 wa-forward-cb"' + checked + ' data-phone="' + ph + '">';
            h += '<div class="flex-grow-1 min-w-0 text-start">';
            h += '<div class="fw-medium text-truncate">' + escapeHtml(line) + '</div>';
            h += '<div class="small text-muted text-truncate">' + ph + '</div>';
            h += '</div></label>';
        });
        listEl.innerHTML = h;
        listEl.querySelectorAll('.wa-forward-cb').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var ph = cb.getAttribute('data-phone') || '';
                if (cb.checked) {
                    waForwardSelectedPhones[ph] = true;
                } else {
                    delete waForwardSelectedPhones[ph];
                }
                waUpdateForwardSendButton();
            });
        });
        waUpdateForwardSendButton();
    }

    function waOpenForwardModal() {
        var modalEl = document.getElementById('wa-forward-modal');
        var listEl = document.getElementById('wa-forward-modal-list');
        var searchEl = document.getElementById('wa-forward-modal-search');
        if (!modalEl || !activeChatsForForwardUrl || !listEl) {
            return;
        }
        if (searchEl) {
            searchEl.value = '';
        }
        waForwardSelectedPhones = Object.create(null);
        waUpdateForwardSendButton();
        listEl.innerHTML = '<div class="list-group-item text-muted small">…</div>';
        var inst = window.bootstrap && bootstrap.Modal ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        if (inst) {
            inst.show();
        }
        fetch(activeChatsForForwardUrl + '?exclude_phone=' + encodeURIComponent(currentPhone || ''), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (res) {
                waForwardChatsCache = res.data || [];
                waRenderForwardList(waForwardChatsFilter(waForwardChatsCache, searchEl ? searchEl.value : ''));
            })
            .catch(function () {
                waForwardChatsCache = [];
                listEl.innerHTML = '<div class="list-group-item text-danger small">Failed to load</div>';
            });
    }

    function waForwardPostOne(destPhone, body) {
        var fd = new FormData();
        fd.append('_token', csrf);
        fd.append('phone', destPhone);
        fd.append('body', body);
        return fetch(replyUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: fd,
        }).then(function (r) {
            return r.json().catch(function () {
                return {};
            });
        });
    }

    function waSendForwardToSelected() {
        var phones = Object.keys(waForwardSelectedPhones);
        if (!phones.length) {
            return;
        }
        var raw = waB64DecodeUtf8(waForwardPayloadB64 || '');
        if (!raw) {
            return;
        }
        var body = strForwardPrefix + '\n\n' + raw;
        var sendBtn = document.getElementById('wa-forward-modal-send');
        if (sendBtn) {
            sendBtn.disabled = true;
        }
        var chain = Promise.resolve();
        var ok = 0;
        var lastWarning = '';
        phones.forEach(function (destPhone) {
            chain = chain.then(function () {
                return waForwardPostOne(destPhone, body).then(function (res) {
                    if (res.whatsapp_sent !== false) {
                        ok++;
                    } else {
                        lastWarning = String(res.whatsapp_error || '');
                    }
                }).catch(function () {
                    lastWarning = 'Forward failed';
                });
            });
        });
        chain.then(function () {
            var modalEl = document.getElementById('wa-forward-modal');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
                var mi = bootstrap.Modal.getInstance(modalEl);
                if (mi) {
                    mi.hide();
                }
            }
            waForwardSelectedPhones = Object.create(null);
            waUpdateForwardSendButton();
            if (typeof toastr !== 'undefined') {
                if (phones.length === 1) {
                    if (ok) {
                        toastr.success(strForwardSent);
                    } else {
                        toastr.warning(lastWarning || 'Forward may be saved locally only');
                    }
                } else if (ok === phones.length) {
                    toastr.success(strForwardSentMultiple.replace(':count', String(ok)));
                } else if (ok > 0) {
                    toastr.warning(strForwardSentMultiple.replace(':count', String(ok)) + ' (' + (phones.length - ok) + ' failed)');
                } else {
                    toastr.error(lastWarning || 'Forward failed');
                }
            }
        });
    }

    function waPatchReactionsOnRow(panel, waMid, rx) {
        if (!panel || !waMid) {
            return;
        }
        var rows = panel.querySelectorAll('.wa-msg-row');
        var row = null;
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].getAttribute('data-wa-message-id') === waMid) {
                row = rows[i];
                break;
            }
        }
        if (!row) {
            return;
        }
        var bubble = row.querySelector('.wa-msg-bubble');
        if (!bubble) {
            return;
        }
        var isOut = row.getAttribute('data-msg-direction') === 'out';
        var existing = bubble.querySelector('.wa-msg-reactions');
        var rc = rx && rx.customer ? String(rx.customer) : '';
        var ra = rx && rx.agent ? String(rx.agent) : '';
        if (!rc && !ra) {
            if (existing) {
                existing.remove();
            }
            return;
        }
        var html = waFormatReactionsStrip(isOut, rx);
        if (existing) {
            existing.outerHTML = html;
        } else {
            bubble.insertAdjacentHTML('beforeend', html);
        }
    }

    function waApplyMessagingWindow(res) {
        var mw = res.messaging_window;
        var open = true;
        if (mw && Object.prototype.hasOwnProperty.call(mw, 'session_open')) {
            open = !!mw.session_open;
        }
        waSessionWindowOpen = open;

        var banner = document.getElementById('wa-session-window-banner');
        var panel = document.getElementById('wa-waba-template-panel');
        var convWrap = document.getElementById('wa-conv-tpl-wrap');
        var openBlock = document.getElementById('wa-reply-session-open-block');
        var ta = document.getElementById('wa-reply-body');
        var attLabel = document.querySelector('label[for="wa-attachment-input"]');
        var emojiBtn = document.getElementById('wa-emoji-toggle');
        var handler = res.handler || currentHandler || { type: 'AI' };
        var showComposer = handler.type === 'USER';

        if (!showComposer) {
            if (banner) banner.classList.add('d-none');
            if (panel) panel.classList.add('d-none');
            if (openBlock) openBlock.classList.add('d-none');
            return;
        }

        if (open) {
            if (banner) banner.classList.add('d-none');
            if (panel) panel.classList.add('d-none');
            if (openBlock) openBlock.classList.remove('d-none');
            if (convWrap) convWrap.classList.remove('d-none');
            if (ta) {
                ta.disabled = false;
                ta.removeAttribute('placeholder');
            }
            if (attLabel) {
                attLabel.classList.remove('opacity-50', 'pe-none');
            }
            if (attachmentInputEl) attachmentInputEl.disabled = false;
            if (emojiBtn) emojiBtn.disabled = false;
            updateSendDisabled();
            return;
        }

        if (banner) {
            banner.textContent = strSessionBanner;
            banner.classList.remove('d-none');
        }
        if (panel) panel.classList.remove('d-none');
        if (openBlock) openBlock.classList.add('d-none');
        if (convWrap) convWrap.classList.add('d-none');
        if (ta) {
            ta.disabled = true;
            ta.value = '';
            ta.setAttribute('placeholder', strSessionTextareaPh);
        }
        if (attLabel) {
            attLabel.classList.add('opacity-50', 'pe-none');
        }
        if (attachmentInputEl) attachmentInputEl.disabled = true;
        if (emojiBtn) emojiBtn.disabled = true;
        var emojiPanelMw = document.getElementById('wa-emoji-panel');
        if (emojiPanelMw) emojiPanelMw.classList.add('d-none');
        waHideTplSuggest();
        updateSendDisabled();
        waLoadWabaTemplatesIfNeeded();
    }

    function waTemplateSelectValue(t) {
        return (t.name || '') + '\t' + (t.language || '');
    }

    function waDestroyWabaTemplateSelect2() {
        var sel = document.getElementById('wa-waba-template-select');
        if (!sel || typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }
        var $s = jQuery(sel);
        if ($s.data('select2') && $s.hasClass('select2-hidden-accessible')) {
            try {
                $s.select2('destroy');
            } catch (e) { /* ignore */ }
        }
    }

    function waInitWabaTemplateSelect2() {
        var sel = document.getElementById('wa-waba-template-select');
        if (!sel || typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }
        waDestroyWabaTemplateSelect2();
        var $s = jQuery(sel);
        $s.select2({
            width: '100%',
            dropdownParent: jQuery('body'),
            dropdownCssClass: 'select2-wa-session-tpl-dd',
            minimumResultsForSearch: 0,
            matcher: function (params, data) {
                if (jQuery.trim(params.term) === '') {
                    return data;
                }
                if (!data.id) {
                    return data;
                }
                var term = params.term.toLowerCase();
                var txt = String(data.text || '').toLowerCase();
                var name = '';
                var lang = '';
                var cat = '';
                if (data.element) {
                    name = String(data.element.getAttribute('data-wa-tpl-name') || '').toLowerCase();
                    lang = String(data.element.getAttribute('data-wa-tpl-language') || '').toLowerCase();
                    cat = String(data.element.getAttribute('data-wa-tpl-category') || '').toLowerCase();
                }
                if (txt.indexOf(term) > -1 || name.indexOf(term) > -1 || lang.indexOf(term) > -1 || cat.indexOf(term) > -1) {
                    return data;
                }
                return null;
            },
            templateResult: function (state) {
                if (!state.id) {
                    return state.text;
                }
                var el = state.element;
                var name = el ? String(el.getAttribute('data-wa-tpl-name') || '').trim() : '';
                var lang = el ? String(el.getAttribute('data-wa-tpl-language') || '').trim() : '';
                var cat = el ? String(el.getAttribute('data-wa-tpl-category') || '').trim() : '';
                var line2 = [lang, cat].filter(function (x) { return x; }).join(' · ');
                var $wrap = jQuery('<div class="wa-session-tpl-opt py-1"></div>');
                $wrap.append(jQuery('<div class="fw-semibold small text-break"></div>').text(name || state.text));
                if (line2) {
                    $wrap.append(jQuery('<div class="text-muted" style="font-size:0.78rem;"></div>').text(line2));
                }
                return $wrap;
            },
            templateSelection: function (state) {
                if (!state.id) {
                    return state.text;
                }
                var el = state.element;
                var name = el ? String(el.getAttribute('data-wa-tpl-name') || '').trim() : '';
                var lang = el ? String(el.getAttribute('data-wa-tpl-language') || '').trim() : '';
                if (name && lang) {
                    return name + ' (' + lang + ')';
                }
                return name || state.text;
            },
        });
    }

    function waLoadWabaTemplatesIfNeeded() {
        var sel = document.getElementById('wa-waba-template-select');
        if (!sel || !wabaTemplatesUrl) {
            return;
        }
        if (waWabaTemplatesList !== null || waWabaTemplatesLoading) {
            return;
        }
        waWabaTemplatesLoading = true;
        waDestroyWabaTemplateSelect2();
        sel.innerHTML = '<option value="">…</option>';
        fetch(wabaTemplatesUrl, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                waWabaTemplatesLoading = false;
                if (!data || !data.ok) {
                    waWabaTemplatesList = null;
                    if (typeof toastr !== 'undefined') {
                        toastr.error(strTplLoadFailed);
                    }
                    sel.innerHTML = '<option value="">' + escapeHtml(strTplLoadFailed) + '</option>';
                    waInitWabaTemplateSelect2();
                    return;
                }
                waWabaTemplatesList = data.templates || [];
                sel.innerHTML = '<option value="">' + escapeHtml(strWaSessionTplSelectPlaceholder) + '</option>';
                waWabaTemplatesList.forEach(function (t) {
                    var opt = document.createElement('option');
                    opt.value = waTemplateSelectValue(t);
                    opt.textContent = (t.name || '') + ' · ' + (t.language || '') + (t.category ? ' (' + t.category + ')' : '');
                    opt.setAttribute('data-n', String(t.body_placeholder_count != null ? t.body_placeholder_count : 0));
                    opt.setAttribute('data-wa-tpl-name', t.name || '');
                    opt.setAttribute('data-wa-tpl-language', t.language || '');
                    opt.setAttribute('data-wa-tpl-category', t.category || '');
                    sel.appendChild(opt);
                });
                waInitWabaTemplateSelect2();
            })
            .catch(function () {
                waWabaTemplatesLoading = false;
                waWabaTemplatesList = null;
                if (typeof toastr !== 'undefined') {
                    toastr.error(strTplLoadFailed);
                }
                if (sel) {
                    waDestroyWabaTemplateSelect2();
                    sel.innerHTML = '<option value="">' + escapeHtml(strTplLoadFailed) + '</option>';
                    waInitWabaTemplateSelect2();
                }
            });
    }

    /** Meta-style double-brace placeholders; build with unicode escapes so Blade does not parse them. */
    function waMetaTplVarBraces(inner) {
        return '\u007B\u007B' + String(inner) + '\u007D\u007D';
    }

    function waRebuildWabaTemplateFields() {
        var sel = document.getElementById('wa-waba-template-select');
        var host = document.getElementById('wa-waba-template-params');
        if (!host) {
            return;
        }
        host.innerHTML = '';
        var raw = sel && sel.value ? sel.value : '';
        if (!raw || !waWabaTemplatesList || !waWabaTemplatesList.length) {
            return;
        }
        var parts = String(raw).split('\t');
        var tname = parts[0];
        var tlang = parts.length > 1 ? parts[1] : '';
        var tpl = null;
        waWabaTemplatesList.forEach(function (t) {
            if ((t.name || '') === tname && (t.language || '') === tlang) {
                tpl = t;
            }
        });
        if (!tpl) {
            return;
        }
        var pv = (tpl.preview || tpl.body_text || '').trim();
        if (pv) {
            var hint = document.createElement('div');
            hint.className = 'col-12 small text-muted mb-2';
            hint.textContent = pv;
            host.appendChild(hint);
        }
        var htc = parseInt(tpl.header_text_placeholder_count, 10) || 0;
        var bpc = parseInt(tpl.body_placeholder_count, 10) || 0;
        var hm = tpl.header_media_format || null;
        var i;
        if (htc > 0) {
            var hsec = document.createElement('div');
            hsec.className = 'col-12 fw-semibold small mt-1';
            hsec.textContent = strTplHeaderVars;
            host.appendChild(hsec);
            for (i = 1; i <= htc; i++) {
                var colH = document.createElement('div');
                colH.className = 'col-12 col-md-6';
                var labH = document.createElement('label');
                labH.className = 'form-label small mb-0';
                labH.setAttribute('for', 'wa-waba-hdr-param-' + i);
                var hBraced = (tpl.header_text_parameter_format === 'named' && tpl.header_named_param_names && tpl.header_named_param_names[i - 1])
                    ? waMetaTplVarBraces(tpl.header_named_param_names[i - 1])
                    : waMetaTplVarBraces(i);
                labH.textContent = hBraced;
                var inpH = document.createElement('input');
                inpH.type = 'text';
                inpH.className = 'form-control form-control-sm';
                inpH.id = 'wa-waba-hdr-param-' + i;
                inpH.autocomplete = 'off';
                inpH.placeholder = hBraced;
                colH.appendChild(labH);
                colH.appendChild(inpH);
                host.appendChild(colH);
            }
        }
        if (hm && htc === 0) {
            var colM = document.createElement('div');
            colM.className = 'col-12';
            var labM = document.createElement('label');
            labM.className = 'form-label small mb-0';
            labM.setAttribute('for', 'wa-waba-header-media-url');
            labM.textContent = strTplMediaUrlLabel + ' (' + hm + ')';
            var inpM = document.createElement('input');
            inpM.type = 'url';
            inpM.className = 'form-control form-control-sm';
            inpM.id = 'wa-waba-header-media-url';
            inpM.placeholder = 'https://';
            inpM.autocomplete = 'off';
            colM.appendChild(labM);
            colM.appendChild(inpM);
            host.appendChild(colM);
        }
        if (bpc > 0) {
            var bsec = document.createElement('div');
            bsec.className = 'col-12 fw-semibold small mt-2';
            bsec.textContent = strTplBodyVars;
            host.appendChild(bsec);
            for (i = 1; i <= bpc; i++) {
                var colB = document.createElement('div');
                colB.className = 'col-12 col-md-6';
                var labB = document.createElement('label');
                labB.className = 'form-label small mb-0';
                labB.setAttribute('for', 'wa-waba-body-param-' + i);
                var bBraced = (tpl.body_parameter_format === 'named' && tpl.body_named_param_names && tpl.body_named_param_names[i - 1])
                    ? waMetaTplVarBraces(tpl.body_named_param_names[i - 1])
                    : waMetaTplVarBraces(i);
                labB.textContent = bBraced;
                var inpB = document.createElement('input');
                inpB.type = 'text';
                inpB.className = 'form-control form-control-sm';
                inpB.id = 'wa-waba-body-param-' + i;
                inpB.autocomplete = 'off';
                inpB.placeholder = bBraced;
                colB.appendChild(labB);
                colB.appendChild(inpB);
                host.appendChild(colB);
            }
        }
    }

    function waClearWabaTemplateComposer() {
        var sel = document.getElementById('wa-waba-template-select');
        if (sel) {
            if (typeof jQuery !== 'undefined' && jQuery(sel).data('select2')) {
                jQuery(sel).val('').trigger('change');
            } else {
                sel.value = '';
            }
        }
        waRebuildWabaTemplateFields();
    }

    function waChatMessagesInner(panel) {
        if (!panel) {
            return null;
        }
        var inner = panel.querySelector('.wa-chat-messages-inner');
        if (!inner) {
            inner = document.createElement('div');
            inner.className = 'wa-chat-messages-inner px-3 pt-2';
            panel.textContent = '';
            panel.appendChild(inner);
        }
        return inner;
    }

    function waConvLeadsEscapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function waConvLeadsFormatPhoneDisplay(phoneRaw) {
        var digits = String(phoneRaw || '').replace(/\D+/g, '');
        if (!digits) return '—';
        return digits.length > 10 ? digits.slice(-10) : digits;
    }

    function waConvLeadsRenderTable(leads) {
        if (!leads || !leads.length) {
            return '<p class="text-muted mb-0">' + waConvLeadsEscapeHtml(strConvLeadsEmpty) + '</p>';
        }
        function leadTypeMeta(type) {
            var normalized = String(type || '').toLowerCase();
            if (normalized === 'invalid') {
                return { label: 'Invalid', badgeClass: 'bg-danger text-white' };
            }
            if (normalized === 'customer') {
                return { label: 'Customer', badgeClass: 'bg-success text-white' };
            }
            if (normalized === 'provider') {
                return { label: 'Provider', badgeClass: 'bg-primary text-white' };
            }
            if (normalized === 'future_customer') {
                return { label: 'Future Customer', badgeClass: 'bg-info text-dark' };
            }
            if (normalized === 'unknown') {
                return { label: 'Unknown', badgeClass: 'bg-warning text-dark' };
            }
            var fallbackLabel = String(type || '—').replace(/_/g, ' ');
            return { label: fallbackLabel, badgeClass: 'bg-warning text-dark' };
        }
        var html = '<div class="table-responsive"><table class="table table-sm align-middle text-nowrap">';
        html += '<thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Type</th><th>Status</th><th>Received</th><th class="text-end">Action</th></tr></thead><tbody>';
        leads.forEach(function (lead) {
            var typeMeta = leadTypeMeta(lead.lead_type);
            html += '<tr>';
            html += '<td>#' + waConvLeadsEscapeHtml(String(lead.id != null ? lead.id : '')) + '</td>';
            html += '<td>' + waConvLeadsEscapeHtml(lead.name || '—') + '</td>';
            html += '<td>' + waConvLeadsEscapeHtml(waConvLeadsFormatPhoneDisplay(lead.phone_number)) + '</td>';
            html += '<td><span class="badge rounded-pill ' + typeMeta.badgeClass + '">' + waConvLeadsEscapeHtml(typeMeta.label) + '</span></td>';
            var isOpen = !!lead.is_open;
            var statusLabel = isOpen ? 'Open' : 'Closed';
            var statusClass = isOpen ? 'bg-danger' : 'bg-success';
            html += '<td><span class="badge rounded-pill ' + statusClass + '">' + waConvLeadsEscapeHtml(statusLabel) + '</span></td>';
            html += '<td>' + waConvLeadsEscapeHtml(lead.received_at || '—') + '</td>';
            html += '<td class="text-end"><a href="' + waConvLeadsEscapeHtml(lead.url || '#') + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">' + waConvLeadsEscapeHtml(strViewLeadLink) + '</a></td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        return html;
    }

    function waOpenConversationLeadsModal(phoneRaw) {
        var modalEl = document.getElementById('wa-conversation-leads-modal');
        var bodyEl = document.getElementById('wa-conversation-leads-body');
        if (!modalEl || !bodyEl || !phoneRaw) {
            return;
        }
        bodyEl.innerHTML = '<div class="text-center py-4 text-muted">' + waConvLeadsEscapeHtml(strLoadingEllipsis) + '</div>';
        var bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
        fetch(waUserDetailsForLeadsUrl + '?phone=' + encodeURIComponent(phoneRaw), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.json().then(function (json) {
                return { ok: r.ok, data: json };
            });
        }).then(function (result) {
            var data = result.data || {};
            if (data.error) {
                bodyEl.innerHTML = '<div class="alert alert-danger">' + waConvLeadsEscapeHtml(data.error || 'Failed to load') + '</div>';
                return;
            }
            bodyEl.innerHTML = waConvLeadsRenderTable(data.leads || []);
        }).catch(function () {
            bodyEl.innerHTML = '<div class="alert alert-danger">' + waConvLeadsEscapeHtml('Failed to load leads') + '</div>';
        });
    }

    function loadMessages(phone, isPoll) {
        var panel = document.getElementById('whatsapp-chat-messages');
        if (!panel) {
            return;
        }
        var inner = waChatMessagesInner(panel);
        var wasNearBottom = true;
        if (isPoll && panel) {
            var threshold = 100;
            wasNearBottom = (panel.scrollHeight - panel.scrollTop - panel.clientHeight) <= threshold;
        }
        if (!isPoll && inner) {
            inner.innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
        }
        var url = messagesUrl + '?phone=' + encodeURIComponent(phone) + '&full=1&mark_seen=1';
        if (stickyFocusMessageId) {
            url += '&focus_message_id=' + encodeURIComponent(String(stickyFocusMessageId));
        }
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.thread_phone) {
                    var ctp = String(res.thread_phone).trim();
                    if (ctp) {
                        phone = ctp;
                        currentPhone = ctp;
                        var rpeSync = document.getElementById('whatsapp-reply-phone');
                        if (rpeSync) {
                            rpeSync.value = ctp;
                        }
                    }
                }
                waApplyMessagingWindow(res);
                var html = '';
                (res.data || []).forEach(function(m) {
                    var isOut = (m.direction || '').toUpperCase() === 'OUT';
                    var time = '';
                    if (m.created_at) {
                        time = waFormatChatMessageTime(m.created_at);
                    }
                    var rawMsg = String(m.message_text || m.body || '');
                    var mediaUrl = m.media_url || '';
                    var msgType = (m.message_type || '').toUpperCase();
                    var body;
                    if (msgType === 'TEMPLATE' && rawMsg.indexOf('\n\n') !== -1) {
                        var ix = rawMsg.indexOf('\n\n');
                        var tLine = rawMsg.slice(0, ix).trim();
                        var tRest = rawMsg.slice(ix + 2).trim();
                        body = '<div class="fw-semibold small">' + escapeHtml(tLine) + '</div>';
                        if (tRest) {
                            body += '<div class="mt-2 small wa-template-msg-body" style="white-space:pre-wrap;">' + waEscapeHtmlThenNl2br(tRest) + '</div>';
                        }
                    } else {
                        body = waEscapeHtmlThenNl2br(rawMsg);
                    }
                    var isDocument = msgType === 'DOCUMENT';
                    var isVideo = msgType === 'VIDEO';
                    var isAudio = msgType === 'AUDIO';
                    var isImage = msgType === 'IMAGE' || msgType === 'MEDIA';
                    var isMedia = isImage || isVideo || isAudio || isDocument;
                    var status = (m.status || '').toLowerCase();
                    var statusIcon = '';
                    var statusLabel = '';
                    if (isOut) {
                        if (status === 'sent') {
                            statusIcon = ' <span class="opacity-75">✓</span>';
                            statusLabel = 'Sent';
                        } else if (status === 'delivered') {
                            statusIcon = ' <span class="opacity-75">✓✓</span>';
                            statusLabel = 'Delivered';
                        } else if (status === 'read') {
                            statusIcon = ' <span class="opacity-75" style="color:#8ecae6">✓✓</span>';
                            statusLabel = 'Read';
                        } else if (status === 'failed') {
                            statusLabel = 'Failed';
                        }
                    }
                    var statusDetail = (m.status_detail || '').trim();
                    var sentBy = (m.sent_by || '').trim();
                    var midAttr = (m.id != null && m.id !== '') ? String(m.id) : '';
                    var rowFocusClass = (stickyFocusMessageId !== null && m.id != null && Number(m.id) === stickyFocusMessageId)
                        ? ' wa-msg-selected'
                        : '';
                    var waMid = (m.wa_message_id && String(m.wa_message_id).trim()) || '';
                    var safeWaMidAttr = waMid.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    var canReply = canWaThreadActions && waMid !== '';
                    var canReact = canReply && !isOut && waSessionWindowOpen;
                    var plainForCopy = rawMsg;
                    var copyB64 = waB64EncodeUtf8(plainForCopy);
                    var hasCopyText = plainForCopy.trim() !== '';
                    var previewPlain = plainForCopy.replace(/\s+/g, ' ').trim();
                    if (previewPlain.length > 140) previewPlain = previewPlain.slice(0, 140) + '…';
                    var safePreviewAttr = previewPlain.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    var rowDir = isOut ? 'out' : 'in';
                    var rowAlign = isOut ? 'wa-msg-row--out align-items-end' : 'wa-msg-row--in align-items-start';
                    html += '<div class="mb-3 d-flex flex-column wa-msg-row ' + rowAlign + rowFocusClass + '" data-message-id="' + midAttr.replace(/"/g, '') + '" data-wa-message-id="' + safeWaMidAttr + '" data-msg-direction="' + rowDir + '">';
                    var hasReactStrip = canReact;
                    var hasBottomStrip = canReply || hasCopyText;
                    if (hasReactStrip) {
                        html += '<div class="wa-msg-react-strip wa-msg-react-strip--' + rowDir + '">';
                        var quickReactTop = ['👍','❤️','😂','😮','😢','🙏'];
                        quickReactTop.forEach(function(em) {
                            html += '<button type="button" class="btn btn-link btn-sm wa-msg-action-icon wa-send-reaction" data-wa-mid="' + safeWaMidAttr + '" data-emoji="' + em.replace(/"/g, '&quot;') + '" title="' + strReact + '">' + em + '</button>';
                        });
                        html += '</div>';
                    }
                    var bubbleCls = 'wa-msg-bubble rounded px-3 py-2 ' + (isOut ? 'bg-primary text-white' : 'bg-light');
                    html += '<div class="' + bubbleCls + '">';
                    var rp = (m.reply_preview || '').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var replyTargetWa = (m.reply_to_wa_message_id && String(m.reply_to_wa_message_id).trim()) || '';
                    var safeJumpWa = replyTargetWa.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                    if (rp) {
                        var qCls = 'wa-reply-quote-snippet fz-11 mb-2 mt-1 px-2 py-1 rounded-1 text-start';
                        if (isOut) {
                            qCls += ' wa-reply-quote-snippet--out-hover';
                        }
                        if (replyTargetWa) {
                            html += '<button type="button" class="wa-reply-preview-jump ' + qCls + '" data-jump-wa-mid="' + safeJumpWa + '" title="' + strGoToReplied + '">' + rp + '</button>';
                        } else {
                            html += '<div class="' + qCls + '">' + rp + '</div>';
                        }
                    }
                    if (isDocument && mediaUrl) {
                        var docName = String(waBasenameFromMediaUrl(mediaUrl))
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;');
                        html += '<div class="mt-1 d-flex align-items-center gap-2"><a href="' + mediaUrl.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener" class="text-decoration-none d-flex align-items-center gap-2">';
                        html += '<span class="opacity-90">📄</span><span class="text-break">' + docName + '</span></a></div>';
                    } else if (isVideo && mediaUrl) {
                        var safeVideoUrl = mediaUrl.replace(/"/g, '&quot;');
                        html += '<div class="mt-1">';
                        html += '<button type="button" class="btn p-0 border-0 bg-transparent whatsapp-video-thumb" data-video-url="' + safeVideoUrl + '">';
                        html += '<div class="position-relative bg-dark" style="width:220px; height:140px; border-radius:8px; overflow:hidden;">';
                        html += '<div class="position-absolute top-50 start-50 translate-middle text-white d-flex flex-column align-items-center justify-content-center">';
                        html += '<div style="width:58px; height:58px; border-radius:50%; background:rgba(0,0,0,0.55); display:flex; align-items:center; justify-content:center;">';
                        html += '<span style="border-style:solid; border-width:10px 0 10px 16px; border-color:transparent transparent transparent white; margin-left:3px;"></span>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '</button>';
                        html += '</div>';
                    } else if (isAudio && mediaUrl) {
                        var safeAudioUrl = mediaUrl.replace(/"/g, '&quot;');
                        html += '<div class="mt-1">';
                        html += '<button type="button" class="btn p-0 border-0 bg-transparent whatsapp-audio-thumb" data-audio-url="' + safeAudioUrl + '">';
                        html += '<div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white text-dark border">';
                        html += '<span class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px;">';
                        html += '<span style="border-style:solid; border-width:6px 0 6px 10px; border-color:transparent transparent transparent #fff; margin-left:2px;"></span>';
                        html += '</span>';
                        html += '<span class="small">Play audio</span>';
                        html += '</div>';
                        html += '</button>';
                        html += '</div>';
                    } else if (isImage && mediaUrl) {
                        html += '<div class="mt-1"><img src="' + mediaUrl.replace(/"/g, '&quot;') + '" alt="" style="max-width:100%; max-height:280px; object-fit:contain; border-radius:6px;" /></div>';
                    }
                    if (body) {
                        html += '<div class="mt-1">' + body + '</div>';
                    }
                    html += waFormatReactionsStrip(isOut, m.reactions || {});
                    html += '</div>';
                    var staffMetaParts = [];
                    staffMetaParts.push(isOut ? 'OUT' : 'IN');
                    staffMetaParts.push(msgType || 'TEXT');
                    if (time) {
                        staffMetaParts.push(time);
                    }
                    if (isOut && statusLabel) {
                        staffMetaParts.push(statusLabel);
                    }
                    var staffMetaLine = staffMetaParts.join(' · ');
                    if (isOut && sentBy) {
                        staffMetaLine += ' · Sent by ' + sentBy.replace(/</g, '&lt;');
                    }
                    html += '<div class="wa-msg-staff-note text-start">';
                    html += '<div class="wa-msg-staff-note__label">' + String(strStaffHeading || 'Staff').replace(/</g, '&lt;') + '</div>';
                    html += '<div class="text-break">' + staffMetaLine.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>';
                    if (isOut && status === 'failed' && statusDetail) {
                        var safeDetail = statusDetail.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        html += '<div class="mt-1 text-danger text-break">' + safeDetail + '</div>';
                    }
                    if (isOut && waMid === '' && status !== 'failed') {
                        var safeNotSent = String(strOutNotSent || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        html += '<div class="mt-1 text-warning">' + safeNotSent + '</div>';
                    }
                    html += '</div>';
                    if (hasBottomStrip) {
                        html += '<div class="wa-msg-bottom-strip wa-msg-bottom-strip--' + rowDir + '">';
                        if (canReply) {
                            html += '<button type="button" class="btn btn-link btn-sm wa-msg-action-icon wa-action-reply" data-wa-mid="' + safeWaMidAttr + '" data-preview="' + safePreviewAttr + '" title="' + escapeHtml(strReply) + '"><span class="material-icons" aria-hidden="true">reply</span></button>';
                        }
                        if (hasCopyText) {
                            html += '<button type="button" class="btn btn-link btn-sm wa-msg-action-icon wa-action-copy" data-copy-b64="' + escapeHtml(copyB64) + '" title="' + escapeHtml(strCopy) + '"><span class="material-icons" aria-hidden="true">content_copy</span></button>';
                        }
                        if (canReply && hasCopyText && activeChatsForForwardUrl) {
                            html += '<button type="button" class="btn btn-link btn-sm wa-msg-action-icon wa-action-forward" data-copy-b64="' + escapeHtml(copyB64) + '" title="' + escapeHtml(strForward) + '"><span class="material-icons" aria-hidden="true">forward</span></button>';
                        }
                        html += '</div>';
                    }
                    html += '</div>';
                });
                if (inner) {
                    inner.innerHTML = html || '<p class="text-muted text-center py-4">No messages yet</p>';
                }
                // Bind video preview buttons
                panel.querySelectorAll('.whatsapp-video-thumb').forEach(function(btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var url = this.getAttribute('data-video-url');
                        openVideoPreview(url);
                    });
                });
                // Bind audio preview buttons
                panel.querySelectorAll('.whatsapp-audio-thumb').forEach(function(btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var url = this.getAttribute('data-audio-url');
                        openAudioPreview(url);
                    });
                });
                if (!isPoll && stickyFocusMessageId) {
                    requestAnimationFrame(function() {
                        var hit = panel.querySelector('.wa-msg-row[data-message-id="' + String(stickyFocusMessageId) + '"]');
                        if (hit) {
                            hit.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }
                    });
                } else if (!isPoll || wasNearBottom) {
                    panel.scrollTop = panel.scrollHeight;
                }
                var sysPillsEl = document.getElementById('whatsapp-chat-system-pills');
                if (sysPillsEl) {
                    sysPillsEl.innerHTML = waRenderedSystemPills(res.system_link || {}, true);
                }
                var headerLineAfter = document.getElementById('whatsapp-chat-phone-line');
                if (headerLineAfter && res.display_line) {
                    headerLineAfter.textContent = res.display_line;
                }
                waCustomerName = (res.customer_name != null && res.customer_name !== undefined)
                    ? String(res.customer_name).trim()
                    : '';
                var leadsViewSlot = document.getElementById('whatsapp-chat-view-leads-slot');
                var overSlot = document.getElementById('whatsapp-chat-override-slot');
                var delSlot = document.getElementById('whatsapp-chat-delete-slot');
                if (leadsViewSlot) leadsViewSlot.innerHTML = '';
                if (overSlot) overSlot.innerHTML = '';
                if (delSlot) delSlot.innerHTML = '';
                if (leadsViewSlot && phone) {
                    var btnViewLeads = document.createElement('button');
                    btnViewLeads.type = 'button';
                    btnViewLeads.className = 'btn btn-sm btn-success text-white';
                    btnViewLeads.textContent = strViewLeads;
                    btnViewLeads.addEventListener('click', function () {
                        waOpenConversationLeadsModal(phone);
                    });
                    leadsViewSlot.appendChild(btnViewLeads);
                }
                var skipMetaUi =
                    (document.getElementById('wa-chat-status-select') &&
                        !document.getElementById('wa-chat-status-select').classList.contains('d-none')) ||
                    (document.getElementById('wa-manage-tags-panel') &&
                        !document.getElementById('wa-manage-tags-panel').classList.contains('d-none'));
                if (!skipMetaUi) {
                    waApplyChatHeaderMeta(phone, res);
                }
                var actions = document.getElementById('whatsapp-chat-actions');
                if (actions) {
                    actions.innerHTML = '';
                    if (res.booking_link) {
                        var a = document.createElement('a');
                        a.href = res.booking_link;
                        a.target = '_blank';
                        a.className = 'btn btn-sm btn--primary';
                        a.textContent = 'View Booking';
                        actions.appendChild(a);
                    }
                    if (res.conversation_state && (res.conversation_state.active_module || res.conversation_state.current_step)) {
                        var span = document.createElement('span');
                        span.className = 'badge bg-secondary';
                        span.textContent = (res.conversation_state.active_module || '') + ' · ' + (res.conversation_state.current_step || '');
                        actions.appendChild(span);
                    }
                }

                // Handler UI: who owns this chat, and override/assign-back controls
                var handler = res.handler || currentHandler || { type: 'AI', id: null, name: 'AI' };
                currentHandler = handler;
                var replyForm = document.getElementById('whatsapp-reply-form');
                var replyFooter = replyForm ? replyForm.closest('.card-footer') : null;
                var canSend = handler.type === 'USER';
                if (replyFooter) {
                    replyFooter.style.display = canSend ? '' : 'none';
                }

                var handledPillEl = document.getElementById('whatsapp-chat-handled-pill');
                if (handledPillEl) {
                    handledPillEl.innerHTML = waRenderedHandledPill(handler);
                }

                if (handler.type === 'AI' && canWaHandoff) {
                    var btnTake = document.createElement('button');
                    btnTake.type = 'button';
                    btnTake.className = 'btn btn-sm btn--primary';
                    btnTake.textContent = strOverrideChat;
                    btnTake.onclick = function () {
                        Swal.fire({
                            title: 'Are you sure you want to override this chat?',
                            text: 'Overriding means AI will no longer respond to this chat until it is assigned back.',
                            icon: 'warning',
                            showCancelButton: true,
                            cancelButtonColor: 'var(--bs-secondary)',
                            confirmButtonColor: 'var(--bs-primary)',
                            cancelButtonText: '{{ translate('Cancel') }}',
                            confirmButtonText: '{{ translate('Yes') }}',
                            reverseButtons: true,
                            showLoaderOnConfirm: true,
                            allowOutsideClick: () => !Swal.isLoading(),
                            preConfirm: function () {
                                return fetch(handoffUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrf,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ phone: phone, mode: 'take' })
                                }).then(function (response) {
                                    return response.json().then(function (body) {
                                        if (!response.ok) {
                                            var msg = (body && (body.message || body.error)) ? String(body.message || body.error) : 'Request failed';
                                            throw new Error(msg);
                                        }
                                        if (body && body.ok === false) {
                                            throw new Error((body.message || body.error) ? String(body.message || body.error) : 'Request failed');
                                        }
                                        return body;
                                    });
                                }).catch(function (error) {
                                    Swal.showValidationMessage(error.message || 'Request failed');
                                    throw error;
                                });
                            }
                        }).then(function(result) {
                            if (!result.isConfirmed) return;
                            loadMessages(phone, false);
                        });
                    };
                    if (overSlot) overSlot.appendChild(btnTake);
                    else if (actions) actions.appendChild(btnTake);
                } else if (handler.type === 'USER' && String(handler.id) === String(currentAdminId) && canWaHandoff) {
                    var btnAI = document.createElement('button');
                    btnAI.type = 'button';
                    btnAI.className = 'btn btn-sm btn--secondary';
                    btnAI.textContent = strAssignBackAi;
                    btnAI.onclick = function () {
                        Swal.fire({
                            title: 'Are you sure you want to assign this chat back to AI?',
                            text: 'AI will take over responding to this chat again.',
                            icon: 'warning',
                            showCancelButton: true,
                            cancelButtonColor: 'var(--bs-secondary)',
                            confirmButtonColor: 'var(--bs-primary)',
                            cancelButtonText: '{{ translate('Cancel') }}',
                            confirmButtonText: '{{ translate('Yes') }}',
                            reverseButtons: true,
                            showLoaderOnConfirm: true,
                            allowOutsideClick: () => !Swal.isLoading(),
                            preConfirm: function () {
                                return fetch(handoffUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrf,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ phone: phone, mode: 'ai' })
                                }).then(function (response) {
                                    return response.json().then(function (body) {
                                        if (!response.ok) {
                                            var msg = (body && (body.message || body.error)) ? String(body.message || body.error) : 'Request failed';
                                            throw new Error(msg);
                                        }
                                        if (body && body.ok === false) {
                                            throw new Error((body.message || body.error) ? String(body.message || body.error) : 'Request failed');
                                        }
                                        return body;
                                    });
                                }).catch(function (error) {
                                    Swal.showValidationMessage(error.message || 'Request failed');
                                    throw error;
                                });
                            }
                        }).then(function(result) {
                            if (!result.isConfirmed) return;
                            loadMessages(phone, false);
                        });
                    };
                    if (overSlot) overSlot.appendChild(btnAI);
                    else if (actions) actions.appendChild(btnAI);
                }

                if (canDeleteChatHistory && deleteHistoryUrl) {
                    var btnDel = document.createElement('button');
                    btnDel.type = 'button';
                    btnDel.className = 'btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center px-2';
                    btnDel.setAttribute('title', strDeleteChatTitle);
                    btnDel.setAttribute('aria-label', strDeleteChatTitle);
                    btnDel.innerHTML = '<span class="material-icons" style="font-size:18px;line-height:1;">delete_outline</span>';
                    btnDel.onclick = function () {
                        Swal.fire({
                            title: {!! json_encode(translate('delete_chat') . '?') !!},
                            text: {!! json_encode(translate('are_you_sure')) !!},
                            icon: 'warning',
                            showCancelButton: true,
                            cancelButtonColor: 'var(--bs-secondary)',
                            confirmButtonColor: 'var(--bs-danger)',
                            cancelButtonText: '{{ translate('Cancel') }}',
                            confirmButtonText: '{{ translate('Yes') }}',
                            reverseButtons: true,
                            showLoaderOnConfirm: true,
                            allowOutsideClick: () => !Swal.isLoading(),
                            preConfirm: function () {
                                return fetch(deleteHistoryUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrf,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ phone: phone })
                                }).then(function (response) {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                }).catch(function (error) {
                                    Swal.showValidationMessage('Request failed: ' + error.message);
                                    throw error;
                                });
                            }
                        }).then(function(result) {
                            if (!result.isConfirmed) return;
                            stickyFocusMessageId = null;
                            loadMessages(phone, false);
                            try {
                                var listContainer = document.querySelector('.whatsapp-active-list-container');
                                if (!listContainer) return;
                                var url = new URL(window.location.href);
                                fetch(url.toString(), {
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                }).then(function(r) { return r.text(); })
                                  .then(function(html) {
                                    var newList = waParseConversationsIndexHtml(html);
                                    if (newList) {
                                        waApplyActiveChatListHtml(listContainer, newList);
                                    }
                                  });
                            } catch (e) {}
                        });
                    };
                    if (delSlot) delSlot.appendChild(btnDel);
                    else if (actions) actions.appendChild(btnDel);
                }
                try {
                    if (typeof window.pkAdminRefreshWhatsAppUnread === 'function') {
                        window.pkAdminRefreshWhatsAppUnread({ skipSound: true });
                    }
                } catch (e) {}
            })
            .catch(function() {
                if (!isPoll) {
                    var inEl = waChatMessagesInner(panel);
                    if (inEl) {
                        inEl.innerHTML = '<p class="text-danger text-center py-4">Failed to load messages</p>';
                    }
                }
            });
    }

    function waParseConversationsIndexHtml(html) {
        try {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            return doc.querySelector('.whatsapp-active-list-container');
        } catch (e) {
            return null;
        }
    }

    function waActiveListScrollParent(listContainer) {
        if (!listContainer) return null;
        var items = listContainer.querySelector('#wa-active-chat-items');
        var n = items ? items.parentElement : null;
        while (n && listContainer.contains(n)) {
            if (n.scrollHeight > n.clientHeight + 1) {
                return n;
            }
            n = n.parentElement;
        }
        return listContainer.querySelector('.wa-active-chat-list-scroll')
            || listContainer.querySelector('.card-body.overflow-auto')
            || listContainer.querySelector('.card-body');
    }

    /** Refresh list items without replacing the scrollable .card-body (avoids scroll jump). Fallback: full replace + restore scroll. */
    function waApplyActiveChatListHtml(listContainer, newListFragment) {
        if (!listContainer || !newListFragment) return;
        var curItems = listContainer.querySelector('#wa-active-chat-items');
        var nextItems = newListFragment.querySelector('#wa-active-chat-items');
        if (curItems && nextItems) {
            curItems.innerHTML = nextItems.innerHTML;
            bindActiveChatListClicks(listContainer);
            return;
        }
        var scrollEl = waActiveListScrollParent(listContainer);
        var prevTop = scrollEl ? scrollEl.scrollTop : 0;
        listContainer.innerHTML = newListFragment.innerHTML;
        bindActiveChatListClicks(listContainer);
        var nextScroll = waActiveListScrollParent(listContainer);
        if (nextScroll) {
            nextScroll.scrollTop = prevTop;
            requestAnimationFrame(function () {
                nextScroll.scrollTop = prevTop;
            });
        }
    }

    function startPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        if (activeListTimer) {
            clearInterval(activeListTimer);
        }
        if (!currentPhone) return;
        pollTimer = setInterval(function() {
            if (currentPhone) {
                loadMessages(currentPhone, true);
            }
        }, 2000);
        // Also refresh active chats list (left column)
        activeListTimer = setInterval(function() {
            try {
                var listContainer = document.querySelector('.whatsapp-active-list-container');
                if (!listContainer) return;
                var url = new URL(window.location.href);
                fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function(r) { return r.text(); })
                  .then(function(html) {
                    var newList = waParseConversationsIndexHtml(html);
                    if (newList) {
                        waApplyActiveChatListHtml(listContainer, newList);
                    }
                  })
                  .catch(function() {});
            } catch (e) {}
        }, 5000);
    }

    var listCol = document.querySelector('.whatsapp-active-list-container');
    if (listCol) bindActiveChatListClicks(listCol);

    document.addEventListener('click', function(e) {
        var tagPanel = document.getElementById('wa-manage-tags-panel');
        if (tagPanel && !tagPanel.classList.contains('d-none') && !e.target.closest('.wa-manage-tags-wrap')) {
            tagPanel.classList.add('d-none');
        }
        if (e.target.closest('#wa-global-search') || e.target.closest('#wa-global-search-dropdown')) {
            return;
        }
        if (typeof window.waHideGlobalSearchDropdown === 'function') {
            window.waHideGlobalSearchDropdown();
        }
        if (
            e.target.closest('#wa-tpl-suggest') ||
            e.target.closest('#wa-reply-body') ||
            e.target.closest('#wa-conv-tpl-wrap') ||
            e.target.closest('.wa-manage-tags-wrap')
        ) {
            return;
        }
        waCloseTplSuggestPanelOnly();
    });

    var waFwdSearchEl = document.getElementById('wa-forward-modal-search');
    if (waFwdSearchEl) {
        waFwdSearchEl.addEventListener('input', function () {
            waRenderForwardList(waForwardChatsFilter(waForwardChatsCache, waFwdSearchEl.value));
        });
    }
    var waFwdSendBtn = document.getElementById('wa-forward-modal-send');
    if (waFwdSendBtn) {
        waFwdSendBtn.addEventListener('click', function () {
            waSendForwardToSelected();
        });
    }

    function waOpenChatFromQuery() {
        var hidden = document.getElementById('wa-initial-open-phone');
        var fromInput = hidden && hidden.value ? String(hidden.value).trim() : '';
        var fromUrl = new URLSearchParams(window.location.search).get('phone');
        var raw = (fromInput || fromUrl || '').trim();
        if (!raw) {
            return;
        }
        var key = waResolvePhoneToListKey(raw);
        var focusHidden = document.getElementById('wa-initial-focus-message-id');
        var focusRaw = (focusHidden && focusHidden.value ? String(focusHidden.value).trim() : '') || (new URLSearchParams(window.location.search).get('focus_message_id') || '').trim();
        var parsedFocus = focusRaw !== '' ? parseInt(focusRaw, 10) : NaN;
        var focusOpt = !isNaN(parsedFocus) ? { focusMessageId: String(parsedFocus) } : {};
        try {
            openChat(key, focusOpt);
        } catch (e) {
            console.error('waOpenChatFromQuery', e);
        }
    }

    window.waOpenChatFromSearch = function (phone, messageId) {
        if (typeof window.waHideGlobalSearchDropdown === 'function') {
            window.waHideGlobalSearchDropdown();
        }
        var gInp = document.getElementById('wa-global-search');
        if (gInp) {
            gInp.value = '';
        }
        var mid = messageId != null && String(messageId).trim() !== '' ? String(messageId).trim() : '';
        openChat(phone, mid ? { focusMessageId: mid } : {});
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            requestAnimationFrame(waOpenChatFromQuery);
        });
    } else {
        requestAnimationFrame(waOpenChatFromQuery);
    }

    var replyFormEl = document.getElementById('whatsapp-reply-form');
    var replyBodyEl = document.getElementById('wa-reply-body');
    var attachmentInputEl = document.getElementById('wa-attachment-input');
    var attachmentNameEl = document.getElementById('wa-attachment-name');
    var attachmentPreviewEl = document.getElementById('wa-attachment-preview');
    var emojiToggleEl = document.getElementById('wa-emoji-toggle');
    var emojiPanelEl = document.getElementById('wa-emoji-panel');
    var sendBtnEl = replyFormEl ? replyFormEl.querySelector('button[type="submit"]') : null;
    var attachmentFiles = []; // keep our own list of files across changes

    var waReplyClearBtn = document.getElementById('wa-reply-quote-clear');
    if (waReplyClearBtn) {
        waReplyClearBtn.addEventListener('click', function () {
            waClearReplyTarget();
        });
    }
    var chatMessagesPanel = document.getElementById('whatsapp-chat-messages');
    if (chatMessagesPanel) {
        chatMessagesPanel.addEventListener('click', function (e) {
            var jumpBtn = e.target.closest ? e.target.closest('.wa-reply-preview-jump') : null;
            if (jumpBtn) {
                e.preventDefault();
                var jw = jumpBtn.getAttribute('data-jump-wa-mid');
                waScrollToMessageByWaId(chatMessagesPanel, jw);
                return;
            }
            var copyBtn = e.target.closest ? e.target.closest('.wa-action-copy') : null;
            if (copyBtn) {
                e.preventDefault();
                var b64c = copyBtn.getAttribute('data-copy-b64') || '';
                var t = waB64DecodeUtf8(b64c);
                if (!t) {
                    return;
                }
                var done = function () {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(strCopied);
                    }
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(t).then(done).catch(function () {
                        try {
                            var ta = document.createElement('textarea');
                            ta.value = t;
                            ta.style.position = 'fixed';
                            ta.style.left = '-9999px';
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            done();
                        } catch (err2) {}
                    });
                } else {
                    try {
                        var ta2 = document.createElement('textarea');
                        ta2.value = t;
                        ta2.style.position = 'fixed';
                        ta2.style.left = '-9999px';
                        document.body.appendChild(ta2);
                        ta2.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta2);
                        done();
                    } catch (err3) {}
                }
                return;
            }
            var fwdBtn = e.target.closest ? e.target.closest('.wa-action-forward') : null;
            if (fwdBtn) {
                e.preventDefault();
                waForwardPayloadB64 = fwdBtn.getAttribute('data-copy-b64') || '';
                waOpenForwardModal();
                return;
            }
            var replyBtn = e.target.closest ? e.target.closest('.wa-action-reply') : null;
            if (replyBtn) {
                e.preventDefault();
                var wm = replyBtn.getAttribute('data-wa-mid');
                var pv = replyBtn.getAttribute('data-preview') || '';
                waSetReplyTarget(wm, pv);
                return;
            }
            var rxBtn = e.target.closest ? e.target.closest('.wa-send-reaction') : null;
            if (rxBtn) {
                e.preventDefault();
                var wm2 = rxBtn.getAttribute('data-wa-mid');
                var em = rxBtn.getAttribute('data-emoji') || '';
                var tel = document.getElementById('whatsapp-reply-phone');
                var ph = tel ? tel.value : '';
                if (!ph || !wm2) return;
                fetch(reactionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        phone: ph,
                        target_wa_message_id: wm2,
                        emoji: em,
                    }),
                })
                    .then(function (r) {
                        return r.json().catch(function () {
                            return {};
                        });
                    })
                    .then(function (res) {
                        if (res && res.ok) {
                            waPatchReactionsOnRow(chatMessagesPanel, wm2, res.reactions || {});
                        } else if (typeof toastr !== 'undefined') {
                            toastr.warning('Could not send reaction');
                        }
                    })
                    .catch(function () {
                        if (typeof toastr !== 'undefined') toastr.error('Reaction failed');
                    });
            }
        });
    }

    function waInterpolateConvTemplateBody(text) {
        if (!text) return '';
        var agent = waAgentName || '';
        var cust = (waCustomerName || '').trim();
        var s = String(text);
        s = s.split('{agent_name}').join(agent).split('@{{agent_name}}').join(agent);
        s = s.split('{customer_name}').join(cust).split('@{{customer_name}}').join(cust);
        return s;
    }

    function waFilterQuickReplyTemplates(q) {
        var needle = String(q || '').trim().toLowerCase();
        if (!needle) {
            return [];
        }
        var out = [];
        waConvTemplates.forEach(function (t, i) {
            var title = String(t.title || '').toLowerCase();
            var body = String(t.body || '').toLowerCase();
            if (title.indexOf(needle) !== -1 || body.indexOf(needle) !== -1) {
                out.push(i);
            }
        });
        return out.slice(0, waTplSuggestMax);
    }

    function waHideTplSuggest() {
        var el = document.getElementById('wa-tpl-suggest');
        if (el) {
            el.classList.add('d-none');
            el.innerHTML = '';
        }
        waTplSuggestSelected = -1;
        waTplSuggestMatches = [];
    }

    function waCloseTplSuggestPanelOnly() {
        var el = document.getElementById('wa-tpl-suggest');
        if (el) {
            el.classList.add('d-none');
            el.innerHTML = '';
        }
        waTplSuggestSelected = -1;
        waTplSuggestMatches = [];
    }

    function waHighlightTplSuggest(newSel) {
        var box = document.getElementById('wa-tpl-suggest');
        if (!box) {
            return;
        }
        var items = box.querySelectorAll('.wa-tpl-suggest-item');
        waTplSuggestSelected = newSel;
        items.forEach(function (node, i) {
            node.classList.toggle('wa-tpl-suggest-item--active', i === newSel);
        });
    }

    function waApplyTemplateIndex(tplIdx) {
        if (!replyBodyEl || waConvTemplates[tplIdx] == null) {
            return;
        }
        replyBodyEl.value = waInterpolateConvTemplateBody(waConvTemplates[tplIdx].body || '');
        replyBodyEl.dispatchEvent(new Event('input', { bubbles: true }));
        waHideTplSuggest();
        replyBodyEl.focus();
        updateSendDisabled();
        if (replyBodyEl.style) {
            replyBodyEl.style.height = 'auto';
            replyBodyEl.style.height = Math.min(replyBodyEl.scrollHeight, 120) + 'px';
        }
    }

    function waSyncTplSuggestUI() {
        var suggestEl = document.getElementById('wa-tpl-suggest');
        if (!suggestEl || !replyBodyEl) {
            return;
        }
        var needle = String(replyBodyEl.value || '').trim();
        if (needle === '') {
            waHideTplSuggest();
            return;
        }
        var idxs = waFilterQuickReplyTemplates(needle);
        if (idxs.length === 0) {
            suggestEl.classList.add('d-none');
            suggestEl.innerHTML = '';
            waTplSuggestMatches = [];
            waTplSuggestSelected = -1;
            return;
        }
        waTplSuggestMatches = idxs;
        waTplSuggestSelected = 0;
        suggestEl.classList.remove('d-none');
        suggestEl.innerHTML = '';
        idxs.forEach(function (tplIdx, li) {
            var t = waConvTemplates[tplIdx];
            var preview = waInterpolateConvTemplateBody(t.body || '');
            var previewShort = preview.length > 140 ? preview.slice(0, 140) + '\u2026' : preview;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'wa-tpl-suggest-item' + (li === 0 ? ' wa-tpl-suggest-item--active' : '');
            btn.setAttribute('role', 'option');
            btn.setAttribute('data-wa-tpl-idx', String(tplIdx));
            var titleEl = document.createElement('div');
            titleEl.className = 'wa-tpl-suggest-title';
            titleEl.textContent = t.title || ('#' + t.id);
            var prevEl = document.createElement('div');
            prevEl.className = 'wa-tpl-suggest-preview';
            prevEl.textContent = previewShort;
            btn.appendChild(titleEl);
            btn.appendChild(prevEl);
            suggestEl.appendChild(btn);
        });
    }

    function waInitConvTemplatePicker() {
        var wrap = document.getElementById('wa-conv-tpl-wrap');
        var host = document.getElementById('wa-conv-tpl-chips');
        if (!wrap || !host) {
            return;
        }
        host.innerHTML = '';
        waHideTplSuggest();
        if (!waConvTemplates || !waConvTemplates.length) {
            wrap.classList.add('d-none');
            return;
        }
        wrap.classList.remove('d-none');
        waConvTemplates.slice(0, waTplChipMax).forEach(function (t, i) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'btn btn-sm btn-outline-secondary text-truncate';
            chip.style.maxWidth = '12rem';
            var prev = waInterpolateConvTemplateBody(t.body || '');
            chip.title = (t.title || '') + (prev ? (' \u2014 ' + prev.slice(0, 100)) : '');
            chip.textContent = t.title || ('#' + t.id);
            chip.setAttribute('data-wa-tpl-index', String(i));
            host.appendChild(chip);
        });
    }

    var waTplSuggestEl = document.getElementById('wa-tpl-suggest');
    if (waTplSuggestEl) {
        waTplSuggestEl.addEventListener('click', function (e) {
            var btn = e.target.closest('.wa-tpl-suggest-item');
            if (!btn || !replyBodyEl) {
                return;
            }
            var idx = parseInt(btn.getAttribute('data-wa-tpl-idx'), 10);
            if (!isNaN(idx)) {
                waApplyTemplateIndex(idx);
            }
        });
    }

    if (replyBodyEl) {
        replyBodyEl.addEventListener('input', function () {
            waSyncTplSuggestUI();
        });
        replyBodyEl.addEventListener('focus', function () {
            waSyncTplSuggestUI();
        });
        replyBodyEl.addEventListener('keydown', function (e) {
            var box = document.getElementById('wa-tpl-suggest');
            if (!box || box.classList.contains('d-none') || waTplSuggestMatches.length === 0) {
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                var n = (waTplSuggestSelected + 1) % waTplSuggestMatches.length;
                waHighlightTplSuggest(n);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                var n2 = (waTplSuggestSelected - 1 + waTplSuggestMatches.length) % waTplSuggestMatches.length;
                waHighlightTplSuggest(n2);
            } else if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var cur = waTplSuggestMatches[waTplSuggestSelected];
                if (cur != null) {
                    waApplyTemplateIndex(cur);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                waCloseTplSuggestPanelOnly();
            }
        });
    }

    var waConvTplWrapEl = document.getElementById('wa-conv-tpl-wrap');
    if (waConvTplWrapEl && !waConvTplWrapEl.dataset.waChipDelegateBound) {
        waConvTplWrapEl.dataset.waChipDelegateBound = '1';
        waConvTplWrapEl.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-wa-tpl-index]');
            if (!chip || !replyBodyEl) {
                return;
            }
            var idx = parseInt(chip.getAttribute('data-wa-tpl-index'), 10);
            if (isNaN(idx) || !waConvTemplates[idx]) {
                return;
            }
            waApplyTemplateIndex(idx);
        });
    }

    waInitConvTemplatePicker();

    // Lazy-init simple Bootstrap modal for media (video/audio) preview
    var waVideoModalEl = null;
    var waVideoModalBody = null;
    var waVideoModalInstance = null;

    function ensureVideoModal() {
        if (waVideoModalEl) return;
        waVideoModalEl = document.createElement('div');
        waVideoModalEl.className = 'modal fade';
        waVideoModalEl.id = 'waVideoPreviewModal';
        waVideoModalEl.tabIndex = -1;
        waVideoModalEl.innerHTML =
            '<div class="modal-dialog modal-lg modal-dialog-centered">' +
                '<div class="modal-content bg-dark text-white">' +
                    '<div class="modal-header border-0">' +
                        '<h5 class="modal-title">{{ translate('Media') }}</h5>' +
                        '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>' +
                    '</div>' +
                    '<div class="modal-body d-flex justify-content-center" id="waVideoPreviewBody">' +
                        '<div class="text-center py-4 text-muted">{{ translate('Loading…') }}</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        document.body.appendChild(waVideoModalEl);
        waVideoModalBody = waVideoModalEl.querySelector('#waVideoPreviewBody');
    }

    function openVideoPreview(url) {
        if (!url) return;
        ensureVideoModal();
        if (!waVideoModalBody) return;
        var safeUrl = url.replace(/"/g, '&quot;');
        waVideoModalBody.innerHTML =
            '<video controls style="max-width:100%; max-height:70vh; border-radius:8px;"><source src="' + safeUrl + '"></video>';
        if (typeof bootstrap !== 'undefined') {
            waVideoModalInstance = waVideoModalInstance || new bootstrap.Modal(waVideoModalEl);
            waVideoModalInstance.show();
        } else {
            window.open(url, '_blank');
        }
    }

    function openAudioPreview(url) {
        if (!url) return;
        ensureVideoModal();
        if (!waVideoModalBody) return;
        var safeUrl = url.replace(/"/g, '&quot;');
        waVideoModalBody.innerHTML =
            '<audio controls style="width:100%;"><source src="' + safeUrl + '"></audio>';
        if (typeof bootstrap !== 'undefined') {
            waVideoModalInstance = waVideoModalInstance || new bootstrap.Modal(waVideoModalEl);
            waVideoModalInstance.show();
        } else {
            window.open(url, '_blank');
        }
    }

    function updateSendDisabled() {
        if (!sendBtnEl) return;
        var hasText = replyBodyEl && replyBodyEl.value.trim().length > 0;
        var hasFiles = attachmentFiles && attachmentFiles.length > 0;
        var winOk = typeof waSessionWindowOpen === 'undefined' || waSessionWindowOpen;
        sendBtnEl.disabled = !winOk || !(hasText || hasFiles);
    }

    if (emojiToggleEl && emojiPanelEl) {
        emojiToggleEl.addEventListener('click', function (e) {
            e.stopPropagation();
            emojiPanelEl.classList.toggle('d-none');
        });
        emojiPanelEl.querySelectorAll('.wa-emoji-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var emoji = this.getAttribute('data-emoji') || '';
                if (!replyBodyEl) return;
                var start = replyBodyEl.selectionStart || replyBodyEl.value.length;
                var end = replyBodyEl.selectionEnd || replyBodyEl.value.length;
                var text = replyBodyEl.value;
                replyBodyEl.value = text.slice(0, start) + emoji + text.slice(end);
                replyBodyEl.focus();
                var pos = start + emoji.length;
                replyBodyEl.setSelectionRange(pos, pos);
                // Make sure send button reacts to emoji-only messages
                updateSendDisabled();
            });
        });
        // Close emoji panel when clicking outside
        document.addEventListener('click', function (e) {
            if (!emojiPanelEl || emojiPanelEl.classList.contains('d-none')) return;
            if (emojiPanelEl.contains(e.target) || (emojiToggleEl && emojiToggleEl.contains(e.target))) return;
            emojiPanelEl.classList.add('d-none');
        });
    }

    if (replyBodyEl) {
        var autoResize = function () {
            replyBodyEl.style.height = 'auto';
            var newHeight = Math.min(replyBodyEl.scrollHeight, 120);
            replyBodyEl.style.height = newHeight + 'px';
            updateSendDisabled();
        };
        replyBodyEl.addEventListener('input', autoResize);
        // Initialize height and button state
        autoResize();
    } else {
        updateSendDisabled();
    }

    function renderAttachmentPreview() {
        if (!attachmentPreviewEl) return;
        attachmentPreviewEl.innerHTML = '';
        if (!attachmentFiles || !attachmentFiles.length) {
            attachmentPreviewEl.classList.add('d-none');
            return;
        }
        attachmentPreviewEl.classList.remove('d-none');

        // Show total count badge
        var countBadge = document.createElement('div');
        countBadge.className = 'w-100 mb-1 d-flex justify-content-end';
        countBadge.innerHTML = '<span class="badge bg-secondary">' + attachmentFiles.length + ' file' + (attachmentFiles.length > 1 ? 's' : '') + '</span>';
        attachmentPreviewEl.appendChild(countBadge);

        attachmentFiles.forEach(function (entry, index) {
            var file = entry.file;
            var item = document.createElement('div');
            item.className = 'position-relative me-2 mb-1 d-inline-block';
            var isImage = file.type && file.type.indexOf('image/') === 0;
            var isPdf = file.name && (file.name.toLowerCase().endsWith('.pdf') || (file.type && file.type === 'application/pdf'));
            if (isImage) {
                var img = document.createElement('img');
                img.className = 'img-thumbnail';
                img.style.maxHeight = '80px';
                img.style.maxWidth = '120px';
                img.style.objectFit = 'contain';
                img.src = URL.createObjectURL(file);
                item.appendChild(img);
            } else if (isPdf) {
                var docWrap = document.createElement('div');
                docWrap.className = 'border rounded px-2 py-2 bg-light d-flex align-items-center gap-2';
                docWrap.style.minWidth = '140px';
                var docIcon = document.createElement('span');
                docIcon.className = 'text-danger';
                docIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>';
                var docName = document.createElement('span');
                docName.className = 'small text-break';
                docName.textContent = file.name;
                docWrap.appendChild(docIcon);
                docWrap.appendChild(docName);
                item.appendChild(docWrap);
            } else {
                var span = document.createElement('span');
                span.className = 'badge bg-secondary';
                span.textContent = file.name;
                item.appendChild(span);
            }
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger position-absolute top-0 end-0 translate-middle p-0 d-flex align-items-center justify-content-center';
            removeBtn.style.width = '18px';
            removeBtn.style.height = '18px';
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                attachmentFiles.splice(index, 1);
                if (attachmentInputEl && window.DataTransfer) {
                    var dt = new DataTransfer();
                    attachmentFiles.forEach(function (entry2) { dt.items.add(entry2.file); });
                    attachmentInputEl.files = dt.files;
                }
                renderAttachmentPreview();
                updateSendDisabled();
            });
            item.appendChild(removeBtn);
            attachmentPreviewEl.appendChild(item);
        });
    }

    if (attachmentInputEl && (attachmentNameEl || attachmentPreviewEl)) {
        attachmentInputEl.addEventListener('change', function () {
            var files = this.files ? Array.from(this.files) : [];

            // If nothing selected, clear everything
            if (!files.length) {
                attachmentFiles = [];
            } else {
                // Add newly selected files on top of existing ones (support multiple OS picks)
                files.forEach(function (f) {
                    var exists = attachmentFiles.some(function (entry) {
                        var ef = entry.file;
                        return ef &&
                            ef.name === f.name &&
                            ef.size === f.size &&
                            ef.lastModified === f.lastModified;
                    });
                    if (!exists) {
                        attachmentFiles.push({ file: f });
                    }
                });
            }

            if (attachmentNameEl) {
                if (attachmentFiles.length === 1) {
                    attachmentNameEl.textContent = attachmentFiles[0].file.name;
                } else if (attachmentFiles.length > 1) {
                    attachmentNameEl.textContent = attachmentFiles.length + ' files selected';
                } else {
                    attachmentNameEl.textContent = '';
                }
            }

            // Sync native input FileList from attachmentFiles, so backend gets all
            if (attachmentInputEl && window.DataTransfer) {
                var dt = new DataTransfer();
                attachmentFiles.forEach(function (entry) { dt.items.add(entry.file); });
                attachmentInputEl.files = dt.files;
            }

            renderAttachmentPreview();
            updateSendDisabled();
        });
    }

    if (replyFormEl) {
    replyFormEl.addEventListener('submit', function(e) {
        e.preventDefault();
        if (typeof waSessionWindowOpen !== 'undefined' && !waSessionWindowOpen) {
            if (typeof toastr !== 'undefined') {
                toastr.warning({!! json_encode(translate('whatsapp_session_window_closed_server')) !!});
            }
            return;
        }
        var phone = document.getElementById('whatsapp-reply-phone').value;
        var body = replyBodyEl.value;
        var hasFilesSubmit = attachmentFiles && attachmentFiles.length > 0;
        if (!phone || (!body && !hasFilesSubmit)) return;

        var replyToEl = document.getElementById('wa-reply-to-wa-id');
        var replyToWaVal = replyToEl && replyToEl.value ? replyToEl.value.trim() : '';
        var replyQuoteBarText = document.getElementById('wa-reply-quote-text');
        var replyQuoteText = replyQuoteBarText ? replyQuoteBarText.textContent : '';
        var safeReplyQuote = String(replyQuoteText || '')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        var optReplyBlock = (replyToWaVal && safeReplyQuote)
            ? ('<div class="fz-11 mb-1 px-2 py-1 rounded-1 border-start border-3 border-light" style="background:rgba(255,255,255,0.15)">' + safeReplyQuote + '</div>')
            : '';

        // Capture files for request and optimistic UI, then clear preview immediately
        var filesToSend = (attachmentFiles && attachmentFiles.length) ? attachmentFiles.slice() : [];
        attachmentFiles = [];
        if (attachmentInputEl) attachmentInputEl.value = '';
        if (attachmentNameEl) attachmentNameEl.textContent = '';
        if (attachmentPreviewEl) {
            attachmentPreviewEl.innerHTML = '';
            attachmentPreviewEl.classList.add('d-none');
        }
        replyBodyEl.value = '';
        if (replyBodyEl.style) replyBodyEl.style.height = '40px';
        updateSendDisabled();

        // Optimistic UI: show message immediately with \"Sending…\" status.
        var panel = document.getElementById('whatsapp-chat-messages');
        var innerPanel = waChatMessagesInner(panel);
        var time = waFormatChatMessageDateTimeNow();
        var safeBody = body.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
        var tempId = 'wa-temp-' + Date.now();
        var statusSpan = null;
        // Optimistic block(s) for attachments + text
        if (filesToSend.length) {
            filesToSend.forEach(function (entry, index) {
                var file = entry.file;
                var wrap = document.createElement('div');
                wrap.className = 'mb-3 d-flex justify-content-end';
                var inner = '<div class=\"rounded px-3 py-2 bg-primary text-white\" style=\"max-width:94%\">' +
                    '<div class=\"fz-12 opacity-75\">OUT · ' + time + ' · <span data-temp-status=\"' + tempId + '\">Sending…</span></div>' +
                    optReplyBlock;
                if (file.type && file.type.indexOf('image/') === 0) {
                    var imgUrl = URL.createObjectURL(file);
                    inner += '<div class=\"mt-1\"><img src=\"' + imgUrl + '\" style=\"max-width:200px; max-height:160px; object-fit:contain;\" /></div>';
                    if (index === 0 && safeBody) {
                        inner += '<div class=\"mt-1\">' + safeBody + '</div>';
                    }
                } else {
                    var isPdfOpt = file.name && (file.name.toLowerCase().endsWith('.pdf') || (file.type && file.type === 'application/pdf'));
                    if (isPdfOpt) {
                        inner += '<div class=\"mt-1 d-flex align-items-center gap-2\"><span class=\"text-danger\"><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" fill=\"currentColor\" viewBox=\"0 0 16 16\"><path d=\"M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z\"/></svg></span><span class=\"opacity-90\">' + (file.name || 'Document').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></div>';
                    } else {
                        inner += '<div class=\"mt-1 opacity-90\">' + (file.name || 'File').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>';
                    }
                    if (index === 0 && safeBody) {
                        inner += '<div class=\"mt-1\">' + safeBody + '</div>';
                    }
                }
                inner += '</div>';
                wrap.innerHTML = inner;
                if (innerPanel) {
                    innerPanel.appendChild(wrap);
                }
                if (!statusSpan) {
                    statusSpan = wrap.querySelector('[data-temp-status=\"' + tempId + '\"]');
                }
            });
        } else {
            var wrapper = document.createElement('div');
            wrapper.className = 'mb-3 d-flex justify-content-end';
            wrapper.innerHTML =
                '<div class=\"rounded px-3 py-2 bg-primary text-white\" style=\"max-width:94%\">' +
                    '<div class=\"fz-12 opacity-75\">OUT · ' + time + ' · <span data-temp-status=\"' + tempId + '\">Sending…</span></div>' +
                    optReplyBlock +
                    '<div class=\"mt-1\">' + safeBody + '</div>' +
                '</div>';
            if (innerPanel) {
                innerPanel.appendChild(wrapper);
            }
            statusSpan = wrapper.querySelector('[data-temp-status=\"' + tempId + '\"]');
        }
        if (panel) {
            panel.scrollTop = panel.scrollHeight;
        }

        var btn = this.querySelector('button[type=\"submit\"]');
        btn.disabled = true;
        var formData = new FormData();
        formData.append('phone', phone);
        formData.append('body', body);
        formData.append('_token', csrf);
        if (replyToWaVal) {
            formData.append('reply_to_wa_message_id', replyToWaVal);
        }
        if (filesToSend.length) {
            filesToSend.forEach(function (entry, idx) {
                formData.append('attachments[' + idx + ']', entry.file);
            });
        }
        fetch(replyUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            replyBodyEl.value = '';
            if (replyBodyEl) {
                replyBodyEl.style.height = '40px';
            }
            if (attachmentInputEl) {
                attachmentInputEl.value = '';
            }
            if (attachmentNameEl) {
                attachmentNameEl.textContent = '';
            }
            attachmentFiles = [];
            if (attachmentPreviewEl) {
                attachmentPreviewEl.innerHTML = '';
                attachmentPreviewEl.classList.add('d-none');
            }
            var sent = !!(res && res.whatsapp_sent);
            var errCode = res && res.whatsapp_error ? String(res.whatsapp_error) : '';
            if (res && res.whatsapp_graph && typeof console !== 'undefined' && console.info) {
                console.info('WhatsApp Graph API response:', res.whatsapp_graph);
            }
            if (statusSpan) {
                statusSpan.textContent = sent ? 'Sent' : 'Failed';
            }
            if (typeof toastr !== 'undefined') {
                if (sent) {
                    toastr.success('Sent');
                    waClearReplyTarget();
                } else if (errCode === 'invalid_phone') {
                    toastr.error({!! json_encode(translate('Invalid_whatsapp_phone')) !!});
                } else if (errCode === 'whatsapp_session_window_closed' && res.messaging_window) {
                    waApplyMessagingWindow({ messaging_window: res.messaging_window, handler: currentHandler });
                    toastr.warning({!! json_encode(translate('whatsapp_session_window_closed_server')) !!});
                } else {
                    toastr.warning('Saved, but WhatsApp API failed');
                }
            }
            if (sent && currentPhone) {
                loadMessages(currentPhone, false);
            }
            updateSendDisabled();
        }.bind(this))
        .catch(function() {
            if (statusSpan) {
                statusSpan.textContent = 'Failed';
            }
            if (typeof toastr !== 'undefined') toastr.error('Failed to send');
            updateSendDisabled();
        });
    });
    }

    (function waBindWabaTemplateComposer() {
        var waTplSel = document.getElementById('wa-waba-template-select');
        var waTplSend = document.getElementById('wa-waba-template-send-btn');
        if (waTplSel && !waTplSel.dataset.waBound) {
            waTplSel.dataset.waBound = '1';
            waTplSel.addEventListener('change', function () {
                waRebuildWabaTemplateFields();
            });
        }
        if (waTplSend && !waTplSend.dataset.waBound && sendTemplateUrl) {
            waTplSend.dataset.waBound = '1';
            waTplSend.addEventListener('click', function () {
                var sel = document.getElementById('wa-waba-template-select');
                var phoneEl = document.getElementById('whatsapp-reply-phone');
                var phone = phoneEl ? phoneEl.value : '';
                var raw = sel && sel.value ? sel.value : '';
                if (!phone || !raw) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning({!! json_encode(translate('whatsapp_session_window_select_template')) !!});
                    }
                    return;
                }
                var parts = String(raw).split('\t');
                var tname = parts[0];
                var tlang = parts.length > 1 ? parts[1] : 'en';
                var tpl = null;
                if (waWabaTemplatesList && waWabaTemplatesList.length) {
                    waWabaTemplatesList.forEach(function (t) {
                        if ((t.name || '') === tname && (t.language || '') === tlang) {
                            tpl = t;
                        }
                    });
                }
                if (!tpl) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning({!! json_encode(translate('whatsapp_session_window_select_template')) !!});
                    }
                    return;
                }
                var htc = tpl ? (parseInt(tpl.header_text_placeholder_count, 10) || 0) : 0;
                var bpc = tpl ? (parseInt(tpl.body_placeholder_count, 10) || 0) : 0;
                var headerTextParams = [];
                var i;
                for (i = 1; i <= htc; i++) {
                    var hi = document.getElementById('wa-waba-hdr-param-' + i);
                    headerTextParams.push(hi ? String(hi.value || '').trim() : '');
                }
                var bodyParams = [];
                for (i = 1; i <= bpc; i++) {
                    var bi = document.getElementById('wa-waba-body-param-' + i);
                    bodyParams.push(bi ? String(bi.value || '').trim() : '');
                }
                var mediaEl = document.getElementById('wa-waba-header-media-url');
                var headerImageUrl = mediaEl ? String(mediaEl.value || '').trim() : '';
                waTplSend.disabled = true;
                fetch(sendTemplateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        phone: phone,
                        template_name: tname,
                        language: tlang,
                        body_parameters: bodyParams,
                        header_text_parameters: headerTextParams,
                        header_image_url: headerImageUrl || null,
                    }),
                })
                    .then(function (r) {
                        return r.json().then(function (j) {
                            return { ok: r.ok, status: r.status, body: j };
                        });
                    })
                    .then(function (pack) {
                        waTplSend.disabled = false;
                        if (pack.ok && pack.body && pack.body.ok) {
                            if (typeof toastr !== 'undefined') {
                                toastr.success(strTplSentOk);
                            }
                            waClearWabaTemplateComposer();
                            if (pack.body.messaging_window) {
                                waApplyMessagingWindow({
                                    messaging_window: pack.body.messaging_window,
                                    handler: currentHandler,
                                });
                            }
                            if (currentPhone) {
                                loadMessages(currentPhone, false);
                            }
                        } else {
                            var errMsg = (pack.body && pack.body.user_message) ? String(pack.body.user_message) : strTplSentFail;
                            if (typeof toastr !== 'undefined') {
                                toastr.error(errMsg);
                            }
                        }
                    })
                    .catch(function () {
                        waTplSend.disabled = false;
                        if (typeof toastr !== 'undefined') {
                            toastr.error(strTplSentFail);
                        }
                    });
            });
        }
    })();
})();
                </script>
                @endpush
            <?php endif; ?>

            {{-- Tab: Provider Leads --}}
            <?php if (($tab ?? '') === 'leads'): ?>
                <div class="card">
                    <?php if (!empty($leadsError ?? null)) { ?>
                        <div class="alert alert-warning m-3 mb-0">{{ translate('Could not load provider leads') }}: {{ $leadsError }}</div>
                    <?php } ?>
                    <div class="table-responsive">
                        <table class="table align-middle table-borderless text-nowrap">
                            <thead class="border-bottom">
                            <tr>
                                <th>{{ translate('Lead ID') }}</th>
                                <th>{{ translate('Phone') }}</th>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('Service') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Created') }}</th>
                                @can('lead_add')
                                    <th class="text-end">{{ translate('Action') }}</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (($leads ?? collect())->isNotEmpty()) { ?>
                                @foreach($leads as $lead)
                                <?php $waLeadRowId = 'wa-s-l-' . md5((string) ($lead->lead_id ?? $lead->id ?? '')); ?>
                                <tr id="{{ $waLeadRowId }}">
                                    <td>{{ $lead->lead_id ?? $lead->id ?? '—' }}</td>
                                    <td>{{ $displayPhone($lead->phone ?? null) }}</td>
                                    <td>{{ $lead->name ?? '—' }}</td>
                                    <td>{{ $lead->service ?? '—' }}</td>
                                    <td>{{ $lead->status ?? '—' }}</td>
                                    <td>{{ $lead->created_at?->format('M j, H:i') ?? '—' }}</td>
                                    @can('lead_add')
                                        <td class="text-end text-wrap">
                                            @if(!empty($lead->lead_id))
                                                <a href="{{ route('admin.lead.create-from-whatsapp-provider', ['lead_id' => $lead->lead_id]) }}"
                                                   class="btn btn-sm btn--primary">{{ translate('WhatsApp_prefill_provider_lead') }}</a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                                @endforeach
                            <?php } else { ?>
                                <tr>
                                    <td colspan="{{ auth()->user()->can('lead_add') ? 7 : 6 }}" class="text-center py-5 text-muted">{{ translate('No provider leads') }}</td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (isset($leads) && $leads->hasPages()) { ?>
                        <div class="card-footer border-0">{{ $leads->links() }}</div>
                    <?php } ?>
                </div>
            <?php endif; ?>

            {{-- Tab: Bookings --}}
            <?php if (($tab ?? '') === 'bookings'): ?>
                <div class="card">
                    <?php if (!empty($bookingsError ?? null)) { ?>
                        <div class="alert alert-warning m-3 mb-0">{{ translate('Could not load bookings') }}: {{ $bookingsError }}</div>
                    <?php } ?>
                    <div class="table-responsive overflow-auto">
                        <table class="table align-middle table-borderless text-nowrap" style="min-width: 1200px;">
                            <thead class="border-bottom">
                            <tr>
                                <th>{{ translate('Booking ID') }}</th>
                                <th>{{ translate('Phone') }}</th>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('Service') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Created') }}</th>
                                @can('booking_view')
                                    <th class="text-end">{{ translate('Action') }}</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (($bookings ?? collect())->isNotEmpty()) { ?>
                                @foreach($bookings as $booking)
                                <tr id="wa-s-b-{{ (int) ($booking->id ?? 0) }}">
                                    <td>{{ $booking->booking_id ?? $booking->id ?? '—' }}</td>
                                    <td>{{ $displayPhone($booking->phone ?? null) }}</td>
                                    <td>{{ $booking->name ?? '—' }}</td>
                                    <td>{{ $booking->service ?? '—' }}</td>
                                    <td class="text-wrap" style="max-width: 16rem;">
                                        <span>{{ $booking->status ?? '—' }}</span>
                                        @if(($booking->status ?? '') === \Modules\WhatsAppModule\Entities\WhatsAppBooking::STATUS_CANCELLED && !empty($booking->cancellation_reason))
                                            <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit((string) $booking->cancellation_reason, 200) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $booking->created_at?->format('M j, H:i') ?? '—' }}</td>
                                    @can('booking_view')
                                        <td class="text-end text-wrap">
                                            @if(!empty($booking->system_booking_id))
                                                <a href="{{ route('admin.booking.details', ['id' => $booking->system_booking_id]) }}"
                                                   class="btn btn-sm btn--primary">{{ translate('WhatsApp_open_system_booking') }}</a>
                                            @elseif(($booking->status ?? '') === \Modules\WhatsAppModule\Entities\WhatsAppBooking::STATUS_CANCELLED)
                                                <span class="text-muted small">—</span>
                                            @elseif(!empty($booking->booking_id))
                                                <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                                                    <a href="{{ route('admin.booking.create-from-whatsapp-booking', ['booking_id' => $booking->booking_id]) }}"
                                                       class="btn btn-sm btn--primary">{{ translate('WhatsApp_create_booking_from_chat') }}</a>
                                                    @can('booking_add')
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger wa-open-cancel-wa-booking"
                                                                data-booking-id="{{ e($booking->booking_id) }}"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#waCancelWhatsappBookingModal">
                                                            {{ translate('WhatsApp_cancel_whatsapp_booking') }}
                                                        </button>
                                                    @endcan
                                                </div>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                                @endforeach
                            <?php } else { ?>
                                <tr>
                                    <td colspan="{{ auth()->user()->can('booking_view') ? 7 : 6 }}" class="text-center py-5 text-muted">{{ translate('No bookings') }}</td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (isset($bookings) && $bookings->hasPages()) { ?>
                        <div class="card-footer border-0">{{ $bookings->links() }}</div>
                    <?php } ?>
                </div>

                @can('booking_add')
                    <div class="modal fade" id="waCancelWhatsappBookingModal" tabindex="-1" aria-labelledby="waCancelWhatsappBookingModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" action="{{ route('admin.whatsapp.conversations.bookings.cancel', ['channel' => request()->route('channel') ?? 'whatsapp']) }}" id="waCancelWhatsappBookingForm">
                                    @csrf
                                    <input type="hidden" name="booking_id" id="waCancelWhatsappBookingId" value="">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="waCancelWhatsappBookingModalLabel">{{ translate('WhatsApp_cancel_whatsapp_booking_modal_title') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted small mb-3">{{ translate('WhatsApp_cancel_whatsapp_booking_modal_body') }}</p>
                                        <label class="form-label" for="waCancelWhatsappBookingReason">{{ translate('WhatsApp_cancellation_reason') }}</label>
                                        <textarea class="form-control"
                                                  id="waCancelWhatsappBookingReason"
                                                  name="cancellation_reason"
                                                  rows="4"
                                                  required
                                                  minlength="3"
                                                  maxlength="2000"
                                                  placeholder="{{ translate('WhatsApp_cancellation_reason_placeholder') }}"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                        <button type="submit" class="btn btn-danger">{{ translate('WhatsApp_confirm_cancel_booking') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @push('script')
                    <script>
                    (function () {
                        var modalEl = document.getElementById('waCancelWhatsappBookingModal');
                        if (!modalEl) return;
                        var form = document.getElementById('waCancelWhatsappBookingForm');
                        var idInput = document.getElementById('waCancelWhatsappBookingId');
                        var reasonEl = document.getElementById('waCancelWhatsappBookingReason');
                        modalEl.addEventListener('show.bs.modal', function (ev) {
                            var btn = ev.relatedTarget;
                            if (btn && btn.getAttribute('data-booking-id') && idInput) {
                                idInput.value = btn.getAttribute('data-booking-id');
                            }
                        });
                        modalEl.addEventListener('hidden.bs.modal', function () {
                            if (form) {
                                form.reset();
                            }
                            if (idInput) {
                                idInput.value = '';
                            }
                        });
                    })();
                    </script>
                    @endpush
                @endcan
            <?php endif; ?>

            {{-- Tab: WhatsApp Users (Neon DB only, separate from main app users) --}}
            <?php if (($tab ?? '') === 'users'): ?>
                <div class="card">
                    <?php if (!empty($usersError ?? null)) { ?>
                        <div class="alert alert-warning m-3 mb-0">{{ translate('Could not load WhatsApp users') }}: {{ $usersError }}</div>
                    <?php } ?>
                    <div class="table-responsive overflow-auto">
                        <table class="table align-middle table-borderless text-nowrap" style="min-width: 1200px;">
                            <thead class="border-bottom">
                            <tr>
                                <th>{{ translate('Phone') }}</th>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('Email') }}</th>
                                <th>{{ translate('In system') }}</th>
                                <th>{{ translate('Leads') }}</th>
                                <th>{{ translate('Alternate phone') }}</th>
                                <th>{{ translate('Address') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Created') }}</th>
                                <th class="text-end">{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (($users ?? collect())->isNotEmpty()) { ?>
                                @foreach($users as $waUser)
                                <tr id="wa-s-u-{{ (int) ($waUser->id ?? 0) }}">
                                    <td>{{ $displayPhone($waUser->phone ?? null) }}</td>
                                    <td>{{ $waUser->name ?? '—' }}</td>
                                    <td class="text-break">{{ $waUser->email ?? '—' }}</td>
                                    <td class="align-middle">
                                        @include('whatsappmodule::admin.conversations.partials.system-link-pills', [
                                            'systemLink' => $waUser->system_link ?? [],
                                            'onUnread' => false,
                                            'showNames' => false,
                                        ])
                                    </td>
                                    <td>
                                        <?php $leadCount = (int) ($waUser->lead_count ?? 0); ?>
                                        @if($leadCount > 0)
                                            <span class="badge bg-success">
                                                {{ $leadCount }} {{ $leadCount === 1 ? translate('lead') : translate('leads') }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $waUser->alternate_phone ?? '—' }}</td>
                                    <td>{{ $waUser->address ?? '—' }}</td>
                                    <td><span class="badge bg-secondary">{{ $waUser->type ?? '—' }}</span></td>
                                    <td>{{ $waUser->created_at?->format('M j, H:i') ?? '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.whatsapp.conversations.index', ['channel' => request()->route('channel') ?? 'whatsapp', 'tab' => 'chats', 'phone' => $waUser->phone]) }}" class="btn btn-sm btn--primary">{{ translate('View chat') }}</a>
                                        <button type="button"
                                                class="btn btn-sm btn-success wa-user-leads text-white"
                                                data-phone="{{ e($waUser->phone) }}">
                                            {{ translate('View leads') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn--secondary wa-user-more"
                                                data-phone="{{ e($waUser->phone) }}">
                                            {{ translate('View more') }}
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            <?php } else { ?>
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">{{ translate('No WhatsApp users') }}</td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (isset($users) && $users->hasPages()) { ?>
                        <div class="card-footer border-0">{{ $users->links() }}</div>
                    <?php } ?>
                </div>

                {{-- Modal: View more (full user details + bookings) --}}
                <div class="modal fade" id="waUserDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ translate('User details') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="waUserDetailsBody">
                                <div class="text-center py-4 text-muted">{{ translate('Loading…') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="waUserLeadsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ translate('User leads') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="waUserLeadsBody">
                                <div class="text-center py-4 text-muted">{{ translate('Loading…') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
@push('script')
                <script>
(function() {
    var modal = document.getElementById('waUserDetailsModal');
    var body = document.getElementById('waUserDetailsBody');
    var leadsModal = document.getElementById('waUserLeadsModal');
    var leadsBody = document.getElementById('waUserLeadsBody');
    var detailsUrl = '{{ route("admin.whatsapp.users.details", ['channel' => request()->route('channel') ?? 'whatsapp']) }}';
    var strWaCustomerM = {!! json_encode(translate('whatsapp_system_customer')) !!};
    var strWaProviderM = {!! json_encode(translate('whatsapp_system_provider')) !!};
    var strWaNoneM = {!! json_encode(translate('whatsapp_not_in_system')) !!};

    function formatPhoneDisplay(phone) {
        var digits = String(phone || '').replace(/\D+/g, '');
        if (!digits) return '—';
        return digits.length > 10 ? digits.slice(-10) : digits;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderSystemLinkFromApi(link) {
        link = link || {};
        var cust = link.customer;
        var prov = link.provider;
        var parts = [];
        if (cust && cust.url) {
            parts.push('<a href="' + escapeHtml(cust.url) + '" target="_blank" rel="noopener" class="wa-sys-pill wa-sys-pill--customer text-decoration-none">' + escapeHtml(strWaCustomerM) + '</a>');
        }
        if (cust && cust.url && prov && prov.url) {
            parts.push('<span class="wa-sys-pill-sep text-muted px-0">|</span>');
        }
        if (prov && prov.url) {
            parts.push('<a href="' + escapeHtml(prov.url) + '" target="_blank" rel="noopener" class="wa-sys-pill wa-sys-pill--provider text-decoration-none">' + escapeHtml(strWaProviderM) + '</a>');
        }
        if (!parts.length) {
            parts.push('<span class="wa-sys-pill wa-sys-pill--none">' + escapeHtml(strWaNoneM) + '</span>');
        }
        return '<div class="wa-sys-pills d-flex align-items-center gap-1 flex-wrap">' + parts.join('') + '</div>';
    }

    function renderLeadsTable(leads) {
        if (!leads || !leads.length) {
            return '<p class="text-muted mb-0">No leads found</p>';
        }

        function leadTypeMeta(type) {
            var normalized = String(type || '').toLowerCase();
            if (normalized === 'invalid') {
                return { label: 'Invalid', badgeClass: 'bg-danger text-white' };
            }
            if (normalized === 'customer') {
                return { label: 'Customer', badgeClass: 'bg-success text-white' };
            }
            if (normalized === 'provider') {
                return { label: 'Provider', badgeClass: 'bg-primary text-white' };
            }
            if (normalized === 'future_customer') {
                return { label: 'Future Customer', badgeClass: 'bg-info text-dark' };
            }
            if (normalized === 'unknown') {
                return { label: 'Unknown', badgeClass: 'bg-warning text-dark' };
            }
            var fallbackLabel = String(type || '—').replace(/_/g, ' ');
            return { label: fallbackLabel, badgeClass: 'bg-warning text-dark' };
        }

        var html = '<div class="table-responsive"><table class="table table-sm align-middle text-nowrap">';
        html += '<thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Type</th><th>Status</th><th>Received</th><th class="text-end">Action</th></tr></thead><tbody>';
        leads.forEach(function (lead) {
            var typeMeta = leadTypeMeta(lead.lead_type);
            html += '<tr>';
            html += '<td>#' + escapeHtml(lead.id) + '</td>';
            html += '<td>' + escapeHtml(lead.name || '—') + '</td>';
            html += '<td>' + formatPhoneDisplay(lead.phone_number) + '</td>';
            html += '<td><span class="badge rounded-pill ' + typeMeta.badgeClass + '">' + escapeHtml(typeMeta.label) + '</span></td>';
            var isOpen = !!lead.is_open;
            var statusLabel = isOpen ? 'Open' : 'Closed';
            var statusClass = isOpen ? 'bg-danger' : 'bg-success';
            html += '<td><span class="badge rounded-pill ' + statusClass + '">' + statusLabel + '</span></td>';
            html += '<td>' + escapeHtml(lead.received_at || '—') + '</td>';
            html += '<td class="text-end"><a href="' + escapeHtml(lead.url || '#') + '" target="_blank" class="btn btn-sm btn-outline-success">View lead</a></td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        return html;
    }

    function fetchUserDetails(phone) {
        return fetch(detailsUrl + '?phone=' + encodeURIComponent(phone), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.json().then(function (json) {
                return { ok: r.ok, data: json };
            });
        });
    }

    if (modal && body) {
        document.querySelectorAll('.wa-user-more').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var phone = this.getAttribute('data-phone');
                if (!phone) return;
                body.innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
                var bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                fetchUserDetails(phone)
                    .then(function(result) {
                        var data = result.data || {};
                        if (data.error) {
                            body.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed to load') + '</div>';
                            return;
                        }
                        var u = data.user || {};
                        var type = (u.type || '').toUpperCase();
                        var html = '<div class="row mb-3">';
                        html += '<div class="col-md-6"><strong>Phone</strong><br>' + formatPhoneDisplay(u.phone) + '</div>';
                        html += '<div class="col-md-6"><strong>Name</strong><br>' + (u.name || '—') + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>{{ translate('Email') }}</strong><br>' + (u.email || '—') + '</div>';
                        html += '<div class="col-12 mt-2"><strong>{{ translate('In system') }}</strong><br>' + renderSystemLinkFromApi(data.system_link) + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>Alternate phone</strong><br>' + (u.alternate_phone || '—') + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>Type</strong><br><span class="badge bg-secondary">' + (u.type || '—') + '</span></div>';
                        html += '<div class="col-12 mt-2"><strong>Address</strong><br>' + (u.address || '—') + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>Created</strong><br>' + (u.created_at || '—') + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>Updated</strong><br>' + (u.updated_at || '—') + '</div>';
                        if (data.leads && data.leads.length > 1) {
                            html += '<div class="col-12 mt-2"><strong>Leads</strong><br><span class="badge bg-warning text-dark">' + data.leads.length + ' linked leads</span></div>';
                        } else if (data.lead && data.lead.id) {
                            html += '<div class="col-md-6 mt-2"><strong>Lead</strong><br><a href="' + data.lead.url + '" target="_blank" class="badge bg-success text-decoration-none">#' + data.lead.id + '</a></div>';
                            html += '<div class="col-md-6 mt-2"><strong>Lead type</strong><br><span class="badge bg-info text-dark">' + (data.lead.lead_type || '—') + '</span></div>';
                        } else {
                            html += '<div class="col-12 mt-2"><strong>Lead</strong><br><span class="text-muted">—</span></div>';
                        }
                        html += '</div>';
                        html += '<hr><h6>' + (type === 'CUSTOMER' ? 'Bookings' : 'Bookings') + '</h6>';
                        if ((data.bookings || []).length === 0) {
                            html += '<p class="text-muted">No bookings</p>';
                        } else {
                            html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Booking</th><th>Service</th><th>Status</th><th>Created</th></tr></thead><tbody>';
                            data.bookings.forEach(function(b) {
                                html += '<tr><td>' + (b.booking_id || b.id || '—') + '</td><td>' + (b.service || '—') + '</td><td>' + (b.status || '—') + '</td><td>' + (b.created_at || '—') + '</td></tr>';
                            });
                            html += '</tbody></table></div>';
                        }
                        body.innerHTML = html;
                    })
                    .catch(function() {
                        body.innerHTML = '<div class="alert alert-danger">Failed to load details</div>';
                    });
            });
        });
    }

    if (leadsModal && leadsBody) {
        document.querySelectorAll('.wa-user-leads').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var phone = this.getAttribute('data-phone');
                if (!phone) return;
                leadsBody.innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
                var bsLeadsModal = new bootstrap.Modal(leadsModal);
                bsLeadsModal.show();
                fetchUserDetails(phone)
                    .then(function(result) {
                        var data = result.data || {};
                        if (data.error) {
                            leadsBody.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed to load') + '</div>';
                            return;
                        }
                        leadsBody.innerHTML = renderLeadsTable(data.leads || []);
                    })
                    .catch(function() {
                        leadsBody.innerHTML = '<div class="alert alert-danger">Failed to load leads</div>';
                    });
            });
        });
    }
})();
                </script>
                @endpush
            <?php endif; ?>

            <?php if (($tab ?? '') === 'quick_replies'): ?>
                @include('whatsappmodule::admin.conversations.partials.quick-replies')
            <?php endif; ?>

            <?php if (($tab ?? '') === 'chat_config'): ?>
                @include('whatsappmodule::admin.conversations.partials.chat-configuration')
            <?php endif; ?>
        </div>
    </div>

    @if(in_array($tab ?? '', $waSearchableTabs ?? [], true))
        @php
            $waNavTabsForSearch = [
                ['key' => 'chats', 'label' => (string) translate('Active Chats'), 'aliases' => ['chat', 'active']],
                ['key' => 'human_support', 'label' => (string) translate('Human support'), 'aliases' => ['human', 'support', 'agent']],
                ['key' => 'leads', 'label' => (string) translate('Provider Leads'), 'aliases' => ['lead', 'provider']],
                ['key' => 'bookings', 'label' => (string) translate('Bookings'), 'aliases' => ['booking', 'order']],
                ['key' => 'users', 'label' => (string) translate('WhatsApp Users'), 'aliases' => ['user', 'whatsapp']],
            ];
            if (auth()->check() && auth()->user()->can('whatsapp_message_template_update')) {
                $waNavTabsForSearch[] = ['key' => 'quick_replies', 'label' => (string) translate('WhatsApp_quick_replies_tab'), 'aliases' => ['quick', 'reply', 'template']];
                $waNavTabsForSearch[] = ['key' => 'chat_config', 'label' => (string) translate('whatsapp_chat_configuration'), 'aliases' => ['config', 'configuration', 'status', 'tag']];
            }
        @endphp
        @push('script')
            <script>
                (function () {
                    var waConvBaseUrl = @json(route('admin.whatsapp.conversations.index', ['channel' => request()->route('channel') ?? 'whatsapp']));
                    var waFullscreenKeep = @json(!empty($fullscreen));
                    var searchUrl = @json(route('admin.whatsapp.conversations.search', ['channel' => request()->route('channel') ?? 'whatsapp']));
                    var waNavTabsForSearch = @json($waNavTabsForSearch);
                    var strPages = {!! json_encode(translate('Pages')) !!};
                    var strOpenTabHint = {!! json_encode(translate('Open')) !!};
                    var strChats = {!! json_encode(translate('Chats')) !!};
                    var strMessages = {!! json_encode(translate('Messages')) !!};
                    var strProviderLeads = {!! json_encode(translate('Provider Leads')) !!};
                    var strBookings = {!! json_encode(translate('Bookings')) !!};
                    var strWaUsers = {!! json_encode(translate('WhatsApp Users')) !!};
                    var strQuickReplies = {!! json_encode(translate('WhatsApp_quick_replies_tab')) !!};
                    var strChatConfig = {!! json_encode(translate('whatsapp_chat_configuration')) !!};
                    var strNoResults = {!! json_encode(translate('No results')) !!};

                    function debounce(fn, ms) {
                        var t;
                        return function () {
                            var a = arguments;
                            var ctx = this;
                            clearTimeout(t);
                            t = setTimeout(function () {
                                fn.apply(ctx, a);
                            }, ms);
                        };
                    }

                    function escapeHtml(value) {
                        return String(value || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    }

                    function formatPhoneDisplay(phone) {
                        var digits = String(phone || '').replace(/\D+/g, '');
                        if (!digits) {
                            return '—';
                        }
                        return digits.length > 10 ? digits.slice(-10) : digits;
                    }

                    function waBuildConvUrl(tab, queryObj) {
                        var u = new URL(waConvBaseUrl, window.location.origin);
                        u.search = '';
                        u.searchParams.set('tab', tab || 'chats');
                        if (waFullscreenKeep) {
                            u.searchParams.set('fullscreen', '1');
                        }
                        queryObj = queryObj || {};
                        Object.keys(queryObj).forEach(function (k) {
                            var v = queryObj[k];
                            if (v != null && String(v).trim() !== '') {
                                u.searchParams.set(k, String(v));
                            }
                        });
                        return u.pathname + u.search;
                    }

                    window.waHideGlobalSearchDropdown = function () {
                        var dd = document.getElementById('wa-global-search-dropdown');
                        if (dd) {
                            dd.style.display = 'none';
                            dd.innerHTML = '';
                        }
                    };

                    function appendSection(html, title, items, renderRow) {
                        if (!items || !items.length) {
                            return html;
                        }
                        html += '<div class="list-group-item text-uppercase fz-11 text-muted border-0 py-1">' + escapeHtml(title) + '</div>';
                        items.forEach(function (item) {
                            html += renderRow(item);
                        });
                        return html;
                    }

                    function waMatchingNavTabs(needle) {
                        needle = String(needle || '').trim().toLowerCase();
                        if (needle.length < 2) {
                            return [];
                        }
                        return waNavTabsForSearch.filter(function (t) {
                            var parts = [t.label, t.key, ((t.aliases || []).join(' '))].join(' ').toLowerCase();
                            return parts.indexOf(needle) !== -1;
                        });
                    }

                    function renderGlobalSearchResults(data, searchQuery) {
                        var dd = document.getElementById('wa-global-search-dropdown');
                        if (!dd) {
                            return;
                        }
                        data = data || {};
                        var tabHits = waMatchingNavTabs(searchQuery);
                        var chats = data.chats || [];
                        var messages = data.messages || [];
                        var leads = data.leads || [];
                        var bookings = data.bookings || [];
                        var users = data.users || [];
                        var quickReplies = data.quick_replies || [];
                        var chatConfig = data.chat_config || [];
                        var total =
                            tabHits.length +
                            chats.length +
                            messages.length +
                            leads.length +
                            bookings.length +
                            users.length +
                            quickReplies.length +
                            chatConfig.length;
                        if (!total) {
                            dd.innerHTML = '<div class="list-group-item text-muted small">' + escapeHtml(strNoResults) + '</div>';
                            dd.style.display = 'block';
                            return;
                        }
                        var html = '';
                        html = appendSection(html, strPages, tabHits, function (t) {
                            var href = waBuildConvUrl(t.key);
                            return (
                                '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-hit-kind="link" data-href="' +
                                escapeHtml(href) +
                                '">' +
                                '<div class="fw-medium text-truncate">' +
                                escapeHtml(t.label || t.key) +
                                '</div>' +
                                '<div class="small text-muted text-truncate">' +
                                escapeHtml(strOpenTabHint) +
                                '</div></button>'
                            );
                        });
                        html = appendSection(html, strChats, chats, function (c) {
                            var label =
                                c.display_line && String(c.display_line).trim()
                                    ? c.display_line
                                    : (c.name || '').trim()
                                        ? c.name + ' · ' + formatPhoneDisplay(c.phone)
                                        : formatPhoneDisplay(c.phone);
                            var prev = escapeHtml(c.preview || '');
                            var ph = escapeHtml(c.phone || '');
                            return (
                                '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-hit-kind="chat" data-phone="' +
                                ph +
                                '">' +
                                '<div class="fw-medium text-truncate">' +
                                escapeHtml(label) +
                                '</div>' +
                                '<div class="small text-muted text-truncate">' +
                                prev +
                                '</div></button>'
                            );
                        });
                        html = appendSection(html, strMessages, messages, function (m) {
                            var who =
                                (m.name || '').trim() ? m.name + ' · ' + formatPhoneDisplay(m.phone) : formatPhoneDisplay(m.phone);
                            var snip = escapeHtml(m.snippet || '');
                            var ph = escapeHtml(m.phone || '');
                            var mid = m.id != null && m.id !== '' ? String(m.id) : '';
                            return (
                                '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-hit-kind="message" data-phone="' +
                                ph +
                                '" data-message-id="' +
                                mid.replace(/"/g, '') +
                                '">' +
                                '<div class="fw-medium text-truncate">' +
                                escapeHtml(who) +
                                '</div>' +
                                '<div class="small text-muted text-truncate">' +
                                snip +
                                '</div></button>'
                            );
                        });
                        html = appendSection(html, strProviderLeads, leads, function (l) {
                            var line =
                                (l.name || '').trim() ? l.name + ' · ' + formatPhoneDisplay(l.phone) : formatPhoneDisplay(l.phone);
                            var href = l.row_anchor ? waBuildConvUrl('leads') + '#' + l.row_anchor : waBuildConvUrl('leads');
                            return (
                                '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-hit-kind="link" data-href="' +
                                escapeHtml(href) +
                                '">' +
                                '<div class="fw-medium text-truncate">' +
                                escapeHtml(line) +
                                '</div>' +
                                '<div class="small text-muted text-truncate">' +
                                escapeHtml(l.snippet || '') +
                                '</div></button>'
                            );
                        });
                        html = appendSection(html, strBookings, bookings, function (b) {
                            var line =
                                'Booking ' +
                                (b.booking_id || b.id || '') +
                                ' · ' +
                                ((b.name || '').trim() ? b.name : formatPhoneDisplay(b.phone));
                            var href = b.row_anchor ? waBuildConvUrl('bookings') + '#' + b.row_anchor : waBuildConvUrl('bookings');
                            return (
                                '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-hit-kind="link" data-href="' +
                                escapeHtml(href) +
                                '">' +
                                '<div class="fw-medium text-truncate">' +
                                escapeHtml(line) +
                                '</div>' +
                                '<div class="small text-muted text-truncate">' +
                                escapeHtml(b.snippet || '') +
                                '</div></button>'
                            );
                        });
                        html = appendSection(html, strWaUsers, users, function (u) {
                            var line = (u.name || '').trim() ? u.name + ' · ' + formatPhoneDisplay(u.phone) : formatPhoneDisplay(u.phone);
                            var href = u.row_anchor ? waBuildConvUrl('users') + '#' + u.row_anchor : waBuildConvUrl('users');
                            return (
                                '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-hit-kind="link" data-href="' +
                                escapeHtml(href) +
                                '">' +
                                '<div class="fw-medium text-truncate">' +
                                escapeHtml(line) +
                                '</div>' +
                                '<div class="small text-muted text-truncate">' +
                                escapeHtml(u.snippet || '') +
                                '</div></button>'
                            );
                        });
                        html = appendSection(html, strQuickReplies, quickReplies, function (t) {
                            var href = t.row_anchor ? waBuildConvUrl('quick_replies') + '#' + t.row_anchor : waBuildConvUrl('quick_replies');
                            return (
                                '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-hit-kind="link" data-href="' +
                                escapeHtml(href) +
                                '">' +
                                '<div class="fw-medium text-truncate">' +
                                escapeHtml(t.title || '') +
                                '</div>' +
                                '<div class="small text-muted text-truncate">' +
                                escapeHtml(t.snippet || '') +
                                '</div></button>'
                            );
                        });
                        html = appendSection(html, strChatConfig, chatConfig, function (c) {
                            var href = c.row_anchor ? waBuildConvUrl('chat_config') + '#' + c.row_anchor : waBuildConvUrl('chat_config');
                            var sub = c.detail || '';
                            return (
                                '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-hit-kind="link" data-href="' +
                                escapeHtml(href) +
                                '">' +
                                '<div class="fw-medium text-truncate">' +
                                escapeHtml(c.name || '') +
                                '</div>' +
                                '<div class="small text-muted text-truncate">' +
                                escapeHtml(sub) +
                                '</div></button>'
                            );
                        });
                        dd.innerHTML = html;
                        dd.style.display = 'block';
                        dd.querySelectorAll('.wa-global-hit').forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                var kind = this.getAttribute('data-hit-kind');
                                var ph = this.getAttribute('data-phone');
                                var mid = this.getAttribute('data-message-id');
                                var href = this.getAttribute('data-href');
                                window.waHideGlobalSearchDropdown();
                                var gInp = document.getElementById('wa-global-search');
                                if (gInp) {
                                    gInp.value = '';
                                }
                                if (kind === 'chat' || kind === 'message') {
                                    if (typeof window.waOpenChatFromSearch === 'function') {
                                        window.waOpenChatFromSearch(ph, mid || '');
                                    } else {
                                        var q = { phone: ph };
                                        if (mid) {
                                            q.focus_message_id = mid;
                                        }
                                        window.location.href = waBuildConvUrl('chats', q);
                                    }
                                    return;
                                }
                                if (href) {
                                    window.location.href = href;
                                }
                            });
                        });
                    }

                    var debouncedGlobalSearch = debounce(function () {
                        var inp = document.getElementById('wa-global-search');
                        if (!inp) {
                            return;
                        }
                        var q = inp.value.trim();
                        if (q.length < 2) {
                            window.waHideGlobalSearchDropdown();
                            return;
                        }
                        if (waMatchingNavTabs(q).length) {
                            renderGlobalSearchResults(
                                {
                                    chats: [],
                                    messages: [],
                                    leads: [],
                                    bookings: [],
                                    users: [],
                                    quick_replies: [],
                                    chat_config: [],
                                },
                                q
                            );
                        }
                        fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        })
                            .then(function (r) {
                                return r.json();
                            })
                            .then(function (data) {
                                renderGlobalSearchResults(data, q);
                            })
                            .catch(function () {
                                try {
                                    renderGlobalSearchResults(
                                        {
                                            chats: [],
                                            messages: [],
                                            leads: [],
                                            bookings: [],
                                            users: [],
                                            quick_replies: [],
                                            chat_config: [],
                                        },
                                        q
                                    );
                                } catch (err) {
                                    window.waHideGlobalSearchDropdown();
                                }
                            });
                    }, 350);

                    document.addEventListener('input', function (e) {
                        if (e.target && e.target.id === 'wa-global-search') {
                            debouncedGlobalSearch();
                        }
                    });
                    document.addEventListener('click', function (e) {
                        if (e.target.closest('#wa-global-search') || e.target.closest('#wa-global-search-dropdown')) {
                            return;
                        }
                        window.waHideGlobalSearchDropdown();
                    });

                    function waScrollHashTarget() {
                        var h = window.location.hash;
                        if (!h || h.length < 2) {
                            return;
                        }
                        var id = decodeURIComponent(h.slice(1));
                        if (!/^[-_a-zA-Z0-9]+$/.test(id)) {
                            return;
                        }
                        var el = document.getElementById(id);
                        if (el) {
                            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            el.classList.add('table-warning');
                            setTimeout(function () {
                                el.classList.remove('table-warning');
                            }, 2000);
                        }
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function () {
                            requestAnimationFrame(waScrollHashTarget);
                        });
                    } else {
                        requestAnimationFrame(waScrollHashTarget);
                    }
                })();
            </script>
        @endpush
    @endif
@endsection
