@extends('adminmodule::layouts.new-master')

@section('title', $program->name.' AI test')

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between gap-2 flex-wrap">
                <div>
                    <h2 class="page-title mb-1">AI test</h2>
                    <p class="text-muted mb-0">{{ $program->name }}. Pass mark {{ $program->ai_pass_mark }} of {{ $program->markingPoints->count() }} points. This base scores the answer against the marking guide. A live model can replace that later.</p>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('admin.training.index') }}">Back to my training</a>
            </div>

            <div class="card mb-3"><div class="card-body">{{ $program->ai_situation ?: 'No situation has been set.' }}</div></div>

            <form method="POST" action="{{ route('admin.training.ai.submit', $assignment) }}">
                @csrf
                <textarea class="form-control mb-3" name="answer" rows="6" placeholder="Write what you will say and what you will save">{{ old('answer', $progress->ai_answer) }}</textarea>
                <button class="btn btn--primary" type="submit">Submit for evaluation</button>
                @if($progress->snapshot()['ready'] || $progress->certified_at)
                    <a class="btn btn-outline-secondary" href="{{ route('admin.training.certificate', $assignment) }}">Certificate</a>
                @endif
            </form>

            @if(is_array($progress->ai_hits))
                <div class="card mt-3">
                    <div class="card-body">
                        <h3 class="h6">{{ $progress->ai_score }} of {{ $program->markingPoints->count() }} points</h3>
                        @foreach($progress->ai_hits as $hit)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>{{ $hit['label'] ?? '' }}</span>
                                @include('adminmodule::admin.training._badge', ['text' => !empty($hit['pass']) ? 'Hit' : 'Missed', 'tone' => !empty($hit['pass']) ? 'ok' : 'bad'])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
