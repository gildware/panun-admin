@extends('adminmodule::layouts.new-master')

@section('title', $assignment->test->name)

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
    @include('adminmodule::admin.training.calls._styles')
@endpush

@section('content')
    @php
        $mine = auth()->id() === $assignment->user_id;
        $employeeName = \Modules\AdminModule\Support\TrainingPeople::name($assignment->user);
    @endphp
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">{{ $assignment->test->name }}</h2>
                    <div class="trn-meta mt-1">
                        @unless($mine)
                            {{ $employeeName }} ·
                        @endunless
                        Pass mark {{ $assignment->test->pass_mark }}@if($markTotal = array_sum(array_map('intval', (array) ($assignment->test->skill_marks ?? [])))) of {{ $markTotal }}@endif.
                    </div>
                </div>
                <div class="lms-pagehead-actions">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ $mine ? route('admin.training.calls.index') : route('admin.training.calls.manage.edit', $assignment->test) }}">Back</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="trn-meta mb-2">Scored skills</div>
                    <div class="lms-course-meta">
                        @foreach($assignment->test->skills ?? [] as $skillId)
                            @if($skill = $skills[$skillId] ?? null)
                                <span class="lms-pill">{{ $skill['name'] }}@if(isset($assignment->test->skill_marks[$skillId])) · {{ (int) $assignment->test->skill_marks[$skillId] }}@endif</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            @foreach($assignment->test->scenarios ?? [] as $scenarioId)
                @php
                    $scenario = $scenarios[$scenarioId] ?? null;
                    $session = $rollup['sessions'][$scenarioId] ?? null;
                @endphp
                @continue(! $scenario)
                <div class="card">
                    <div class="card-body">
                        <div class="call-box-head">
                            <div>
                                <h3 class="h6 mb-1">{{ $scenario['name'] }}</h3>
                                <div class="trn-meta">{{ $scenario['caller_name'] }} · {{ $scenario['caller_role'] }}. {{ $scenario['summary'] }}</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                @if($session)
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.result', [$assignment, 'scenario' => $scenarioId]) }}">Result</a>
                                @endif
                                @if($mine)
                                    <a class="btn btn--primary btn-sm" href="{{ route('admin.training.calls.room', [$assignment, 'scenario' => $scenarioId, 'fresh' => $session ? 1 : 0]) }}">{{ $session ? 'Call again' : 'Start call' }}</a>
                                @endif
                            </div>
                        </div>
                        <div class="lms-course-meta mb-2">
                            @if($session)
                                @include('adminmodule::admin.training._badge', [
                                    'text' => $session->passed ? 'Passed' : 'Below the pass mark',
                                    'tone' => $session->passed ? 'ok' : 'bad',
                                ])
                                <span class="trn-meta">{{ $session->overall }}</span>
                            @else
                                @include('adminmodule::admin.training._badge', ['text' => 'Not started', 'tone' => 'muted'])
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
