@php
    $pkPos = $slide['hero_position'] ?? 'center 30%';
    $pkHasPhoto = !empty($slide['hero_image']);
    $pkFit = ($slide['hero_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
@endphp
<div class="pg-pk-visual{{ $pkHasPhoto ? ' pg-pk-visual--with-photo' : '' }}{{ $pkFit === 'contain' ? ' pg-pk-visual--contain' : '' }}">
@if ($pkHasPhoto)
    <div class="pg-pk-hero">
        <img src="{{ process_guide_training_asset($slide['hero_image']) }}" alt="" loading="lazy" class="pg-pk-hero-img pg-pk-hero-img--{{ $pkFit }}" style="object-position: {{ $pkPos }}">
    </div>
@endif
<div class="pg-pk-visual-main">

@if (!empty($slide['tagline']) && !empty($slide['hero_image']))
    <p class="pg-pk-lede">{{ $slide['tagline'] }}</p>
@endif

@if (!empty($slide['pills']))
    <ul class="pg-pk-pills">
        @foreach ($slide['pills'] as $pill)
            <li>
                @if (is_array($pill))
                    @if (!empty($pill['icon']))
                        <span class="material-icons" aria-hidden="true">{{ $pill['icon'] }}</span>
                    @endif
                    {{ $pill['label'] ?? '' }}
                @else
                    {{ $pill }}
                @endif
            </li>
        @endforeach
    </ul>
@endif

@include('adminmodule::admin.process-guide.partials._training-pk-icons', ['slide' => $slide])

@if (!empty($slide['chips']))
    <ul class="pg-pk-chips">
        @foreach ($slide['chips'] as $chip)
            <li>
                @if (is_array($chip))
                    @if (!empty($chip['icon']))
                        <span class="material-icons" aria-hidden="true">{{ $chip['icon'] }}</span>
                    @endif
                    {{ $chip['label'] ?? '' }}
                @else
                    {{ $chip }}
                @endif
            </li>
        @endforeach
    </ul>
@endif

@if (!empty($slide['panel_links']))
    <div class="pg-training-panel-links">
        <h5 class="pg-training-panel-links-title">
            <span class="material-icons pg-training-block-icon" aria-hidden="true">link</span>
            Open in panel
        </h5>
        <div class="pg-training-panel-links-grid">
            @foreach ($slide['panel_links'] as $link)
                <a href="{{ $link['url'] }}" class="pg-training-panel-link" target="_blank" rel="noopener noreferrer">
                    <span class="material-icons" aria-hidden="true">open_in_new</span>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
@endif

@if (!empty($slide['shift_checklist']))
    <div class="pg-training-shift-checklist">
        <h5 class="pg-training-shift-checklist-title">
            <span class="material-icons pg-training-block-icon" aria-hidden="true">playlist_add_check</span>
            Every shift — check these first
        </h5>
        <ol class="pg-training-shift-checklist-list">
            @foreach ($slide['shift_checklist'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ol>
    </div>
@endif

@if (!empty($slide['source_guides']))
    <div class="pg-training-source-guides">
        @foreach ($slide['source_guides'] as $guide)
            <article class="pg-training-source-guide pg-training-source-guide--{{ $guide['tone'] ?? 'default' }}">
                <header class="pg-training-source-guide-head">
                    @if (!empty($guide['icon']))
                        <span class="material-icons pg-training-source-guide-icon" aria-hidden="true">{{ $guide['icon'] }}</span>
                    @endif
                    <div class="pg-training-source-guide-head-text">
                        <h5 class="pg-training-source-guide-title">{{ $guide['title'] }}</h5>
                        @if (!empty($guide['summary']))
                            <p class="pg-training-source-guide-summary">{{ $guide['summary'] }}</p>
                        @endif
                    </div>
                    @if (!empty($guide['badge']))
                        <span class="pg-training-source-guide-badge pg-training-source-guide-badge--{{ $guide['tone'] ?? 'default' }}">{{ $guide['badge'] }}</span>
                    @elseif (!empty($guide['tone']))
                        <span class="pg-training-source-guide-badge pg-training-source-guide-badge--{{ $guide['tone'] }}">
                            @if ($guide['tone'] === 'manual')
                                Manual lead
                            @elseif ($guide['tone'] === 'inbox')
                                Panel inbox
                            @elseif ($guide['tone'] === 'auto')
                                Auto lead
                            @elseif ($guide['tone'] === 'live')
                                Live priority
                            @else
                                {{ ucfirst($guide['tone']) }}
                            @endif
                        </span>
                    @endif
                </header>
                <div class="pg-training-source-guide-cols">
                    <div class="pg-training-source-guide-col">
                        <h6 class="pg-training-source-guide-col-title">
                            <span class="material-icons" aria-hidden="true">place</span>
                            {{ $slide['source_guide_cols']['where'] ?? 'Where to check' }}
                        </h6>
                        <ul>
                            @foreach ($guide['where'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="pg-training-source-guide-col pg-training-source-guide-col--warn">
                        <h6 class="pg-training-source-guide-col-title">
                            <span class="material-icons" aria-hidden="true">alarm</span>
                            {{ $slide['source_guide_cols']['dont_miss'] ?? 'How not to miss' }}
                        </h6>
                        <ul>
                            @foreach ($guide['dont_miss'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="pg-training-source-guide-col pg-training-source-guide-col--good">
                        <h6 class="pg-training-source-guide-col-title">
                            <span class="material-icons" aria-hidden="true">task_alt</span>
                            {{ $slide['source_guide_cols']['manage'] ?? 'How to manage' }}
                        </h6>
                        <ul>
                            @foreach ($guide['manage'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @if (!empty($guide['links']))
                    <div class="pg-training-source-guide-links">
                        <span class="pg-training-source-guide-links-label">
                            <span class="material-icons" aria-hidden="true">link</span>
                            Panel links
                        </span>
                        <div class="pg-training-source-guide-links-grid">
                            @foreach ($guide['links'] as $link)
                                <a href="{{ $link['url'] }}" class="pg-training-panel-link pg-training-panel-link--sm" target="_blank" rel="noopener noreferrer">
                                    <span class="material-icons" aria-hidden="true">open_in_new</span>
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
@endif

@if (!empty($slide['card_groups']))
    @foreach ($slide['card_groups'] as $group)
        <div class="pg-training-source-group pg-training-source-group--{{ $group['tone'] ?? 'default' }}">
            @if (!empty($group['title']) || !empty($group['hint']))
                <div class="pg-training-source-group-head">
                    @if (!empty($group['title']))
                        <h5 class="pg-training-source-group-title">{{ $group['title'] }}</h5>
                    @endif
                    @if (!empty($group['hint']))
                        <p class="pg-training-source-group-hint">{{ $group['hint'] }}</p>
                    @endif
                </div>
            @endif
            <div class="pg-training-type-grid{{ !empty($group['layout']) ? ' pg-training-type-grid--' . $group['layout'] : '' }}">
                @foreach ($group['cards'] as $card)
                    <div class="pg-training-type-card pg-training-type-card--{{ $card['color'] ?? 'default' }}{{ !empty($card['points']) ? ' pg-training-type-card--rich' : '' }}">
                        @if (!empty($card['icon']))
                            <span class="material-icons pg-training-type-icon" aria-hidden="true">{{ $card['icon'] }}</span>
                        @elseif (!empty($card['tag']))
                            <span class="pg-training-type-tag">{{ $card['tag'] }}</span>
                        @endif
                        <strong>{{ $card['title'] }}</strong>
                        <p>@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $card['text'] ?? ''])</p>
                        @if (!empty($card['points']))
                            <ul class="pg-training-type-points">
                                @foreach ($card['points'] as $point)
                                    <li>@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $point])</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif

@if (!empty($slide['ui_maps']))
    <div class="pg-training-ui-maps">
        @foreach ($slide['ui_maps'] as $map)
            <article class="pg-training-ui-map">
                <h5 class="pg-training-ui-map-title">
                    <span class="material-icons pg-training-block-icon" aria-hidden="true">map</span>
                    {{ $map['title'] ?? 'Panel map' }}
                </h5>
                @if (!empty($map['summary']))
                    <p class="pg-training-ui-map-summary">{{ $map['summary'] }}</p>
                @endif
                @if (!empty($map['image']))
                    <div class="pg-training-ui-map-media">
                        <img src="{{ process_guide_training_asset($map['image']) }}"
                             alt="{{ $map['title'] ?? 'Panel screenshot' }}"
                             loading="lazy">
                    </div>
                @endif
                <ol class="pg-training-ui-map-steps">
                    @foreach ($map['steps'] ?? [] as $step)
                        <li>
                            @if (!empty($step['label']))
                                <strong>{{ $step['label'] }}</strong>
                            @endif
                            @if (!empty($step['text']))
                                <span>{{ $step['text'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </article>
        @endforeach
    </div>
@endif

@if (!empty($slide['cards']) && empty($slide['source_guides']) && empty($slide['card_groups']))
    <div class="pg-training-type-grid{{ !empty($slide['cards_layout']) ? ' pg-training-type-grid--' . $slide['cards_layout'] : '' }}">
        @foreach ($slide['cards'] as $card)
            <div class="pg-training-type-card pg-training-type-card--{{ $card['color'] ?? 'default' }}{{ !empty($card['points']) ? ' pg-training-type-card--rich' : '' }}">
                @if (!empty($card['icon']))
                    <span class="material-icons pg-training-type-icon" aria-hidden="true">{{ $card['icon'] }}</span>
                @elseif (!empty($card['tag']))
                    <span class="pg-training-type-tag">{{ $card['tag'] }}</span>
                @endif
                <strong>{{ $card['title'] }}</strong>
                <p>{{ $card['text'] }}</p>
                @if (!empty($card['points']))
                    <ul class="pg-training-type-points">
                        @foreach ($card['points'] as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endif

@php
    $followSteps = $slide['follow_steps'] ?? ($slide['steps'] ?? []);
@endphp
@if (!empty($followSteps))
    <div class="pg-training-follow-block">
        <h5 class="pg-training-follow-title">
            <span class="material-icons pg-training-block-icon" aria-hidden="true">checklist</span>
            Steps to follow
        </h5>
        <ol class="pg-training-follow-steps">
            @foreach ($followSteps as $step)
                <li class="pg-training-follow-step">
                    @include('adminmodule::admin.process-guide.partials._training-step-body', ['step' => $step])
                </li>
            @endforeach
        </ol>
    </div>
@endif

@if (!empty($slide['qualifier']))
    <div class="pg-training-qualifier">
        <h5 class="pg-training-qualifier-title">
            <span class="material-icons pg-training-block-icon" aria-hidden="true">quiz</span>
            {{ $slide['qualifier']['title'] ?? 'Lead qualifier' }}
        </h5>
        <div class="pg-training-qualifier-list">
            @foreach ($slide['qualifier']['items'] ?? [] as $item)
                <div class="pg-training-qualifier-item">
                    <p class="pg-training-qualifier-question">{{ $item['question'] ?? '' }}</p>
                    <p class="pg-training-qualifier-meta">
                        <span class="pg-training-qualifier-type">{{ $item['type'] ?? '' }}</span>
                        @if (!empty($item['note']))
                            <span class="pg-training-qualifier-note">{{ $item['note'] }}</span>
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if (!empty($slide['path_steps']))
    @foreach ($slide['path_steps'] as $path)
        <div class="pg-training-path-steps-block">
            <h6 class="pg-training-path-steps-title">{{ $path['label'] ?? 'Path' }}</h6>
            <ol class="pg-training-follow-steps pg-training-follow-steps--path">
                @foreach ($path['steps'] as $step)
                    <li class="pg-training-follow-step">
                        @include('adminmodule::admin.process-guide.partials._training-step-body', ['step' => $step])
                    </li>
                @endforeach
            </ol>
        </div>
    @endforeach
@endif

@if (!empty($slide['scenarios']))
    <div class="pg-training-scenarios">
        @foreach ($slide['scenarios'] as $scenario)
            <div class="pg-training-scenario-card">
                @if (!empty($scenario['title']))
                    <h5 class="pg-training-scenario-title">
                        <span class="material-icons pg-training-scenario-title-icon" aria-hidden="true">play_lesson</span>
                        {{ $scenario['title'] }}
                    </h5>
                @endif
                @if (!empty($scenario['trigger']))
                    <p class="pg-training-scenario-row">
                        <span class="pg-training-scenario-label"><span class="material-icons" aria-hidden="true">notifications</span>Trigger</span>
                        {{ $scenario['trigger'] }}
                    </p>
                @endif
                @if (!empty($scenario['action']))
                    <p class="pg-training-scenario-row">
                        <span class="pg-training-scenario-label"><span class="material-icons" aria-hidden="true">touch_app</span>Action</span>
                        {{ $scenario['action'] }}
                    </p>
                @endif
                @if (!empty($scenario['panel']))
                    <p class="pg-training-scenario-row pg-training-scenario-row--panel">
                        <span class="pg-training-scenario-label"><span class="material-icons" aria-hidden="true">dashboard</span>Panel</span>
                        {{ $scenario['panel'] }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>
@endif

@if (!empty($slide['remember']) || !empty($slide['avoid']))
    <div class="pg-training-quick-rules">
        @if (!empty($slide['remember']))
            <div class="pg-training-quick-rules-col pg-training-quick-rules-col--do">
                <span class="pg-training-quick-rules-label">
                    <span class="material-icons pg-training-block-icon" aria-hidden="true">check_circle</span>
                    Key points
                </span>
                <ul>
                    @foreach ($slide['remember'] as $r)
                        <li>@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $r])</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (!empty($slide['avoid']))
            <div class="pg-training-quick-rules-col pg-training-quick-rules-col--dont">
                <span class="pg-training-quick-rules-label">
                    <span class="material-icons pg-training-block-icon" aria-hidden="true">cancel</span>
                    Avoid
                </span>
                <ul>
                    @foreach ($slide['avoid'] as $a)
                        <li>@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $a])</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

@if (!empty($slide['messages']))
    @foreach ($slide['messages'] as $msg)
        @include('adminmodule::admin.process-guide.partials._training-wa-accordion', ['msg' => $msg])
    @endforeach
@elseif (!empty($slide['message']))
    @include('adminmodule::admin.process-guide.partials._training-wa-accordion', ['msg' => $slide['message']])
@endif

@php
    $flowcharts = $slide['flowcharts'] ?? [];
    if (empty($flowcharts) && !empty($slide['flowchart'])) {
        $flowcharts = [['key' => $slide['flowchart'], 'title' => 'Process flow']];
    }
@endphp
@foreach ($flowcharts as $fc)
    <div class="pg-training-flowchart-block">
        @if (!empty($fc['title']))
            <h5 class="pg-training-flowchart-block-title">{{ $fc['title'] }}</h5>
        @endif
        @include('adminmodule::admin.process-guide.partials._training-flowchart', ['flowchartKey' => $fc['key'], 'flowchartsClass' => $flowchartsClass ?? null])
    </div>
@endforeach

@if (!empty($slide['tab_groups']))
    @include('adminmodule::admin.process-guide.partials._training-tab-grid', ['tabGroups' => $slide['tab_groups']])
@endif

@if (!empty($slide['highlight']))
    <p class="pg-pk-highlight">{{ $slide['highlight'] }}</p>
@endif
</div>
</div>
