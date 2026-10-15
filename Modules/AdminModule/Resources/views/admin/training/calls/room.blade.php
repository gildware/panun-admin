@extends('adminmodule::layouts.new-master')

@section('title', $scenario['name'])

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
    @include('adminmodule::admin.training.calls._styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">{{ $scenario['name'] }}</h2>
                    <div class="trn-meta mt-1">{{ $assignment->test->name }}. The caller speaks Urdu, Hindi, and English. Pick the microphone language, then answer the way you would on a real call.</div>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.show', $assignment) }}">Leave</a>
            </div>

            <div class="call-layout" id="call-room"
                 data-start="{{ route('admin.training.calls.start', $assignment) }}"
                 data-turn="{{ route('admin.training.calls.turn', $assignment) }}"
                 data-voice="{{ route('admin.training.calls.voice', $assignment) }}"
                 data-finish="{{ route('admin.training.calls.finish', $assignment) }}"
                 data-scenario="{{ $scenarioId }}"
                 data-fresh="{{ request()->boolean('fresh') ? '1' : '0' }}"
                 data-caller="{{ $scenario['caller_name'] }}"
                 data-lang="{{ $assignment->test->default_language ?: 'en' }}"
                 data-speed="{{ ($assignment->test->speed ?? '') === 'slow' ? '0.86' : (($assignment->test->speed ?? '') === 'fast' ? '1.15' : '1') }}"
                 data-max="{{ (int) ($assignment->test->max_minutes ?: 8) }}">
                <section class="call-phone" aria-label="Call">
                    <div class="call-kicker">{{ $scenario['caller_role'] }}</div>
                    <h2>{{ $scenario['caller_name'] }}</h2>
                    <div class="call-avatar" id="call-avatar">{{ mb_substr($scenario['caller_name'], 0, 1) }}</div>
                    <div class="call-timer" id="call-timer">00:00</div>
                    <div class="call-status is-wait" id="call-status" aria-live="polite">Connecting</div>
                    <p class="call-note" id="call-note" hidden></p>
                    <div class="call-langs" id="call-langs" role="group" aria-label="Microphone language">
                        <button type="button" data-lang="en">English</button>
                        <button type="button" data-lang="hi">Hindi</button>
                        <button type="button" data-lang="ur">Urdu</button>
                    </div>
                    <button class="btn-end" type="button" id="call-end" disabled>End call</button>
                </section>
                <section class="call-log" aria-label="Transcript">
                    <div class="call-lines" id="call-lines"></div>
                    <div class="call-heard" id="call-heard"></div>
                    <form class="call-compose" id="call-type">
                        <input class="form-control" id="call-input" type="text" placeholder="Type if the microphone cannot be used" autocomplete="off">
                        <button class="btn btn--primary btn-sm" type="submit">Send</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const root = document.getElementById('call-room');
            if (!root || root.dataset.booted === '1') return;
            root.dataset.booted = '1';

            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const linesEl = document.getElementById('call-lines');
            const heardEl = document.getElementById('call-heard');
            const statusEl = document.getElementById('call-status');
            const timerEl = document.getElementById('call-timer');
            const avatarEl = document.getElementById('call-avatar');
            const endBtn = document.getElementById('call-end');
            const typeForm = document.getElementById('call-type');
            const typeInput = document.getElementById('call-input');
            const caller = root.dataset.caller || 'Caller';

            let transcript = [];
            let sending = false;
            let sessionId = null;
            let speaking = false;
            let finishing = false;
            let ended = false;
            let buffer = '';
            let sendTimer = null;
            let startedAt = Date.now();
            let recognition = null;
            let player = null;
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            let listenLang = root.dataset.lang || 'en';
            if (listenLang !== 'en' && listenLang !== 'hi' && listenLang !== 'ur') listenLang = 'en';
            const voiceLang = { en: 'en-IN', hi: 'hi-IN', ur: 'ur-PK' };
            const maxSeconds = Math.max(60, (parseInt(root.dataset.max || '8', 10) || 8) * 60);
            const playback = parseFloat(root.dataset.speed || '1') || 1;

            function showCallerNote(data) {
                if (data.source !== 'script') return;
                const note = document.getElementById('call-note');
                note.hidden = false;
                note.textContent = data.reason === 'credits'
                    ? 'Gemini has no credits left, so this call is the saved scenario. Add credits in Google AI Studio, then start the call again.'
                    : 'The live caller is unavailable, so this call is using the saved scenario.';
            }

            function setBusy(busy) {
                typeInput.disabled = busy;
                typeForm.querySelector('button').disabled = busy;
            }

            function setStatus(text, mode) {
                statusEl.textContent = text;
                statusEl.classList.toggle('is-listen', mode === 'listen');
                statusEl.classList.toggle('is-talk', mode === 'talk');
                statusEl.classList.toggle('is-wait', mode === 'wait');
                avatarEl.classList.toggle('is-talking', mode === 'talk');
                avatarEl.classList.toggle('is-listen', mode === 'listen');
                setBusy(mode === 'wait' || mode === 'talk');
            }

            function render(rows) {
                transcript = (rows || []).filter(function (row) { return !row.pending; });
                linesEl.innerHTML = '';
                (rows || []).forEach(function (row) {
                    const bubble = document.createElement('div');
                    const mine = row.role === 'employee';
                    bubble.className = 'call-bubble ' + (mine ? 'is-employee' : 'is-customer') + (row.pending ? ' is-pending' : '');
                    const who = document.createElement('small');
                    who.textContent = mine ? 'You' : caller;
                    bubble.appendChild(who);
                    bubble.appendChild(document.createTextNode(row.text || ''));
                    linesEl.appendChild(bubble);
                });
                linesEl.scrollTop = linesEl.scrollHeight;
            }

            async function post(url, body) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(body)
                });
                const data = await response.json().catch(function () { return {}; });
                if (!response.ok) {
                    throw new Error(data.message || 'The call could not continue.');
                }
                return data;
            }

            async function loadVoice(line, lang) {
                if (!line) return {};
                setStatus('Loading voice', 'wait');
                try {
                    return await post(root.dataset.voice, {
                        line: line,
                        lang: lang || 'en',
                        scenario: root.dataset.scenario
                    });
                } catch (error) {
                    return {};
                }
            }

            function speak(audio, mime) {
                return new Promise(function (resolve) {
                    if (!audio) {
                        resolve();
                        return;
                    }
                    if (player) {
                        player.pause();
                        player = null;
                    }
                    player = new Audio('data:' + (mime || 'audio/wav') + ';base64,' + audio);
                    player.playbackRate = playback;
                    speaking = true;
                    setStatus(caller + ' is speaking', 'talk');
                    stopListening();
                    player.onended = function () { speaking = false; resolve(); };
                    player.onerror = function () { speaking = false; resolve(); };
                    player.play().catch(function () { speaking = false; resolve(); });
                });
            }

            function stopListening() {
                clearTimeout(sendTimer);
                if (recognition) {
                    recognition.onresult = null;
                    recognition.onend = null;
                    try { recognition.stop(); } catch (error) {}
                }
            }

            function listen() {
                if (ended || speaking || !recognition) {
                    if (!recognition && !ended) setStatus('Listening. Type if the microphone is off.', 'listen');
                    return;
                }
                buffer = '';
                heardEl.textContent = '';
                setStatus('Listening', 'listen');
                recognition.onresult = function (event) {
                    let interim = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const piece = event.results[i][0].transcript;
                        if (event.results[i].isFinal) buffer = (buffer + ' ' + piece).trim();
                        else interim += piece;
                    }
                    heardEl.textContent = buffer || interim;
                    if (interim && !buffer) setStatus('Listening', 'listen');
                    if (buffer) {
                        clearTimeout(sendTimer);
                        sendTimer = setTimeout(function () { sendTurn(buffer); }, 700);
                    }
                };
                recognition.onerror = function () {};
                recognition.onend = function () {
                    if (!ended && !speaking && !finishing) {
                        try { recognition.start(); } catch (error) {}
                    }
                };
                try { recognition.start(); } catch (error) {}
            }

            async function sendTurn(text) {
                const spoken = (text || '').trim();
                if (!spoken || ended || finishing || sending || !sessionId) return;
                sending = true;
                buffer = '';
                heardEl.textContent = '';
                stopListening();
                render(transcript.concat([
                    { role: 'employee', text: spoken },
                    { role: 'customer', text: 'Processing…', pending: true }
                ]));
                setStatus('Processing', 'wait');
                try {
                    const data = await post(root.dataset.turn, { session_id: sessionId, text: spoken, listen: listenLang });
                    showCallerNote(data);
                    render(data.transcript);
                    const clip = await loadVoice(data.line, data.lang);
                    await speak(clip.audio, clip.mime);
                    if (ended) return;
                    if (data.done) {
                        await finish();
                        return;
                    }
                    listen();
                } catch (error) {
                    render(transcript);
                    setStatus(error.message, null);
                    listen();
                } finally {
                    sending = false;
                }
            }

            async function finish() {
                if (finishing || !sessionId) return;
                finishing = true;
                ended = true;
                endBtn.disabled = true;
                stopListening();
                if (player) {
                    player.pause();
                    player = null;
                }
                setStatus('Scoring the call', 'wait');
                try {
                    const data = await post(root.dataset.finish, { session_id: sessionId });
                    window.location.assign(data.url);
                } catch (error) {
                    finishing = false;
                    ended = false;
                    endBtn.disabled = false;
                    setStatus(error.message, null);
                }
            }

            function tick() {
                const seconds = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
                const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
                timerEl.textContent = minutes + ':' + String(seconds % 60).padStart(2, '0');
                if (!ended && !finishing && sessionId && seconds >= maxSeconds) {
                    setStatus('Time is up', null);
                    finish();
                }
            }

            endBtn.addEventListener('click', finish);
            typeForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const text = typeInput.value;
                typeInput.value = '';
                sendTurn(text);
            });

            function applyListen(lang) {
                listenLang = voiceLang[lang] ? lang : 'en';
                if (recognition) recognition.lang = voiceLang[listenLang];
                document.querySelectorAll('#call-langs button').forEach(function (button) {
                    button.classList.toggle('is-on', button.dataset.lang === listenLang);
                });
            }

            document.getElementById('call-langs').addEventListener('click', function (event) {
                const button = event.target.closest('[data-lang]');
                if (!button || speaking || finishing) return;
                applyListen(button.dataset.lang);
                if (!ended && recognition) {
                    stopListening();
                    listen();
                }
            });

            if (SpeechRecognition) {
                recognition = new SpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = true;
            }
            applyListen(listenLang);
            setInterval(tick, 1000);

            (async function boot() {
                setStatus('Connecting', 'wait');
                try {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        stream.getTracks().forEach(function (track) { track.stop(); });
                    }
                } catch (error) {
                    recognition = null;
                }
                try {
                    const data = await post(root.dataset.start, {
                        scenario: root.dataset.scenario,
                        fresh: root.dataset.fresh === '1',
                        listen: listenLang
                    });
                    sessionId = data.session_id;
                    startedAt = Date.now();
                    showCallerNote(data);
                    render(data.transcript);
                    endBtn.disabled = false;
                    if (data.speak) {
                        const clip = await loadVoice(data.speak, data.lang);
                        await speak(clip.audio, clip.mime);
                    }
                    if (!ended) listen();
                } catch (error) {
                    setStatus(error.message, null);
                }
            })();
        })();
    </script>
@endpush
