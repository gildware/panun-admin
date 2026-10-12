@extends('adminmodule::layouts.new-master')

@section('title', 'Call result')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
    @include('adminmodule::admin.training.calls._styles')
@endpush

@section('content')
    @php
        $scenario = $session ? ($scenarios[$session->scenario] ?? null) : null;
        $next = $rollup['next'] ?? null;
        $mine = auth()->id() === $assignment->user_id;
    @endphp
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">{{ $scenario['name'] ?? $assignment->test->name }}</h2>
                    <div class="trn-meta mt-1">
                        @unless($mine)
                            {{ \Modules\AdminModule\Support\TrainingPeople::name($assignment->user) }} ·
                        @endunless
                        {{ $assignment->test->name }}. Pass mark {{ $assignment->test->pass_mark }}@if($markTotal = array_sum(array_map('intval', (array) ($assignment->test->skill_marks ?? [])))) of {{ $markTotal }}@endif.
                    </div>
                </div>
                <div class="lms-pagehead-actions">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.show', $assignment) }}">All scenarios</a>
                    @if($mine && $session)
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.room', [$assignment, 'scenario' => $session->scenario, 'fresh' => 1]) }}">Call again</a>
                    @endif
                    @if($mine && $next)
                        <a class="btn btn--primary btn-sm" href="{{ route('admin.training.calls.room', [$assignment, 'scenario' => $next]) }}">Next call</a>
                    @endif
                </div>
            </div>

            @if($session && $session->overall !== null)
                @php
                    $earned = 0;
                    $hasMarks = false;
                    foreach ($session->scores ?? [] as $scoreRow) {
                        if (isset($scoreRow['marks'])) {
                            $hasMarks = true;
                            $earned += (int) ($scoreRow['earned'] ?? 0);
                        }
                    }
                @endphp
                <div class="lms-stats mb-3">
                    <div class="lms-stat {{ $session->passed ? 'is-ok' : 'is-bad' }}">
                        <b>{{ $hasMarks ? $earned : $session->overall }}</b>
                        <span>{{ $session->passed ? 'Passed' : 'Below the pass mark' }}</span>
                    </div>
                    <div class="lms-stat">
                        <b>{{ $rollup['done'] }}/{{ $rollup['total'] }}</b>
                        <span>Scenarios done</span>
                    </div>
                    @if($rollup['overall'] !== null)
                        <div class="lms-stat {{ $rollup['complete'] ? ($rollup['passed'] ? 'is-ok' : 'is-bad') : '' }}">
                            <b>{{ $rollup['overall'] }}</b>
                            <span>Test average</span>
                        </div>
                    @endif
                </div>
                <div class="card">
                    <div class="card-body">
                        <p class="mb-3">{{ $session->summary }}</p>
                        @foreach($session->scores ?? [] as $row)
                            <div class="call-score">
                                <div>
                                    <strong>{{ $row['label'] ?? '' }}</strong>
                                    <p class="trn-meta mb-0">{{ $row['note'] ?? '' }}</p>
                                </div>
                                <div class="lms-progress-cell">
                                    <div class="lms-bar {{ ((int) ($row['score'] ?? 0)) >= 70 ? 'is-done' : '' }}">
                                        <span style="width: {{ (int) ($row['score'] ?? 0) }}%"></span>
                                    </div>
                                    <span>
                                        @if(isset($row['marks']))
                                            {{ (int) ($row['earned'] ?? 0) }} / {{ (int) $row['marks'] }}
                                        @else
                                            {{ (int) ($row['score'] ?? 0) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h3 class="h6">Transcript</h3>
                        <div class="call-lines" style="max-height: 360px">
                            @foreach($session->transcript ?? [] as $row)
                                <div class="call-bubble {{ ($row['role'] ?? '') === 'employee' ? 'is-employee' : 'is-customer' }}">
                                    <small>{{ ($row['role'] ?? '') === 'employee' ? \Modules\AdminModule\Support\TrainingPeople::name($assignment->user) : ($scenario['caller_name'] ?? 'Caller') }}</small>
                                    {{ $row['text'] ?? '' }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body trn-meta">This call does not have a result yet.</div>
                </div>
            @endif
        </div>
    </div>
@endsection
