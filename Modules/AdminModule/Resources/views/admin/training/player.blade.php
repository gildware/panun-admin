@extends('adminmodule::layouts.new-master')

@section('title', $program->name)

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    @php
        $position = $current ? $lessons->search(fn ($lesson) => $lesson->id === $current->id) : false;
        $blocked = false;
    @endphp
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">{{ $program->name }}</h2>
                    <div class="trn-meta mt-1">
                        @include('adminmodule::admin.training._difficulty', ['difficulty' => $program->difficulty])
                        @if(filled($program->short_description))
                            <span>{{ $program->short_description }}</span>
                        @endif
                    </div>
                </div>
                <div class="lms-pagehead-actions">
                    @include('adminmodule::admin.training._restart', ['assignment' => $assignment])
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.index') }}">Back to my training</a>
                </div>
            </div>
            <div class="lms-player">
                <div class="lms-player-side">
                    <div class="card mb-0">
                        <div class="card-body lms-outline">
                            @foreach($program->sections as $section)
                                <div class="lms-outline-section">{{ $section->title }}</div>
                                @foreach($section->lessons as $lesson)
                                    @php
                                        $done = $certified || $doneIds->contains($lesson->id);
                                        $locked = ! $certified && $blocked;
                                        if (! $done && ! $certified) {
                                            $blocked = true;
                                        }
                                    @endphp
                                    @if($locked)
                                        <div class="is-locked">
                                            @include('adminmodule::admin.training._type', ['type' => $lesson->type])
                                            <span>{{ $lesson->title }}</span>
                                            <span class="material-icons" style="font-size:16px">lock</span>
                                        </div>
                                    @else
                                        <a class="{{ $current && $current->id === $lesson->id ? 'is-current' : '' }}"
                                           href="{{ route('admin.training.content', [$assignment, 'lesson' => $lesson->id]) }}">
                                            @include('adminmodule::admin.training._type', ['type' => $lesson->type])
                                            <span>{{ $lesson->title }}</span>
                                            @if($done)
                                                <span class="material-icons" style="font-size:16px;color:#166534">check</span>
                                            @else
                                                <span class="trn-meta">{{ $lesson->typeLabel() }}</span>
                                            @endif
                                        </a>
                                    @endif
                                @endforeach
                            @endforeach
                            @if($certified)
                                <a class="btn btn--primary btn-sm mt-2" href="{{ route('admin.training.certificate', $assignment) }}">Certificate</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="lms-player-main">
                    <div class="card mb-0">
                        <div class="card-body">
                            @if(! $current)
                                <div class="trn-meta">This course has no lessons yet.</div>
                            @else
                                @php $lessonDone = $certified || ($lessonProgress && $lessonProgress->completed_at); @endphp
                                @unless($stage === 'result')
                                    <div class="lms-lesson-head">
                                        <div>
                                            <div class="lms-lesson-meta mb-1">
                                                @include('adminmodule::admin.training._type', ['type' => $current->type])
                                                <span class="lms-pill">{{ $current->typeLabel() }}</span>
                                                @if($position !== false)
                                                    <span class="trn-meta">Lesson {{ $position + 1 }} of {{ $lessons->count() }}</span>
                                                @endif
                                            </div>
                                            <h3 class="h6 mb-2">{{ $current->title }}</h3>
                                        </div>
                                        @if($lessonDone && ! in_array($stage, ['questions', 'ai'], true))
                                            <div class="d-flex gap-2 align-items-center">
                                                <span class="lms-pill is-ready">Finished</span>
                                                @if(is_array($lessonProgress?->quiz_answers) && $lessonProgress->quiz_answers !== [])
                                                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.content', [$assignment, 'lesson' => $current->id, 'step' => 'result']) }}">View result</a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endunless
                                @if($stage === 'ai')
                                    <div class="mb-3" style="white-space: pre-wrap">{{ $current->body }}</div>
                                    <form method="POST" action="{{ route('admin.training.lesson.ai', [$assignment, $current]) }}">
                                        @csrf
                                        <label class="form-label" for="ai-answer">Your response</label>
                                        <textarea id="ai-answer" class="form-control mb-2" name="answer" rows="6">{{ request('step') === 'retake' ? '' : $lessonProgress?->ai_answer }}</textarea>
                                        <button class="btn btn--primary btn-sm" type="submit">Submit for evaluation</button>
                                    </form>
                                @elseif($stage === 'questions')
                                    @if(in_array($current->type, ['audio', 'video'], true))
                                        <a class="btn btn-outline-secondary btn-sm mb-2" href="{{ route('admin.training.content', [$assignment, 'lesson' => $current->id, 'step' => 'watch']) }}">Back to the {{ $current->type }}</a>
                                    @endif
                                    <div class="fw-semibold mb-2">Questions</div>
                                    @if($current->questions->isEmpty())
                                        <div class="trn-meta">This lesson has no questions yet.</div>
                                    @else
                                        @include('adminmodule::admin.training._quiz_step', [
                                            'assignment' => $assignment,
                                            'lesson' => $current,
                                            'lessonProgress' => $lessonProgress,
                                            'review' => $certified || ($lessonProgress && $lessonProgress->completed_at),
                                        ])
                                    @endif
                                @elseif($stage === 'result')
                                    @include('adminmodule::admin.training._lesson_result', [
                                        'assignment' => $assignment,
                                        'lesson' => $current,
                                        'lessonProgress' => $lessonProgress,
                                        'certified' => $certified,
                                        'nextLessonId' => $nextLessonId,
                                    ])
                                @else
                                    @php $mediaLesson = in_array($current->type, ['audio', 'video'], true); @endphp
                                    <div class="lms-stage {{ $mediaLesson ? '' : 'is-single' }}">
                                        <div class="lms-stage-main">
                                            @if($mediaLesson)
                                                @include('adminmodule::admin.training._media', ['lesson' => $current])
                                            @else
                                                @php
                                                    $lessonHtml = (string) $current->body;
                                                    $lessonIsHtml = $lessonHtml !== strip_tags($lessonHtml);
                                                @endphp
                                                <div class="trn-prose">{!! $lessonIsHtml ? $lessonHtml : nl2br(e($lessonHtml)) !!}</div>
                                            @endif
                                            @if(! $lessonDone && $mediaLesson)
                                                @if($lessonProgress?->content_seen_at && $current->questions->isNotEmpty())
                                                    <a class="btn btn-outline-secondary btn-sm mb-2" href="{{ route('admin.training.content', [$assignment, 'lesson' => $current->id, 'step' => 'questions']) }}">Back to questions</a>
                                                @endif
                                                <form method="POST" action="{{ route('admin.training.lesson.seen', [$assignment, $current]) }}">
                                                    @csrf
                                                    <div class="lms-play-choice" data-media-choice hidden>
                                                        <div class="mb-2">{{ $current->type === 'audio' ? 'The audio has ended.' : 'The video has ended.' }}</div>
                                                        <div class="d-flex gap-2">
                                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-media-again>{{ $current->type === 'audio' ? 'Listen again' : 'Watch again' }}</button>
                                                            <button class="btn btn--primary btn-sm" type="submit">{{ $current->questions->isNotEmpty() ? 'Continue to questions' : 'I have finished this lesson' }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            @elseif(! $lessonDone && $current->questions->isNotEmpty())
                                                <form class="mt-3" method="POST" action="{{ route('admin.training.lesson.seen', [$assignment, $current]) }}">
                                                    @csrf
                                                    <button class="btn btn--primary btn-sm" type="submit">Continue to questions</button>
                                                </form>
                                            @elseif(! $lessonDone)
                                                <form class="mt-3" method="POST" action="{{ route('admin.training.lesson.done', [$assignment, $current]) }}">
                                                    @csrf
                                                    <button class="btn btn--primary btn-sm" type="submit">I have finished this lesson</button>
                                                </form>
                                            @endif
                                        </div>
                                        @if($mediaLesson)
                                            <aside class="lms-stage-side">
                                                @if(filled($current->body))
                                                    <div class="trn-prose">{!! nl2br(e($current->body)) !!}</div>
                                                @else
                                                    <div class="trn-meta">No notes for this lesson.</div>
                                                @endif
                                            </aside>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
