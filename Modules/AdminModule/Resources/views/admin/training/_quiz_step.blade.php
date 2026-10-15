@php
    $review = $review ?? false;
@endphp
<form class="lms-step" method="POST" action="{{ route('admin.training.lesson.quiz', [$assignment, $lesson]) }}" data-quiz-step @if($review) data-quiz-review="1" @endif>
    @csrf
    <div class="lms-step-top">
        <div class="trn-meta" data-quiz-count>Question 1 of {{ $lesson->questions->count() }}</div>
        <div class="lms-step-bar" aria-hidden="true"><span data-quiz-bar></span></div>
    </div>
    @foreach($lesson->questions as $question)
        @php $picked = $lessonProgress?->completed_at ? (($lessonProgress->quiz_answers ?? [])[$question->id] ?? null) : null; @endphp
        <fieldset class="lms-step-q" data-quiz-pane @if(! $loop->first) hidden @endif>
            <legend>{{ $question->prompt }}</legend>
            @foreach($question->options as $index => $option)
                <label class="lms-option">
                    <input type="radio" name="q[{{ $question->id }}]" value="{{ $index }}" @checked($picked !== null && (int) $picked === (int) $index) @disabled($review)>
                    <span>{{ $option }}</span>
                </label>
            @endforeach
        </fieldset>
    @endforeach
    <div class="lms-step-nav">
        <button class="btn btn-outline-secondary btn-sm" type="button" data-quiz-back hidden>Back</button>
        <div class="d-flex gap-2">
            <button class="btn btn--primary btn-sm" type="button" data-quiz-next @if($lesson->questions->count() < 2) hidden @endif>Next</button>
            @unless($review)
                <button class="btn btn--primary btn-sm" type="submit" data-quiz-submit @if($lesson->questions->count() > 1) hidden @endif>Submit answers</button>
            @endunless
        </div>
    </div>
</form>
<script>
    (function () {
        document.querySelectorAll('[data-quiz-step]').forEach(function (form) {
            if (form.getAttribute('data-quiz-ready') === '1') {
                return;
            }
            form.setAttribute('data-quiz-ready', '1');
            var panes = Array.prototype.slice.call(form.querySelectorAll('[data-quiz-pane]'));
            var index = 0;
            var review = form.getAttribute('data-quiz-review') === '1';
            var count = form.querySelector('[data-quiz-count]');
            var bar = form.querySelector('[data-quiz-bar]');
            var back = form.querySelector('[data-quiz-back]');
            var next = form.querySelector('[data-quiz-next]');
            var submit = form.querySelector('[data-quiz-submit]');

            function paint() {
                panes.forEach(function (pane, paneIndex) {
                    pane.hidden = paneIndex !== index;
                });
                count.textContent = 'Question ' + (index + 1) + ' of ' + panes.length;
                bar.style.width = ((index + 1) / panes.length * 100) + '%';
                back.hidden = index === 0;
                var last = index === panes.length - 1;
                next.hidden = last;
                if (submit) {
                    submit.hidden = !last;
                }
            }

            function go(nextIndex) {
                if (nextIndex > index && !review && !panes[index].querySelector('input:checked')) {
                    panes[index].classList.add('is-needed');
                    return;
                }
                panes[index].classList.remove('is-needed');
                index = nextIndex;
                paint();
            }

            next.addEventListener('click', function () {
                go(index + 1);
            });
            back.addEventListener('click', function () {
                go(index - 1);
            });
            form.addEventListener('submit', function (event) {
                var missing = panes.findIndex(function (pane) {
                    return !pane.querySelector('input:checked');
                });
                if (missing !== -1) {
                    event.preventDefault();
                    index = missing;
                    panes[missing].classList.add('is-needed');
                    paint();
                }
            });
            paint();
        });
    })();
</script>
