@extends('adminmodule::layouts.new-master')

@section('title', $program->name.' questions')

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between gap-2 flex-wrap">
                <div>
                    <h2 class="page-title mb-1">Questions</h2>
                    <p class="text-muted mb-0">{{ $program->name }}. Pass mark {{ $program->question_pass_mark }} of {{ $program->questions->count() }}.</p>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('admin.training.index') }}">Back to my training</a>
            </div>

            @if($program->questions->isEmpty())
                <div class="card"><div class="card-body text-muted">This program has no questions.</div></div>
            @else
                <form method="POST" action="{{ route('admin.training.questions.submit', $assignment) }}">
                    @csrf
                    @foreach($program->questions as $question)
                        @php $picked = $progress->quiz_answers[$question->id] ?? null; @endphp
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">{{ $question->prompt }}</div>
                                @foreach($question->options as $index => $option)
                                    <label class="d-block mb-1">
                                        <input type="radio" name="q[{{ $question->id }}]" value="{{ $index }}" @checked($picked !== null && (int) $picked === (int) $index)>
                                        {{ $option }}
                                    </label>
                                @endforeach
                                @if($picked !== null)
                                    <div class="small mt-2 {{ (int) $picked === (int) $question->correct_index ? 'text-success' : 'text-danger' }}">
                                        {{ $question->explanation }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    <button class="btn btn--primary" type="submit">Submit answers</button>
                    @php $snap = $progress->snapshot(); @endphp
                    @if($progress->quiz_correct !== null && ! $snap['quiz_blocking'])
                        <a class="btn btn-outline-secondary" href="{{ route($snap['ai_blocking'] ? 'admin.training.ai' : 'admin.training.certificate', $assignment) }}">Continue</a>
                    @endif
                </form>
            @endif
        </div>
    </div>
@endsection
