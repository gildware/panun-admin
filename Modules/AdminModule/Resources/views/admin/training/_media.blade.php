@php
    $embed = $lesson->embedUrl();
    $playback = $lesson->playbackUrl();
    $youtube = is_string($embed) && str_contains($embed, 'youtube');
    $vimeo = is_string($embed) && str_contains($embed, 'vimeo');
@endphp
<div class="lms-play" data-media-lock>
    @if($embed)
        <div class="lms-frame mb-2">
            <iframe
                data-media-embed
                @if($youtube) data-media-youtube @endif
                @if($vimeo) data-media-vimeo @endif
                src="{{ $embed }}{{ str_contains($embed, '?') ? '&' : '?' }}{{ $youtube ? 'enablejsapi=1&controls=0&disablekb=1&fs=0&rel=0&modestbranding=1&playsinline=1' : 'controls=0&keyboard=0&dnt=1' }}"
                title="{{ $lesson->title }}"
                allow="autoplay; encrypted-media; picture-in-picture"
            ></iframe>
        </div>
    @elseif($playback)
        @if($lesson->type === 'audio')
            <audio data-media-el preload="auto" src="{{ $playback }}"></audio>
        @else
            <div class="lms-frame">
                <video data-media-el preload="auto" playsinline disablepictureinpicture src="{{ $playback }}"></video>
            </div>
        @endif
        <div class="lms-play-bar">
            <button class="lms-play-btn" type="button" data-media-toggle aria-label="Play">
                <span class="material-icons">play_arrow</span>
            </button>
            <button class="lms-play-btn" type="button" data-media-rewind aria-label="Rewind 10 seconds">
                <span class="material-icons">replay_10</span>
            </button>
            <div class="lms-play-track" data-media-track><span data-media-fill></span></div>
            <button class="lms-play-btn" type="button" data-media-mute aria-label="Mute">
                <span class="material-icons">volume_up</span>
            </button>
            <input class="lms-play-volume" type="range" min="0" max="1" step="0.05" value="1" data-media-volume aria-label="Volume">
            <span class="lms-play-status" data-media-status hidden></span>
            <span class="lms-play-clock" data-media-clock>0:00</span>
        </div>
    @endif
