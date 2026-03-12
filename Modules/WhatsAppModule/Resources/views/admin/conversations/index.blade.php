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
            @if(($tab ?? '') === 'chats')
                <div class="row g-3">
                    <div class="col-md-4 col-lg-3 whatsapp-active-list-container">
                        <div class="card h-100 d-flex flex-column">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center gap-2">
                                <strong>{{ translate('Chats') }}</strong>
                                @php($handlerFilter = $handlerFilter ?? 'all')
                                @if(!empty($chatHandlers ?? null))
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
                                @endif
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
                                @can('whatsapp_chat_reply')
                                    <div class="card-footer border-top">
                                        <form id="whatsapp-reply-form" class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="phone" id="whatsapp-reply-phone" value="">
                                            <textarea name="body" class="form-control flex-grow-1" rows="2" required placeholder="{{ translate('Type your reply...') }}"></textarea>
                                            <button type="submit" class="btn btn--primary align-self-end">
                                                <span class="material-icons">send</span> {{ translate('Send') }}
                                            </button>
                                        </form>
                                    </div>
                                @endcan
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
                    html += '<div class="mt-1">' + body + '</div></div></div>';
                });
                panel.innerHTML = html || '<p class="text-muted text-center py-4">No messages yet</p>';
                panel.scrollTop = panel.scrollHeight;
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

    document.getElementById('whatsapp-reply-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var phone = document.getElementById('whatsapp-reply-phone').value;
        var body = this.querySelector('[name=\"body\"]').value;
        if (!phone || !body) return;

        // Optimistic UI: show message immediately with \"Sending…\" status.
        var panel = document.getElementById('whatsapp-chat-messages');
        var now = new Date();
        var time = now.toLocaleString();
        var safeBody = body.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\\n/g, '<br>');
        var tempId = 'wa-temp-' + Date.now();
        var wrapper = document.createElement('div');
        wrapper.className = 'mb-3 d-flex justify-content-end';
        wrapper.innerHTML =
            '<div class=\"rounded px-3 py-2 bg-primary text-white\" style=\"max-width:85%\">' +
                '<div class=\"fz-12 opacity-75\">OUT · ' + time + ' · <span data-temp-status=\"' + tempId + '\">Sending…</span></div>' +
                '<div class=\"mt-1\">' + safeBody + '</div>' +
            '</div>';
        panel.appendChild(wrapper);
        panel.scrollTop = panel.scrollHeight;
        var statusSpan = wrapper.querySelector('[data-temp-status=\"' + tempId + '\"]');

        var btn = this.querySelector('button[type=\"submit\"]');
        btn.disabled = true;
        fetch(replyUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ phone: phone, body: body, _token: csrf })
        })
        .then(function(r) { return r.json().catch(function() { return {}; }); })
        .then(function(res) {
            btn.disabled = false;
            this.querySelector('[name=\"body\"]').value = '';
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
        }.bind(this))
        .catch(function() {
            btn.disabled = false;
            if (statusSpan) {
                statusSpan.textContent = 'Failed';
            }
            if (typeof toastr !== 'undefined') toastr.error('Failed to send');
        });
    });
})();
                </script>
                @endpush
            @endif

            {{-- Tab: Provider Leads --}}
            @if(($tab ?? '') === 'leads')
                <div class="card">
                    @if(!empty($leadsError ?? null))
                        <div class="alert alert-warning m-3 mb-0">{{ translate('Could not load provider leads') }}: {{ $leadsError }}</div>
                    @endif
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
                            @if(($leads ?? collect())->isNotEmpty())
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
                            @else
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">{{ translate('No provider leads') }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                    @if(isset($leads) && $leads->hasPages())
                        <div class="card-footer border-0">{{ $leads->links() }}</div>
                    @endif
                </div>
            @endif

            {{-- Tab: Bookings --}}
            @if(($tab ?? '') === 'bookings')
                <div class="card">
                    @if(!empty($bookingsError ?? null))
                        <div class="alert alert-warning m-3 mb-0">{{ translate('Could not load bookings') }}: {{ $bookingsError }}</div>
                    @endif
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
                            @if(($bookings ?? collect())->isNotEmpty())
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
                            @else
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">{{ translate('No bookings') }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                    @if(isset($bookings) && $bookings->hasPages())
                        <div class="card-footer border-0">{{ $bookings->links() }}</div>
                    @endif
                </div>
            @endif

            {{-- Tab: WhatsApp Users (Neon DB only, separate from main app users) --}}
            @if(($tab ?? '') === 'users')
                <div class="card">
                    @if(!empty($usersError ?? null))
                        <div class="alert alert-warning m-3 mb-0">{{ translate('Could not load WhatsApp users') }}: {{ $usersError }}</div>
                    @endif
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
                            @if(($users ?? collect())->isNotEmpty())
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
                            @else
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">{{ translate('No WhatsApp users') }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                    @if(isset($users) && $users->hasPages())
                        <div class="card-footer border-0">{{ $users->links() }}</div>
                    @endif
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
            @endif
        </div>
    </div>
@endsection
