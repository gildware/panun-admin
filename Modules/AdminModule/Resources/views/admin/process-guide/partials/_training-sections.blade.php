@if (!empty($sections))
    <div class="pg-training-sections">
        @foreach ($sections as $section)
            <div class="pg-training-section-card{{ !empty($section['is_definition']) ? ' pg-training-section-card--definition' : '' }}">
                <h5 class="pg-training-section-title">{{ $section['title'] }}</h5>

                @if (!empty($section['intro']))
                    <p class="pg-training-section-intro">@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $section['intro']])</p>
                @endif

                @if (!empty($section['what_is']))
                    <div class="pg-training-def-block">
                        <span class="pg-training-def-label">What it is</span>
                        <p>@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $section['what_is']])</p>
                    </div>
                @endif

                @if (!empty($section['when_to_use']))
                    <div class="pg-training-def-block">
                        <span class="pg-training-def-label">When to use</span>
                        <p>@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $section['when_to_use']])</p>
                    </div>
                @endif

                @if (!empty($section['not_this']))
                    <div class="pg-training-def-block pg-training-def-block--not">
                        <span class="pg-training-def-label">Do not confuse with</span>
                        <ul>
                            @foreach ($section['not_this'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($section['panel_value']))
                    <div class="pg-training-def-block pg-training-def-block--panel">
                        <span class="pg-training-def-label">Panel value</span>
                        <p><code>{{ $section['panel_value'] }}</code></p>
                    </div>
                @endif

                @if (!empty($section['do']))
                    <div class="pg-training-playbook-block">
                        <span class="pg-training-playbook-label">Do</span>
                        <ul>
                            @foreach ($section['do'] as $d)
                                <li>@include('adminmodule::admin.process-guide.partials._training-formatted-text', ['text' => $d])</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($section['say']))
                    <div class="pg-training-playbook-block pg-training-playbook-block--say">
                        <span class="pg-training-playbook-label">Say on call</span>
                        <blockquote>{{ $section['say'] }}</blockquote>
                    </div>
                @endif

                @if (!empty($section['mandatory']))
                    <div class="pg-training-section-block pg-training-section-block--mandatory">
                        <span class="pg-training-section-block-label">Mandatory</span>
                        <ul>
                            @foreach ($section['mandatory'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($section['dont_miss']))
                    <div class="pg-training-section-block pg-training-section-block--dont-miss">
                        <span class="pg-training-section-block-label">Do not miss</span>
                        <ul>
                            @foreach ($section['dont_miss'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($section['roleplay']))
                    @php $rp = $section['roleplay']; @endphp
                    <div class="pg-training-section-block pg-training-section-block--roleplay">
                        <span class="pg-training-section-block-label">Role-play{{ !empty($rp['title']) ? ': ' . $rp['title'] : '' }}</span>
                        @if (!empty($rp['when']))
                            <p class="pg-training-rp-when"><strong>When this happens:</strong> {{ $rp['when'] }}</p>
                        @elseif (!empty($rp['situation']))
                            <p class="pg-training-rp-situation"><strong>When:</strong> {{ $rp['situation'] }}</p>
                        @endif
                        @if (!empty($rp['script']))
                            <div class="pg-training-rp-script">
                                @foreach ($rp['script'] as $line)
                                    @php
                                        $whoClass = strtolower(preg_replace('/[^a-z0-9]+/', '-', $line['who'] ?? 'other'));
                                    @endphp
                                    <div class="pg-training-rp-line pg-training-rp-line--{{ $whoClass }}">
                                        <span class="pg-training-rp-who">{{ $line['who'] ?? '—' }}</span>
                                        <span class="pg-training-rp-text">{{ $line['line'] ?? '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if (!empty($rp['do_after']))
                            <p class="pg-training-roleplay-panel"><strong>Then do:</strong> {{ $rp['do_after'] }}</p>
                        @elseif (!empty($rp['panel_after']))
                            <p class="pg-training-roleplay-panel"><strong>Then do:</strong> {{ $rp['panel_after'] }}</p>
                        @endif
                        @if (!empty($rp['avoid']))
                            <p class="pg-training-roleplay-avoid"><strong>Wrong:</strong> {{ $rp['avoid'] }}</p>
                        @endif
                    </div>
                @endif

                @if (!empty($section['roleplays']))
                    @foreach ($section['roleplays'] as $rp)
                        <div class="pg-training-section-block pg-training-section-block--roleplay">
                            <span class="pg-training-section-block-label">Role-play: {{ $rp['title'] ?? 'Scenario' }}</span>
                            @if (!empty($rp['when']))
                                <p class="pg-training-rp-when"><strong>When this happens:</strong> {{ $rp['when'] }}</p>
                            @elseif (!empty($rp['situation']))
                                <p class="pg-training-rp-situation"><strong>When:</strong> {{ $rp['situation'] }}</p>
                            @endif
                            @if (!empty($rp['script']))
                                <div class="pg-training-rp-script">
                                    @foreach ($rp['script'] as $line)
                                        @php
                                            $whoClass = strtolower(preg_replace('/[^a-z0-9]+/', '-', $line['who'] ?? 'other'));
                                        @endphp
                                        <div class="pg-training-rp-line pg-training-rp-line--{{ $whoClass }}">
                                            <span class="pg-training-rp-who">{{ $line['who'] ?? '—' }}</span>
                                            <span class="pg-training-rp-text">{{ $line['line'] ?? '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if (!empty($rp['do_after']))
                                <p class="pg-training-roleplay-panel"><strong>Then do:</strong> {{ $rp['do_after'] }}</p>
                            @elseif (!empty($rp['panel_after']))
                                <p class="pg-training-roleplay-panel"><strong>Then do:</strong> {{ $rp['panel_after'] }}</p>
                            @endif
                            @if (!empty($rp['avoid']))
                                <p class="pg-training-roleplay-avoid"><strong>Wrong:</strong> {{ $rp['avoid'] }}</p>
                            @endif
                        </div>
                    @endforeach
                @endif

                @if (!empty($section['examples']))
                    <div class="pg-training-section-block pg-training-section-block--examples">
                        <span class="pg-training-section-block-label">{{ !empty($section['is_definition']) ? 'Identification — real phrases & what type they are' : 'Scenarios — what to do & when' }}</span>
                        @foreach ($section['examples'] as $ex)
                            <div class="pg-training-example">
                                @if (!empty($ex['label']))
                                    <strong>{{ $ex['label'] }}</strong>
                                @endif
                                @if (!empty($ex['when']))
                                    <p class="pg-training-scenario-row"><span>When</span> {{ $ex['when'] }}</p>
                                    <p class="pg-training-scenario-row"><span>Do</span> {{ $ex['do'] ?? '' }}</p>
                                    @if (!empty($ex['outcome']))
                                        <p class="pg-training-scenario-row"><span>Outcome</span> {{ $ex['outcome'] }}</p>
                                    @endif
                                @elseif (!empty($ex['body']))
                                    <p>{{ $ex['body'] }}</p>
                                    @if (!empty($ex['detail']))
                                        <ul class="pg-training-example-detail">
                                            @foreach ($ex['detail'] as $d)
                                                <li>{{ $d }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                @endif
                                @if (!empty($ex['message']))
                                    @include('adminmodule::admin.process-guide.partials._training-wa-accordion', ['msg' => $ex['message']])
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (!empty($section['message']))
                    @include('adminmodule::admin.process-guide.partials._training-wa-accordion', ['msg' => $section['message']])
                @endif

                @if (!empty($section['panel']))
                    <div class="pg-training-playbook-block pg-training-playbook-block--panel">
                        <span class="pg-training-playbook-label">Panel</span>
                        <ul>
                            @foreach ($section['panel'] as $p)
                                <li>{{ $p }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($section['flowchart']))
                    @include('adminmodule::admin.process-guide.partials._training-flowchart', ['flowchartKey' => $section['flowchart']])
                @endif
            </div>
        @endforeach
    </div>
@endif
