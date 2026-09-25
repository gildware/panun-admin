@php
    $user = $person['user'] ?? null;
    $profile = $person['profile'] ?? null;
    $tab = $person['tab'] ?? 'documents';
    $tabs = [
        'documents' => 'Documents',
        'leaves' => 'Leaves',
        'salary' => 'Salary',
        'payslips' => 'Payslips',
        'bank' => 'Bank details',
    ];
    $latestStructure = ($person['structures'] ?? collect())->first();
    $documentReturn = old('form_context') === 'document';
    $bankEditing = old('return_tab') === 'bank' && $errors->any();
@endphp
@if(! $user || ! $profile)
    <div class="people-ws-head"><div><h1>People file</h1><p>Choose a person from the People tab.</p></div></div>
@else
    <div class="people-file-back">
        <a class="btn-pw" href="{{ route('admin.hr.index', ['section' => 'people']) }}">Back to people</a>
    </div>
    @php
        $managerName = $profile->manager ? $workspace->displayName($profile->manager) : 'Not set';
    @endphp
    <div class="people-file">
        <aside class="people-file-side">
        <div id="person-details-view" @if($errors->any() && ! $documentReturn && ! $bankEditing) hidden @endif>
            <article class="people-ws-card people-id-card">
                <div class="people-id-top">
                    <span class="people-id-kicker">Details</span>
                    <span class="people-id-actions">
                        <button class="btn-pw" type="button" id="person-details-edit">Edit</button>
                        <span class="people-id-pill">{{ $profile->employment_type === 'contract' ? 'Contract' : 'Full time' }}</span>
                    </span>
                </div>
                <div class="people-id-person">
                    <img class="people-id-photo avatar-img" src="{{ admin_nav_image_src($user->profile_image_full_path, 'profile') }}" alt="{{ $workspace->displayName($user) }}">
                    <div>
                        <h2>{{ $workspace->displayName($user) }}</h2>
                        <p>{{ $profile->job_title ?: 'Seat not set' }}@if($profile->department) · {{ $profile->department }}@endif</p>
                        <p class="people-id-code">{{ $profile->employee_code ?: 'No employee code' }}</p>
                    </div>
                </div>
                <dl class="people-id-grid">
                    <div class="people-id-wide">
                        <dt>Email</dt>
                        <dd class="{{ $user->email ? '' : 'is-empty' }}">{{ $user->email ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd class="{{ $user->phone ? '' : 'is-empty' }}">{{ $user->phone ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Department</dt>
                        <dd class="{{ $profile->department ? '' : 'is-empty' }}">{{ $profile->department ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Manager</dt>
                        <dd class="{{ $profile->manager ? '' : 'is-empty' }}">{{ $managerName }}</dd>
                    </div>
                    <div>
                        <dt>Location</dt>
                        <dd class="{{ $profile->work_location ? '' : 'is-empty' }}">{{ $profile->work_location ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>{{ $workspace->statusLabel($profile->employment_status ?: 'active') }}</dd>
                    </div>
                    <div>
                        <dt>Seat</dt>
                        <dd class="{{ $profile->job_title ? '' : 'is-empty' }}">{{ $profile->job_title ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Type</dt>
                        <dd>{{ $profile->employment_type === 'contract' ? 'Contract' : 'Full time' }}</dd>
                    </div>
                    <div>
                        <dt>Joined</dt>
                        <dd class="{{ $profile->joined_on ? '' : 'is-empty' }}">{{ $profile->joined_on ? $profile->joined_on->format('j M Y') : 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Last working day</dt>
                        <dd class="{{ $profile->last_working_day ? '' : 'is-empty' }}">{{ $profile->last_working_day ? $profile->last_working_day->format('j M Y') : 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Date of birth</dt>
                        <dd class="{{ $profile->date_of_birth ? '' : 'is-empty' }}">{{ $profile->date_of_birth ? $profile->date_of_birth->format('j M Y') : 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Emergency contact</dt>
                        <dd class="{{ $profile->emergency_contact ? '' : 'is-empty' }}">{{ $profile->emergency_contact ?: 'Not set' }}</dd>
                    </div>
                    <div class="people-id-wide">
                        <dt>Address</dt>
                        <dd class="{{ $profile->address ? '' : 'is-empty' }}">{{ $profile->address ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </article>
        </div>
        <form class="people-ws-card people-ws-mt" id="person-details-form" method="post" action="{{ route('admin.hr.person') }}" @if(! $errors->any() || $documentReturn || $bankEditing) hidden @endif>
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="people-ws-form-grid">
                <div class="field"><label>Name</label><input value="{{ $workspace->displayName($user) }}" readonly></div>
                <div class="field"><label>Email</label><input value="{{ $user->email }}" readonly></div>
                <div class="field"><label>Phone</label><input value="{{ $user->phone ?: 'Not set' }}" readonly></div>
                <div class="field"><label>Employee code</label><input value="{{ $profile->employee_code }}" readonly></div>
                <div class="field"><label for="job_title">Seat</label><input id="job_title" name="job_title" value="{{ old('job_title', $profile->job_title) }}" required></div>
                <div class="field"><label for="department">Department</label>
                    <select id="department" name="department">
                        <option value="">Not set</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->name }}" @selected(old('department', $profile->department) === $department->name)>{{ $department->name }}</option>
                        @endforeach
                        @if($profile->department && ! $departments->contains('name', $profile->department))
                            <option value="{{ $profile->department }}" selected>{{ $profile->department }}</option>
                        @endif
                    </select>
                    <span class="people-ws-note">Add a missing department on the Departments tab.</span>
                </div>
                <div class="field"><label for="work_location">Work location</label><input id="work_location" name="work_location" value="{{ old('work_location', $profile->work_location) }}"></div>
                <div class="field"><label for="employment_type">Type</label>
                    <select id="employment_type" name="employment_type">
                        <option value="full_time" @selected(old('employment_type', $profile->employment_type) === 'full_time')>Full time</option>
                        <option value="contract" @selected(old('employment_type', $profile->employment_type) === 'contract')>Contract</option>
                    </select>
                </div>
                <div class="field"><label for="employment_status">Status</label>
                    <select id="employment_status" name="employment_status">
                        @foreach(['active' => 'Active', 'notice' => 'On notice', 'exited' => 'Exited'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('employment_status', $profile->employment_status ?: 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="manager_id">Manager</label>
                    <select id="manager_id" name="manager_id">
                        <option value="">Not set</option>
                        @foreach($staff as $candidate)
                            @if($candidate->id !== $user->id)
                                <option value="{{ $candidate->id }}" @selected(old('manager_id', $profile->manager_id) === $candidate->id)>{{ $workspace->displayName($candidate) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="joined_on">Joined</label><input id="joined_on" type="date" name="joined_on" value="{{ old('joined_on', optional($profile->joined_on)->toDateString()) }}"></div>
                <div class="field"><label for="last_working_day">Last working day</label><input id="last_working_day" type="date" name="last_working_day" value="{{ old('last_working_day', optional($profile->last_working_day)->toDateString()) }}"></div>
                <div class="field"><label for="date_of_birth">Date of birth</label><input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($profile->date_of_birth)->toDateString()) }}"></div>
                <div class="field"><label for="emergency_contact">Emergency contact</label><input id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact', $profile->emergency_contact) }}"></div>
                <div class="field" style="grid-column:1/-1"><label for="address">Address</label><input id="address" name="address" value="{{ old('address', $profile->address) }}"></div>
                <input type="hidden" name="bank_name" value="{{ old('bank_name', $profile->bank_name) }}">
                <input type="hidden" name="bank_account" value="{{ old('bank_account', $profile->bank_account) }}">
                <input type="hidden" name="bank_ifsc" value="{{ old('bank_ifsc', $profile->bank_ifsc) }}">
                <input type="hidden" name="pan" value="{{ old('pan', $profile->pan) }}">
                <input type="hidden" name="aadhaar" value="{{ old('aadhaar', $profile->aadhaar) }}">
                <input type="hidden" name="uan" value="{{ old('uan', $profile->uan) }}">
                <input type="hidden" name="esi_number" value="{{ old('esi_number', $profile->esi_number) }}">
            </div>
            <div class="people-ws-actions">
                <button class="btn-pw" type="button" id="person-details-cancel">Cancel</button>
                <button class="btn-pw primary" type="submit">Save file</button>
            </div>
        </form>
        <script>
            (function () {
                var view = document.getElementById('person-details-view');
                var form = document.getElementById('person-details-form');
                var edit = document.getElementById('person-details-edit');
                var cancel = document.getElementById('person-details-cancel');
                var side = document.querySelector('.people-file-side');
                function showEdit(on) {
                    if (view) view.hidden = on;
                    if (form) form.hidden = !on;
                    if (edit) edit.hidden = on;
                    if (side) side.classList.toggle('is-editing', on);
                }
                if (edit) edit.addEventListener('click', function () { showEdit(true); });
                if (cancel) cancel.addEventListener('click', function () { showEdit(false); });
                if (form && !form.hidden) showEdit(true);
            })();
        </script>
        </aside>
        <div class="people-file-main">
        <nav class="people-ws-tabs" aria-label="Employee file">
            @foreach($tabs as $key => $label)
                <a class="{{ $tab === $key ? 'is-on' : '' }}" href="{{ route('admin.hr.index', ['section' => 'person', 'user' => $user->id, 'tab' => $key]) }}">{{ $label }}</a>
            @endforeach
        </nav>

    @if($tab === 'bank')
        <div id="person-bank-view" @if($bankEditing) hidden @endif>
            <article class="people-ws-card people-id-card">
                <div class="people-id-top">
                    <span class="people-id-kicker">Bank details</span>
                    <button class="btn-pw" type="button" id="person-bank-edit">Edit</button>
                </div>
                <dl class="people-id-grid">
                    <div>
                        <dt>Bank</dt>
                        <dd class="{{ $profile->bank_name ? '' : 'is-empty' }}">{{ $profile->bank_name ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Account</dt>
                        <dd class="{{ $profile->bank_account ? '' : 'is-empty' }}">{{ $profile->bank_account ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>IFSC</dt>
                        <dd class="{{ $profile->bank_ifsc ? '' : 'is-empty' }}">{{ $profile->bank_ifsc ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>PAN</dt>
                        <dd class="{{ $profile->pan ? '' : 'is-empty' }}">{{ $profile->pan ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>Aadhaar</dt>
                        <dd class="{{ $profile->aadhaar ? '' : 'is-empty' }}">{{ $profile->aadhaar ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>UAN</dt>
                        <dd class="{{ $profile->uan ? '' : 'is-empty' }}">{{ $profile->uan ?: 'Not set' }}</dd>
                    </div>
                    <div>
                        <dt>ESI number</dt>
                        <dd class="{{ $profile->esi_number ? '' : 'is-empty' }}">{{ $profile->esi_number ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </article>
        </div>
        <form class="people-ws-card" id="person-bank-form" method="post" action="{{ route('admin.hr.person') }}" @if(! $bankEditing) hidden @endif>
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="return_tab" value="bank">
            <input type="hidden" name="job_title" value="{{ $profile->job_title }}">
            <input type="hidden" name="department" value="{{ $profile->department }}">
            <input type="hidden" name="work_location" value="{{ $profile->work_location }}">
            <input type="hidden" name="employment_type" value="{{ $profile->employment_type ?: 'full_time' }}">
            <input type="hidden" name="employment_status" value="{{ $profile->employment_status ?: 'active' }}">
            <input type="hidden" name="manager_id" value="{{ $profile->manager_id }}">
            <input type="hidden" name="joined_on" value="{{ optional($profile->joined_on)->toDateString() }}">
            <input type="hidden" name="last_working_day" value="{{ optional($profile->last_working_day)->toDateString() }}">
            <input type="hidden" name="date_of_birth" value="{{ optional($profile->date_of_birth)->toDateString() }}">
            <input type="hidden" name="emergency_contact" value="{{ $profile->emergency_contact }}">
            <input type="hidden" name="address" value="{{ $profile->address }}">
            <h2>Bank details</h2>
            <div class="people-ws-form-grid">
                <div class="field"><label for="bank_name">Bank</label><input id="bank_name" name="bank_name" value="{{ old('bank_name', $profile->bank_name) }}"></div>
                <div class="field"><label for="bank_account">Account</label><input id="bank_account" name="bank_account" value="{{ old('bank_account', $profile->bank_account) }}"></div>
                <div class="field"><label for="bank_ifsc">IFSC</label><input id="bank_ifsc" name="bank_ifsc" value="{{ old('bank_ifsc', $profile->bank_ifsc) }}"></div>
                <div class="field"><label for="pan">PAN</label><input id="pan" name="pan" value="{{ old('pan', $profile->pan) }}" maxlength="10"></div>
                <div class="field"><label for="aadhaar">Aadhaar</label><input id="aadhaar" name="aadhaar" value="{{ old('aadhaar', $profile->aadhaar) }}" maxlength="12"></div>
                <div class="field"><label for="uan">UAN</label><input id="uan" name="uan" value="{{ old('uan', $profile->uan) }}"></div>
                <div class="field"><label for="esi_number">ESI number</label><input id="esi_number" name="esi_number" value="{{ old('esi_number', $profile->esi_number) }}"></div>
            </div>
            <div class="people-ws-actions">
                <button class="btn-pw" type="button" id="person-bank-cancel">Cancel</button>
                <button class="btn-pw primary" type="submit">Save bank details</button>
            </div>
        </form>
        <script>
            (function () {
                var view = document.getElementById('person-bank-view');
                var form = document.getElementById('person-bank-form');
                var edit = document.getElementById('person-bank-edit');
                var cancel = document.getElementById('person-bank-cancel');
                function showBank(on) {
                    if (view) view.hidden = on;
                    if (form) form.hidden = !on;
                }
                if (edit) edit.addEventListener('click', function () { showBank(true); });
                if (cancel) cancel.addEventListener('click', function () { showBank(false); });
                if (form && !form.hidden) showBank(true);
            })();
        </script>
    @endif

    @if($tab === 'documents')
        @php $uploadedDocuments = $person['documents']->filter(fn ($document) => filled($document->file_path)); @endphp
        <div class="people-doc-toolbar">
            <button class="btn-pw primary" type="button" data-bs-toggle="modal" data-bs-target="#documentUploadModal">Upload a document</button>
        </div>
        <article class="people-ws-card people-ws-mt people-ws-scroll">
            <table>
                <thead><tr><th>Document</th><th>File</th><th>Added</th><th></th></tr></thead>
                <tbody>
                @forelse($uploadedDocuments as $document)
                    @php
                        $documentExt = strtolower(pathinfo($document->original_name ?: $document->file_path, PATHINFO_EXTENSION));
                        $documentKind = in_array($documentExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? 'image' : ($documentExt === 'pdf' ? 'pdf' : 'file');
                    @endphp
                    <tr>
                        <td>
                            <button
                                class="people-doc-open"
                                type="button"
                                data-doc-view
                                data-url="{{ route('admin.people.documents.view', $document) }}"
                                data-download="{{ route('admin.people.documents.download', $document) }}"
                                data-name="{{ $document->title }}"
                                data-kind="{{ $documentKind }}"
                            >{{ $document->title }}</button>
                        </td>
                        <td>{{ $document->original_name ?: 'File' }}</td>
                        <td>{{ $document->uploaded_at ? $document->uploaded_at->format('j M Y') : '—' }}</td>
                        <td class="people-ws-actions">
                            <button
                                class="btn-pw"
                                type="button"
                                data-doc-view
                                data-url="{{ route('admin.people.documents.view', $document) }}"
                                data-download="{{ route('admin.people.documents.download', $document) }}"
                                data-name="{{ $document->title }}"
                                data-kind="{{ $documentKind }}"
                            >View</button>
                            <a class="btn-pw" href="{{ route('admin.people.documents.download', $document) }}">Download</a>
                            <button
                                class="btn-pw danger"
                                type="button"
                                data-doc-remove
                                data-action="{{ route('admin.hr.documents.destroy', $document) }}"
                                data-name="{{ $document->title }}"
                            >Remove</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="people-ws-note">No documents uploaded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>

        <div class="modal fade" id="documentUploadModal" tabindex="-1" aria-labelledby="documentUploadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content people-ws-dept-modal">
                    <form method="post" action="{{ route('admin.hr.documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <input type="hidden" name="form_context" value="document">
                        <div class="modal-header">
                            <h2 class="modal-title" id="documentUploadModalLabel">Upload a document</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="people-ws-note">Name the document, then choose the file. PDF, Word, or an image, up to 5 MB.</p>
                            <div class="field">
                                <label for="doc_title">Document name</label>
                                <input id="doc_title" name="title" value="{{ $documentReturn ? old('title') : '' }}" required maxlength="80" placeholder="Offer letter">
                            </div>
                            <div class="field people-doc-upload">
                                <label>File</label>
                                <div class="upload-file">
                                    <input type="file" class="upload-file__input" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" data-maxFileSize="5MB" required>
                                    <div class="upload-file__img">
                                        <img src="{{ asset('assets/admin-module/img/media/upload-file.png') }}" alt="Upload file">
                                    </div>
                                    <span class="upload-file__edit">
                                        <span class="material-icons">edit</span>
                                    </span>
                                </div>
                                <p class="people-ws-note" id="name_of_file">No file chosen</p>
                                <div class="people-doc-chosen" id="documentChosenPreview" hidden>
                                    <img id="documentChosenImage" alt="Selected document" hidden>
                                    <iframe id="documentChosenFrame" title="Selected document" hidden></iframe>
                                    <p class="people-ws-note" id="documentChosenNote" hidden></p>
                                </div>
                                <span id="progress-label" hidden>0%</span>
                                <progress id="uploadProgress" value="0" max="100" hidden></progress>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn-pw primary" type="submit">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="documentRemoveModal" tabindex="-1" aria-labelledby="documentRemoveTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content people-ws-dept-modal">
                    <form id="documentRemoveForm" method="post" action="#">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h2 class="modal-title" id="documentRemoveTitle">Remove this document?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="people-ws-note" id="documentRemoveText">This document will be taken off this file.</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn-pw danger" type="submit">Remove</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content people-ws-dept-modal">
                    <div class="modal-header">
                        <h2 class="modal-title" id="documentPreviewTitle">Document</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body people-doc-preview">
                        <img id="documentPreviewImage" alt="" hidden>
                        <iframe id="documentPreviewFrame" title="Document" hidden></iframe>
                        <p class="people-ws-note" id="documentPreviewNote" hidden>This file cannot be shown here. Download it to open it.</p>
                    </div>
                    <div class="modal-footer">
                        <a class="btn-pw" id="documentPreviewDownload" href="#">Download</a>
                        <button class="btn-pw" type="button" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @push('script')
            <script>
                (function () {
                    var preview = document.getElementById('documentPreviewModal');
                    var previewTitle = document.getElementById('documentPreviewTitle');
                    var previewImg = document.getElementById('documentPreviewImage');
                    var previewFrame = document.getElementById('documentPreviewFrame');
                    var previewNote = document.getElementById('documentPreviewNote');
                    var previewDownload = document.getElementById('documentPreviewDownload');
                    var chosen = document.getElementById('documentChosenPreview');
                    var chosenImg = document.getElementById('documentChosenImage');
                    var chosenFrame = document.getElementById('documentChosenFrame');
                    var chosenNote = document.getElementById('documentChosenNote');
                    var input = document.querySelector('#documentUploadModal .upload-file__input');
                    var chosenUrl = null;

                    function clearChosen() {
                        if (chosenUrl) {
                            URL.revokeObjectURL(chosenUrl);
                            chosenUrl = null;
                        }
                        if (!chosen) return;
                        chosen.hidden = true;
                        chosenImg.hidden = true;
                        chosenImg.removeAttribute('src');
                        chosenFrame.hidden = true;
                        chosenFrame.removeAttribute('src');
                        chosenNote.hidden = true;
                        chosenNote.textContent = '';
                    }

                    function showStored(url, name, kind, downloadUrl) {
                        previewTitle.textContent = name || 'Document';
                        previewDownload.href = downloadUrl || url;
                        previewImg.hidden = true;
                        previewImg.removeAttribute('src');
                        previewFrame.hidden = true;
                        previewFrame.removeAttribute('src');
                        previewNote.hidden = true;
                        if (kind === 'image') {
                            previewImg.hidden = false;
                            previewImg.alt = name || 'Document';
                            previewImg.src = url;
                        } else if (kind === 'pdf') {
                            previewFrame.hidden = false;
                            previewFrame.src = url;
                        } else {
                            previewNote.hidden = false;
                        }
                        if (window.bootstrap) {
                            window.bootstrap.Modal.getOrCreateInstance(preview).show();
                        }
                    }

                    document.querySelectorAll('[data-doc-view]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            showStored(
                                button.getAttribute('data-url'),
                                button.getAttribute('data-name'),
                                button.getAttribute('data-kind'),
                                button.getAttribute('data-download')
                            );
                        });
                    });

                    if (preview) {
                        preview.addEventListener('hidden.bs.modal', function () {
                            previewImg.removeAttribute('src');
                            previewFrame.removeAttribute('src');
                        });
                    }

                    if (input && chosen) {
                        input.addEventListener('change', function () {
                            var file = input.files && input.files[0];
                            clearChosen();
                            if (!file) return;
                            chosen.hidden = false;
                            chosenUrl = URL.createObjectURL(file);
                            var type = file.type || '';
                            var name = file.name || '';
                            if (type.indexOf('image/') === 0) {
                                chosenImg.hidden = false;
                                chosenImg.src = chosenUrl;
                            } else if (type === 'application/pdf' || /\.pdf$/i.test(name)) {
                                chosenFrame.hidden = false;
                                chosenFrame.src = chosenUrl;
                            } else {
                                chosenNote.hidden = false;
                                chosenNote.textContent = name + ' will be saved. Word files open as a download from the list.';
                            }
                        });
                    }

                    var uploadModal = document.getElementById('documentUploadModal');
                    if (uploadModal) {
                        uploadModal.addEventListener('hidden.bs.modal', clearChosen);
                    }

                    var removeModal = document.getElementById('documentRemoveModal');
                    var removeForm = document.getElementById('documentRemoveForm');
                    var removeText = document.getElementById('documentRemoveText');
                    document.querySelectorAll('[data-doc-remove]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            if (!removeForm || !removeModal || !window.bootstrap) return;
                            removeForm.action = button.getAttribute('data-action');
                            removeText.textContent = (button.getAttribute('data-name') || 'This document') + ' will be taken off this file.';
                            window.bootstrap.Modal.getOrCreateInstance(removeModal).show();
                        });
                    });
                })();
            </script>
        @endpush
    @endif

    @if($tab === 'leaves')
        <div class="people-ws-grid-3 people-ws-mt">
            @foreach($leaveTypes->where('tracks_balance', true) as $type)
                <article class="people-ws-card people-ws-stat">
                    <div class="label">{{ $type->name }} left</div>
                    <div class="value">{{ $person['balance'] ? $person['balance']->remaining($type->code) : 0 }}</div>
                    <div class="sub">of {{ $person['balance'] ? $person['balance']->allowance($type->code) : 0 }} this year</div>
                </article>
            @endforeach
        </div>
        <article class="people-ws-card people-ws-mt">
            <p class="people-ws-note">
                @if($person['assignments']->isEmpty())
                    Policy: Not assigned
                @else
                    Policies:
                    @foreach($person['assignments'] as $assignment)
                        {{ $assignment->policy->name ?? 'Policy' }}@if($assignment->policy?->leaveType) ({{ $assignment->policy->leaveType->name }})@endif{{ $loop->last ? '' : ', ' }}
                    @endforeach
                @endif
            </p>
        </article>
        <article class="people-ws-card people-ws-mt people-ws-scroll">
            <h2>Requests</h2>
            <table>
                <thead><tr><th>Type</th><th>Dates</th><th>Days</th><th>Reason</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($person['leaveRequests'] as $leave)
                    <tr>
                        <td>{{ $workspace->leaveLabel($leave->leave_type) }}</td>
                        <td>{{ $leave->starts_on->format('j M Y') }} to {{ $leave->ends_on->format('j M Y') }}</td>
                        <td>{{ $leave->days }}</td>
                        <td>{{ $leave->reason }}</td>
                        <td>@include('adminmodule::admin.people._badge', ['status' => $leave->status])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="people-ws-note">No leave requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
    @endif

    @if($tab === 'salary')
        @php
            $salaryEditing = $errors->any() && old('effective_from');
            $rupee = fn ($amount) => '₹'.number_format((float) $amount, 0);
            $dateLabel = fn ($value) => $value ? \Carbon\Carbon::parse(strlen($value) === 7 ? $value.'-01' : $value)->format('j F Y') : 'Not set';
            $salaryHistory = $person['structures']->values();
            $effectiveValue = old('effective_from', $latestStructure->effective_from ?? ($period.'-01'));
            if (strlen((string) $effectiveValue) === 7) {
                $effectiveValue .= '-01';
            }
        @endphp
        <article class="people-ws-card people-ws-mt" id="salary-preview" @if($salaryEditing) hidden @endif>
            <div class="people-id-top">
                <h2>Pay structure</h2>
                <button class="btn-pw" type="button" id="salary-edit">Edit</button>
            </div>
            @if($latestStructure)
                <dl class="people-id-grid">
                    <div><dt>Effective from</dt><dd>{{ $dateLabel($latestStructure->effective_from) }}</dd></div>
                    <div><dt>Gross</dt><dd>{{ $rupee($latestStructure->gross()) }}</dd></div>
                    <div><dt>Basic</dt><dd>{{ $rupee($latestStructure->basic) }}</dd></div>
                    <div><dt>HRA</dt><dd>{{ $rupee($latestStructure->hra) }}</dd></div>
                    <div><dt>Special allowance</dt><dd>{{ $rupee($latestStructure->special_allowance) }}</dd></div>
                    <div><dt>Provident fund, employee</dt><dd>{{ $rupee($latestStructure->pf_employee) }}</dd></div>
                    <div><dt>Provident fund, employer</dt><dd>{{ $rupee($latestStructure->pf_employer) }}</dd></div>
                    <div><dt>Professional tax</dt><dd>{{ $rupee($latestStructure->professional_tax) }}</dd></div>
                    <div><dt>Tax deducted at source</dt><dd>{{ $rupee($latestStructure->tds) }}</dd></div>
                    <div><dt>Other deduction</dt><dd>{{ $rupee($latestStructure->other_deduction) }}</dd></div>
                </dl>
            @else
                <p class="people-ws-note">No salary saved yet.</p>
            @endif
        </article>
        <form class="people-ws-card people-ws-mt" id="salary-form" method="post" action="{{ route('admin.hr.salary') }}" @if(! $salaryEditing) hidden @endif>
            @csrf
            <input type="hidden" name="return_to" value="person">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <h2>Pay structure</h2>
            <p class="people-ws-note">Choose a new date to record an appraisal. That keeps every earlier salary. Choosing a date already on file replaces only that date.</p>
            <div class="people-ws-form-grid">
                <div class="field"><label for="effective_from">Effective from</label><input id="effective_from" class="people-date-field" name="effective_from" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ $effectiveValue }}" required readonly></div>
                <div class="field"><label for="basic">Basic</label><input id="basic" name="basic" type="number" min="0" step="0.01" value="{{ old('basic', $latestStructure->basic ?? 0) }}" required></div>
                <div class="field"><label for="hra">HRA</label><input id="hra" name="hra" type="number" min="0" step="0.01" value="{{ old('hra', $latestStructure->hra ?? 0) }}" required></div>
                <div class="field"><label for="special_allowance">Special allowance</label><input id="special_allowance" name="special_allowance" type="number" min="0" step="0.01" value="{{ old('special_allowance', $latestStructure->special_allowance ?? 0) }}" required></div>
                <div class="field"><label for="pf_employee">Provident fund, employee</label><input id="pf_employee" name="pf_employee" type="number" min="0" step="0.01" value="{{ old('pf_employee', $latestStructure->pf_employee ?? 0) }}" required></div>
                <div class="field"><label for="pf_employer">Provident fund, employer</label><input id="pf_employer" name="pf_employer" type="number" min="0" step="0.01" value="{{ old('pf_employer', $latestStructure->pf_employer ?? 0) }}" required></div>
                <div class="field"><label for="professional_tax">Professional tax</label><input id="professional_tax" name="professional_tax" type="number" min="0" step="0.01" value="{{ old('professional_tax', $latestStructure->professional_tax ?? 0) }}" required></div>
                <div class="field"><label for="tds">Tax deducted at source</label><input id="tds" name="tds" type="number" min="0" step="0.01" value="{{ old('tds', $latestStructure->tds ?? 0) }}" required></div>
                <div class="field"><label for="other_deduction">Other deduction</label><input id="other_deduction" name="other_deduction" type="number" min="0" step="0.01" value="{{ old('other_deduction', $latestStructure->other_deduction ?? 0) }}" required></div>
            </div>
            <div class="people-ws-actions">
                <button class="btn-pw" type="button" id="salary-cancel">Cancel</button>
                <button class="btn-pw primary" type="submit">Save structure</button>
            </div>
        </form>
        <article class="people-ws-card people-ws-mt people-ws-scroll">
            <h2>Salary history</h2>
            <p class="people-ws-note">Each row is the salary from that date until the day before the next one. A new date is an appraisal. Earlier salaries stay as they were paid.</p>
            <table>
                <thead><tr><th>From</th><th>Until</th><th>Basic</th><th>HRA</th><th>Special allowance</th><th>Gross</th></tr></thead>
                <tbody>
                @forelse($salaryHistory as $structure)
                    @php
                        $newer = $loop->first ? null : $salaryHistory[$loop->index - 1];
                        $until = $newer
                            ? \Carbon\Carbon::parse(strlen($newer->effective_from) === 7 ? $newer->effective_from.'-01' : $newer->effective_from)->subDay()->format('j F Y')
                            : 'Current';
                    @endphp
                    <tr>
                        <td>{{ $dateLabel($structure->effective_from) }}</td>
                        <td>{{ $until }}</td>
                        <td>{{ $rupee($structure->basic) }}</td>
                        <td>{{ $rupee($structure->hra) }}</td>
                        <td>{{ $rupee($structure->special_allowance) }}</td>
                        <td>{{ $rupee($structure->gross()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="people-ws-note">No salary saved yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
        <script>
            (function () {
                var preview = document.getElementById('salary-preview');
                var form = document.getElementById('salary-form');
                var edit = document.getElementById('salary-edit');
                var cancel = document.getElementById('salary-cancel');
                function showEdit(on) {
                    if (preview) preview.hidden = on;
                    if (form) form.hidden = !on;
                }
                if (edit) edit.addEventListener('click', function () { showEdit(true); });
                if (cancel) cancel.addEventListener('click', function () { showEdit(false); });
            })();
        </script>
    @endif

    @if($tab === 'payslips')
        <article class="people-ws-card people-ws-mt people-ws-scroll">
            <table>
                <thead><tr><th>Month</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($person['payslips'] as $payslip)
                    <tr>
                        <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $payslip->period)->format('F Y') }}</td>
                        <td>₹{{ number_format((float) $payslip->gross, 0) }}</td>
                        <td>₹{{ number_format((float) $payslip->deductions, 0) }}</td>
                        <td>₹{{ number_format((float) $payslip->net, 0) }}</td>
                        <td>@include('adminmodule::admin.people._badge', ['status' => $payslip->held ? 'held' : $payslip->status])</td>
                        <td><a class="btn-pw" href="{{ route('admin.people.payslips.download', $payslip) }}">Download</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="people-ws-note">No payslips yet. Build the month on Payroll.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
    @endif
        </div>
    </div>
@endif
