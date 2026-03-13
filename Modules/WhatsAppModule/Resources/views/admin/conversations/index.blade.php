@extends('adminmodule::layouts.master')

@section('title', translate('WhatsApp'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/select2/select2.min.css') }}"/>
@endpush

@section('content')
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
                </ul>
            </div>

            {{-- Tab: Active Chats — left: scrollable list, right: open chat --}}
            <?php if (($tab ?? '') === 'chats'): ?>
                <div class="row g-3">
                    <div class="col-md-4 col-lg-3 whatsapp-active-list-container">
                        <div class="card h-100 d-flex flex-column">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center gap-2">
                                <strong>{{ translate('Chats') }}</strong>
                                @php($handlerFilter = $handlerFilter ?? 'all')
                                <?php if (!empty($chatHandlers ?? null)) { ?>
                                    <div class="ms-auto" style="min-width: 220px;">
                                        <select id="chat-handler-filter"
                                                class="form-select form-select-sm w-100"
                                                style="min-width: 220px; padding-right: 1.75rem;"
                                                onchange="if(this.value){ window.location.href = this.value; }">
                                            @foreach($chatHandlers as $h)
                                                <option value="{{ route('admin.whatsapp.conversations.index', ['tab' => 'chats', 'handler' => $h['key']]) }}"
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
                                <?php if ($chatCollection->isNotEmpty()): ?>
                                    <?php foreach ($chatCollection as $chat): ?>
                                        <?php
                                            $created = $chat->created_at ?? null;
                                            $phone = $chat->phone ?? '';
                                            $name = trim($chat->name ?? '');
                                            $display = $name !== '' ? $name . ' (' . $phone . ')' : ($phone ?: '—');
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
                                        <div class="whatsapp-chat-item border-bottom p-3 cursor-pointer{{ $hasUnread ? ' bg-primary text-white' : '' }}"
                                             data-phone="{{ e($phone) }}"
                                             role="button">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <strong class="text-truncate{{ $hasUnread ? ' text-white' : '' }}" title="{{ e($display) }}">{{ $display }}</strong>
                                                <?php if ($created): ?>
                                                    <span class="fz-12{{ $hasUnread ? ' text-white-50' : ' text-muted' }}">{{ \Carbon\Carbon::parse($created)->diffForHumans() }}</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="fz-12 text-truncate mt-1 d-flex justify-content-between align-items-center gap-2{{ $hasUnread ? ' text-white-50' : ' text-muted' }}">
                                                <span class="{{ $hasUnread ? 'text-white' : '' }}">
                                                    {{ \Illuminate\Support\Str::limit($chat->message_text ?? '', 40) }}
                                                </span>
                                                <span class="ms-2 text-nowrap">
                                                    <?php if (!empty($chat->unread_count)): ?>
                                                        <span class="badge me-1 {{ $hasUnread ? 'bg-light text-primary' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                                                            {{ (int) $chat->unread_count }}
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($statusIcon): ?>
                                                        <span class="{{ $hasUnread ? 'text-white' : ($status === 'read' ? 'text-primary' : '') }}">{{ $statusIcon }}</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <?php
                                                $handledByLabel = $chat->handled_by_label ?? 'AI';
                                                $handledText = $handledByLabel === 'AI'
                                                    ? 'Handled by AI'
                                                    : 'Handled by ' . $handledByLabel;
                                            ?>
                                            <div class="fz-11 mt-1 text-end{{ $hasUnread ? ' text-white-50' : ' text-muted' }}">
                                                {{ $handledText }}
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-muted">{{ translate('No active chats') }}</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 col-lg-9">
                        <div class="card h-100 d-flex flex-column">
                            <div id="whatsapp-chat-placeholder" class="card-body d-flex align-items-center justify-content-center flex-grow-1 text-muted" style="min-height: 400px;">
                                <span>{{ translate('Select a chat') }}</span>
                            </div>
                            <div id="whatsapp-chat-panel" class="d-none flex-column h-100">
                                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
                                    <strong id="whatsapp-chat-phone"></strong>
                                    <div id="whatsapp-chat-actions"></div>
                                </div>
                                <div id="whatsapp-chat-messages" class="card-body overflow-auto flex-grow-1" style="min-height: 280px; max-height: 50vh;"></div>
                                <?php if(auth()->check() && auth()->user()->can('whatsapp_chat_reply')): ?>
                                    <div class="card-footer border-top">
                                        <form id="whatsapp-reply-form" class="d-flex align-items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="phone" id="whatsapp-reply-phone" value="">

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
                                                <textarea name="body"
                                                          id="wa-reply-body"
                                                          class="form-control rounded-pill ps-3 pe-3"
                                                          rows="1"
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
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                @push('script')
                <script>
(function() {
    var messagesUrl = '{{ route("admin.whatsapp.conversations.chat.messages") }}';
    var replyUrl = '{{ route("admin.whatsapp.conversations.reply") }}';
    var handoffUrl = '{{ route("admin.whatsapp.conversations.handoff") }}';
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var currentAdminId = '{{ (string) auth()->id() }}';
    var currentPhone = null;
    var pollTimer = null;
    var currentHandler = null;
    var activeListTimer = null;

    function openChat(phone) {
        if (!phone) return;
        document.getElementById('whatsapp-reply-phone').value = phone;
        document.getElementById('whatsapp-chat-phone').textContent = phone;
        document.getElementById('whatsapp-chat-placeholder').classList.add('d-none');
        document.getElementById('whatsapp-chat-panel').classList.remove('d-none');
        document.getElementById('whatsapp-chat-panel').classList.add('d-flex');
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
                // Remove unread badge if present
                var badge = el.querySelector('.badge');
                if (badge) {
                    badge.parentNode.removeChild(badge);
                }
            }
        });
        currentPhone = phone;
        startPolling();
        loadMessages(phone, false);
    }

    function loadMessages(phone, isPoll) {
        var panel = document.getElementById('whatsapp-chat-messages');
        var wasNearBottom = true;
        if (isPoll && panel) {
            var threshold = 100;
            wasNearBottom = (panel.scrollHeight - panel.scrollTop - panel.clientHeight) <= threshold;
        }
        if (!isPoll) {
            panel.innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
        }
        var url = messagesUrl + '?phone=' + encodeURIComponent(phone) + '&full=1';
        if (!isPoll) {
            url += '&mark_seen=1';
        }
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var html = '';
                (res.data || []).forEach(function(m) {
                    var isOut = (m.direction || '').toUpperCase() === 'OUT';
                    var time = '';
                    if (m.created_at) {
                        var d = new Date(m.created_at);
                        time = d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit', hour12: true });
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
                    var sentBy = (m.sent_by || '').trim();
                    html += '<div class="mb-3 d-flex ' + (isOut ? 'justify-content-end' : '') + '">';
                    html += '<div class="rounded px-3 py-2 ' + (isOut ? 'bg-primary text-white' : 'bg-light') + '" style="max-width:85%">';
                    html += '<div class="fz-12 opacity-75 d-flex justify-content-between align-items-center gap-2">';
                    html += '<span>' + (time || '') + (statusLabel ? ' · ' + statusLabel : '') + statusIcon + '</span>';
                    if (isOut && sentBy) {
                        html += '<span class="ms-2 text-end">Sent by ' + sentBy + '</span>';
                    } else {
                        html += '<span></span>';
                    }
                    html += '</div>';
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
                    html += '</div></div>';
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
                if (!isPoll || wasNearBottom) {
                    panel.scrollTop = panel.scrollHeight;
                }
                var actions = document.getElementById('whatsapp-chat-actions');
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

                // Handler UI: who owns this chat, and override/assign-back controls
                var handler = res.handler || currentHandler || { type: 'AI', id: null, name: 'AI' };
                currentHandler = handler;
                var replyForm = document.getElementById('whatsapp-reply-form');
                var replyFooter = replyForm ? replyForm.closest('.card-footer') : null;
                var canSend = handler.type === 'USER';
                if (replyFooter) {
                    replyFooter.style.display = canSend ? '' : 'none';
                }

                var handlerBadge = document.createElement('span');
                handlerBadge.className = 'badge bg-light text-dark';
                handlerBadge.textContent = handler.type === 'AI'
                    ? 'Handled by AI'
                    : 'Handled by ' + (handler.name || 'Agent');
                actions.appendChild(handlerBadge);

                if (handler.type === 'AI') {
                    var btnTake = document.createElement('button');
                    btnTake.type = 'button';
                    btnTake.className = 'btn btn-sm btn--primary ms-2';
                    btnTake.textContent = 'Override chat';
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
                                        'X-CSRF-TOKEN': csrf,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ phone: phone, mode: 'take' })
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
                            loadMessages(phone, false);
                        });
                    };
                    actions.appendChild(btnTake);
                } else if (handler.type === 'USER' && handler.id === currentAdminId) {
                    var btnAI = document.createElement('button');
                    btnAI.type = 'button';
                    btnAI.className = 'btn btn-sm btn--secondary ms-2';
                    btnAI.textContent = 'Assign back to AI';
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
                                        'X-CSRF-TOKEN': csrf,
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: JSON.stringify({ phone: phone, mode: 'ai' })
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
                            loadMessages(phone, false);
                        });
                    };
                    actions.appendChild(btnAI);
                }
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
        }, 3000);
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
                        // Re-bind click handlers
                        listContainer.querySelectorAll('.whatsapp-chat-item').forEach(function(el) {
                            el.addEventListener('click', function() {
                                openChat(this.getAttribute('data-phone'));
                            });
                        });
                    }
                  })
                  .catch(function() {});
            } catch (e) {}
        }, 5000);
    }

    document.querySelectorAll('.whatsapp-chat-item').forEach(function(el) {
        el.addEventListener('click', function() {
            openChat(this.getAttribute('data-phone'));
        });
    });

    var initialPhone = new URLSearchParams(window.location.search).get('phone');
    if (initialPhone) openChat(initialPhone);

    var replyFormEl = document.getElementById('whatsapp-reply-form');
    var replyBodyEl = document.getElementById('wa-reply-body');
    var attachmentInputEl = document.getElementById('wa-attachment-input');
    var attachmentNameEl = document.getElementById('wa-attachment-name');
    var attachmentPreviewEl = document.getElementById('wa-attachment-preview');
    var emojiToggleEl = document.getElementById('wa-emoji-toggle');
    var emojiPanelEl = document.getElementById('wa-emoji-panel');
    var sendBtnEl = replyFormEl ? replyFormEl.querySelector('button[type="submit"]') : null;
    var attachmentFiles = []; // keep our own list of files across changes

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
        var now = new Date();
        var time = now.toLocaleString();
        var safeBody = body.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\\n/g, '<br>');
        var tempId = 'wa-temp-' + Date.now();
        var statusSpan = null;
        // Optimistic block(s) for attachments + text
        if (filesToSend.length) {
            filesToSend.forEach(function (entry, index) {
                var file = entry.file;
                var wrap = document.createElement('div');
                wrap.className = 'mb-3 d-flex justify-content-end';
                var inner = '<div class=\"rounded px-3 py-2 bg-primary text-white\" style=\"max-width:85%\">' +
                    '<div class=\"fz-12 opacity-75\">OUT · ' + time + ' · <span data-temp-status=\"' + tempId + '\">Sending…</span></div>';
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
                '<div class=\"rounded px-3 py-2 bg-primary text-white\" style=\"max-width:85%\">' +
                    '<div class=\"fz-12 opacity-75\">OUT · ' + time + ' · <span data-temp-status=\"' + tempId + '\">Sending…</span></div>' +
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
            if (statusSpan) {
                statusSpan.textContent = sent ? 'Sent' : 'Failed';
            }
            if (typeof toastr !== 'undefined') {
                if (sent) {
                    toastr.success('Sent');
                } else {
                    toastr.warning('Saved, but WhatsApp API failed');
                }
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
                        <table class="table align-middle table-borderless">
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
                                    <td>{{ $lead->phone ?? '—' }}</td>
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
                    <div class="table-responsive">
                        <table class="table align-middle table-borderless">
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
                                    <td>{{ $booking->phone ?? '—' }}</td>
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
                    <div class="table-responsive">
                        <table class="table align-middle table-borderless">
                            <thead class="border-bottom">
                            <tr>
                                <th>{{ translate('Phone') }}</th>
                                <th>{{ translate('Name') }}</th>
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
                                    <td>{{ $waUser->phone ?? '—' }}</td>
                                    <td>{{ $waUser->name ?? '—' }}</td>
                                    <td>{{ $waUser->alternate_phone ?? '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($waUser->address ?? '—', 40) }}</td>
                                    <td><span class="badge bg-secondary">{{ $waUser->type ?? '—' }}</span></td>
                                    <td>{{ $waUser->created_at?->format('M j, H:i') ?? '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.whatsapp.conversations.index', ['tab' => 'chats', 'phone' => $waUser->phone]) }}" class="btn btn-sm btn--primary">{{ translate('View chat') }}</a>
                                        <button type="button" class="btn btn-sm btn--secondary wa-user-more" data-phone="{{ e($waUser->phone) }}">{{ translate('View more') }}</button>
                                    </td>
                                </tr>
                                @endforeach
                            <?php } else { ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">{{ translate('No WhatsApp users') }}</td>
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
@push('script')
                <script>
(function() {
    var modal = document.getElementById('waUserDetailsModal');
    var body = document.getElementById('waUserDetailsBody');
    var detailsUrl = '{{ route("admin.whatsapp.users.details") }}';
    if (modal && body) {
        document.querySelectorAll('.wa-user-more').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var phone = this.getAttribute('data-phone');
                if (!phone) return;
                body.innerHTML = '<div class="text-center py-4 text-muted">Loading…</div>';
                var bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                fetch(detailsUrl + '?phone=' + encodeURIComponent(phone))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.error) {
                            body.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed to load') + '</div>';
                            return;
                        }
                        var u = data.user || {};
                        var type = (u.type || '').toUpperCase();
                        var html = '<div class="row mb-3">';
                        html += '<div class="col-md-6"><strong>Phone</strong><br>' + (u.phone || '—') + '</div>';
                        html += '<div class="col-md-6"><strong>Name</strong><br>' + (u.name || '—') + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>Alternate phone</strong><br>' + (u.alternate_phone || '—') + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>Type</strong><br><span class="badge bg-secondary">' + (u.type || '—') + '</span></div>';
                        html += '<div class="col-12 mt-2"><strong>Address</strong><br>' + (u.address || '—') + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>Created</strong><br>' + (u.created_at || '—') + '</div>';
                        html += '<div class="col-md-6 mt-2"><strong>Updated</strong><br>' + (u.updated_at || '—') + '</div>';
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
})();
                </script>
                @endpush
            <?php endif; ?>
        </div>
    </div>
@endsection
