@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/select2/select2.min.css') }}"/>
    <?php if (in_array(($tab ?? ''), ['chats', 'human_support', 'users'], true)): ?>
        <style>
            .wa-msg-selected > .wa-msg-bubble {
                outline: 3px solid var(--bs-warning, #ffc107);
                outline-offset: 3px;
                box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.06);
            }
            .whatsapp-active-list-container .card-header {
                min-width: 0;
            }
            .whatsapp-active-list-container #chat-handler-filter {
                max-width: 100%;
                min-width: 0;
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
            .wa-chat-main-panel #whatsapp-chat-messages {
                max-height: min(60vh, 720px);
            }
            .wa-chat-column {
                min-width: 300px;
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
        </style>
    <?php endif; ?>
@endpush

@section('content')
    @php
        // UI only: show last 10 digits. Full number stays in data-phone / hidden input for send + API.
        $displayPhone = function ($phone) {
            $digits = preg_replace('/\D+/', '', (string) $phone);
            if (!$digits) {
                return '—';
            }
            return strlen($digits) > 10 ? substr($digits, -10) : $digits;
        };
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title d-flex gap-3 align-items-center">
                    <span class="material-icons">chat</span>
                    {{ translate('WhatsApp') }}
                </h2>
            </div>

            <div class="card card-body mb-3">
                <ul class="nav nav--tabs">
                    <li class="nav-item">
                        <a class="nav-link {{ ($tab ?? '') === 'chats' ? 'active' : '' }}"
                           href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'chats']) }}">
                            {{ translate('Active Chats') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($tab ?? '') === 'human_support' ? 'active' : '' }}"
                           href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'human_support']) }}">
                            {{ translate('Human support') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($tab ?? '') === 'leads' ? 'active' : '' }}"
                           href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'leads']) }}">
                            {{ translate('Provider Leads') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($tab ?? '') === 'bookings' ? 'active' : '' }}"
                           href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'bookings']) }}">
                            {{ translate('Bookings') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($tab ?? '') === 'users' ? 'active' : '' }}"
                           href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'users']) }}">
                            {{ translate('WhatsApp Users') }}
                        </a>
                    </li>
                    @can('whatsapp_message_template_update')
                        <li class="nav-item">
                            <a class="nav-link {{ ($tab ?? '') === 'chat_config' ? 'active' : '' }}"
                               href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'chat_config']) }}">
                                {{ translate('whatsapp_chat_configuration') }}
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>

            {{-- Tab: Active Chats / Human support — left: scrollable list, right: open chat --}}
            <?php if (($tab ?? '') === 'chats' || ($tab ?? '') === 'human_support'): ?>
                <div class="card card-body mb-3 py-3">
                    <label for="wa-global-search" class="form-label mb-1">{{ translate('Search here') }}</label>
                    <div class="position-relative">
                        <input type="search"
                               id="wa-global-search"
                               class="form-control"
                               placeholder="{{ translate('Search name, number, or message') }}…"
                               autocomplete="off"
                               aria-autocomplete="list"
                               aria-controls="wa-global-search-dropdown">
                        <div id="wa-global-search-dropdown"
                             class="list-group position-absolute w-100 shadow-sm mt-1 rounded border bg-white"
                             style="z-index: 25; max-height: 340px; overflow-y: auto; display: none;"
                             role="listbox"></div>
                    </div>
                </div>
                <input type="hidden" id="wa-initial-open-phone" value="{{ e(request()->query('phone', '')) }}">
                <div class="row g-3">
                    <div class="col-12 col-md-4 col-lg-3 col-xl-4 whatsapp-active-list-container">
                        <div class="card h-100 d-flex flex-column">
                            <div class="card-header py-2 d-flex align-items-center gap-2 min-w-0">
                                <strong class="flex-shrink-0">{{ !empty($humanSupportTab ?? false) ? translate('Human support requests') : translate('Chats') }}</strong>
                                @php($handlerFilter = $handlerFilter ?? 'all')
                                <?php if (empty($humanSupportTab ?? false) && !empty($chatHandlers ?? null)) { ?>
                                    <div class="flex-grow-1 min-w-0">
                                        <select id="chat-handler-filter"
                                                class="form-select form-select-sm w-100"
                                                style="padding-right: 1.75rem;"
                                                onchange="if(this.value){ window.location.href = this.value; }">
                                            @foreach($chatHandlers as $h)
                                                <option value="{{ route('admin.whatsapp.conversations.index', ['tab' => ($tab ?? 'chats'), 'handler' => $h['key']]) }}"
                                                    {{ $handlerFilter === $h['key'] ? 'selected' : '' }}>
                                                    {{ $h['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="card-body p-0 overflow-auto flex-grow-1" style="max-height: 65vh;">
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
                    <div class="col-12 col-md-8 col-lg-9 col-xl-8 wa-chat-column">
                        <div class="card h-100 d-flex flex-column wa-chat-main-panel">
                            <div id="whatsapp-chat-placeholder" class="card-body d-flex align-items-center justify-content-center flex-grow-1 text-muted" style="min-height: 400px;">
                                <span>{{ translate('Select a chat') }}</span>
                            </div>
                            <div id="whatsapp-chat-panel" class="d-none flex-column h-100">
                                <div class="card-header wa-conversation-header">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2 min-w-0 flex-grow-1">
                                            <strong id="whatsapp-chat-phone-line" class="mb-0 text-truncate wa-header-title"></strong>
                                            <span id="whatsapp-chat-system-pills" class="d-flex flex-wrap align-items-center gap-1 min-w-0"></span>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 flex-shrink-0">
                                            <span id="whatsapp-chat-handled-pill" class="flex-shrink-0"></span>
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
                                <div id="whatsapp-chat-messages" class="card-body overflow-auto flex-grow-1" style="min-height: 320px;"></div>
                                <?php if(auth()->check() && auth()->user()->can('whatsapp_chat_reply')): ?>
                                    <div class="card-footer border-top">
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
                @push('script')
                <script>
(function() {
    var waConvTemplates = @json($waQuickTplPayload ?? []);
    var waAgentName = @json($waAgentDisplayNameForTemplates ?? '');
    var waCustomerName = '';
    var waTplSuggestMax = 10;
    var waTplChipMax = 5;
    var waTplSuggestSelected = -1;
    var waTplSuggestMatches = [];
    var messagesUrl = '{{ route("admin.whatsapp.conversations.chat.messages") }}';
    var replyUrl = '{{ route("admin.whatsapp.conversations.reply") }}';
    var reactionUrl = '{{ route("admin.whatsapp.conversations.reaction") }}';
    var handoffUrl = '{{ route("admin.whatsapp.conversations.handoff") }}';
    var deleteHistoryUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_delete') ? route('admin.whatsapp.conversations.delete-history') : '');
    var canDeleteChatHistory = @json((bool) (auth()->check() && auth()->user()->can('whatsapp_chat_delete')));
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var currentAdminId = '{{ (string) auth()->id() }}';
    var currentPhone = null;
    var pollTimer = null;
    var currentHandler = null;
    var activeListTimer = null;
    var searchUrl = '{{ route("admin.whatsapp.conversations.search") }}';
    /** When set, loads a window around this message id and keeps it highlighted until another chat opens. */
    var stickyFocusMessageId = null;
    var strChats = {!! json_encode(translate('Chats')) !!};
    var strMessages = {!! json_encode(translate('Messages')) !!};
    var strNoResults = {!! json_encode(translate('No results')) !!};
    var strWaCustomer = {!! json_encode(translate('whatsapp_system_customer')) !!};
    var strWaProvider = {!! json_encode(translate('whatsapp_system_provider')) !!};
    var strWaNone = {!! json_encode(translate('whatsapp_not_in_system')) !!};
    var strHandlerAiLabel = {!! json_encode(translate('AI')) !!};
    var strOverrideChat = {!! json_encode(translate('Override chat')) !!};
    var strAssignBackAi = {!! json_encode(translate('Assign back to AI')) !!};
    var strDeleteChatTitle = {!! json_encode(translate('delete_chat')) !!};
    var strReply = {!! json_encode(translate('Reply')) !!};
    var strReact = {!! json_encode(translate('WhatsApp_react')) !!};
    var strGoToReplied = {!! json_encode(translate('WhatsApp_go_to_replied_message')) !!};
    var strCopy = {!! json_encode(translate('WhatsApp_copy')) !!};
    var strForward = {!! json_encode(translate('WhatsApp_forward')) !!};
    var strCopied = {!! json_encode(translate('WhatsApp_copied')) !!};
    var strForwardPrefix = {!! json_encode(translate('WhatsApp_forward_prefix')) !!};
    var strForwardSent = {!! json_encode(translate('WhatsApp_forward_sent')) !!};
    var strForwardSentMultiple = {!! json_encode(translate('WhatsApp_forward_sent_multiple')) !!};
    var strForwardSelectedCount = {!! json_encode(translate('WhatsApp_forward_selected_count')) !!};
    var activeChatsForForwardUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_reply') ? route('admin.whatsapp.conversations.active-chats-forward') : '');
    var threadStatusUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_reply') ? route('admin.whatsapp.conversations.thread-status') : '');
    var threadTagsUrl = @json(auth()->check() && auth()->user()->can('whatsapp_chat_reply') ? route('admin.whatsapp.conversations.thread-tags') : '');
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

    function hideGlobalSearchDropdown() {
        var dd = document.getElementById('wa-global-search-dropdown');
        if (dd) {
            dd.style.display = 'none';
            dd.innerHTML = '';
        }
    }

    function renderGlobalSearchResults(data) {
        var dd = document.getElementById('wa-global-search-dropdown');
        if (!dd) return;
        var chats = data.chats || [];
        var messages = data.messages || [];
        if (!chats.length && !messages.length) {
            dd.innerHTML = '<div class="list-group-item text-muted small">' + escapeHtml(strNoResults) + '</div>';
            dd.style.display = 'block';
            return;
        }
        var html = '';
        if (chats.length) {
            html += '<div class="list-group-item text-uppercase fz-11 text-muted border-0 py-1">' + escapeHtml(strChats) + '</div>';
            chats.forEach(function(c) {
                var label = (c.display_line && String(c.display_line).trim()) ? c.display_line : ((c.name || '').trim() ? c.name + ' · ' + formatPhoneDisplay(c.phone) : formatPhoneDisplay(c.phone));
                var prev = escapeHtml(c.preview || '');
                var ph = escapeHtml(c.phone || '');
                html += '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-phone="' + ph + '">';
                html += '<div class="fw-medium text-truncate">' + escapeHtml(label) + '</div>';
                html += '<div class="small text-muted text-truncate">' + prev + '</div>';
                html += '</button>';
            });
        }
        if (messages.length) {
            html += '<div class="list-group-item text-uppercase fz-11 text-muted border-0 py-1">' + escapeHtml(strMessages) + '</div>';
            messages.forEach(function(m) {
                var who = (m.name || '').trim() ? m.name + ' · ' + formatPhoneDisplay(m.phone) : formatPhoneDisplay(m.phone);
                var snip = escapeHtml(m.snippet || '');
                var ph = escapeHtml(m.phone || '');
                var mid = (m.id != null && m.id !== '') ? String(m.id) : '';
                html += '<button type="button" class="list-group-item list-group-item-action text-start wa-global-hit py-2" data-phone="' + ph + '" data-message-id="' + mid.replace(/"/g, '') + '">';
                html += '<div class="fw-medium text-truncate">' + escapeHtml(who) + '</div>';
                html += '<div class="small text-muted text-truncate">' + snip + '</div>';
                html += '</button>';
            });
        }
        dd.innerHTML = html;
        dd.style.display = 'block';
        dd.querySelectorAll('.wa-global-hit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var ph = this.getAttribute('data-phone');
                var mid = this.getAttribute('data-message-id');
                hideGlobalSearchDropdown();
                var gInp = document.getElementById('wa-global-search');
                if (gInp) gInp.value = '';
                openChat(ph, mid ? { focusMessageId: mid } : {});
            });
        });
    }

    var debouncedGlobalSearch = debounce(function() {
        var inp = document.getElementById('wa-global-search');
        if (!inp) return;
        var q = inp.value.trim();
        if (q.length < 2) {
            hideGlobalSearchDropdown();
            return;
        }
        fetch(searchUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) { return r.json(); })
          .then(renderGlobalSearchResults)
          .catch(function() { hideGlobalSearchDropdown(); });
    }, 350);

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

    function loadMessages(phone, isPoll) {
        var panel = document.getElementById('whatsapp-chat-messages');
        if (!panel) {
            return;
        }
        var wasNearBottom = true;
        if (isPoll && panel) {
            var threshold = 100;
            wasNearBottom = (panel.scrollHeight - panel.scrollTop - panel.clientHeight) <= threshold;
        }
        if (!isPoll) {
            panel.innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
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
                var html = '';
                (res.data || []).forEach(function(m) {
                    var isOut = (m.direction || '').toUpperCase() === 'OUT';
                    var time = '';
                    if (m.created_at) {
                        time = waFormatChatMessageTime(m.created_at);
                    }
                    var body = (m.message_text || m.body || '').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                    var mediaUrl = m.media_url || '';
                    var msgType = (m.message_type || '').toUpperCase();
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
                    var canReact = canReply && !isOut;
                    var plainForCopy = String(m.message_text || m.body || '');
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
                    html += '<div class="fz-12 opacity-75 d-flex justify-content-between align-items-center gap-2">';
                    html += '<span>' + (time || '') + (statusLabel ? ' · ' + statusLabel : '') + statusIcon + '</span>';
                    if (isOut && sentBy) {
                        html += '<span class="ms-2 text-end">Sent by ' + sentBy.replace(/</g, '&lt;') + '</span>';
                    } else {
                        html += '<span></span>';
                    }
                    html += '</div>';
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
                    if (isOut && status === 'failed' && statusDetail) {
                        var safeDetail = statusDetail.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                        html += '<div class="fz-11 mt-1 opacity-90 text-break" style="max-width:100%">' + safeDetail + '</div>';
                    }
                    if (isDocument && mediaUrl) {
                        var docName = (body || 'Document').replace(/</g, '&lt;').replace(/>/g, '&gt;');
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
                    if (body && !(isDocument && mediaUrl)) {
                        html += '<div class="mt-1">' + body + '</div>';
                    }
                    html += waFormatReactionsStrip(isOut, m.reactions || {});
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
                panel.innerHTML = html || '<p class="text-muted text-center py-4">No messages yet</p>';
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
                var overSlot = document.getElementById('whatsapp-chat-override-slot');
                var delSlot = document.getElementById('whatsapp-chat-delete-slot');
                if (overSlot) overSlot.innerHTML = '';
                if (delSlot) delSlot.innerHTML = '';
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
                                    var tmp = document.createElement('div');
                                    tmp.innerHTML = html;
                                    var newList = tmp.querySelector('.whatsapp-active-list-container');
                                    if (newList) {
                                        listContainer.innerHTML = newList.innerHTML;
                                        bindActiveChatListClicks(listContainer);
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
                    panel.innerHTML = '<p class="text-danger text-center py-4">Failed to load messages</p>';
                }
            });
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
                    // Extract just the left list markup using a temporary DOM
                    var tmp = document.createElement('div');
                    tmp.innerHTML = html;
                    var newList = tmp.querySelector('.whatsapp-active-list-container');
                    if (newList) {
                        listContainer.innerHTML = newList.innerHTML;
                        bindActiveChatListClicks(listContainer);
                    }
                  })
                  .catch(function() {});
            } catch (e) {}
        }, 5000);
    }

    var listCol = document.querySelector('.whatsapp-active-list-container');
    if (listCol) bindActiveChatListClicks(listCol);

    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'wa-global-search') {
            debouncedGlobalSearch();
        }
    });
    document.addEventListener('click', function(e) {
        var tagPanel = document.getElementById('wa-manage-tags-panel');
        if (tagPanel && !tagPanel.classList.contains('d-none') && !e.target.closest('.wa-manage-tags-wrap')) {
            tagPanel.classList.add('d-none');
        }
        if (e.target.closest('#wa-global-search') || e.target.closest('#wa-global-search-dropdown')) {
            return;
        }
        hideGlobalSearchDropdown();
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
        try {
            openChat(key);
        } catch (e) {
            console.error('waOpenChatFromQuery', e);
        }
    }
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
        sendBtnEl.disabled = !(hasText || hasFiles);
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

    replyFormEl.addEventListener('submit', function(e) {
        e.preventDefault();
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
        var time = waFormatChatMessageDateTimeNow();
        var safeBody = body.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\\n/g, '<br>');
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
                panel.appendChild(wrap);
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
            panel.appendChild(wrapper);
            statusSpan = wrapper.querySelector('[data-temp-status=\"' + tempId + '\"]');
        }
        panel.scrollTop = panel.scrollHeight;

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
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (($leads ?? collect())->isNotEmpty()) { ?>
                                @foreach($leads as $lead)
                                <tr>
                                    <td>{{ $lead->lead_id ?? $lead->id ?? '—' }}</td>
                                    <td>{{ $displayPhone($lead->phone ?? null) }}</td>
                                    <td>{{ $lead->name ?? '—' }}</td>
                                    <td>{{ $lead->service ?? '—' }}</td>
                                    <td>{{ $lead->status ?? '—' }}</td>
                                    <td>{{ $lead->created_at?->format('M j, H:i') ?? '—' }}</td>
                                </tr>
                                @endforeach
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">{{ translate('No provider leads') }}</td>
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
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (($bookings ?? collect())->isNotEmpty()) { ?>
                                @foreach($bookings as $booking)
                                <tr>
                                    <td>{{ $booking->booking_id ?? $booking->id ?? '—' }}</td>
                                    <td>{{ $displayPhone($booking->phone ?? null) }}</td>
                                    <td>{{ $booking->name ?? '—' }}</td>
                                    <td>{{ $booking->service ?? '—' }}</td>
                                    <td>{{ $booking->status ?? '—' }}</td>
                                    <td>{{ $booking->created_at?->format('M j, H:i') ?? '—' }}</td>
                                </tr>
                                @endforeach
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">{{ translate('No bookings') }}</td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (isset($bookings) && $bookings->hasPages()) { ?>
                        <div class="card-footer border-0">{{ $bookings->links() }}</div>
                    <?php } ?>
                </div>
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
                                <tr>
                                    <td>{{ $displayPhone($waUser->phone ?? null) }}</td>
                                    <td>{{ $waUser->name ?? '—' }}</td>
                                    <td class="align-middle">
                                        @include('whatsappmodule::admin.conversations.partials.system-link-pills', [
                                            'systemLink' => $waUser->system_link ?? [],
                                            'onUnread' => false,
                                            'showNames' => false,
                                        ])
                                    </td>
                                    <td>
                                        @php($leadCount = (int) ($waUser->lead_count ?? 0))
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
                                        <a href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'chats', 'phone' => $waUser->phone]) }}" class="btn btn-sm btn--primary">{{ translate('View chat') }}</a>
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
                                    <td colspan="9" class="text-center py-5 text-muted">{{ translate('No WhatsApp users') }}</td>
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
    var detailsUrl = '{{ route("admin.whatsapp.users.details") }}';
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

            <?php if (($tab ?? '') === 'chat_config'): ?>
                @include('whatsappmodule::admin.conversations.partials.chat-configuration')
            <?php endif; ?>
        </div>
    </div>
@endsection
