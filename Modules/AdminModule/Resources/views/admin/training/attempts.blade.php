@extends('adminmodule::layouts.new-master')

@section('title', $program->name.' record')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">{{ $program->name }}</h2>
                    <div class="trn-meta mt-1">
                        @if($assignment->user_id !== auth()->id())
                            {{ \Modules\AdminModule\Support\TrainingPeople::name($assignment->user) }} ·
                        @endif
                        Restarted {{ (int) $progress->restart_count }} {{ (int) $progress->restart_count === 1 ? 'time' : 'times' }}.
                        Each earlier attempt stays in this record.
                    </div>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="{{ $assignment->user_id === auth()->id() ? route('admin.training.content', $assignment) : route('admin.training.live') }}">Back</a>
            </div>

            @forelse($attempts as $attempt)
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
                            <div class="fw-semibold">Attempt {{ $attempt->attempt_number }}</div>
                            <div class="trn-meta">
                                @if($attempt->started_at)
                                    Started {{ $attempt->started_at->format('j M Y, H:i') }}
                                @endif
                                · Restarted {{ $attempt->restarted_at->format('j M Y, H:i') }}
                            </div>
                        </div>
                        <div class="mb-3">
                            @include('adminmodule::admin.training._badge', [
                                'text' => $attempt->certified_at ? 'Certificate issued '.$attempt->certified_at->format('j M Y') : 'Not certified',
                                'tone' => $attempt->certified_at ? 'ok' : 'muted',
                            ])
                            @if($attempt->ai_score !== null)
                                <span class="trn-meta ms-2">AI test {{ $attempt->ai_score }}</span>
                            @endif
                        </div>
                        @foreach($attempt->lessons ?? [] as $lesson)
                            <div class="border-top py-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-semibold">{{ $lesson['title'] ?? 'Lesson' }}</div>
                                    <div class="trn-meta">
                                        @if(! empty($lesson['completed_at']))
                                            Passed
                                            @if(($lesson['question_count'] ?? 0) > 0)
                                                · {{ (int) ($lesson['quiz_correct'] ?? 0) }} of {{ (int) $lesson['question_count'] }}
                                            @endif
                                            @if(is_array($lesson['ai_hits'] ?? null))
                                                · {{ (int) ($lesson['ai_score'] ?? 0) }} of {{ count($lesson['ai_hits']) }}
                                            @endif
                                        @else
                                            Not passed
                                        @endif
                                    </div>
                                </div>
                                @foreach($lesson['questions'] ?? [] as $question)
                                    @if(($question['picked_text'] ?? null) !== null)
                                        <div class="trn-meta mt-1">{{ $question['prompt'] }}</div>
                                        <div>{{ $question['picked_text'] }} · {{ ! empty($question['correct']) ? 'Correct' : 'Not correct' }}</div>
                                    @endif
                                @endforeach
                                @if(filled($lesson['ai_answer'] ?? null))
                                    <div class="trn-meta mt-1">Reply</div>
                                    <div style="white-space: pre-wrap">{{ $lesson['ai_answer'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card">
                    <div class="card-body trn-meta">No earlier attempts yet. Restart saves the current attempt here.</div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
