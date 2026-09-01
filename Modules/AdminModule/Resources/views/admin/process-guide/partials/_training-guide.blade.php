@php
    $trainingGuideClass = $trainingGuideClass ?? \Modules\AdminModule\Support\LeadQualificationTrainingGuide::class;
    $flowchartsClass = $flowchartsClass ?? \Modules\AdminModule\Support\LeadQualificationTrainingFlowcharts::class;
    $slides = $trainingGuideClass::slides();
    $guideKey = $guideKey ?? '';
    $stageTypes = ['pk-cover', 'pk-close'];
    $pkDeck = ($guideKey ?? '') === 'panun-kaergar';
    $pkSkipHeader = ['pk-cover', 'pk-close', 'pk-promise', 'pk-qna', 'pk-who', 'pk-why', 'pk-problem'];
@endphp

<div class="pg-training-guide" id="pg-training-guide" data-pg-training-total="{{ count($slides) }}" data-pg-deck="{{ $guideKey }}">
    <div class="pg-text-guide-header">
        <div class="pg-training-header-row">
            <div class="pg-training-header-copy">
                <h3>{{ $title ?? 'Lead Qualification Flow' }} — Training</h3>
                <p class="pg-flow-sub">{{ $trainingSubtitle ?? 'Process training — aligned with the official flowchart.' }}</p>
            </div>
            <button
                type="button"
                class="pg-training-present-btn"
                data-pg-training-fullscreen
                aria-pressed="false"
                title="Full screen presentation (F)"
            >
                <span class="material-icons pg-training-present-icon" aria-hidden="true">fullscreen</span>
                <span class="pg-training-present-label">Present</span>
            </button>
        </div>
    </div>

    <div class="pg-training-layout" id="pg-training-layout">
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
                        @if (!empty($slide['icon']))
                            <span class="material-icons pg-training-toc-icon" aria-hidden="true">{{ $slide['icon'] }}</span>
                        @endif
                        <span class="pg-training-toc-label">{{ $slide['title'] }}</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="pg-training-main">
            <div class="pg-training-stage-fit">
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
                @if ($pkDeck && !in_array($slide['type'] ?? '', $pkSkipHeader, true))
                <header class="pg-pk-head">
                    @if (!empty($slide['kicker']))
                        <p class="pg-pk-kicker">{{ $slide['kicker'] }}</p>
                    @endif
                    <h4 class="pg-pk-title">{{ $slide['title'] }}</h4>
                    @if (!empty($slide['subtitle']))
                        <p class="pg-pk-sub">{{ $slide['subtitle'] }}</p>
                    @endif
                    @if (!empty($slide['tagline']) && empty($slide['hero_image']))
                        <p class="pg-pk-tagline">{{ $slide['tagline'] }}</p>
                    @endif
                </header>
                @elseif (!$pkDeck && !in_array($slide['type'] ?? '', $stageTypes, true))
                <header class="pg-training-slide-header">
                    <div class="pg-training-slide-header-row">
                        @if (!empty($slide['icon']))
                            <span class="material-icons pg-training-slide-icon" aria-hidden="true">{{ $slide['icon'] }}</span>
                        @endif
                        <span class="pg-training-slide-index">{{ str_pad((string) $slide['number'], 2, '0', STR_PAD_LEFT) }}</span>
                        <h4 class="pg-training-slide-title">{{ $slide['title'] }}</h4>
                        <span class="pg-training-slide-badge">Slide {{ $slide['number'] }}</span>
                    </div>
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
                @endif

                <div class="pg-training-slide-body">
                    @if (in_array($slide['type'] ?? '', $stageTypes, true))
                        @include('adminmodule::admin.process-guide.partials._training-pk-stage', ['slide' => $slide])
                    @endif
                    @if (!empty($slide['overview']))
                        <div class="pg-training-overview">
                            <span class="material-icons pg-training-overview-icon" aria-hidden="true">info</span>
                            <div class="pg-training-overview-body">
                                <span class="pg-training-overview-label">About this slide</span>
                                <p class="pg-training-overview-text">{{ $slide['overview'] }}</p>
                            </div>
                        </div>
                    @endif
                    @if (!empty($slide['intro']))
                        <p class="pg-training-intro">@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $slide['intro']])</p>
                    @endif
                    @if (!empty($slide['note']))
                        <p class="pg-training-note">@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $slide['note']])</p>
                    @endif
                    @if (!empty($slide['important']))
                        <p class="pg-training-important">@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $slide['important']])</p>
                    @endif
                    @if (!empty($slide['warning']))
                        <p class="pg-training-warning">@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $slide['warning']])</p>
                    @endif

                    @if ($slide['type'] === 'flowchart-only' && (!empty($slide['flowcharts']) || !empty($slide['flowchart'])))
                        <div class="pg-training-flowchart-only">
                            @php
                                $fcOnly = $slide['flowcharts'] ?? [];
                                if (empty($fcOnly) && !empty($slide['flowchart'])) {
                                    $fcOnly = [['key' => $slide['flowchart'], 'title' => 'Process flow']];
                                }
                            @endphp
                            @foreach ($fcOnly as $fc)
                                <div class="pg-training-flowchart-block">
                                    @if (!empty($fc['title']))
                                        <h5 class="pg-training-flowchart-block-title">{{ $fc['title'] }}</h5>
                                    @endif
                                    @include('adminmodule::admin.process-guide.partials._training-flowchart', ['flowchartKey' => $fc['key'], 'flowchartsClass' => $flowchartsClass])
                                </div>
                            @endforeach
                        </div>
                    @elseif (!empty($slide['flowchart']) && $slide['type'] !== 'visual')
                        @include('adminmodule::admin.process-guide.partials._training-flowchart', ['flowchartKey' => $slide['flowchart'], 'flowchartsClass' => $flowchartsClass])
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

                    @if ($slide['type'] === 'point-cards')
                        @include('adminmodule::admin.process-guide.partials._training-point-cards', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'deck-guide')
                        @include('adminmodule::admin.process-guide.partials._training-deck-guide', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'visual')
                        @include('adminmodule::admin.process-guide.partials._training-visual', ['slide' => $slide, 'flowchartsClass' => $flowchartsClass ?? null])
                    @endif

                    @if ($slide['type'] === 'pk-who')
                        @include('adminmodule::admin.process-guide.partials._training-pk-who', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-why')
                        @include('adminmodule::admin.process-guide.partials._training-pk-why', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-problem')
                        @include('adminmodule::admin.process-guide.partials._training-pk-problem', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-compare')
                        @include('adminmodule::admin.process-guide.partials._training-pk-compare', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-journey')
                        @include('adminmodule::admin.process-guide.partials._training-pk-journey', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-promise')
                        @include('adminmodule::admin.process-guide.partials._training-pk-promise', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-process')
                        @include('adminmodule::admin.process-guide.partials._training-pk-process', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-split')
                        @include('adminmodule::admin.process-guide.partials._training-pk-split', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-mission')
                        @include('adminmodule::admin.process-guide.partials._training-pk-mission', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-people')
                        @include('adminmodule::admin.process-guide.partials._training-pk-people', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-funnel')
                        @include('adminmodule::admin.process-guide.partials._training-pk-funnel', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'pk-qna')
                        @include('adminmodule::admin.process-guide.partials._training-pk-qna', ['slide' => $slide])
                    @endif

                    @if ($slide['type'] === 'sections' && !empty($slide['sections']))
                        @include('adminmodule::admin.process-guide.partials._training-sections', ['sections' => $slide['sections']])
                    @endif

                    @if ($slide['type'] === 'definitions' && !empty($slide['sections']))
                        @include('adminmodule::admin.process-guide.partials._training-sections', ['sections' => $slide['sections']])
                    @endif

                    @if ($slide['type'] === 'checklist' && !empty($slide['items']))
                        @if (!empty($slide['subtitle']))
                            <p class="pg-training-slide-subtitle">{{ $slide['subtitle'] }}</p>
                        @endif
                        <ol class="pg-training-checklist">
                            @foreach ($slide['items'] as $item)
                                <li>
                                    <strong>{{ $item['title'] }}</strong>
                                    <p>{{ $item['body'] }}</p>
                                    @if (!empty($item['details']))
                                        <ul class="pg-training-checklist-details">
                                            @foreach ($item['details'] as $detail)
                                                <li>{{ $detail }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
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
                                            <span class="pg-training-playbook-label">Say on call</span>
                                            <blockquote>{{ $step['say'] }}</blockquote>
                                        </div>
                                    @endif
                                    @if (!empty($step['message']))
                                        @php $msg = $step['message']; @endphp
                                        @include('adminmodule::admin.process-guide.partials._training-wa-accordion', ['msg' => $msg])
                                    @endif
                                    @if (!empty($step['warning']))
                                        <p class="pg-training-warning pg-training-warning--inline">{{ $step['warning'] }}</p>
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

                    @if ($slide['type'] === 'tab-grid' && !empty($slide['tab_groups']))
                        @include('adminmodule::admin.process-guide.partials._training-tab-grid', ['tabGroups' => $slide['tab_groups']])
                    @endif

                    @if ($slide['type'] === 'pipeline' && !empty($slide['pipeline']))
                        <ol class="pg-training-pipeline">
                            @foreach ($slide['pipeline'] as $item)
                                <li class="pg-training-pipeline-item{{ !empty($item['phase']) ? ' pg-training-pipeline-item--phase' : '' }}">
                                    <span class="pg-training-pipeline-step">{{ $item['step'] }}</span>
                                    <div>
                                        <strong>{{ $item['title'] }}</strong>
                                        <span>@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $item['body'] ?? ''])</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if (!empty($slide['flowchart']) && $slide['type'] === 'pipeline')
                        <div class="pg-training-flowchart-block pg-training-flowchart-block--after-pipeline">
                            @include('adminmodule::admin.process-guide.partials._training-flowchart', ['flowchartKey' => $slide['flowchart'], 'flowchartsClass' => $flowchartsClass ?? null])
                        </div>
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

                    @if (!empty($slide['steps']) && !in_array($slide['type'], ['playbook', 'visual'], true))
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
                        @if (!empty($slide['steps']))
                            <div class="pg-training-playbook pg-training-playbook--onboarding">
                                @foreach ($slide['steps'] as $step)
                                    <div class="pg-training-playbook-card">
                                        <h5>{{ $step['title'] }}</h5>
                                        @if (!empty($step['message']))
                                            @php $msg = $step['message']; @endphp
                                            @include('adminmodule::admin.process-guide.partials._training-wa-accordion', ['msg' => $msg])
                                        @endif
                                        @if (!empty($step['warning']))
                                            <p class="pg-training-warning pg-training-warning--inline">{{ $step['warning'] }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
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

                    @if ($slide['type'] === 'message-formats' && !empty($slide['formats']))
                        <div class="pg-training-msg-formats">
                            @foreach ($slide['formats'] as $fmt)
                                <div class="pg-training-msg-card">
                                    <h5>{{ $fmt['title'] }}</h5>
                                    @if (!empty($fmt['when']))
                                        <p class="pg-training-msg-when"><strong>When:</strong> {{ $fmt['when'] }}</p>
                                    @endif
                                    @if (!empty($fmt['must_include']))
                                        <div class="pg-training-msg-must">
                                            <span class="pg-training-playbook-label">Must include</span>
                                            <ul>
                                                @foreach ($fmt['must_include'] as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    @if (!empty($fmt['template']))
                                        <div class="pg-training-msg-block">
                                            <span class="pg-training-playbook-label">General format</span>
                                            <pre class="pg-training-msg-pre">{{ $fmt['template'] }}</pre>
                                        </div>
                                    @endif
                                    @if (!empty($fmt['example']))
                                        <div class="pg-training-msg-block pg-training-msg-block--example">
                                            <span class="pg-training-playbook-label">Filled example</span>
                                            <pre class="pg-training-msg-pre">{{ $fmt['example'] }}</pre>
                                        </div>
                                    @endif
                                    @if (!empty($fmt['tips']))
                                        <ul class="pg-training-msg-tips">
                                            @foreach ($fmt['tips'] as $tip)
                                                <li>{{ $tip }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($slide['type'] === 'roleplay' && !empty($slide['scenarios']))
                        <div class="pg-training-roleplay">
                            @foreach ($slide['scenarios'] as $scenario)
                                <div class="pg-training-roleplay-card">
                                    <h5>{{ $scenario['title'] }}</h5>
                                    @if (!empty($scenario['situation']))
                                        <p><strong>Situation:</strong> {{ $scenario['situation'] }}</p>
                                    @endif
                                    @if (!empty($scenario['user_says']))
                                        <blockquote class="pg-training-roleplay-user">User: “{{ $scenario['user_says'] }}”</blockquote>
                                    @endif
                                    @if (!empty($scenario['good_response']))
                                        <blockquote class="pg-training-roleplay-good">You say: {{ $scenario['good_response'] }}</blockquote>
                                    @endif
                                    @if (!empty($scenario['panel']))
                                        <p class="pg-training-roleplay-panel"><strong>Panel:</strong> {{ $scenario['panel'] }}</p>
                                    @endif
                                    @if (!empty($scenario['avoid']))
                                        <p class="pg-training-roleplay-avoid"><strong>Avoid:</strong> {{ $scenario['avoid'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($slide['type'] === 'quiz' && !empty($slide['questions']))
                        <div class="pg-training-quiz" id="pg-training-quiz">
                            @foreach ($slide['questions'] as $qi => $q)
                                <div class="pg-training-quiz-q" data-quiz-q="{{ $qi }}" data-correct="{{ $q['correct'] }}">
                                    <p class="pg-training-quiz-question"><span>{{ $qi + 1 }}.</span> {{ $q['question'] }}</p>
                                    <div class="pg-training-quiz-options">
                                        @foreach ($q['options'] as $oi => $opt)
                                            <button type="button" class="pg-training-quiz-opt" data-opt="{{ $oi }}">{{ $opt }}</button>
                                        @endforeach
                                    </div>
                                    <p class="pg-training-quiz-explain" hidden>{{ $q['explain'] }}</p>
                                </div>
                            @endforeach
                            <p class="pg-training-quiz-score" id="pg-training-quiz-score" hidden></p>
                        </div>
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
                @if ($pkDeck && !in_array($slide['type'] ?? '', ['pk-cover', 'pk-who', 'pk-why', 'pk-problem'], true))
                    <footer class="pg-pk-foot">
                        <span>Panun Kaergar</span>
                        <span>{{ str_pad((string) $slide['number'], 2, '0', STR_PAD_LEFT) }}</span>
                    </footer>
                @endif
            </article>
        @endforeach
                </div>
            </div>
            </div>

            <footer class="pg-training-nav" aria-label="Slide navigation">
                <button type="button" class="pg-training-nav-btn" data-pg-training-prev disabled>← Previous</button>
                <div class="pg-training-nav-center">
                    <button
                        type="button"
                        class="pg-training-nav-btn pg-training-nav-btn--icon"
                        data-pg-training-fullscreen
                        aria-pressed="false"
                        title="Full screen presentation (F)"
                    >
                        <span class="material-icons" aria-hidden="true">fullscreen</span>
                    </button>
                    <span class="pg-training-nav-counter" data-pg-training-counter>1 / {{ count($slides) }}</span>
                </div>
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
        closePointCardDrawer();
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
        var typing = e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable);
        if (e.key === 'Escape' && presenting) {
            e.preventDefault();
            exitPresentation();
            return;
        }
        if ((e.key === 'f' || e.key === 'F') && !typing) {
            e.preventDefault();
            toggleTrainingFullscreen();
            return;
        }
        if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === 'ArrowDown' || (e.key === ' ' && !typing)) {
            e.preventDefault();
            show(current + 1);
        } else if (e.key === 'ArrowLeft' || e.key === 'PageUp' || e.key === 'ArrowUp') {
            e.preventDefault();
            show(current - 1);
        } else if (e.key === 'Home') {
            e.preventDefault();
            show(0);
        } else if (e.key === 'End') {
            e.preventDefault();
            show(total - 1);
        }
    });

    var fsBtns = guide.querySelectorAll('[data-pg-training-fullscreen]');
    var presenting = false;
    var nativePresenting = false;

    function nativeFsElement() {
        return document.fullscreenElement || document.webkitFullscreenElement || null;
    }

    function setPresentationUi(on) {
        presenting = on;
        guide.classList.toggle('is-presentation', on);
        document.body.classList.toggle('pg-training-presenting', on);
        fsBtns.forEach(function (btn) {
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.title = on ? 'Exit full screen (Esc)' : 'Full screen presentation (F)';
            var icon = btn.querySelector('.material-icons');
            if (icon) icon.textContent = on ? 'fullscreen_exit' : 'fullscreen';
            var label = btn.querySelector('.pg-training-present-label');
            if (label) label.textContent = on ? 'Exit' : 'Present';
        });
        fitPresentationStage();
    }

    var stageFit = guide.querySelector('.pg-training-stage-fit');
    var stageEl = guide.querySelector('.pg-training-stage');
    var SLIDE_RATIO = 16 / 9;

    function fitPresentationStage() {
        if (!stageEl) return;
        if (!presenting || !stageFit) {
            stageEl.style.width = '';
            stageEl.style.height = '';
            return;
        }
        var availW = stageFit.clientWidth;
        var availH = stageFit.clientHeight;
        if (availW < 2 || availH < 2) return;
        var slideW;
        var slideH;
        if (availW / availH > SLIDE_RATIO) {
            slideH = availH;
            slideW = availH * SLIDE_RATIO;
        } else {
            slideW = availW;
            slideH = availW / SLIDE_RATIO;
        }
        stageEl.style.width = Math.round(slideW) + 'px';
        stageEl.style.height = Math.round(slideH) + 'px';
    }

    function enterPresentation() {
        setPresentationUi(true);
        var req = guide.requestFullscreen || guide.webkitRequestFullscreen;
        if (!req) return;
        try {
            var pending = req.call(guide);
            if (pending && pending.then) {
                pending.then(function () {
                    nativePresenting = true;
                    requestAnimationFrame(fitPresentationStage);
                }).catch(function () {});
            }
        } catch (err) {}
    }

    function exitPresentation() {
        if (nativeFsElement()) {
            var exit = document.exitFullscreen || document.webkitExitFullscreen;
            if (exit) {
                try { exit.call(document); } catch (err) {}
            }
            if (nativePresenting) return;
        }
        nativePresenting = false;
        setPresentationUi(false);
    }

    function toggleTrainingFullscreen() {
        if (presenting) exitPresentation();
        else enterPresentation();
    }

    function syncPresentationFromNative() {
        var el = nativeFsElement();
        if (el === guide) {
            nativePresenting = true;
            if (!presenting) setPresentationUi(true);
            return;
        }
        if (!el && nativePresenting) {
            nativePresenting = false;
            setPresentationUi(false);
        }
    }

    fsBtns.forEach(function (btn) {
        btn.addEventListener('click', toggleTrainingFullscreen);
    });
    document.addEventListener('fullscreenchange', function () {
        syncPresentationFromNative();
        requestAnimationFrame(fitPresentationStage);
    });
    document.addEventListener('webkitfullscreenchange', function () {
        syncPresentationFromNative();
        requestAnimationFrame(fitPresentationStage);
    });
    window.addEventListener('resize', function () {
        if (presenting) fitPresentationStage();
    });
    if (window.ResizeObserver && stageFit) {
        new ResizeObserver(function () {
            if (presenting) fitPresentationStage();
        }).observe(stageFit);
    }

    var activePointDrawer = null;

    function assetBaseUrl(base) {
        if (!base) return '';
        return base.charAt(base.length - 1) === '/' ? base : base + '/';
    }

    function versionedAssetUrl(base, file, version) {
        var url = assetBaseUrl(base) + file;
        if (!version) return url;
        return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + version;
    }

    window.pgTrainingImageFallback = function (img) {
        if (!img) return;
        img.onerror = null;
        var media = img.parentElement;
        if (!media) return;
        var icon = img.getAttribute('data-pg-fallback-icon') || 'info';
        media.className = 'pg-training-point-card-media pg-training-point-card-media--icon';
        media.innerHTML = '<span class="material-icons" aria-hidden="true">' + icon + '</span>';
    };

    function fillPointDrawerExamples(container, items, exampleBase, trainingBase, exampleVersion, trainingVersion) {
        if (!container) return;
        container.innerHTML = '';
        (items || []).forEach(function (item) {
            if (typeof item === 'string') {
                var fallback = document.createElement('p');
                fallback.className = 'pg-training-point-example-text';
                fallback.textContent = item;
                container.appendChild(fallback);
                return;
            }
            var card = document.createElement('article');
            var type = item.type || 'neutral';
            card.className = 'pg-training-point-example pg-training-point-example--' + type;

            if (item.image) {
                var media = document.createElement('div');
                media.className = 'pg-training-point-example-media';
                var img = document.createElement('img');
                img.src = versionedAssetUrl(exampleBase || trainingBase, item.image, exampleBase ? exampleVersion : trainingVersion);
                img.alt = item.label || 'Example illustration';
                img.loading = 'lazy';
                img.onerror = function () {
                    img.onerror = null;
                    if (media.parentElement) {
                        media.parentElement.removeChild(media);
                    }
                };
                media.appendChild(img);
                card.appendChild(media);
            }

            var body = document.createElement('div');
            body.className = 'pg-training-point-example-body';
            if (item.label) {
                var label = document.createElement('span');
                label.className = 'pg-training-point-example-label';
                label.textContent = item.label;
                body.appendChild(label);
            }
            if (item.text) {
                var text = document.createElement('p');
                text.className = 'pg-training-point-example-text';
                text.textContent = item.text;
                body.appendChild(text);
            }
            card.appendChild(body);
            container.appendChild(card);
        });
    }

    function fillPointDrawerList(el, items) {
        if (!el) return;
        el.innerHTML = '';
        (items || []).forEach(function (item) {
            var li = document.createElement('li');
            li.textContent = item;
            el.appendChild(li);
        });
    }

    function closePointCardDrawer() {
        if (!activePointDrawer) return;
        var root = activePointDrawer.root;
        var drawer = activePointDrawer.drawer;
        var backdrop = activePointDrawer.backdrop;
        if (drawer) {
            drawer.classList.remove('is-open');
            drawer.setAttribute('hidden', '');
        }
        if (backdrop) backdrop.setAttribute('hidden', '');
        if (root) {
            root.querySelectorAll('.pg-training-point-card.is-selected').forEach(function (card) {
                card.classList.remove('is-selected');
                card.setAttribute('aria-expanded', 'false');
            });
        }
        activePointDrawer = null;
    }

    function openPointCardDrawer(root, cardId) {
        var dataRaw = root.getAttribute('data-pg-point-cards');
        if (!dataRaw) return;
        var cards;
        try {
            cards = JSON.parse(dataRaw);
        } catch (e) {
            return;
        }
        var card = cards.find(function (c) { return c.id === cardId; });
        if (!card) return;

        var drawer = root.querySelector('[data-pg-point-drawer]');
        var backdrop = root.querySelector('.pg-training-point-drawer-backdrop');
        if (!drawer || !backdrop) return;

        var titleEl = drawer.querySelector('[data-pg-point-drawer-title]');
        var iconEl = drawer.querySelector('[data-pg-point-drawer-icon]');
        var detailEl = drawer.querySelector('[data-pg-point-drawer-detail]');
        var heroWrap = drawer.querySelector('[data-pg-point-drawer-hero]');
        var heroImg = drawer.querySelector('[data-pg-point-drawer-hero-img]');
        var trainingBase = assetBaseUrl(root.getAttribute('data-pg-training-asset-base') || '');
        var exampleBase = assetBaseUrl(root.getAttribute('data-pg-example-asset-base') || '');
        var trainingVersion = root.getAttribute('data-pg-training-asset-version') || '';
        var exampleVersion = root.getAttribute('data-pg-example-asset-version') || '';

        if (titleEl) titleEl.textContent = card.title || '';
        if (iconEl) iconEl.textContent = card.icon || 'info';
        if (detailEl) detailEl.textContent = card.detail || card.description || '';

        var detailPointsEl = drawer.querySelector('[data-pg-point-drawer-detail-points]');
        if (detailPointsEl) {
            detailPointsEl.innerHTML = '';
            var points = card.detail_points || [];
            if (points.length) {
                points.forEach(function (point) {
                    var li = document.createElement('li');
                    li.textContent = point;
                    detailPointsEl.appendChild(li);
                });
                detailPointsEl.removeAttribute('hidden');
            } else {
                detailPointsEl.setAttribute('hidden', '');
            }
        }

        if (heroWrap && heroImg) {
            if (card.image) {
                heroImg.onerror = function () {
                    heroImg.onerror = null;
                    heroImg.removeAttribute('src');
                    heroWrap.setAttribute('hidden', '');
                };
                heroImg.src = versionedAssetUrl(trainingBase, card.image, trainingVersion);
                heroImg.alt = card.title || 'Training illustration';
                heroWrap.removeAttribute('hidden');
            } else {
                heroImg.onerror = null;
                heroImg.removeAttribute('src');
                heroWrap.setAttribute('hidden', '');
            }
        }

        fillPointDrawerExamples(
            drawer.querySelector('[data-pg-point-drawer-examples]'),
            card.examples,
            exampleBase,
            trainingBase,
            exampleVersion,
            trainingVersion
        );
        fillPointDrawerList(drawer.querySelector('[data-pg-point-drawer-practices]'), card.best_practices);
        fillPointDrawerList(drawer.querySelector('[data-pg-point-drawer-avoid]'), card.avoid);

        root.querySelectorAll('.pg-training-point-card').forEach(function (btn) {
            var selected = btn.getAttribute('data-pg-point-card-id') === cardId;
            btn.classList.toggle('is-selected', selected);
            btn.setAttribute('aria-expanded', selected ? 'true' : 'false');
        });

        backdrop.removeAttribute('hidden');
        drawer.removeAttribute('hidden');
        requestAnimationFrame(function () {
            drawer.classList.add('is-open');
        });

        activePointDrawer = { root: root, drawer: drawer, backdrop: backdrop };
        var closeBtn = drawer.querySelector('[data-pg-point-drawer-close]');
        if (closeBtn) closeBtn.focus();
    }

    guide.querySelectorAll('[data-pg-training-point-cards]').forEach(function (root) {
        root.querySelectorAll('.pg-training-point-card').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openPointCardDrawer(root, btn.getAttribute('data-pg-point-card-id'));
            });
        });
        root.querySelectorAll('[data-pg-point-drawer-close]').forEach(function (btn) {
            btn.addEventListener('click', closePointCardDrawer);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && activePointDrawer) {
            e.preventDefault();
            closePointCardDrawer();
        }
    });

    var initialSlideId = @json($initialSlideId ?? '');
    if (initialSlideId) {
        var initialIndex = byId(initialSlideId);
        if (initialIndex >= 0) {
            show(initialIndex);
        } else {
            show(0);
        }
    } else {
        show(0);
    }

    var quiz = document.getElementById('pg-training-quiz');
    if (quiz) {
        var answered = {};
        var totalQ = quiz.querySelectorAll('[data-quiz-q]').length;
        var scoreEl = document.getElementById('pg-training-quiz-score');

        quiz.querySelectorAll('.pg-training-quiz-q').forEach(function (block) {
            var correct = parseInt(block.getAttribute('data-correct'), 10);
            var qid = block.getAttribute('data-quiz-q');
            block.querySelectorAll('.pg-training-quiz-opt').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (answered[qid]) return;
                    var pick = parseInt(btn.getAttribute('data-opt'), 10);
                    var ok = pick === correct;
                    answered[qid] = ok;
                    block.querySelectorAll('.pg-training-quiz-opt').forEach(function (b) {
                        b.disabled = true;
                        var idx = parseInt(b.getAttribute('data-opt'), 10);
                        if (idx === correct) b.classList.add('is-correct');
                        else if (idx === pick && !ok) b.classList.add('is-wrong');
                    });
                    var exp = block.querySelector('.pg-training-quiz-explain');
                    if (exp) exp.removeAttribute('hidden');
                    var score = Object.keys(answered).filter(function (k) { return answered[k]; }).length;
                    if (scoreEl && Object.keys(answered).length === totalQ) {
                        scoreEl.textContent = 'Score: ' + score + ' / ' + totalQ + (score === totalQ ? ' — Certified! You know the full process.' : ' — Perfect score required. Review every slide and retake.');
                        scoreEl.removeAttribute('hidden');
                    }
                });
            });
        });
    }
})();
</script>
