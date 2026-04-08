@php
    $geminiReady = !empty($runtime['gemini_key_set'] ?? false) && !empty($runtime['ai_support_enabled'] ?? false);
    $threadUrl = route('admin.whatsapp.ai-playground.thread');
    $runUrl = route('admin.whatsapp.ai-playground.run');
    $resetUrl = route('admin.whatsapp.ai-playground.reset');
@endphp

<div class="card border-0 shadow-sm mb-4 wa-playground-card">
    <div class="card-header bg-body border-bottom py-3 d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <strong>{{ __('whatsapp_ai.playground_chat_title') }}</strong>
            <div class="text-muted small fw-normal mt-1">{{ __('whatsapp_ai.playground_chat_intro') }}</div>
        </div>
    </div>
    <div class="card-body p-0">
        @if(!$geminiReady)
            <div class="alert alert-warning small m-3 mb-0">{{ __('whatsapp_ai.playground_gemini_required') }}</div>
        @endif

        <div class="wa-play-toolbar px-3 py-2 border-bottom bg-light d-flex flex-wrap align-items-end gap-2">
            <div class="flex-grow-1" style="min-width: 200px;">
                <label class="form-label small text-muted mb-0">{{ __('whatsapp_ai.playground_sandbox_phone') }}</label>
                <input type="text" class="form-control form-control-sm font-monospace" id="wa-play-phone"
                       value="{{ $playgroundDefaultPhone }}" autocomplete="off">
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="wa-play-load">{{ __('whatsapp_ai.playground_reload_thread') }}</button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="wa-play-reset">{{ __('whatsapp_ai.playground_reset_thread') }}</button>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.whatsapp.ai-settings.edit', ['tab' => 'executions']) }}">{{ __('whatsapp_ai.playground_view_executions') }}</a>
        </div>

        <div class="wa-play-window">
            <div class="wa-play-thread" id="wa-play-thread" aria-live="polite"></div>

            <div class="wa-play-quick-wrap px-3 py-2 border-top d-none" id="wa-play-quick-wrap">
                <div class="small text-muted mb-1">{{ __('whatsapp_ai.playground_quick_actions') }}</div>
                <div class="d-flex flex-wrap gap-2 align-items-center" id="wa-play-quick-buttons"></div>
                <div class="d-flex flex-wrap gap-2 mt-2" id="wa-play-url-row"></div>
                <div class="d-flex flex-wrap gap-2 mt-1" id="wa-play-phone-row"></div>
            </div>

            <div class="wa-play-presets px-3 py-2 border-top bg-body-secondary">
                <span class="small text-muted me-2">{{ __('whatsapp_ai.playground_presets') }}</span>
                @foreach($playgroundScenarios as $sc)
                    <button type="button" class="btn btn-sm btn-outline-secondary wa-play-preset" data-text="{{ $sc['text'] }}">{{ $sc['label'] }}</button>
                @endforeach
            </div>

            <div class="wa-play-composer p-3 border-top bg-light">
                <label class="form-label small text-muted mb-1">{{ __('whatsapp_ai.playground_type_message') }}</label>
                <div class="d-flex gap-2 align-items-end">
                    <textarea class="form-control wa-play-input" id="wa-play-input" rows="2" placeholder="{{ __('whatsapp_ai.playground_placeholder') }}"></textarea>
                    <button type="button" class="btn btn--primary shrink-0" id="wa-play-send" @if(!$geminiReady) disabled @endif>{{ __('whatsapp_ai.playground_send') }}</button>
                </div>
                <div class="small text-muted mt-2" id="wa-play-meta"></div>
            </div>
        </div>
    </div>
</div>

