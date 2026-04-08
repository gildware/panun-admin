@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp') . ' - ' . translate('Chat'))

@push('css_or_js')
    <style>
        .wa-msg-bubble {
            max-width: 85%;
        }
        .wa-msg-react-strip,
        .wa-msg-bottom-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 2px;
            width: fit-content;
            max-width: 85%;
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
        .wa-msg-bubble.wa-msg-jump-flash {
            animation: wa-msg-jump-highlight 1.25s ease;
        }
        @keyframes wa-msg-jump-highlight {
            0%, 100% { box-shadow: none; }
            20%, 80% { box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.75); }
        }
        .wa-msg-staff-note {
            max-width: 85%;
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
        .wa-tpl-suggest-item:last-child { border-bottom: 0; }
        .wa-tpl-suggest-item:hover,
        .wa-tpl-suggest-item.wa-tpl-suggest-item--active {
            background: var(--bs-primary-bg-subtle, #e7f1ff);
        }
        .wa-tpl-suggest-title { font-weight: 600; font-size: 0.8rem; line-height: 1.2; }
        .wa-tpl-suggest-preview {
            font-size: 0.72rem;
            color: var(--bs-secondary, #6c757d);
            margin-top: 0.15rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="page-title d-flex gap-3 align-items-center">
                    <a href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'chats']) }}" class="btn btn-sm btn--secondary">
                        <span class="material-icons">arrow_back</span>
                    </a>
                    <span class="material-icons">chat</span>
                    {{ translate('Chat') }}: {{ $phone }}
                </h2>
                @if($bookingLink ?? null)
                    <a href="{{ $bookingLink }}" class="btn btn--primary" target="_blank">
                        <span class="material-icons">event_note</span>
                        {{ translate('View Booking') }}
                    </a>
                @endif
            </div>

            @php
                $waMeta = $chatMetaPayload ?? [];
                $curSt = $waMeta['chat_status'] ?? null;
            @endphp
            @if(!empty($waMeta['chat_statuses_all']))
                <div class="card card-body mb-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1">{{ translate('whatsapp_chat_status') }}</label>
                            @can('whatsapp_chat_reply')
                                <form method="post" action="{{ route('admin.whatsapp.conversations.thread-status') }}" class="d-flex flex-wrap gap-2 align-items-center">
                                    @csrf
                                    <input type="hidden" name="phone" value="{{ e($phone) }}">
                                    <select name="whatsapp_chat_status_id" class="form-select form-select-sm" style="max-width: 14rem;">
                                        @php
                                            $applied = $waMeta['chat_status_applied_id'] ?? null;
                                            $selId = $applied !== null ? $applied : ($curSt['id'] ?? null);
                                        @endphp
                                        @foreach($waMeta['chat_statuses_all'] as $st)
                                            <option value="{{ $st['id'] }}" @selected((int) $selId === (int) $st['id'])>{{ $st['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn--primary">{{ translate('update') }}</button>
                                </form>
                            @else
                                <div>
                                    <span class="badge {{ ($curSt['bucket'] ?? 'open') === 'closed' ? 'bg-secondary' : 'bg-success' }}">{{ $curSt['name'] ?? '—' }}</span>
                                </div>
                            @endcan
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small text-muted mb-1">{{ translate('whatsapp_chat_tags_label') }}</label>
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                <div class="d-flex flex-wrap gap-2 flex-grow-1 align-items-center">
                                    @php
                                        $waMetaChatTags = $waMeta['chat_tags'] ?? [];
                                    @endphp
                                    @forelse($waMetaChatTags as $tg)
                                        @php
                                            $tc = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($tg['color'] ?? '')) ? $tg['color'] : '#6c757d';
                                        @endphp
                                        <span class="badge fz-12 py-1 px-2" style="background: {{ e($tc) }}; color: #fff;">{{ e($tg['name'] ?? '') }}</span>
                                    @empty
                                        <span class="text-muted small">—</span>
                                    @endforelse
                                </div>
                                @can('whatsapp_chat_reply')
                                    @if(!empty($waMeta['chat_tags_all']))
                                        <div class="flex-shrink-0 wa-manage-tags-wrap position-relative">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#wa-standalone-manage-tags"
                                                    aria-expanded="false"
                                                    aria-controls="wa-standalone-manage-tags">{{ translate('whatsapp_manage_tags') }}</button>
                                            <div class="collapse position-absolute end-0 mt-1 p-2 border rounded bg-white shadow-sm"
                                                 id="wa-standalone-manage-tags"
                                                 style="z-index: 40; min-width: 260px; max-height: 320px;">
                                                <form method="post" action="{{ route('admin.whatsapp.conversations.thread-tags') }}">
                                                    @csrf
                                                    <input type="hidden" name="phone" value="{{ e($phone) }}">
                                                    <div class="form-label small mb-1">{{ translate('whatsapp_manage_tags') }}</div>
                                                    <div class="d-flex flex-column gap-1 border rounded p-2 bg-light" style="max-height: 220px; overflow-y: auto;">
                                                        @php
                                                            $have = collect($waMeta['chat_tags'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
                                                        @endphp
                                                        @foreach($waMeta['chat_tags_all'] as $opt)
                                                            @php
                                                                $oid = (int) ($opt['id'] ?? 0);
                                                            @endphp
                                                            <div class="form-check mb-0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       name="tag_ids[]"
                                                                       id="wa-st-tag-{{ $oid }}"
                                                                       value="{{ $oid }}"
                                                                       @checked(in_array($oid, $have, true))>
                                                                <label class="form-check-label small" for="wa-st-tag-{{ $oid }}">{{ $opt['name'] }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div class="d-flex justify-content-end gap-2 mt-2">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#wa-standalone-manage-tags">{{ translate('Cancel') }}</button>
                                                        <button type="submit" class="btn btn-sm btn--primary">{{ translate('save') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($conversationState ?? null)
                <div class="card card-body mb-3">
                    <strong>{{ translate('Status') }}:</strong>
                    {{ $conversationState->active_module ?? '—' }} · {{ $conversationState->current_step ?? '—' }}
                    @if($conversationState->after_hours ?? false)
                        <span class="badge bg-warning">{{ translate('After hours') }}</span>
                    @endif
                </div>
            @endif

            @php
                $waPreviewById = [];
                foreach ($messages as $_m) {
                    if (!empty($_m->wa_message_id)) {
                        $waPreviewById[$_m->wa_message_id] = \Illuminate\Support\Str::limit((string) ($_m->message_text ?? ''), 160);
                    }
                }
            @endphp
            <div class="row">
                <div class="col-lg-8">
                    <div class="card card-body">
                        <div class="chat-messages overflow-auto mb-3" style="min-height: 320px; max-height: 50vh;">
                            @foreach($messages as $msg)
                                @php
                                    $isOut = strtoupper($msg->direction ?? '') === 'OUT';
                                    $st = strtolower((string) ($msg->status ?? ''));
                                    $waMid = trim((string) ($msg->wa_message_id ?? ''));
                                    $rx = is_array($msg->reactions ?? null) ? $msg->reactions : [];
                                    $replyPrev = !empty($msg->reply_to_wa_message_id) ? ($waPreviewById[$msg->reply_to_wa_message_id] ?? null) : null;
                                    $waCopyPlain = (string) ($msg->message_text ?? $msg->body ?? '');
                                    $waCopyB64 = $waCopyPlain !== '' ? base64_encode($waCopyPlain) : '';
                                    $waCanThread = auth()->check() && auth()->user()->can('whatsapp_chat_reply');
                                    $waShowReplyBtn = $waCanThread && $waMid !== '';
                                    $waShowCopyBtn = $waCopyPlain !== '';
                                    $waShowForwardBtn = $waCanThread && $waMid !== '' && $waCopyPlain !== '';
                                    $waShowReactBtns = $waCanThread && $waMid !== '' && !$isOut;
                                    $hasReactStrip = $waShowReactBtns;
                                    $hasBottomStrip = $waShowReplyBtn || $waShowCopyBtn || $waShowForwardBtn;
                                @endphp
                                <div class="mb-3 d-flex flex-column wa-msg-row {{ $isOut ? 'wa-msg-row--out align-items-end' : 'wa-msg-row--in align-items-start' }}"
                                     data-wa-message-id="{{ e($waMid) }}"
                                     data-msg-direction="{{ $isOut ? 'out' : 'in' }}">
                                    @if($hasReactStrip)
                                        <div class="wa-msg-react-strip {{ $isOut ? 'wa-msg-react-strip--out' : 'wa-msg-react-strip--in' }}">
                                            @foreach(['👍','❤️','😂','😮','😢','🙏'] as $em)
                                                <button type="button" class="btn btn-link btn-sm wa-msg-action-icon wa-chat-send-reaction"
                                                        data-wa-mid="{{ e($waMid) }}" data-emoji="{{ $em }}" title="{{ translate('WhatsApp_react') }}">{{ $em }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="wa-msg-bubble rounded px-3 py-2 {{ $isOut ? 'bg-primary text-white' : 'bg-light' }}">
                                        @if(!empty($replyPrev))
                                            @php
                                                $qSnippet = 'wa-reply-quote-snippet fz-11 mb-2 mt-1 px-2 py-1 rounded-1 text-start' . ($isOut ? ' wa-reply-quote-snippet--out-hover' : '');
                                            @endphp
                                            @if(!empty($msg->reply_to_wa_message_id))
                                                <button type="button"
                                                        class="wa-reply-preview-jump {{ $qSnippet }}"
                                                        data-jump-wa-mid="{{ e($msg->reply_to_wa_message_id) }}"
                                                        title="{{ translate('WhatsApp_go_to_replied_message') }}">{{ e($replyPrev) }}</button>
                                            @else
                                                <div class="{{ $qSnippet }}">{{ e($replyPrev) }}</div>
                                            @endif
                                        @endif
                                        @php
                                            $mtShow = strtoupper((string) ($msg->message_type ?? 'TEXT'));
                                            $txtShow = (string) ($msg->message_text ?? $msg->body ?? '');
                                        @endphp
                                        @if($mtShow === 'TEMPLATE' && str_contains($txtShow, "\n\n"))
                                            @php
                                                $parts = explode("\n\n", $txtShow, 2);
                                                $tplTitle = trim((string) ($parts[0] ?? ''));
                                                $tplRest = trim((string) ($parts[1] ?? ''));
                                            @endphp
                                            <div class="fw-semibold small">{{ e($tplTitle) }}</div>
                                            @if($tplRest !== '')
                                                <div class="mt-2 small" style="white-space:pre-wrap;">{!! nl2br(e($tplRest)) !!}</div>
                                            @endif
                                        @else
                                            <div class="mt-1">{!! nl2br(e($txtShow)) !!}</div>
                                        @endif
                                        @if(!empty($rx['customer']) || !empty($rx['agent']))
                                            <div class="fz-11 mt-2 pt-1 wa-msg-reactions" style="opacity:0.92;border-top:1px solid {{ $isOut ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.08)' }}">
                                                @if(!empty($rx['customer']))
                                                    <span class="me-2" title="Customer">👤 {{ $rx['customer'] }}</span>
                                                @endif
                                                @if(!empty($rx['agent']))
                                                    <span title="You">✓ {{ $rx['agent'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @php
                                        $staffMetaLine = ($msg->direction ?? 'IN')
                                            .' · '.($msg->message_type ?? 'TEXT')
                                            .' · '.\Modules\WhatsAppModule\Support\WhatsAppMessageTime::formatBlade($msg->created_at);
                                        if ($isOut && $st !== '') {
                                            $staffMetaLine .= ' · '.ucfirst($st);
                                        }
                                    @endphp
                                    <div class="wa-msg-staff-note text-start">
                                        <div class="wa-msg-staff-note__label">{{ translate('WhatsApp_chat_staff_note_heading') }}</div>
                                        <div class="text-break">{{ $staffMetaLine }}</div>
                                        @if($isOut && $waMid === '' && $st !== 'failed')
                                            <div class="mt-1 text-warning">{{ translate('WhatsApp_out_not_sent_to_user') }}</div>
                                        @endif
                                        @if($isOut && $st === 'failed' && !empty($msg->status_detail))
                                            <div class="mt-1 text-danger text-break">{{ e($msg->status_detail) }}</div>
                                        @endif
                                    </div>
                                    @if($hasBottomStrip)
                                        <div class="wa-msg-bottom-strip {{ $isOut ? 'wa-msg-bottom-strip--out' : 'wa-msg-bottom-strip--in' }}">
                                            @if($waShowReplyBtn)
                                                <button type="button" class="btn btn-link btn-sm wa-msg-action-icon wa-chat-action-reply"
                                                        data-wa-mid="{{ e($waMid) }}"
                                                        data-preview="{{ e(\Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) ($msg->message_text ?? '')), 140)) }}"
                                                        title="{{ translate('Reply') }}">
                                                    <span class="material-icons" aria-hidden="true">reply</span>
                                                </button>
                                            @endif
                                            @if($waShowCopyBtn)
                                                <button type="button" class="btn btn-link btn-sm wa-msg-action-icon wa-chat-action-copy"
                                                        data-copy-b64="{{ e($waCopyB64) }}"
                                                        title="{{ translate('WhatsApp_copy') }}">
                                                    <span class="material-icons" aria-hidden="true">content_copy</span>
                                                </button>
                                            @endif
                                            @if($waShowForwardBtn)
                                                <button type="button" class="btn btn-link btn-sm wa-msg-action-icon wa-chat-action-forward"
                                                        data-copy-b64="{{ e($waCopyB64) }}"
                                                        title="{{ translate('WhatsApp_forward') }}">
                                                    <span class="material-icons" aria-hidden="true">forward</span>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            @if($messages->isEmpty())
                                <p class="text-muted text-center py-4">{{ translate('No messages yet') }}</p>
                            @endif
                        </div>

                        @can('whatsapp_chat_reply')
                            <div class="border-top pt-3">
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
                                <div id="wa-reply-session-open-block-chat">
                                <div id="wa-conv-tpl-wrap-chat" class="mb-2 wa-conv-tpl-row d-none">
                                    <div id="wa-conv-tpl-chips-chat" class="d-flex flex-wrap align-items-center gap-1"></div>
                                </div>
                                <form action="{{ route('admin.whatsapp.conversations.reply') }}" method="POST" id="wa-chat-standalone-reply-form" class="d-flex flex-column">
                                    @csrf
                                    <input type="hidden" name="phone" value="{{ $phone }}">
                                    <input type="hidden" name="reply_to_wa_message_id" id="wa-chat-reply-to-wa-id" value="">
                                    <div id="wa-chat-reply-quote-bar" class="mb-2 d-none small border-start border-3 border-primary ps-2 py-1 bg-light rounded-end">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="text-muted fz-11">{{ translate('WhatsApp_replying_to') }}</div>
                                                <div id="wa-chat-reply-quote-text" class="text-break"></div>
                                            </div>
                                            <button type="button" class="btn-close btn-sm flex-shrink-0 mt-0" id="wa-chat-reply-quote-clear" aria-label="{{ translate('close') }}"></button>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 align-items-end flex-wrap">
                                        <div class="flex-grow-1 position-relative" style="min-width: 200px;">
                                            <div id="wa-tpl-suggest-chat" class="wa-tpl-suggest d-none" role="listbox" aria-label="{{ translate('WhatsApp_quick_reply_suggestions') }}"></div>
                                            <textarea name="body" id="wa-chat-standalone-body" class="form-control" rows="2" required
                                                      autocomplete="off"
                                                      placeholder="{{ translate('Type your reply...') }}"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn--primary flex-shrink-0">
                                            <span class="material-icons">send</span>
                                            {{ translate('Send') }}
                                        </button>
                                    </div>
                                </form>
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-body">
                        <h5 class="mb-3">{{ translate('Chat info') }}</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><strong>{{ translate('Phone') }}:</strong> {{ $phone }}</li>
                            @if($conversationState ?? null)
                                <li class="mb-2"><strong>{{ translate('Module') }}:</strong> {{ $conversationState->active_module ?? '—' }}</li>
                                <li class="mb-2"><strong>{{ translate('Step') }}:</strong> {{ $conversationState->current_step ?? '—' }}</li>
                            @endif
                        </ul>
                        @can('whatsapp_chat_delete')
                            <form action="{{ route('admin.whatsapp.conversations.delete-history') }}" method="POST" class="mt-3"
                                  onsubmit="return confirm({{ json_encode(translate('delete_chat') . '? ' . translate('are_you_sure')) }});">
                                @csrf
                                <input type="hidden" name="phone" value="{{ $phone }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                    {{ translate('delete_chat') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        @can('whatsapp_chat_reply')
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
        @endcan
    </div>
@endsection

@php
    $waQuickTplPayloadChat = isset($conversationQuickTemplates)
        ? $conversationQuickTemplates->map(static fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'body' => $t->body,
        ])->values()->all()
        : [];
@endphp

@push('script')
    <script>
        document.querySelector('.chat-messages')?.scrollTo(0, 1e9);
        (function () {
            var waConvTemplates = @json($waQuickTplPayloadChat);
            var waAgentName = @json($waAgentDisplayNameForTemplates ?? '');
            var waCustomerName = @json($waCustomerNameForTemplates ?? '');
            var waTplSuggestMax = 10;
            var waTplChipMax = 5;
            var waTplSuggestSelected = -1;
            var waTplSuggestMatches = [];
            var wrap = document.getElementById('wa-conv-tpl-wrap-chat');
            var ta = document.getElementById('wa-chat-standalone-body');
            var reactionUrl = @json(route('admin.whatsapp.conversations.reaction'));
            var replyUrl = @json(route('admin.whatsapp.conversations.reply'));
            var activeChatsForForwardUrl = @json(route('admin.whatsapp.conversations.active-chats-forward'));
            var wabaTemplatesUrl = @json(route('admin.whatsapp.conversations.chat.waba-templates'));
            var sendTemplateUrl = @json(route('admin.whatsapp.conversations.chat.send-template'));
            var strSessionBanner = @json(translate('whatsapp_session_window_banner'));
            var strSessionTextareaPh = @json(translate('whatsapp_session_window_textarea_placeholder'));
            var strTplLoadFailed = @json(translate('whatsapp_waba_templates_load_failed'));
            var strTplSentOk = @json(translate('whatsapp_template_sent_ok'));
            var strTplSentFail = @json(translate('whatsapp_template_send_failed'));
            var strPlaceholderN = @json(translate('whatsapp_session_window_placeholder_n'));
            var strTplHeaderVars = @json(translate('whatsapp_template_section_header_vars'));
            var strTplBodyVars = @json(translate('whatsapp_template_section_body_vars'));
            var strTplMediaUrlLabel = @json(translate('whatsapp_template_header_media_url_label'));
            var strSelectTpl = @json(translate('whatsapp_session_window_select_template'));
            var waSessionWindowOpen = true;
            var waWabaTemplatesList = null;
            var waWabaTemplatesLoading = false;
            var waInitialMw = @json($messagingWindow ?? ['session_open' => true]);
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            var chatPhone = @json($phone);
            var strCopied = @json(translate('WhatsApp_copied'));
            var strForwardPrefix = @json(translate('WhatsApp_forward_prefix'));
            var strForwardSent = @json(translate('WhatsApp_forward_sent'));
            var strForwardSentMultiple = @json(translate('WhatsApp_forward_sent_multiple'));
            var strForwardSelectedCount = @json(translate('WhatsApp_forward_selected_count'));
            var strNoResults = @json(translate('No results'));
            var waForwardPayloadB64 = '';
            var waForwardChatsCache = [];
            var waForwardSelectedPhones = Object.create(null);

            function escapeHtml(s) {
                return String(s || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function waTemplateSelectValue(t) {
                return (t.name || '') + '\t' + (t.language || '');
            }

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
                    sel.value = '';
                }
                waRebuildWabaTemplateFields();
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
                            return;
                        }
                        waWabaTemplatesList = data.templates || [];
                        sel.innerHTML = '<option value="">' + escapeHtml(strSelectTpl) + '</option>';
                        waWabaTemplatesList.forEach(function (t) {
                            var opt = document.createElement('option');
                            opt.value = waTemplateSelectValue(t);
                            opt.textContent = (t.name || '') + ' · ' + (t.language || '') + (t.category ? ' (' + t.category + ')' : '');
                            opt.setAttribute('data-n', String(t.body_placeholder_count != null ? t.body_placeholder_count : 0));
                            sel.appendChild(opt);
                        });
                    })
                    .catch(function () {
                        waWabaTemplatesLoading = false;
                        waWabaTemplatesList = null;
                        if (typeof toastr !== 'undefined') {
                            toastr.error(strTplLoadFailed);
                        }
                    });
            }

            function waApplyMessagingWindowStandalone(res) {
                var mw = res.messaging_window;
                var open = true;
                if (mw && Object.prototype.hasOwnProperty.call(mw, 'session_open')) {
                    open = !!mw.session_open;
                }
                waSessionWindowOpen = open;
                var banner = document.getElementById('wa-session-window-banner');
                var panel = document.getElementById('wa-waba-template-panel');
                var convWrap = document.getElementById('wa-conv-tpl-wrap-chat');
                var openBlock = document.getElementById('wa-reply-session-open-block-chat');
                var attLabel = document.querySelector('label[for="wa-attachment-input"]');
                var subBtn = document.querySelector('#wa-chat-standalone-reply-form button[type="submit"]');
                if (open) {
                    if (banner) banner.classList.add('d-none');
                    if (panel) panel.classList.add('d-none');
                    if (openBlock) openBlock.classList.remove('d-none');
                    if (convWrap) convWrap.classList.remove('d-none');
                    if (ta) {
                        ta.disabled = false;
                        ta.removeAttribute('placeholder');
                        ta.setAttribute('required', 'required');
                    }
                    if (attLabel) attLabel.classList.remove('opacity-50', 'pe-none');
                    if (subBtn) subBtn.disabled = false;
                } else {
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
                        ta.removeAttribute('required');
                        ta.setAttribute('placeholder', strSessionTextareaPh);
                    }
                    if (attLabel) attLabel.classList.add('opacity-50', 'pe-none');
                    if (subBtn) subBtn.disabled = true;
                    waLoadWabaTemplatesIfNeeded();
                }
            }

            function waB64DecodeUtf8(b64) {
                try {
                    return decodeURIComponent(escape(atob(String(b64 || ''))));
                } catch (e) {
                    return '';
                }
            }

            function formatPhoneDisplay(phone) {
                var digits = String(phone || '').replace(/\D+/g, '');
                if (!digits) {
                    return '—';
                }
                return digits.length > 10 ? digits.slice(-10) : digits;
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

            function waRenderForwardListChat(items) {
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
                    h += '<input type="checkbox" class="form-check-input flex-shrink-0 mt-0 wa-forward-cb-chat"' + checked + ' data-phone="' + ph + '">';
                    h += '<div class="flex-grow-1 min-w-0 text-start">';
                    h += '<div class="fw-medium text-truncate">' + escapeHtml(line) + '</div>';
                    h += '<div class="small text-muted text-truncate">' + ph + '</div>';
                    h += '</div></label>';
                });
                listEl.innerHTML = h;
                listEl.querySelectorAll('.wa-forward-cb-chat').forEach(function (cb) {
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

            function waOpenForwardModalChat() {
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
                fetch(activeChatsForForwardUrl + '?exclude_phone=' + encodeURIComponent(chatPhone || ''), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (res) {
                        waForwardChatsCache = res.data || [];
                        waRenderForwardListChat(waForwardChatsFilter(waForwardChatsCache, searchEl ? searchEl.value : ''));
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
                        toastr.info(@json(translate('WhatsApp_reply_original_not_loaded')));
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

            function waChatClearReply() {
                var hid = document.getElementById('wa-chat-reply-to-wa-id');
                var bar = document.getElementById('wa-chat-reply-quote-bar');
                var txt = document.getElementById('wa-chat-reply-quote-text');
                if (hid) hid.value = '';
                if (bar) bar.classList.add('d-none');
                if (txt) txt.textContent = '';
            }
            function waChatSetReply(waMid, previewPlain) {
                var hid = document.getElementById('wa-chat-reply-to-wa-id');
                var bar = document.getElementById('wa-chat-reply-quote-bar');
                var txt = document.getElementById('wa-chat-reply-quote-text');
                if (!hid || !waMid) return;
                hid.value = waMid;
                if (txt) txt.textContent = previewPlain || '';
                if (bar) bar.classList.remove('d-none');
                if (ta) ta.focus();
            }

            var clr = document.getElementById('wa-chat-reply-quote-clear');
            if (clr) clr.addEventListener('click', waChatClearReply);

            var chatScroll = document.querySelector('.chat-messages');
            if (chatScroll) {
                chatScroll.addEventListener('click', function (e) {
                    var jumpBtn = e.target.closest && e.target.closest('.wa-reply-preview-jump');
                    if (jumpBtn) {
                        e.preventDefault();
                        waScrollToMessageByWaId(chatScroll, jumpBtn.getAttribute('data-jump-wa-mid'));
                        return;
                    }
                    var copyB = e.target.closest && e.target.closest('.wa-chat-action-copy');
                    if (copyB) {
                        e.preventDefault();
                        var b64c = copyB.getAttribute('data-copy-b64') || '';
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
                                    var tx = document.createElement('textarea');
                                    tx.value = t;
                                    tx.style.position = 'fixed';
                                    tx.style.left = '-9999px';
                                    document.body.appendChild(tx);
                                    tx.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(tx);
                                    done();
                                } catch (e2) {}
                            });
                        } else {
                            try {
                                var tx2 = document.createElement('textarea');
                                tx2.value = t;
                                tx2.style.position = 'fixed';
                                tx2.style.left = '-9999px';
                                document.body.appendChild(tx2);
                                tx2.select();
                                document.execCommand('copy');
                                document.body.removeChild(tx2);
                                done();
                            } catch (e3) {}
                        }
                        return;
                    }
                    var fwdB = e.target.closest && e.target.closest('.wa-chat-action-forward');
                    if (fwdB) {
                        e.preventDefault();
                        waForwardPayloadB64 = fwdB.getAttribute('data-copy-b64') || '';
                        waOpenForwardModalChat();
                        return;
                    }
                    var rb = e.target.closest && e.target.closest('.wa-chat-action-reply');
                    if (rb) {
                        e.preventDefault();
                        waChatSetReply(rb.getAttribute('data-wa-mid'), rb.getAttribute('data-preview') || '');
                        return;
                    }
                    var rx = e.target.closest && e.target.closest('.wa-chat-send-reaction');
                    if (rx) {
                        e.preventDefault();
                        var wm = rx.getAttribute('data-wa-mid');
                        var em = rx.getAttribute('data-emoji') || '';
                        if (!wm) return;
                        fetch(reactionUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                phone: chatPhone,
                                target_wa_message_id: wm,
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
                                    waPatchReactionsOnRow(chatScroll, wm, res.reactions || {});
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

            function interpolate(text) {
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
                if (!needle) return [];
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

            function waHideTplSuggestChat() {
                var el = document.getElementById('wa-tpl-suggest-chat');
                if (el) {
                    el.classList.add('d-none');
                    el.innerHTML = '';
                }
                waTplSuggestSelected = -1;
                waTplSuggestMatches = [];
            }

            function waCloseTplSuggestPanelOnlyChat() {
                var el = document.getElementById('wa-tpl-suggest-chat');
                if (el) {
                    el.classList.add('d-none');
                    el.innerHTML = '';
                }
                waTplSuggestSelected = -1;
                waTplSuggestMatches = [];
            }

            function waHighlightTplSuggestChat(newSel) {
                var box = document.getElementById('wa-tpl-suggest-chat');
                if (!box) return;
                var items = box.querySelectorAll('.wa-tpl-suggest-item');
                waTplSuggestSelected = newSel;
                items.forEach(function (node, i) {
                    node.classList.toggle('wa-tpl-suggest-item--active', i === newSel);
                });
            }

            function waApplyTemplateIndexChat(tplIdx) {
                if (!ta || waConvTemplates[tplIdx] == null) return;
                ta.value = interpolate(waConvTemplates[tplIdx].body || '');
                waHideTplSuggestChat();
                ta.focus();
            }

            function waSyncTplSuggestUIChat() {
                var suggestEl = document.getElementById('wa-tpl-suggest-chat');
                if (!suggestEl || !ta) return;
                var needle = String(ta.value || '').trim();
                if (needle === '') {
                    waHideTplSuggestChat();
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
                    var preview = interpolate(t.body || '');
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

            function waInitChatQuickReplies() {
                if (!wrap) {
                    return;
                }
                var hostChips = document.getElementById('wa-conv-tpl-chips-chat');
                if (!hostChips) {
                    return;
                }
                hostChips.innerHTML = '';
                waHideTplSuggestChat();
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
                    var prev = interpolate(t.body || '');
                    chip.title = (t.title || '') + (prev ? (' \u2014 ' + prev.slice(0, 100)) : '');
                    chip.textContent = t.title || ('#' + t.id);
                    chip.setAttribute('data-wa-tpl-index', String(i));
                    hostChips.appendChild(chip);
                });
            }

            if (wrap && !wrap.dataset.waChipDelegateBound) {
                wrap.dataset.waChipDelegateBound = '1';
                wrap.addEventListener('click', function (e) {
                    var chip = e.target.closest('[data-wa-tpl-index]');
                    if (!chip || !ta) {
                        return;
                    }
                    var idx = parseInt(chip.getAttribute('data-wa-tpl-index'), 10);
                    if (isNaN(idx) || !waConvTemplates[idx]) {
                        return;
                    }
                    waApplyTemplateIndexChat(idx);
                });
            }

            waApplyMessagingWindowStandalone({ messaging_window: waInitialMw, handler: { type: 'USER' } });

            var standaloneForm = document.getElementById('wa-chat-standalone-reply-form');
            if (standaloneForm) {
                standaloneForm.addEventListener('submit', function (e) {
                    if (!waSessionWindowOpen) {
                        e.preventDefault();
                        if (typeof toastr !== 'undefined') {
                            toastr.warning(@json(translate('whatsapp_session_window_closed_server')));
                        }
                        return false;
                    }
                });
            }

            (function waBindWabaTemplateComposerChat() {
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
                        var raw = sel && sel.value ? sel.value : '';
                        if (!chatPhone || !raw) {
                            if (typeof toastr !== 'undefined') toastr.warning(strSelectTpl);
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
                            if (typeof toastr !== 'undefined') toastr.warning(strSelectTpl);
                            return;
                        }
                        var htc = parseInt(tpl.header_text_placeholder_count, 10) || 0;
                        var bpc = parseInt(tpl.body_placeholder_count, 10) || 0;
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
                                phone: chatPhone,
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
                                    if (typeof toastr !== 'undefined') toastr.success(strTplSentOk);
                                    waClearWabaTemplateComposer();
                                    window.location.reload();
                                } else {
                                    var errMsg = (pack.body && pack.body.user_message) ? String(pack.body.user_message) : strTplSentFail;
                                    if (typeof toastr !== 'undefined') toastr.error(errMsg);
                                }
                            })
                            .catch(function () {
                                waTplSend.disabled = false;
                                if (typeof toastr !== 'undefined') toastr.error(strTplSentFail);
                            });
                    });
                }
            })();

            waInitChatQuickReplies();

            var suggestBox = document.getElementById('wa-tpl-suggest-chat');
            if (suggestBox) {
                suggestBox.addEventListener('click', function (e) {
                    var btn = e.target.closest('.wa-tpl-suggest-item');
                    if (!btn || !ta) return;
                    var idx = parseInt(btn.getAttribute('data-wa-tpl-idx'), 10);
                    if (!isNaN(idx)) waApplyTemplateIndexChat(idx);
                });
            }

            if (ta) {
                ta.addEventListener('input', waSyncTplSuggestUIChat);
                ta.addEventListener('focus', waSyncTplSuggestUIChat);
                ta.addEventListener('keydown', function (e) {
                    var box = document.getElementById('wa-tpl-suggest-chat');
                    if (!box || box.classList.contains('d-none') || waTplSuggestMatches.length === 0) return;
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        var n = (waTplSuggestSelected + 1) % waTplSuggestMatches.length;
                        waHighlightTplSuggestChat(n);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        var n2 = (waTplSuggestSelected - 1 + waTplSuggestMatches.length) % waTplSuggestMatches.length;
                        waHighlightTplSuggestChat(n2);
                    } else if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        var cur = waTplSuggestMatches[waTplSuggestSelected];
                        if (cur != null) waApplyTemplateIndexChat(cur);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        waCloseTplSuggestPanelOnlyChat();
                    }
                });
            }

            document.addEventListener('click', function (e) {
                if (e.target.closest('#wa-tpl-suggest-chat') || e.target.closest('#wa-chat-standalone-body') || e.target.closest('#wa-conv-tpl-wrap-chat')) {
                    return;
                }
                waCloseTplSuggestPanelOnlyChat();
            });

            var waFwdSearchChat = document.getElementById('wa-forward-modal-search');
            if (waFwdSearchChat) {
                waFwdSearchChat.addEventListener('input', function () {
                    waRenderForwardListChat(waForwardChatsFilter(waForwardChatsCache, waFwdSearchChat.value));
                });
            }
            var waFwdSendBtnChat = document.getElementById('wa-forward-modal-send');
            if (waFwdSendBtnChat) {
                waFwdSendBtnChat.addEventListener('click', function () {
                    waSendForwardToSelected();
                });
            }

            try {
                if (typeof window.pkAdminRefreshWhatsAppUnread === 'function') {
                    window.pkAdminRefreshWhatsAppUnread({ skipSound: true });
                }
            } catch (e) {}
        })();
    </script>
@endpush
