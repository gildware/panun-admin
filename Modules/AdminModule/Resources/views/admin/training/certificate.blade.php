@extends('adminmodule::layouts.new-master')

@section('title', $program->name.' certificate')

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between gap-2 flex-wrap">
                <div>
                    <h2 class="page-title mb-1">Certificate</h2>
                    <p class="text-muted mb-0">Issued only for {{ $program->name }}, version {{ $program->version }}.</p>
                </div>
                <div class="d-flex gap-2">
                    @include('adminmodule::admin.training._restart', ['assignment' => $assignment])
                    <a class="btn btn-outline-secondary" href="{{ route('admin.training.index') }}">Back to my training</a>
                </div>
            </div>
            <div class="card" style="max-width: 640px; {{ $progress->certified_at ? 'border-color: #166534' : '' }}">
                <div class="card-body">
                    <div class="text-muted small">Panun Kaergar</div>
                    <h3 class="mb-3">{{ $program->name }}</h3>
                    @if($progress->certified_at)
                        <p>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }} has passed this program.</p>
                        <p class="mb-0">Issued {{ $progress->certified_at->format('j M Y') }}.</p>
                    @else
                        <div class="d-flex justify-content-between border-bottom py-2"><span>Content finished</span>@include('adminmodule::admin.training._badge', ['text' => $progress->content_done_at ? 'Yes' : 'Not yet', 'tone' => $progress->content_done_at ? 'ok' : 'muted'])</div>
                        <div class="d-flex justify-content-between border-bottom py-2"><span>Questions</span>@include('adminmodule::admin.training._badge', ['text' => $snap['quiz'], 'tone' => $snap['quiz_tone']])</div>
                        <div class="d-flex justify-content-between py-2"><span>AI test</span>@include('adminmodule::admin.training._badge', ['text' => $snap['ai'], 'tone' => $snap['ai_tone']])</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
