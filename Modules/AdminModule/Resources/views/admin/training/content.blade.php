@extends('adminmodule::layouts.new-master')

@section('title', $program->name)

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between gap-2 flex-wrap">
                <div>
                    <h2 class="page-title mb-1">{{ $program->name }}</h2>
                    <p class="text-muted mb-0">Read the lesson, then mark the content finished.</p>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('admin.training.index') }}">Back to my training</a>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="text-muted small mb-1">Text</div>
                    <h3 class="h5">{{ $program->text_title ?: 'Lesson' }}</h3>
                    <div style="white-space: pre-wrap">{{ $program->text_body ?: 'This program has no text lesson yet.' }}</div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="text-muted small mb-1">Audio</div>
                    <div style="white-space: pre-wrap">{{ $program->audio_script ?: 'No audio script yet.' }}</div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="text-muted small mb-1">Video</div>
                    <div style="white-space: pre-wrap">{{ $program->video_note ?: 'No video note yet.' }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.training.content.done', $assignment) }}">
                @csrf
                <button class="btn btn--primary" type="submit" @disabled($assignment->progress->content_done_at)>
                    {{ $assignment->progress->content_done_at ? 'Content finished' : 'I have finished the content' }}
                </button>
                @if($assignment->progress->content_done_at)
                    <a class="btn btn-outline-secondary" href="{{ route('admin.training.index') }}">Back to my training</a>
                @endif
            </form>
        </div>
    </div>
@endsection
