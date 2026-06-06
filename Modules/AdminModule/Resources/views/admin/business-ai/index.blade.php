@extends('adminmodule::layouts.new-master')

@section('title', translate('Talk_With_AI'))

@push('css_or_js')
<style>
    /*
     * .main-area is flex column + 100vh. Fit page header, chat card, and dashboard
     * footer in the viewport; only the message thread scrolls.
     */
    .main-area:has(> .container-fluid.biz-ai-page) {
        overflow: hidden;
    }
    .main-area > footer.footer {
        flex-shrink: 0;
    }
    .biz-ai-page {
        flex: 1 1 auto;
        min-height: 0;
        max-height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding-bottom: 0;
    }
    .biz-ai-page-header {
        flex-shrink: 0;
    }
    .biz-ai-card {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .biz-ai-card > .card-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }
    .biz-ai-thread {
        flex: 1 1 0%;
        height: 0;
        min-height: 8rem;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-y: contain;
        padding: 1.25rem;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    }
    .biz-ai-chat-footer {
        flex-shrink: 0;
        margin-top: auto;
        background: #fff;
    }
    .biz-ai-card > .card-body > .alert {
        flex-shrink: 0;
    }
    .biz-ai-row {
        display: flex;
        width: 100%;
        margin-bottom: 0.75rem;
    }
    .biz-ai-row--user { justify-content: flex-end; }
    .biz-ai-row--assistant { justify-content: flex-start; }
    .biz-ai-bubble {
        max-width: min(92%, 680px);
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.9rem;
        line-height: 1.55;
        word-break: break-word;
    }
    .biz-ai-bubble--user {
        background: #0f766e;
        color: #fff;
        border-bottom-right-radius: 0.2rem;
        white-space: pre-wrap;
    }
    .biz-ai-bubble--assistant {
        background: #fff;
        color: #0f172a;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 0.2rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        max-width: min(96%, 820px);
        padding: 1rem 1.15rem;
    }
    .biz-ai-bubble--welcome {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
        max-width: 100%;
        white-space: pre-wrap;
    }
    .biz-ai-rich > *:first-child { margin-top: 0; }
    .biz-ai-rich > *:last-child { margin-bottom: 0; }
    .biz-ai-rich h4.biz-ai-h,
    .biz-ai-rich h5.biz-ai-h,
    .biz-ai-rich h6.biz-ai-h {
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #4338ca;
        margin: 1rem 0 0.5rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid #e0e7ff;
    }
    .biz-ai-rich h4.biz-ai-h:first-child,
    .biz-ai-rich h5.biz-ai-h:first-child,
    .biz-ai-rich h6.biz-ai-h:first-child { margin-top: 0; }
    .biz-ai-rich p {
        margin: 0 0 0.65rem;
        line-height: 1.6;
        color: #334155;
    }
    .biz-ai-rich ul.biz-ai-ul,
    .biz-ai-rich ol.biz-ai-ol {
        margin: 0.35rem 0 0.75rem;
        padding-left: 1.25rem;
    }
    .biz-ai-rich ul.biz-ai-ul li,
    .biz-ai-rich ol.biz-ai-ol li {
        margin-bottom: 0.35rem;
        line-height: 1.55;
        color: #334155;
    }
    .biz-ai-rich strong {
        color: #0f172a;
        font-weight: 600;
    }
    .biz-ai-rich .biz-ai-metric {
        display: inline-block;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        font-weight: 600;
        padding: 0.1rem 0.45rem;
        border-radius: 0.35rem;
        font-size: 0.85em;
    }
    .biz-ai-rich .biz-ai-priority-high {
        display: inline-block;
        background: #fef2f2;
        color: #b91c1c;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.1rem 0.4rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        margin-right: 0.35rem;
    }
    .biz-ai-rich .biz-ai-priority-medium {
        display: inline-block;
        background: #fffbeb;
        color: #b45309;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.1rem 0.4rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        margin-right: 0.35rem;
    }
    .biz-ai-rich .biz-ai-priority-low {
        display: inline-block;
        background: #f0f9ff;
        color: #0369a1;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.1rem 0.4rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        margin-right: 0.35rem;
    }
    .biz-ai-thinking {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
    }
    .biz-ai-thinking-dots span {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #94a3b8;
        animation: bizAiPulse 1.2s infinite ease-in-out;
    }
    .biz-ai-thinking-dots span:nth-child(2) { animation-delay: 0.15s; }
    .biz-ai-thinking-dots span:nth-child(3) { animation-delay: 0.3s; }
    @keyframes bizAiPulse {
        0%, 80%, 100% { opacity: 0.35; transform: scale(0.85); }
        40% { opacity: 1; transform: scale(1); }
    }
    .biz-ai-composer {
        border-top: 1px solid #e2e8f0;
        background: #fff;
        padding: 1rem 1.25rem;
        flex-shrink: 0;
    }
    .biz-ai-presets {
        flex-shrink: 0;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 0.35rem;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }
    .biz-ai-presets > .small {
        flex-shrink: 0;
    }
    .biz-ai-presets .btn {
        flex-shrink: 0;
        white-space: nowrap;
        font-size: 0.78rem;
    }
    .biz-ai-header-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="container-fluid biz-ai-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2 biz-ai-page-header">
        <div class="d-flex align-items-center gap-3">
            <span class="biz-ai-header-icon" aria-hidden="true">
                <span class="material-symbols-outlined" style="font-size: 1.25rem;">psychology</span>
            </span>
            <div>
                <h4 class="mb-0">{{ translate('Talk_With_AI') }}</h4>
                <p class="text-muted small mb-0">{{ __('admin_business_ai.page_subtitle') }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-danger btn-sm" id="biz-ai-reset">{{ __('admin_business_ai.clear_chat') }}</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">{{ translate('dashboard') }}</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm biz-ai-card mb-0">
        <div class="card-body p-0 d-flex flex-column">
            @if(!$enabled)
                <div class="alert alert-warning m-3 mb-0">{{ __('admin_business_ai.disabled') }}</div>
            @elseif(!$geminiReady)
                <div class="alert alert-warning m-3 mb-0">{{ __('admin_business_ai.missing_api_key') }}</div>
            @endif

            <div class="biz-ai-thread" id="biz-ai-thread" aria-live="polite">
                <div class="biz-ai-row biz-ai-row--assistant">
                    <div class="biz-ai-bubble biz-ai-bubble--welcome">{{ __('admin_business_ai.welcome_message') }}</div>
                </div>
            </div>

            <div class="biz-ai-chat-footer">
                <div class="px-3 py-2 border-top bg-body-secondary biz-ai-presets">
                    <span class="small text-muted me-2">{{ __('admin_business_ai.suggested_questions') }}</span>
                    @foreach(__('admin_business_ai.presets') as $preset)
                        <button type="button" class="btn btn-sm btn-outline-secondary biz-ai-preset" data-text="{{ $preset }}">{{ $preset }}</button>
                    @endforeach
                </div>

                <div class="biz-ai-composer">
                    <div class="d-flex gap-2 align-items-end">
                        <textarea class="form-control" id="biz-ai-input" rows="2" placeholder="{{ __('admin_business_ai.input_placeholder') }}" @if(!$enabled || !$geminiReady) disabled @endif></textarea>
                        <button type="button" class="btn btn--primary shrink-0" id="biz-ai-send" @if(!$enabled || !$geminiReady) disabled @endif>
                            <span class="material-symbols-outlined" style="font-size: 1.1rem; vertical-align: middle;">send</span>
                            {{ __('admin_business_ai.send') }}
                        </button>
                    </div>
                    <div class="small text-muted mt-2" id="biz-ai-meta"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    const messagesUrl = @json(route('admin.business-ai.messages'));
    const chatUrl = @json(route('admin.business-ai.chat'));
    const resetUrl = @json(route('admin.business-ai.reset'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const thinkingLabel = @json(__('admin_business_ai.thinking'));
    const resetConfirm = @json(__('admin_business_ai.reset_confirm'));

    function setMeta(text) {
        const el = document.getElementById('biz-ai-meta');
        if (el) el.textContent = text || '';
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderInlineMarkdown(line) {
        let s = escapeHtml(line);
        s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        s = s.replace(/__(.+?)__/g, '<strong>$1</strong>');
        s = s.replace(/`([^`]+)`/g, '<code class="biz-ai-code">$1</code>');
        s = s.replace(/\(High\)/gi, '<span class="biz-ai-priority-high">High</span>');
        s = s.replace(/\(Medium\)/gi, '<span class="biz-ai-priority-medium">Medium</span>');
        s = s.replace(/\(Low\)/gi, '<span class="biz-ai-priority-low">Low</span>');
        s = s.replace(/((?:₹|INR|USD|\$)\s*[\d,.]+(?:\.\d+)?|\b\d{1,3}(?:,\d{3})+(?:\.\d+)?\b|\b\d+(?:\.\d+)?%)/g, function (match) {
            return '<span class="biz-ai-metric">' + match.trim() + '</span>';
        });
        return s;
    }

    function renderAssistantHtml(text) {
        const lines = String(text || '').replace(/\r\n/g, '\n').split('\n');
        const parts = [];
        let listType = null;
        let listItems = [];

        function flushList() {
            if (!listItems.length) return;
            const tag = listType === 'ol' ? 'ol' : 'ul';
            const cls = listType === 'ol' ? 'biz-ai-ol' : 'biz-ai-ul';
            parts.push('<' + tag + ' class="' + cls + '">' + listItems.join('') + '</' + tag + '>');
            listItems = [];
            listType = null;
        }

        lines.forEach(function (line) {
            const trimmed = line.trim();
            if (trimmed === '') {
                flushList();
                return;
            }
            const h4 = trimmed.match(/^#{1}\s+(.+)$/);
            const h5 = trimmed.match(/^#{2,3}\s+(.+)$/);
            const bullet = trimmed.match(/^[-*•]\s+(.+)$/);
            const numbered = trimmed.match(/^\d+\.\s+(.+)$/);

            if (h4) {
                flushList();
                parts.push('<h4 class="biz-ai-h">' + renderInlineMarkdown(h4[1]) + '</h4>');
                return;
            }
            if (h5) {
                flushList();
                parts.push('<h5 class="biz-ai-h">' + renderInlineMarkdown(h5[1]) + '</h5>');
                return;
            }
            if (bullet) {
                if (listType !== 'ul') {
                    flushList();
                    listType = 'ul';
                }
                listItems.push('<li>' + renderInlineMarkdown(bullet[1]) + '</li>');
                return;
            }
            if (numbered) {
                if (listType !== 'ol') {
                    flushList();
                    listType = 'ol';
                }
                listItems.push('<li>' + renderInlineMarkdown(numbered[1]) + '</li>');
                return;
            }
            flushList();
            parts.push('<p>' + renderInlineMarkdown(trimmed) + '</p>');
        });
        flushList();
        return '<div class="biz-ai-rich">' + parts.join('') + '</div>';
    }

    function bubble(text, role) {
        const row = document.createElement('div');
        row.className = 'biz-ai-row biz-ai-row--' + (role === 'user' ? 'user' : 'assistant');
        const b = document.createElement('div');
        b.className = 'biz-ai-bubble biz-ai-bubble--' + (role === 'user' ? 'user' : 'assistant');
        if (role === 'assistant') {
            b.innerHTML = renderAssistantHtml(text);
        } else {
            b.textContent = text;
        }
        row.appendChild(b);
        return row;
    }

    function thinkingBubble() {
        const row = document.createElement('div');
        row.className = 'biz-ai-row biz-ai-row--assistant';
        row.id = 'biz-ai-thinking-row';
        const b = document.createElement('div');
        b.className = 'biz-ai-thinking';
        b.innerHTML = '<span>' + thinkingLabel + '</span><span class="biz-ai-thinking-dots"><span></span><span></span><span></span></span>';
        row.appendChild(b);
        return row;
    }

    function removeThinking() {
        document.getElementById('biz-ai-thinking-row')?.remove();
    }

    function scrollBottom() {
        const el = document.getElementById('biz-ai-thread');
        if (el) el.scrollTop = el.scrollHeight;
    }

    async function fetchJson(url, opts) {
        const res = await fetch(url, opts);
        const raw = await res.text();
        let data = {};
        try { data = raw ? JSON.parse(raw) : {}; } catch (e) {
            throw new Error('HTTP ' + res.status);
        }
        if (!res.ok || data.ok === false) {
            throw new Error(data.error || data.message || ('HTTP ' + res.status));
        }
        return data;
    }

    async function loadThread() {
        try {
            const data = await fetchJson(messagesUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const thread = document.getElementById('biz-ai-thread');
            if (!thread) return;
            const msgs = data.messages || [];
            if (msgs.length === 0) return;
            thread.innerHTML = '';
            thread.appendChild(bubble(@json(__('admin_business_ai.welcome_message')), 'assistant'));
            msgs.forEach(function (m) {
                const role = m.role === 'assistant' ? 'assistant' : 'user';
                thread.appendChild(bubble(m.text || '', role));
            });
            scrollBottom();
        } catch (e) {
            setMeta(String(e.message || e));
        }
    }

    async function sendMessage(text) {
        const trimmed = String(text || '').trim();
        if (!trimmed) return;

        const thread = document.getElementById('biz-ai-thread');
        const input = document.getElementById('biz-ai-input');
        const sendBtn = document.getElementById('biz-ai-send');

        if (thread) {
            thread.appendChild(bubble(trimmed, 'user'));
            thread.appendChild(thinkingBubble());
        }
        if (input) input.value = '';
        if (sendBtn) sendBtn.disabled = true;
        setMeta('');
        scrollBottom();

        try {
            const data = await fetchJson(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ message: trimmed }),
            });
            removeThinking();
            if (thread) thread.appendChild(bubble(data.reply || '', 'assistant'));
            scrollBottom();
        } catch (e) {
            removeThinking();
            if (thread) {
                const errRow = document.createElement('div');
                errRow.className = 'biz-ai-row biz-ai-row--assistant';
                const errBubble = document.createElement('div');
                errBubble.className = 'biz-ai-bubble biz-ai-bubble--assistant text-danger';
                errBubble.textContent = String(e.message || e);
                errRow.appendChild(errBubble);
                thread.appendChild(errRow);
            }
            setMeta('');
            scrollBottom();
        } finally {
            if (sendBtn) sendBtn.disabled = false;
        }
    }

    document.getElementById('biz-ai-send')?.addEventListener('click', function () {
        const input = document.getElementById('biz-ai-input');
        sendMessage(input ? input.value : '');
    });

    document.getElementById('biz-ai-input')?.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(e.target.value);
        }
    });

    document.querySelectorAll('.biz-ai-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById('biz-ai-input');
            if (input) {
                input.value = btn.getAttribute('data-text') || '';
                input.focus();
            }
        });
    });

    document.getElementById('biz-ai-reset')?.addEventListener('click', async function () {
        if (!confirm(resetConfirm)) return;
        try {
            await fetchJson(resetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            });
            const thread = document.getElementById('biz-ai-thread');
            if (thread) {
                thread.innerHTML = '';
                thread.appendChild(bubble(@json(__('admin_business_ai.welcome_message')), 'assistant'));
            }
            setMeta('');
        } catch (e) {
            setMeta(String(e.message || e));
        }
    });

    loadThread();
})();
</script>
@endpush
