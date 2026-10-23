@php
    $isAi = $lesson->type === 'ai';
    $correct = $isAi ? (int) ($lessonProgress->ai_score ?? 0) : (int) ($lessonProgress->quiz_correct ?? 0);
    $total = $isAi
        ? (is_array($lessonProgress?->ai_hits) ? count($lessonProgress->ai_hits) : $lesson->markingPoints->count())
        : $lesson->questions->count();
    $needed = (int) $lesson->pass_mark;
    $passed = (bool) ($lessonProgress?->completed_at) || $certified;
    $retake = ! $passed && ($isAi || $lesson->questions->isNotEmpty());
    $contentLabel = $lesson->type === 'audio' ? 'Listen to the audio' : ($lesson->type === 'video' ? 'Watch the video' : ($lesson->type === 'text' ? 'Read the lesson' : 'Review the questions'));
    $contentStep = $lesson->type === 'quiz' ? 'questions' : 'watch';
    $gif = $passed
        ? 'https://fonts.gstatic.com/s/e/notoemoji/latest/1f389/512.gif'
        : 'https://fonts.gstatic.com/s/e/notoemoji/latest/1f615/512.gif';
@endphp
<div class="lms-score {{ $passed ? 'is-pass' : 'is-fail' }}">
    <img class="lms-score-gif" src="{{ $gif }}" alt="" width="120" height="120">
    <span class="material-icons lms-score-icon">{{ $passed ? 'check_circle' : 'cancel' }}</span>
    <div class="lms-score-line">{{ $passed ? 'Lesson passed' : 'Not passed' }}</div>
    <div>{{ $correct }} of {{ $total }} correct.</div>
    @unless($passed)
        <div>You need {{ $needed }} to pass.</div>
    @endunless
    <div class="lms-score-actions d-flex gap-2 justify-content-center">
        @if($retake)
            <a class="btn btn--primary btn-sm" href="{{ route('admin.training.content', [$assignment, 'lesson' => $lesson->id, 'step' => 'retake']) }}">Retake test</a>
        @elseif(! $isAi)
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.content', [$assignment, 'lesson' => $lesson->id, 'step' => $contentStep]) }}">{{ $contentLabel }}</a>
        @endif
        @if($passed)
            @if($nextLessonId && $nextLessonId !== $lesson->id)
                <a class="btn btn--primary btn-sm" href="{{ route('admin.training.content', [$assignment, 'lesson' => $nextLessonId]) }}">Next lesson</a>
            @elseif($certified)
                <a class="btn btn--primary btn-sm" href="{{ route('admin.training.certificate', $assignment) }}">Certificate</a>
            @endif
        @endif
    </div>
</div>
@if(! $passed && $retake)
    <p class="trn-meta text-center mt-3 mb-0">Answers are not shown. Retake the test and aim for every question correct.</p>
@endif
