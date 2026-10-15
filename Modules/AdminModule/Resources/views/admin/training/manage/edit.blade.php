@extends('adminmodule::layouts.new-master')

@section('title', 'Course')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">{{ $program->name }}</h2>
                    <div class="trn-meta mt-1">Course cover. Lessons live in the curriculum.</div>
                </div>
                <div class="lms-pagehead-actions">
                    @include('adminmodule::admin.training.manage._nav')
                    <a class="btn btn--primary btn-sm" href="{{ route('admin.training.manage.curriculum', $program) }}">Curriculum</a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.manage') }}">Back</a>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-lg-8">
                    <form method="POST" action="{{ route('admin.training.manage.update', $program) }}">
                        @csrf
                        @method('PUT')
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <label class="form-label" for="course-name">Course name</label>
                                        <input id="course-name" class="form-control" name="name" value="{{ old('name', $program->name) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="course-difficulty">Difficulty</label>
                                        <select id="course-difficulty" class="form-select" name="difficulty" required>
                                            @foreach(\Modules\AdminModule\Entities\TrainingProgram::DIFFICULTIES as $key => $label)
                                                <option value="{{ $key }}" @selected(old('difficulty', $program->difficulty) === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="course-summary">Short description</label>
                                        <input id="course-summary" class="form-control" name="short_description" maxlength="500" value="{{ old('short_description', $program->short_description) }}" placeholder="One line the team sees on the course">
                                        <div class="trn-meta mt-1">Shown on the course list and on My training.</div>
                                    </div>
                                </div>
                                <button class="btn btn--primary btn-sm mt-3" type="submit">Save course</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4">
                    <aside class="lms-side">
                        <dl>
                            <dt>Status</dt>
                            <dd>@include('adminmodule::admin.training._badge', ['text' => $program->isPublished() ? 'Published' : 'Draft', 'tone' => $program->isPublished() ? 'ok' : 'warn'])</dd>
                            <dt>Difficulty</dt>
                            <dd>@include('adminmodule::admin.training._difficulty', ['difficulty' => $program->difficulty])</dd>
                            <dt>Curriculum</dt>
                            <dd>{{ $program->sections_count }} {{ $program->sections_count === 1 ? 'section' : 'sections' }} · {{ $program->lessons_count }} {{ $program->lessons_count === 1 ? 'lesson' : 'lessons' }}</dd>
                            <dt>Assigned</dt>
                            <dd>{{ $program->assignments_count }}</dd>
                            <dt>Version</dt>
                            <dd>v{{ $program->version }}</dd>
                        </dl>
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.manage.curriculum', $program) }}">Open curriculum</a>
                    </aside>
                </div>
            </div>
        </div>
    </div>
@endsection
