@extends('adminmodule::layouts.new-master')

@section('title', 'Training setup')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">Training setup</h2>
                    <div class="trn-meta mt-1">Courses for the team. Publish a course before it can be assigned.</div>
                </div>
                @include('adminmodule::admin.training.manage._nav')
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.training.manage.store') }}" class="lms-toolbar">
                        @csrf
                        <div>
                            <label class="form-label" for="course-name">Course name</label>
                            <input id="course-name" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Customer Support" required value="{{ old('name') }}">
                        </div>
                        <div>
                            <label class="form-label" for="course-difficulty">Difficulty</label>
                            <select id="course-difficulty" class="form-select @error('difficulty') is-invalid @enderror" name="difficulty" required>
                                <option value="" @selected(old('difficulty') === null) disabled>Choose difficulty</option>
                                @foreach(\Modules\AdminModule\Entities\TrainingProgram::DIFFICULTIES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('difficulty') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn--primary btn-sm" type="submit">Create course</button>
                    </form>
                    <div class="trn-filters mt-2">
                        <a class="{{ $difficulty ? '' : 'is-on' }}" href="{{ route('admin.training.manage') }}"
                           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>All {{ $total }}</a>
                        @foreach(\Modules\AdminModule\Entities\TrainingProgram::DIFFICULTIES as $key => $label)
                            <a class="{{ $difficulty === $key ? 'is-on' : '' }}" href="{{ route('admin.training.manage', ['difficulty' => $key]) }}"
                               @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>{{ $label }} {{ (int) ($counts[$key] ?? 0) }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="lms-courses">
                @forelse($programs as $program)
                    <article class="lms-course">
                        <div>
                            <div class="lms-course-title">{{ $program->name }}</div>
                            @if(filled($program->short_description))
                                <div class="trn-meta mt-1">{{ $program->short_description }}</div>
                            @endif
                            <div class="lms-course-meta">
                                @include('adminmodule::admin.training._difficulty', ['difficulty' => $program->difficulty])
                                <span class="trn-meta">{{ $program->sections_count }} {{ $program->sections_count === 1 ? 'section' : 'sections' }}</span>
                                <span class="trn-meta">{{ $program->lessons_count }} {{ $program->lessons_count === 1 ? 'lesson' : 'lessons' }}</span>
                                @include('adminmodule::admin.training._badge', [
                                    'text' => $program->isPublished() ? 'Published' : 'Draft',
                                    'tone' => $program->isPublished() ? 'ok' : 'warn',
                                ])
                                <span class="trn-meta">{{ $program->assignments_count }} assigned</span>
                                <span class="trn-meta">v{{ $program->version }}</span>
                            </div>
                        </div>
                        <div class="lms-course-actions">
                            <a class="btn btn--primary btn-sm" href="{{ route('admin.training.manage.curriculum', $program) }}">Curriculum</a>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.manage.edit', $program) }}">Edit</a>
                            @if(! $program->isPublished())
                                <form method="POST" action="{{ route('admin.training.manage.publish', $program) }}" onsubmit="return confirm('Publish this course? It can then be assigned to the team.')">
                                    @csrf
                                    <button class="btn btn-outline-secondary btn-sm" type="submit">Publish</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="lms-empty">No courses{{ $difficulty ? ' in this difficulty' : '' }} yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
