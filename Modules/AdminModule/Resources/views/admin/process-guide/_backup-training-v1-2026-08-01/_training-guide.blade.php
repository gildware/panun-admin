@php
    use Modules\AdminModule\Support\LeadQualificationTrainingGuide;
    $slides = LeadQualificationTrainingGuide::slides();
@endphp

<div class="pg-training-guide" id="pg-training-guide" data-pg-training-total="{{ count($slides) }}">
    <div class="pg-text-guide-header">
        <h3>{{ $title ?? 'Lead Qualification Flow' }} — Training</h3>
        <p class="pg-flow-sub">Trainer-led guide — what to do, say, and update at every step from lead arrival to final outcome.</p>
    </div>

    <div class="pg-training-layout">
        <aside class="pg-training-sidebar">
            <div class="pg-training-sidebar-head">Slides</div>
            <nav class="pg-training-toc" aria-label="Training slides">
                @foreach ($slides as $i => $slide)
                    <button
                        type="button"
                        class="pg-training-toc-link{{ $i === 0 ? ' is-active' : '' }}"
                        data-pg-training-goto="{{ $i }}"
                        aria-label="Go to slide {{ $slide['number'] }}: {{ $slide['title'] }}"
                    >
                        <span class="pg-training-toc-num">{{ $slide['number'] }}</span>
                        <span class="pg-training-toc-label">{{ $slide['title'] }}</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="pg-training-main">
            <div class="pg-training-stage">
                <div class="pg-training-body">
        @foreach ($slides as $i => $slide)
            <article
                class="pg-training-slide pg-training-slide--{{ $slide['type'] }}{{ $i === 0 ? ' is-active' : '' }}"
                id="pg-training-slide-{{ $slide['id'] }}"
                data-pg-training-index="{{ $i }}"
                data-pg-training-id="{{ $slide['id'] }}"
                @if ($i !== 0) hidden @endif
            >
                <header class="pg-training-slide-header">
                    <span class="pg-training-slide-badge">Slide {{ $slide['number'] }}</span>
                    <h4 class="pg-training-slide-title">{{ $slide['title'] }}</h4>
                    @if (!empty($slide['subtitle']))
                        <p class="pg-training-slide-sub">{{ $slide['subtitle'] }}</p>
                    @endif
                    @if (!empty($slide['tagline']))
                        <p class="pg-training-slide-tagline">{{ $slide['tagline'] }}</p>
                    @endif
                    @if (!empty($slide['footer']))
                        <p class="pg-training-slide-footer">{{ $slide['footer'] }}</p>
                    @endif
                </header>

                <div class="pg-training-slide-body">
                    @if (!empty($slide['intro']))
                        <p class="pg-training-intro">{{ $slide['intro'] }}</p>
                    @endif
                    @if (!empty($slide['note']))
                        <p class="pg-training-note">{{ $slide['note'] }}</p>
                    @endif

                    @if ($slide['type'] === 'mindset' && !empty($slide['principles']))
                        <ul class="pg-training-mindset">
                            @foreach ($slide['principles'] as $p)
                                <li>
                                    <strong>{{ $p['title'] }}</strong>
                                    <span>{{ $p['body'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($slide['type'] === 'end-map')
                        @if (!empty($slide['start']))
                            <p class="pg-training-end-start">{{ $slide['start'] }}</p>
                        @endif
                        @if (!empty($slide['success']))
                            <div class="pg-training-end-group pg-training-end-group--success">
                                <h5>Success — happy outcomes</h5>
                                <ul>
                                    @foreach ($slide['success'] as $item)
                                        <li><strong>{{ $item['label'] }}</strong><span>{{ $item['body'] }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (!empty($slide['nurture']))
                            <div class="pg-training-end-group pg-training-end-group--nurture">
                                <h5>Nurture — still valuable</h5>
                                <ul>
                                    @foreach ($slide['nurture'] as $item)
                                        <li><strong>{{ $item['label'] }}</strong><span>{{ $item['body'] }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (!empty($slide['closure']))
                            <div class="pg-training-end-group pg-training-end-group--closure">
                                <h5>Closure — professional close required</h5>
                                <ul>
                                    @foreach ($slide['closure'] as $item)
                                        <li><strong>{{ $item['label'] }}</strong><span>{{ $item['body'] }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    @if ($slide['type'] === 'checklist' && !empty($slide['items']))
                        <ol class="pg-training-checklist">
                            @foreach ($slide['items'] as $item)
                                <li>
                                    <strong>{{ $item['title'] }}</strong>
                                    <p>{{ $item['body'] }}</p>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($slide['type'] === 'playbook' && !empty($slide['steps']))
                        <div class="pg-training-playbook">
                            @foreach ($slide['steps'] as $step)
                                <div class="pg-training-playbook-card">
                                    <h5>{{ $step['title'] }}</h5>
                                    @if (!empty($step['goal']))
                                        <p class="pg-training-playbook-goal"><span>Goal</span> {{ $step['goal'] }}</p>
                                    @endif
                                    @if (!empty($step['do']))
                                        <div class="pg-training-playbook-block">
                                            <span class="pg-training-playbook-label">Do</span>
                                            <ul>
                                                @foreach ($step['do'] as $d)
                                                    <li>{{ $d }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    @if (!empty($step['say']))
                                        <div class="pg-training-playbook-block pg-training-playbook-block--say">
                                            <span class="pg-training-playbook-label">Say</span>
                                            <blockquote>{{ $step['say'] }}</blockquote>
                                        </div>
                                    @endif
                                    @if (!empty($step['panel']))
                                        <div class="pg-training-playbook-block pg-training-playbook-block--panel">
                                            <span class="pg-training-playbook-label">Panel</span>
                                            <ul>
                                                @foreach ($step['panel'] as $p)
                                                    <li>{{ $p }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    @if (!empty($step['next']))
                                        <p class="pg-training-playbook-next">{{ $step['next'] }}</p>
                                    @endif
                                    @if (!empty($step['tip']))
                                        <p class="pg-training-playbook-tip">{{ $step['tip'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($slide['type'] === 'script' && !empty($slide['questions']))
                        <ol class="pg-training-script">
                            @foreach ($slide['questions'] as $q)
                                <li>
                                    <strong>{{ $q['q'] }}</strong>
                                    <span class="pg-training-script-arrow">→ {{ $q['if_yes'] }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($slide['type'] === 'pipeline' && !empty($slide['pipeline']))
                        <ol class="pg-training-pipeline">
                            @foreach ($slide['pipeline'] as $item)
                                <li class="pg-training-pipeline-item">
                                    <span class="pg-training-pipeline-step">{{ $item['step'] }}</span>
                                    <div>
                                        <strong>{{ $item['title'] }}</strong>
                                        <span>{{ $item['body'] }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($slide['type'] === 'legend' && !empty($slide['legend']))
                        <ul class="pg-training-legend">
                            @foreach ($slide['legend'] as $item)
                                <li class="pg-training-legend-item">
                                    <i style="background: {{ $item['bg'] }}; border-color: {{ $item['color'] }};"></i>
                                    <span>{{ $item['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($slide['type'] === 'columns' && !empty($slide['columns']))
                        <div class="pg-training-columns">
                            @foreach ($slide['columns'] as $col)
                                <div class="pg-training-column">
                                    <h5>{{ $col['title'] }}</h5>
                                    <ul>
                                        @foreach ($col['items'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                    @if (!empty($col['path']))
                                        <p class="pg-training-column-path">{{ $col['path'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (in_array($slide['type'], ['decision', 'flow', 'content', 'onboarding', 'playbook'], true) && !empty($slide['question']))
                        <div class="pg-training-decision-q">{{ $slide['question'] }}</div>
                    @endif

                    @if (!empty($slide['paths']))
                        <div class="pg-training-paths">
                            @foreach ($slide['paths'] as $path)
                                <button type="button" class="pg-training-path-card" data-pg-training-goto-id="{{ $path['ref'] }}">
                                    <span class="pg-training-path-label">{{ $path['label'] }}</span>
                                    <strong>{{ $path['title'] }}</strong>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($slide['bullets']))
                        <ul class="pg-training-bullets">
                            @foreach ($slide['bullets'] as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if (!empty($slide['branches']))
                        <div class="pg-training-branches">
                            @foreach ($slide['branches'] as $branch)
                                <div class="pg-training-branch">
                                    <div class="pg-training-branch-label">{{ $branch['label'] }}</div>
                                    @if (!empty($branch['action']))
                                        <p class="pg-training-branch-action">{{ $branch['action'] }}</p>
                                    @endif
                                    @if (!empty($branch['items']))
                                        <ul>
                                            @foreach ($branch['items'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if (!empty($branch['steps']))
                                        <ol class="pg-training-substeps">
                                            @foreach ($branch['steps'] as $sub)
                                                <li>
                                                    @if (is_array($sub))
                                                        <strong>{{ $sub['title'] }}</strong>
                                                        @if (!empty($sub['body']))
                                                            <span>{{ $sub['body'] }}</span>
                                                        @endif
                                                        @if (!empty($sub['template']))
                                                            <blockquote class="pg-training-template-inline">{{ $sub['template'] }}</blockquote>
                                                        @endif
                                                    @else
                                                        {{ $sub }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ol>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($slide['steps']) && $slide['type'] !== 'playbook')
                        <ol class="pg-training-steps">
                            @foreach ($slide['steps'] as $step)
                                <li>
                                    @if (is_array($step))
                                        <strong>{{ $step['title'] }}</strong>
                                        @if (!empty($step['template']))
                                            <blockquote class="pg-training-template-inline">{{ $step['template'] }}</blockquote>
                                        @endif
                                    @else
                                        {{ $step }}
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if (!empty($slide['decision']))
                        @php $d = $slide['decision']; @endphp
                        <div class="pg-training-decision-block">
                            <div class="pg-training-decision-q">{{ $d['question'] }}</div>
                            @if (!empty($d['yes']))
                                <div class="pg-training-branch">
                                    <div class="pg-training-branch-label">Yes</div>
                                    <p>{{ $d['yes'] }}</p>
                                </div>
                            @endif
                            @if (!empty($d['no']))
                                <div class="pg-training-branch">
                                    <div class="pg-training-branch-label">No</div>
                                    <p>{{ $d['no'] }}</p>
                                </div>
                            @endif
                            @if (!empty($d['branches']))
                                <div class="pg-training-branches">
                                    @foreach ($d['branches'] as $branch)
                                        <div class="pg-training-branch">
                                            <div class="pg-training-branch-label">{{ $branch['label'] }}</div>
                                            @if (!empty($branch['steps']))
                                                <ol class="pg-training-substeps">
                                                    @foreach ($branch['steps'] as $sub)
                                                        <li>{{ $sub }}</li>
                                                    @endforeach
                                                </ol>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($slide['type'] === 'onboarding')
                        @if (!empty($slide['availability']))
                            <div class="pg-training-branches pg-training-branches--compact">
                                @foreach ($slide['availability'] as $item)
                                    <div class="pg-training-branch">
                                        <div class="pg-training-branch-label">{{ $item['label'] }}</div>
                                        <p>{{ $item['action'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if (!empty($slide['phases']))
                            <ol class="pg-training-phases">
                                @foreach ($slide['phases'] as $phase)
                                    <li class="pg-training-phase">
                                        <span class="pg-training-phase-step">{{ $phase['step'] }}</span>
                                        <div>
                                            <strong>{{ $phase['title'] }}</strong>
                                            <p>{{ $phase['body'] }}</p>
                                            @if (!empty($phase['tip']))
                                                <p class="pg-training-phase-tip">{{ $phase['tip'] }}</p>
                                            @endif
                                            @if (!empty($phase['note']))
                                                <p class="pg-training-phase-note">{{ $phase['note'] }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    @endif

                    @if ($slide['type'] === 'outcomes' && !empty($slide['outcomes']))
                        <ul class="pg-training-outcomes">
                            @foreach ($slide['outcomes'] as $outcome)
                                <li>
                                    <strong>{{ $outcome['label'] }}</strong>
                                    <span>{{ $outcome['body'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($slide['type'] === 'templates' && !empty($slide['templates']))
                        <div class="pg-training-templates">
                            @foreach ($slide['templates'] as $tpl)
                                <div class="pg-training-template-card">
                                    <h5>{{ $tpl['title'] }}</h5>
                                    <blockquote>{{ $tpl['text'] }}</blockquote>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($slide['type'] === 'rules' && !empty($slide['rules']))
                        <ul class="pg-training-rules">
                            @foreach ($slide['rules'] as $rule)
                                <li>{{ $rule }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($slide['type'] === 'master' && !empty($slide['flow']))
                        <div class="pg-training-master-flow">
                            @foreach ($slide['flow'] as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
                </div>
            </div>

            <footer class="pg-training-nav" aria-label="Slide navigation">
                <button type="button" class="pg-training-nav-btn" data-pg-training-prev disabled>← Previous</button>
                <span class="pg-training-nav-counter" data-pg-training-counter>1 / {{ count($slides) }}</span>
                <button type="button" class="pg-training-nav-btn pg-training-nav-btn--primary" data-pg-training-next>Next →</button>
            </footer>
        </div>
    </div>
</div>

<script>
(function () {
    var guide = document.getElementById('pg-training-guide');
    if (!guide) return;

    var slides = Array.prototype.slice.call(guide.querySelectorAll('[data-pg-training-index]'));
    var tocLinks = guide.querySelectorAll('[data-pg-training-goto]');
    var prevBtn = guide.querySelector('[data-pg-training-prev]');
    var nextBtn = guide.querySelector('[data-pg-training-next]');
    var counter = guide.querySelector('[data-pg-training-counter]');
    var total = slides.length;
    var current = 0;

    function byId(id) {
        for (var i = 0; i < slides.length; i++) {
            if (slides[i].getAttribute('data-pg-training-id') === id) return i;
        }
        return -1;
    }

    function show(index) {
        if (index < 0 || index >= total) return;
        current = index;
        slides.forEach(function (slide, i) {
            var on = i === index;
            slide.classList.toggle('is-active', on);
            if (on) slide.removeAttribute('hidden');
            else slide.setAttribute('hidden', '');
        });
        tocLinks.forEach(function (link) {
            var on = parseInt(link.getAttribute('data-pg-training-goto'), 10) === index;
            link.classList.toggle('is-active', on);
            if (on && link.scrollIntoView) {
                link.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });
        if (counter) counter.textContent = (index + 1) + ' / ' + total;
        if (prevBtn) prevBtn.disabled = index === 0;
        if (nextBtn) {
            nextBtn.disabled = index >= total - 1;
            nextBtn.textContent = index >= total - 1 ? 'Finish' : 'Next →';
        }
        var body = slides[index].querySelector('.pg-training-slide-body');
        if (body) body.scrollTop = 0;
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () { show(current - 1); });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (current < total - 1) show(current + 1);
        });
    }
    tocLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            show(parseInt(link.getAttribute('data-pg-training-goto'), 10));
        });
    });
    guide.querySelectorAll('[data-pg-training-goto-id]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idx = byId(btn.getAttribute('data-pg-training-goto-id'));
            if (idx >= 0) show(idx);
        });
    });

    document.addEventListener('keydown', function (e) {
        var panel = document.getElementById('pg-panel-training');
        if (!panel || panel.hasAttribute('hidden')) return;
        if (e.key === 'ArrowRight' || e.key === 'PageDown') {
            e.preventDefault();
            show(current + 1);
        } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
            e.preventDefault();
            show(current - 1);
        }
    });

    show(0);
})();
</script>
