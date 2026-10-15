<div class="lms-questions">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
        <div>
            <div class="fw-semibold">Questions</div>
            <div class="trn-meta">These come after the lesson. Write them here, or generate them from the saved lesson text.</div>
        </div>
        @if(in_array($lesson->type, ['text', 'audio', 'video'], true))
            <button class="btn btn-outline-secondary btn-sm" type="submit" form="generate-{{ $lesson->id }}">Generate from lesson</button>
        @endif
    </div>
    <label class="form-label" for="pass-{{ $lesson->id }}">Correct answers required</label>
    <input id="pass-{{ $lesson->id }}" class="form-control mb-2" style="max-width: 88px" type="number" min="1" max="20" name="pass_mark" value="{{ $lesson->pass_mark }}">
    @foreach($lesson->questions as $question)
        <div class="lms-q">
            <div class="lms-q-top">
                <span class="lms-index">{{ $loop->iteration }}</span>
                <input class="form-control" name="questions[{{ $question->id }}][prompt]" value="{{ $question->prompt }}" placeholder="Question" required>
                <button class="lms-iconbtn is-danger" type="submit" form="del-q-{{ $question->id }}" title="Remove question" onclick="return confirm('Remove this question?')"><span class="material-icons">delete</span></button>
            </div>
            <div class="mt-2">
                @foreach(array_pad($question->options, 3, '') as $index => $option)
                    <label class="lms-choice">
                        <input type="radio" name="questions[{{ $question->id }}][correct]" value="{{ $index }}" title="Correct answer" @checked((int) $question->correct_index === (int) $index)>
                        <input class="form-control" name="questions[{{ $question->id }}][options][{{ $index }}]" value="{{ $option }}" placeholder="Option {{ $index + 1 }}">
                    </label>
                @endforeach
            </div>
            <input class="form-control" name="questions[{{ $question->id }}][explanation]" value="{{ $question->explanation }}" placeholder="Why this answer is right">
        </div>
    @endforeach
    <div class="trn-meta mb-1">Add a question. Mark the correct option.</div>
    <input class="form-control mb-2" name="new_question_prompt" placeholder="Question">
    @foreach(range(0, 2) as $index)
        <label class="lms-choice">
            <input type="radio" name="new_question_correct" value="{{ $index }}" title="Correct answer" @checked($index === 0)>
            <input class="form-control" name="new_question_options[{{ $index }}]" placeholder="Option {{ $index + 1 }}">
        </label>
    @endforeach
    <input class="form-control" name="new_question_explanation" placeholder="Why this answer is right">
</div>