</div>
<script>
    (function () {
        document.querySelectorAll('[data-media-lock]').forEach(function (box) {
            if (box.getAttribute('data-media-ready') === '1') {
                return;
            }
            box.setAttribute('data-media-ready', '1');
            var el = box.querySelector('[data-media-el]');
            if (el) {
                lockFile(box, el);
                return;
            }
            if (box.querySelector('[data-media-youtube]')) {
                lockYoutube(box);
            } else if (box.querySelector('[data-media-vimeo]')) {
                lockVimeo(box);
            }
        });

        function finish() {
            var choice = document.querySelector('[data-media-choice]');
            if (choice) {
                choice.hidden = false;
            }
        }

        function bindReplay(replay) {
            setTimeout(function () {
                var again = document.querySelector('[data-media-again]');
                if (!again || again.getAttribute('data-bound') === '1') {
                    return;
                }
                again.setAttribute('data-bound', '1');
                again.addEventListener('click', function () {
                    var choice = document.querySelector('[data-media-choice]');
                    if (choice) {
                        choice.hidden = true;
                    }
                    replay();
                });
            }, 0);
        }

        function clock(seconds) {
            seconds = Math.max(0, Math.floor(seconds || 0));
            var minutes = Math.floor(seconds / 60);
            var rest = seconds % 60;
            return minutes + ':' + (rest < 10 ? '0' : '') + rest;
        }

        function lockFile(box, el) {
            var toggle = box.querySelector('[data-media-toggle]');
            var rewind = box.querySelector('[data-media-rewind]');
            var track = box.querySelector('[data-media-track]');
            var fill = box.querySelector('[data-media-fill]');
            var clockEl = box.querySelector('[data-media-clock]');
            var mute = box.querySelector('[data-media-mute]');
            var volume = box.querySelector('[data-media-volume]');
            var status = box.querySelector('[data-media-status]');
            var bar = box.querySelector('.lms-play-bar');
            var furthest = 0;
            el.playbackRate = 1;
            el.controls = false;

            function setStatus(message, loading) {
                if (!status) {
                    return;
                }
                status.hidden = message === '';
                status.textContent = message;
                if (bar) {
                    bar.classList.toggle('is-loading', !!loading);
                }
            }

            function paintVolume() {
                if (!mute) {
                    return;
                }
                var icon = mute.querySelector('.material-icons');
                var level = el.muted || el.volume === 0 ? 0 : el.volume;
                if (icon) {
                    icon.textContent = level === 0 ? 'volume_off' : (level < 0.5 ? 'volume_down' : 'volume_up');
                }
                mute.setAttribute('aria-label', level === 0 ? 'Unmute' : 'Mute');
                if (volume && document.activeElement !== volume) {
                    volume.value = String(level);
                }
            }

            function rewindBy(seconds) {
                el.currentTime = Math.max(0, el.currentTime - seconds);
            }

            function paint() {
                var duration = el.duration || 0;
                if (fill) {
                    fill.style.width = (duration ? Math.min(100, (el.currentTime / duration) * 100) : 0) + '%';
                }
                if (clockEl) {
                    clockEl.textContent = clock(el.currentTime) + (duration ? ' / ' + clock(duration) : '');
                }
                if (toggle) {
                    var icon = toggle.querySelector('.material-icons');
                    if (icon) {
                        icon.textContent = el.paused ? 'play_arrow' : 'pause';
                    }
                    toggle.setAttribute('aria-label', el.paused ? 'Play' : 'Pause');
                }
            }

            function pullBack() {
                if (el.currentTime > furthest + 0.35) {
                    el.currentTime = furthest;
                }
            }

            el.addEventListener('timeupdate', function () {
                if (el.currentTime > furthest && el.currentTime <= furthest + 1.25) {
                    furthest = el.currentTime;
                }
                pullBack();
                paint();
            });
            el.addEventListener('seeking', pullBack);
            el.addEventListener('seeked', pullBack);
            el.addEventListener('ratechange', function () {
                if (el.playbackRate !== 1) {
                    el.playbackRate = 1;
                }
            });
            el.addEventListener('ended', function () {
                if (!el.duration || furthest >= el.duration - 1) {
                    finish();
                }
            });
            el.addEventListener('click', function () {
                if (el.paused) {
                    el.play();
                } else {
                    el.pause();
                }
            });
            el.addEventListener('contextmenu', function (event) {
                event.preventDefault();
            });
            el.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    event.stopPropagation();
                    rewindBy(10);
                    return;
                }
                var blocked = ['ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'PageUp', 'PageDown'];
                if (blocked.indexOf(event.key) !== -1 || (event.key >= '0' && event.key <= '9')) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
            if (rewind) {
                rewind.addEventListener('click', function () {
                    rewindBy(10);
                });
            }
            if (track) {
                track.addEventListener('click', function (event) {
                    if (!el.duration) {
                        return;
                    }
                    var rect = track.getBoundingClientRect();
                    var ratio = (event.clientX - rect.left) / rect.width;
                    var target = Math.max(0, Math.min(el.duration, ratio * el.duration));
                    if (target <= furthest + 0.25) {
                        el.currentTime = target;
                    }
                });
            }
            bindReplay(function () {
                furthest = 0;
                el.currentTime = 0;
                el.play();
            });
            if (toggle) {
                toggle.addEventListener('click', function () {
                    if (el.paused) {
                        el.play();
                    } else {
                        el.pause();
                    }
                });
            }
            el.addEventListener('play', paint);
            el.addEventListener('pause', paint);
            el.addEventListener('waiting', function () {
                setStatus('Loading…', true);
            });
            el.addEventListener('canplay', function () {
                setStatus('', false);
            });
            el.addEventListener('error', function () {
                setStatus('This file could not be played.', false);
            });
            if (mute) {
                mute.addEventListener('click', function () {
                    el.muted = !el.muted;
                    if (!el.muted && el.volume === 0) {
                        el.volume = 1;
                    }
                    paintVolume();
                });
            }
            if (volume) {
                volume.addEventListener('input', function () {
                    el.volume = Number(volume.value);
                    el.muted = el.volume === 0;
                    paintVolume();
                });
            }
            el.addEventListener('volumechange', paintVolume);
            paint();
            paintVolume();
        }

        function lockYoutube(box) {
            var frame = box.querySelector('[data-media-youtube]');
            frame.id = frame.id || 'lms-yt-' + Math.random().toString(36).slice(2);
            var furthest = 0;
            function boot() {
                if (!window.YT || !window.YT.Player) {
                    return;
                }
                new window.YT.Player(frame.id, {
                    events: {
                        onReady: function (event) {
                            event.target.setPlaybackRate(1);
                            bindReplay(function () {
                                furthest = 0;
                                event.target.seekTo(0, true);
                                event.target.playVideo();
                            });
                        },
                        onStateChange: function (event) {
                            var player = event.target;
                            var now = player.getCurrentTime ? player.getCurrentTime() : 0;
                            if (now > furthest + 1.25) {
                                player.seekTo(furthest, true);
                            } else if (now > furthest) {
                                furthest = now;
                            }
                            if (event.data === window.YT.PlayerState.ENDED && furthest >= (player.getDuration() || 0) - 1) {
                                finish();
                            }
                        }
                    }
                });
                setInterval(function () {
                    var player = window.YT.get(frame.id);
                    if (!player || !player.getCurrentTime) {
                        return;
                    }
                    var now = player.getCurrentTime();
                    if (now > furthest + 1.25) {
                        player.seekTo(furthest, true);
                    } else if (now > furthest) {
                        furthest = now;
                    }
                }, 400);
            }
            if (window.YT && window.YT.Player) {
                boot();
                return;
            }
            var previous = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function () {
                if (typeof previous === 'function') {
                    previous();
                }
                boot();
            };
            if (!document.querySelector('script[data-lms-youtube]')) {
                var script = document.createElement('script');
                script.src = 'https://www.youtube.com/iframe_api';
                script.setAttribute('data-lms-youtube', '1');
                document.head.appendChild(script);
            }
        }

        function lockVimeo(box) {
            var frame = box.querySelector('[data-media-vimeo]');
            function boot() {
                if (!window.Vimeo || !window.Vimeo.Player) {
                    return;
                }
                var player = new window.Vimeo.Player(frame);
                var furthest = 0;
                player.on('timeupdate', function (data) {
                    if (data.seconds > furthest + 1.25) {
                        player.setCurrentTime(furthest);
                    } else if (data.seconds > furthest) {
                        furthest = data.seconds;
                    }
                });
                player.on('ended', function () {
                    finish();
                });
            }
            if (window.Vimeo && window.Vimeo.Player) {
                boot();
                return;
            }
            var script = document.createElement('script');
            script.src = 'https://player.vimeo.com/api/player.js';
            script.onload = boot;
            document.head.appendChild(script);
        }
    })();
</script>
