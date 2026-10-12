@extends('adminmodule::layouts.new-master')

@section('title', 'Curriculum')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
@endpush

@section('content')
    @php
        $lessonTotal = $program->sections->sum(fn ($section) => $section->lessons->count());
        $curriculumUrl = route('admin.training.manage.curriculum', $program);
        $missingLessons = $program->sections->flatMap->lessons->reject(fn ($lesson) => $lesson->hasContent());
    @endphp
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">{{ $program->name }}</h2>
                    <div class="trn-meta mt-1">
                        @include('adminmodule::admin.training._difficulty', ['difficulty' => $program->difficulty])
                        {{ $program->sections->count() }} {{ $program->sections->count() === 1 ? 'section' : 'sections' }}
                        · {{ $lessonTotal }} {{ $lessonTotal === 1 ? 'lesson' : 'lessons' }}
                        · the team takes lessons in this order
                    </div>
                </div>
                <div class="lms-pagehead-actions">
                    @include('adminmodule::admin.training.manage._nav')
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.manage.edit', $program) }}">Course</a>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.manage') }}">Back</a>
                    @if(! $program->isPublished())
                        @if($missingLessons->isNotEmpty())
                            <button class="btn btn--primary btn-sm" type="button" disabled title="{{ $missingLessons->pluck('title')->implode(', ') }} still need content">Publish</button>
                        @else
                            <form method="POST" action="{{ route('admin.training.manage.publish', $program) }}" onsubmit="return confirm('Publish this course? It can then be assigned to the team.')">
                                @csrf
                                <button class="btn btn--primary btn-sm" type="submit">Publish</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            @forelse($program->sections as $section)
                <section class="lms-section" data-section-id="{{ $section->id }}" data-section-reorder="{{ route('admin.training.manage.sections.reorder', $program) }}" data-lesson-reorder="{{ route('admin.training.manage.lessons.reorder', [$program, $section]) }}">
                    <header class="lms-section-head">
                        <button class="lms-iconbtn lms-drag" type="button" draggable="true" data-drag="section" title="Drag to reorder"><span class="material-icons">drag_indicator</span></button>
                        <span class="lms-index">{{ $loop->iteration }}</span>
                        @if($renameSection === $section->id)
                            <form class="lms-rename" method="POST" action="{{ route('admin.training.manage.sections.update', [$program, $section]) }}">
                                @csrf
                                @method('PUT')
                                <input class="form-control" name="title" value="{{ $section->title }}" required aria-label="Section title">
                                <button class="btn btn--primary btn-sm" type="submit">Save</button>
                                <a class="btn btn-outline-secondary btn-sm" href="{{ $curriculumUrl }}">Cancel</a>
                            </form>
                            <span></span>
                            <span></span>
                        @else
                            <h3 class="lms-section-title">{{ $section->title }}</h3>
                            <span class="trn-meta">{{ $section->lessons->count() }} {{ $section->lessons->count() === 1 ? 'lesson' : 'lessons' }}</span>
                            <div class="lms-row-actions">
                                <a class="lms-iconbtn" href="{{ $curriculumUrl }}?rename={{ $section->id }}" title="Rename section"><span class="material-icons">edit</span></a>
                                <button class="lms-iconbtn" type="submit" form="up-section-{{ $section->id }}" title="Move section up" @disabled($loop->first)><span class="material-icons">expand_less</span></button>
                                <button class="lms-iconbtn" type="submit" form="down-section-{{ $section->id }}" title="Move section down" @disabled($loop->last)><span class="material-icons">expand_more</span></button>
                                <button class="lms-iconbtn is-danger" type="submit" form="del-section-{{ $section->id }}" title="Remove section" onclick="return confirm('Remove this section and its lessons?')"><span class="material-icons">delete</span></button>
                            </div>
                        @endif
                    </header>

                    @forelse($section->lessons as $lesson)
                        <div class="lms-lesson {{ $openLesson === $lesson->id ? 'is-open' : '' }}" data-lesson-id="{{ $lesson->id }}">
                            <button class="lms-iconbtn lms-drag" type="button" draggable="true" data-drag="lesson" title="Drag to reorder"><span class="material-icons">drag_indicator</span></button>
                            @include('adminmodule::admin.training._type', ['type' => $lesson->type])
                            <div>
                                <div class="lms-lesson-title">{{ $lesson->title }}</div>
                                <div class="lms-lesson-meta">
                                    <span class="lms-pill">{{ $lesson->typeLabel() }}</span>
                                    @if(in_array($lesson->type, ['text', 'audio', 'video', 'quiz'], true))
                                        <span class="trn-meta">{{ $lesson->questions->count() }} {{ $lesson->questions->count() === 1 ? 'question' : 'questions' }}</span>
                                    @endif
                                    @if(in_array($lesson->type, ['ai'], true))
                                        <span class="trn-meta">Pass {{ $lesson->pass_mark }}</span>
                                    @endif
                                    <span class="lms-pill {{ $lesson->hasContent() ? 'is-ready' : 'is-empty' }}">{{ $lesson->hasContent() ? 'Ready' : 'Needs content' }}</span>
                                </div>
                            </div>
                            <div class="lms-row-actions">
                                <a class="lms-iconbtn" href="{{ $curriculumUrl }}?lesson={{ $lesson->id }}" title="Edit lesson"><span class="material-icons">edit</span></a>
                                <button class="lms-iconbtn" type="submit" form="up-{{ $lesson->id }}" title="Move lesson up" @disabled($loop->first)><span class="material-icons">expand_less</span></button>
                                <button class="lms-iconbtn" type="submit" form="down-{{ $lesson->id }}" title="Move lesson down" @disabled($loop->last)><span class="material-icons">expand_more</span></button>
                                <button class="lms-iconbtn is-danger" type="submit" form="del-lesson-{{ $lesson->id }}" title="Remove lesson" onclick="return confirm('Remove this lesson?')"><span class="material-icons">delete</span></button>
                            </div>
                        </div>

                        @if($openLesson === $lesson->id)
                            <form class="lms-editor" method="POST" enctype="multipart/form-data" action="{{ route('admin.training.manage.lessons.update', [$program, $lesson]) }}">
                                @csrf
                                @method('PUT')
                                <label class="form-label" for="lesson-title-{{ $lesson->id }}">Lesson title</label>
                                <input id="lesson-title-{{ $lesson->id }}" class="form-control mb-2" name="title" value="{{ $lesson->title }}" required>
                                <label class="form-label" for="lesson-type-{{ $lesson->id }}">Lesson type</label>
                                <select id="lesson-type-{{ $lesson->id }}" class="form-select mb-2" name="type" style="max-width: 220px">
                                    @foreach(\Modules\AdminModule\Entities\TrainingLesson::TYPES as $key => $label)
                                        <option value="{{ $key }}" @selected($lesson->type === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if($lesson->type === 'text')
                                    <label class="form-label" for="lesson-body-{{ $lesson->id }}">Lesson text</label>
                                    <textarea id="lesson-body-{{ $lesson->id }}" class="form-control trn-editor" name="body" rows="12">{{ $lesson->body }}</textarea>
                                @elseif(in_array($lesson->type, ['audio', 'video'], true))
                                    @php $mediaKind = old('media_kind', $lesson->media_kind ?: 'url'); @endphp
                                    <div class="lms-media mb-2" data-media-toggle>
                                        <div class="form-label">{{ $lesson->type === 'audio' ? 'Audio' : 'Video' }}</div>
                                        <div class="lms-source">
                                            <label><input type="radio" name="media_kind" value="url" @checked($mediaKind !== 'upload')> Link</label>
                                            <label><input type="radio" name="media_kind" value="upload" @checked($mediaKind === 'upload')> Upload</label>
                                        </div>
                                        <div data-media-panel="url" @if($mediaKind === 'upload') hidden @endif>
                                            <input class="form-control" type="url" name="media_url" value="{{ $lesson->media_kind === 'url' ? $lesson->media_path : '' }}" placeholder="https://">
                                            <div class="trn-meta mt-1">A direct file link, or a YouTube or Vimeo link.</div>
                                        </div>
                                        <div data-media-panel="upload" @if($mediaKind !== 'upload') hidden @endif>
                                            <input class="form-control" type="file" name="media_file" accept="{{ $lesson->type === 'audio' ? 'audio/*' : 'video/mp4,video/webm,video/quicktime' }}">
                                            <div class="trn-meta mt-1">{{ $lesson->type === 'audio' ? 'MP3, WAV, M4A, or OGG.' : 'MP4, WEBM, or MOV.' }} Up to 50 MB.</div>
                                        </div>
                                    </div>
                                    @include('adminmodule::admin.training._media', ['lesson' => $lesson])
                                    <label class="form-label" for="lesson-notes-{{ $lesson->id }}">Lesson text</label>
                                    <textarea id="lesson-notes-{{ $lesson->id }}" class="form-control mb-2" name="body" rows="5">{{ $lesson->body }}</textarea>
                                    <div class="trn-meta mb-2">Write what this lesson teaches. Generate questions uses this text.</div>
                                @elseif($lesson->type === 'ai')
                                    <label class="form-label" for="pass-ai-{{ $lesson->id }}">Points required</label>
                                    <input id="pass-ai-{{ $lesson->id }}" class="form-control mb-2" style="max-width: 88px" type="number" min="1" max="20" name="pass_mark" value="{{ $lesson->pass_mark }}">
                                @endif
                                @if(in_array($lesson->type, ['text', 'audio', 'video', 'quiz'], true))
                                    @include('adminmodule::admin.training.manage._questions', ['lesson' => $lesson])
                                @elseif($lesson->type === 'ai')
                                    <label class="form-label" for="situation-{{ $lesson->id }}">Situation</label>
                                    <textarea id="situation-{{ $lesson->id }}" class="form-control mb-2" name="body" rows="4">{{ $lesson->body }}</textarea>
                                    <div class="trn-meta mb-2">Each row is one point. A comma means every phrase is required. A bar means any one phrase is enough.</div>
                                    @foreach($lesson->markingPoints as $point)
                                        <div class="lms-point mb-1">
                                            <input class="form-control" name="points[{{ $point->id }}][label]" value="{{ $point->label }}" placeholder="Point" required>
                                            <input class="form-control" name="points[{{ $point->id }}][keywords]" value="{{ $point->keywords }}" placeholder="phrase, phrase | other phrase" required>
                                            <button class="lms-iconbtn is-danger" type="submit" form="del-point-{{ $point->id }}" title="Remove point" onclick="return confirm('Remove this point?')"><span class="material-icons">delete</span></button>
                                        </div>
                                    @endforeach
                                    <div class="lms-point">
                                        <input class="form-control" name="new_point_label" placeholder="New point">
                                        <input class="form-control" name="new_point_keywords" placeholder="phrase, phrase | other phrase">
                                        <span></span>
                                    </div>
                                @endif
                                <div class="lms-editor-actions">
                                    <button class="btn btn--primary btn-sm" type="submit">Save lesson</button>
                                    <a class="btn btn-outline-secondary btn-sm" href="{{ $curriculumUrl }}">Close</a>
                                </div>
                            </form>
                        @endif
                    @empty
                        <div class="lms-empty">No lessons in this section yet.</div>
                    @endforelse

                    @if($addSection === $section->id)
                        <form class="lms-add" method="POST" action="{{ route('admin.training.manage.lessons.store', [$program, $section]) }}">
                            @csrf
                            <input class="form-control" name="title" placeholder="Lesson title" required>
                            <select class="form-select" name="type" required aria-label="Lesson type">
                                @foreach(\Modules\AdminModule\Entities\TrainingLesson::TYPES as $key => $label)
                                    @continue($key === 'quiz')
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn--primary btn-sm" type="submit">Add lesson</button>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ $curriculumUrl }}">Cancel</a>
                        </form>
                    @else
                        <div class="lms-section-foot">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ $curriculumUrl }}?add={{ $section->id }}">Add lesson</a>
                        </div>
                    @endif
                </section>
            @empty
                <div class="lms-section">
                    <div class="lms-empty">Add a section, then add the lessons the team will take in order.</div>
                </div>
            @endforelse

            @if($newSection)
                <form class="lms-new-section" method="POST" action="{{ route('admin.training.manage.sections.store', $program) }}">
                    @csrf
                    <input class="form-control" name="title" placeholder="Section title" required>
                    <button class="btn btn--primary btn-sm" type="submit">Add section</button>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ $curriculumUrl }}">Cancel</a>
                </form>
            @else
                <a class="btn btn-outline-secondary btn-sm" href="{{ $curriculumUrl }}?new=1">Add section</a>
            @endif

            @foreach($program->sections as $section)
                <form id="del-section-{{ $section->id }}" method="POST" action="{{ route('admin.training.manage.sections.destroy', [$program, $section]) }}">
                    @csrf
                    @method('DELETE')
                </form>
                <form id="up-section-{{ $section->id }}" method="POST" action="{{ route('admin.training.manage.sections.move', [$program, $section, 'up']) }}">@csrf</form>
                <form id="down-section-{{ $section->id }}" method="POST" action="{{ route('admin.training.manage.sections.move', [$program, $section, 'down']) }}">@csrf</form>
                @foreach($section->lessons as $lesson)
                    <form id="del-lesson-{{ $lesson->id }}" method="POST" action="{{ route('admin.training.manage.lessons.destroy', [$program, $lesson]) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                    <form id="up-{{ $lesson->id }}" method="POST" action="{{ route('admin.training.manage.lessons.move', [$program, $lesson, 'up']) }}">@csrf</form>
                    <form id="down-{{ $lesson->id }}" method="POST" action="{{ route('admin.training.manage.lessons.move', [$program, $lesson, 'down']) }}">@csrf</form>
                    @if(in_array($lesson->type, ['text', 'audio', 'video'], true))
                        <form id="generate-{{ $lesson->id }}" method="POST" action="{{ route('admin.training.manage.lessons.generate', [$program, $lesson]) }}">@csrf</form>
                    @endif
                    @foreach($lesson->questions as $question)
                        <form id="del-q-{{ $question->id }}" method="POST" action="{{ route('admin.training.manage.questions.destroy', [$program, $question]) }}">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                    @foreach($lesson->markingPoints as $point)
                        <form id="del-point-{{ $point->id }}" method="POST" action="{{ route('admin.training.manage.points.destroy', [$program, $point]) }}">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                @endforeach
            @endforeach
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('assets/admin-module/plugins/tinymce/tinymce.min.js') }}"></script>
    <script>
        (function () {
            function csrf() {
                var meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }
            function saveOrder(url, order) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf()
                    },
                    body: JSON.stringify({ order: order })
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('reorder');
                    }
                    window.location.reload();
                }).catch(function () {
                    window.location.reload();
                });
            }
            function placeBefore(parent, node, anchor) {
                parent.insertBefore(node, anchor);
            }
            document.querySelectorAll('[data-drag="lesson"]').forEach(function (handle) {
                var row = handle.closest('.lms-lesson');
                handle.addEventListener('dragstart', function (event) {
                    row.classList.add('is-dragging');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', row.getAttribute('data-lesson-id'));
                });
                handle.addEventListener('dragend', function () {
                    row.classList.remove('is-dragging');
                });
            });
            document.querySelectorAll('.lms-lesson').forEach(function (row) {
                row.addEventListener('dragover', function (event) {
                    if (!document.querySelector('.lms-lesson.is-dragging')) {
                        return;
                    }
                    event.preventDefault();
                });
                row.addEventListener('drop', function (event) {
                    var dragging = document.querySelector('.lms-lesson.is-dragging');
                    if (!dragging || dragging === row) {
                        return;
                    }
                    var from = dragging.closest('.lms-section');
                    if (from !== row.closest('.lms-section')) {
                        return;
                    }
                    event.preventDefault();
                    var editor = dragging.nextElementSibling;
                    var movingEditor = editor && editor.classList.contains('lms-editor') ? editor : null;
                    var rect = row.getBoundingClientRect();
                    var after = event.clientY > rect.top + rect.height / 2;
                    var target = row;
                    if (after) {
                        target = row.nextElementSibling;
                        if (target && target.classList.contains('lms-editor')) {
                            target = target.nextElementSibling;
                        }
                    }
                    placeBefore(from, dragging, target);
                    if (movingEditor) {
                        placeBefore(from, movingEditor, dragging.nextElementSibling);
                    }
                    var ids = Array.prototype.map.call(from.querySelectorAll('.lms-lesson'), function (item) {
                        return item.getAttribute('data-lesson-id');
                    });
                    saveOrder(from.getAttribute('data-lesson-reorder'), ids);
                });
            });
            document.querySelectorAll('[data-drag="section"]').forEach(function (handle) {
                var section = handle.closest('.lms-section');
                handle.addEventListener('dragstart', function (event) {
                    section.classList.add('is-dragging');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', section.getAttribute('data-section-id'));
                });
                handle.addEventListener('dragend', function () {
                    section.classList.remove('is-dragging');
                });
            });
            document.querySelectorAll('.lms-section').forEach(function (section) {
                section.addEventListener('dragover', function (event) {
                    if (!document.querySelector('.lms-section.is-dragging')) {
                        return;
                    }
                    event.preventDefault();
                });
                section.addEventListener('drop', function (event) {
                    var dragging = document.querySelector('.lms-section.is-dragging');
                    if (!dragging || dragging === section || dragging.contains(event.target)) {
                        return;
                    }
                    event.preventDefault();
                    event.stopPropagation();
                    var parent = section.parentNode;
                    var rect = section.getBoundingClientRect();
                    var after = event.clientY > rect.top + rect.height / 2;
                    placeBefore(parent, dragging, after ? section.nextElementSibling : section);
                    var ids = Array.prototype.map.call(parent.querySelectorAll(':scope > .lms-section'), function (item) {
                        return item.getAttribute('data-section-id');
                    });
                    saveOrder(section.getAttribute('data-section-reorder'), ids);
                });
            });
            document.querySelectorAll('[data-media-toggle]').forEach(function (wrap) {
                function syncMedia() {
                    var selected = wrap.querySelector('input[name="media_kind"]:checked');
                    var value = selected ? selected.value : 'url';
                    wrap.querySelectorAll('[data-media-panel]').forEach(function (panel) {
                        var on = panel.getAttribute('data-media-panel') === value;
                        panel.hidden = !on;
                        panel.querySelectorAll('input').forEach(function (field) {
                            field.disabled = !on;
                        });
                    });
                }
                wrap.querySelectorAll('input[name="media_kind"]').forEach(function (input) {
                    input.addEventListener('change', syncMedia);
                });
                syncMedia();
            });
            if (!window.tinymce || !document.querySelector('textarea.trn-editor')) {
                return;
            }
            tinymce.remove('textarea.trn-editor');
            tinymce.init({
                selector: 'textarea.trn-editor',
                width: '100%',
                min_height: 280,
                height: 320,
                menubar: false,
                branding: false,
                promotion: false,
                convert_urls: false,
                relative_urls: false,
                plugins: 'lists link autoresize',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | removeformat',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 15px; line-height: 1.6; }',
                setup: function (editor) {
                    editor.on('change input undo redo', function () {
                        editor.save();
                    });
                    editor.on('init', function () {
                        var form = editor.getElement() && editor.getElement().form;
                        if (form) {
                            form.addEventListener('submit', function () {
                                editor.save();
                            });
                        }
                    });
                }
            });
        })();
    </script>
@endpush