@push('css_or_js')
<style>
    .wa-play-window { background: #e5ddd5; background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,.08) 0, transparent 45%); }
    .wa-play-thread {
        height: min(52vh, 520px);
        overflow-y: auto;
        padding: 1rem 0.75rem 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .wa-play-row { display: flex; width: 100%; }
    .wa-play-row--in { justify-content: flex-end; }
    .wa-play-row--out { justify-content: flex-start; }
    .wa-play-bubble {
        max-width: min(92%, 420px);
        padding: 0.45rem 0.65rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        line-height: 1.45;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .wa-play-bubble--in {
        background: #dcf8c6;
        border: 1px solid rgba(0,0,0,.06);
        color: #111;
    }
    .wa-play-bubble--out {
        background: #fff;
        border: 1px solid rgba(0,0,0,.08);
        color: #111;
    }
    .wa-play-bubble--sys {
        background: rgba(255,255,255,.65);
        font-size: 0.8rem;
        color: #555;
        max-width: 100%;
        text-align: center;
    }
    .wa-play-chip {
        border-radius: 1.25rem;
        padding: 0.35rem 0.85rem;
        font-size: 0.8125rem;
        border: 1px solid #128c7e;
        background: #fff;
        color: #075e54;
        cursor: pointer;
    }
    .wa-play-chip:hover { background: #e8f5e9; }
    .wa-play-quick-wrap { background: #f8f9fa; }
</style>
@endpush

@push('script')
<script>
(function () {
    const threadUrl = @json($threadUrl);
    const runUrl = @json($runUrl);
    const resetUrl = @json($resetUrl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const runningLabel = @json(__('whatsapp_ai.playground_running'));

    function phoneVal() {
        return document.getElementById('wa-play-phone')?.value?.trim() || '';
    }

    function setMeta(t) {
        const el = document.getElementById('wa-play-meta');
        if (el) el.textContent = t || '';
    }

    function bubble(text, dir) {
        const row = document.createElement('div');
        row.className = 'wa-play-row wa-play-row--' + (dir === 'IN' ? 'in' : 'out');
        const b = document.createElement('div');
        b.className = 'wa-play-bubble wa-play-bubble--' + (dir === 'IN' ? 'in' : 'out');
        b.textContent = text;
        row.appendChild(b);
        return row;
    }

    function sysLine(text) {
        const row = document.createElement('div');
        row.className = 'wa-play-row';
        row.style.justifyContent = 'center';
        const b = document.createElement('div');
        b.className = 'wa-play-bubble wa-play-bubble--sys';
        b.textContent = text;
        row.appendChild(b);
        return row;
    }

    function renderInteractive(snap) {
        const wrap = document.getElementById('wa-play-quick-wrap');
        const btns = document.getElementById('wa-play-quick-buttons');
        const urls = document.getElementById('wa-play-url-row');
        const phones = document.getElementById('wa-play-phone-row');
        if (!wrap || !btns || !urls || !phones) return;
        btns.innerHTML = '';
        urls.innerHTML = '';
        phones.innerHTML = '';
        if (!snap || typeof snap !== 'object') {
            wrap.classList.add('d-none');
            return;
        }
        const qrs = snap.quick_replies || [];
        const us = snap.urls || [];
        const ps = snap.phones || [];
        if (qrs.length === 0 && us.length === 0 && ps.length === 0) {
            wrap.classList.add('d-none');
            return;
        }
        wrap.classList.remove('d-none');
        qrs.forEach(function (qr) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'wa-play-chip';
            btn.textContent = qr.title;
            btn.title = qr.id;
            btn.addEventListener('click', function () {
                const t = (qr.title || '').trim() + ' [' + (qr.id || '') + ']';
                sendRaw(t);
            });
            btns.appendChild(btn);
        });
        us.forEach(function (u) {
            const a = document.createElement('a');
            a.href = u.url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.className = 'btn btn-sm btn-outline-primary';
            a.textContent = u.label;
            urls.appendChild(a);
        });
        ps.forEach(function (p) {
            const a = document.createElement('a');
            a.href = 'tel:' + encodeURIComponent(p.phone);
            a.className = 'btn btn-sm btn-outline-secondary';
            a.textContent = p.label + ' · ' + p.phone;
            phones.appendChild(a);
        });
    }

    async function fetchJson(url, opts) {
        const res = await fetch(url, opts);
        const raw = await res.text();
        let data = {};
        try {
            data = raw ? JSON.parse(raw) : {};
        } catch (e) {
            throw new Error('HTTP ' + res.status + ' — not JSON');
        }
        if (!res.ok) {
            throw new Error(data.message || data.error || ('HTTP ' + res.status));
        }
        if (data.ok === false) {
            throw new Error(data.error || data.message || 'Request failed');
        }
        return data;
    }

    function scrollThreadBottom() {
        const el = document.getElementById('wa-play-thread');
        if (el) el.scrollTop = el.scrollHeight;
    }

    async function loadThread() {
        const ph = phoneVal();
        const url = threadUrl + (ph ? ('?phone=' + encodeURIComponent(ph)) : '');
        setMeta(@json(__('whatsapp_ai.playground_loading')));
        try {
            const data = await fetchJson(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!data.ok) {
                setMeta(data.error || 'Error');
                return;
            }
            const thread = document.getElementById('wa-play-thread');
            if (!thread) return;
            thread.innerHTML = '';
            (data.messages || []).forEach(function (m) {
                const dir = (m.direction || '').toUpperCase() === 'IN' ? 'IN' : 'OUT';
                thread.appendChild(bubble(m.text || '', dir));
            });
            renderInteractive(data.last_interactive);
            setMeta('');
            scrollThreadBottom();
        } catch (e) {
            setMeta(String(e.message || e));
        }
    }

    async function sendRaw(text) {
        const ph = phoneVal();
        if (!text || !String(text).trim()) return;
        const thread = document.getElementById('wa-play-thread');
        if (thread) {
            thread.appendChild(bubble(String(text).trim(), 'IN'));
            scrollThreadBottom();
        }
        setMeta(runningLabel);
        try {
            const data = await fetchJson(runUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ message: text, phone: ph || null }),
            });
            if (!data.ok) {
                if (thread) thread.appendChild(sysLine(data.error || 'Error'));
                setMeta('');
                scrollThreadBottom();
                return;
            }
            if (data.playground_warning && thread) {
                const w = document.createElement('div');
                w.className = 'wa-play-row';
                w.style.justifyContent = 'center';
                const b = document.createElement('div');
                b.className = 'alert alert-warning py-2 px-3 small mb-0 text-start';
                b.style.maxWidth = '95%';
                b.textContent = data.playground_warning;
                w.appendChild(b);
                thread.appendChild(w);
            }
            if (thread) {
                const reply = data.reply_text || '(no reply text)';
                thread.appendChild(bubble(reply, 'OUT'));
            }
            renderInteractive(data.interactive);
            setMeta('inbound #' + (data.inbound_message_id || '—') + ' · execution #' + (data.execution_id || '—') + ' · ' + (data.execution_outcome || '—'));
            scrollThreadBottom();
        } catch (e) {
            if (thread) thread.appendChild(sysLine(String(e.message || e)));
            setMeta('');
            scrollThreadBottom();
        }
    }

    function sendFromInput() {
        const ta = document.getElementById('wa-play-input');
        const t = ta ? ta.value : '';
        if (!String(t).trim()) return;
        if (ta) ta.value = '';
        sendRaw(t);
    }

    document.getElementById('wa-play-send')?.addEventListener('click', sendFromInput);
    document.getElementById('wa-play-input')?.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendFromInput();
        }
    });
    document.getElementById('wa-play-load')?.addEventListener('click', loadThread);
    document.querySelectorAll('.wa-play-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const ta = document.getElementById('wa-play-input');
            if (ta) ta.value = btn.getAttribute('data-text') || '';
            ta?.focus();
        });
    });

    document.getElementById('wa-play-reset')?.addEventListener('click', async function () {
        const ph = phoneVal();
        if (!confirm(@json(__('whatsapp_ai.playground_reset_confirm')))) return;
        try {
            await fetchJson(resetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ phone: ph || null }),
            });
            document.getElementById('wa-play-thread').innerHTML = '';
            renderInteractive(null);
            setMeta(@json(__('whatsapp_ai.playground_reset_done')));
        } catch (e) {
            setMeta(String(e.message || e));
        }
    });

    document.getElementById('wa-play-phone')?.addEventListener('change', loadThread);

    loadThread();
})();
</script>
@endpush
