@php
    $pickedScenarios = array_values(array_filter((array) old('scenarios', $test->scenarios ?? [])));
    $pickedScenario = $pickedScenarios[0] ?? '';
    $pickedSkills = array_values(array_filter((array) old('skills', $test->skills ?? [])));
    $pickedMarks = (array) old('skill_marks', $test->skill_marks ?? []);
    $scenarioChoices = collect($scenarios)->map(fn ($scenario, $id) => [
        'id' => $id,
        'name' => $scenario['name'],
        'summary' => $scenario['summary'],
        'prompt' => $scenario['guide'] ?? '',
    ])->values();
    $pickedPrompts = (array) old('scenario_prompts', $test->scenario_prompts ?? []);
    $pickedChoice = $scenarioChoices->firstWhere('id', $pickedScenario);
    $initialPrompt = $pickedScenario === ''
        ? ''
        : (filled($pickedPrompts[$pickedScenario] ?? null) ? $pickedPrompts[$pickedScenario] : ($pickedChoice['prompt'] ?? ''));
    $skillChoices = collect($skills)->map(fn ($skill, $id) => [
        'id' => $id,
        'name' => $skill['name'],
        'blurb' => $skill['blurb'],
    ])->values();
@endphp
<form method="POST" action="{{ $action }}">
    @csrf
    @if(! empty($method))
        @method($method)
    @endif
    <div class="card">
        <div class="card-body">
            <div class="call-box-head">
                <div>
                    <h3 class="h6 mb-1">Test</h3>
                    <div class="trn-meta">The name and how hard the caller pushes.</div>
                </div>
                <button class="btn btn--primary btn-sm" type="submit">{{ $submitLabel }}</button>
            </div>
            <div class="call-details">
                <div>
                    <label class="form-label" for="test-name">Test name</label>
                    <input id="test-name" class="form-control" name="name" required value="{{ old('name', $test->name) }}">
                </div>
                <div>
                    <label class="form-label" for="test-difficulty">Difficulty</label>
                    <select id="test-difficulty" class="form-select" name="difficulty" required>
                        @foreach(\Modules\AdminModule\Entities\TrainingCallTest::DIFFICULTIES as $key => $label)
                            <option value="{{ $key }}" @selected(old('difficulty', $test->difficulty ?: 'standard') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h3 class="h6 mb-1">Call</h3>
            <div class="trn-meta mb-3">Tone, speed, language, who speaks first, and when the call disconnects.</div>
            <div class="call-settings">
                <div>
                    <label class="form-label" for="test-tone">Call tone</label>
                    <select id="test-tone" class="form-select" name="tone" required>
                        @foreach(\Modules\AdminModule\Entities\TrainingCallTest::TONES as $key => $label)
                            <option value="{{ $key }}" @selected(old('tone', $test->tone ?: 'warm') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="test-speed">Speed</label>
                    <select id="test-speed" class="form-select" name="speed" required>
                        @foreach(\Modules\AdminModule\Entities\TrainingCallTest::SPEEDS as $key => $label)
                            <option value="{{ $key }}" @selected(old('speed', $test->speed ?: 'normal') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="test-language">Default language</label>
                    <select id="test-language" class="form-select" name="default_language" required>
                        @foreach(\Modules\AdminModule\Entities\TrainingCallTest::LANGUAGES as $key => $label)
                            <option value="{{ $key }}" @selected(old('default_language', $test->default_language ?: 'en') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="test-starter">Who speaks first</label>
                    <select id="test-starter" class="form-select" name="starter" required>
                        @foreach(\Modules\AdminModule\Entities\TrainingCallTest::STARTERS as $key => $label)
                            <option value="{{ $key }}" @selected(old('starter', $test->starter ?: 'caller') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="test-minutes">Max call length (minutes)</label>
                    <input id="test-minutes" class="form-control" type="number" name="max_minutes" min="1" max="60" required value="{{ old('max_minutes', $test->max_minutes ?: 8) }}">
                </div>
            </div>
        </div>
    </div>
    <div class="call-setup-split">
        <div class="card call-scenario-card">
            <div class="card-body call-fixed-body">
                <div class="call-fixed-head">
                    <h3 class="h6 mb-1">Scenario</h3>
                    <div class="trn-meta mb-2" id="scenario-summary">{{ $pickedChoice['summary'] ?? 'This call uses one scenario.' }}</div>
                    <select id="scenario-picker" class="form-select" name="scenarios[]" required aria-label="Scenario">
                        <option value="">Choose a scenario</option>
                        @foreach($scenarioChoices as $choice)
                            <option value="{{ $choice['id'] }}" @selected($pickedScenario === $choice['id'])>{{ $choice['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="call-scroll">
                    <label class="call-scenario-prompt" for="scenario-prompt">Prompt for the AI
                        <textarea id="scenario-prompt" maxlength="1500" rows="8" @if($pickedScenario !== '') name="scenario_prompts[{{ $pickedScenario }}]" required @endif>{{ $initialPrompt }}</textarea>
                    </label>
                </div>
            </div>
        </div>
        <div class="card call-fixed">
            <div class="card-body call-fixed-body">
                <div class="call-fixed-head">
                    <h3 class="h6 mb-1">Skills to score</h3>
                    <div class="trn-meta mb-2">Set the marks for each skill you add.</div>
                    <div class="call-add">
                        <select id="skill-picker" class="form-select">
                            <option value="">Add a skill</option>
                        </select>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="skill-add">Add</button>
                    </div>
                </div>
                <div class="call-scroll">
                    <div id="skill-empty" class="trn-meta">No skills added yet.</div>
                    <div id="skill-list" class="call-added"></div>
                </div>
                <div class="call-pass-row">
                    <div>
                        <label class="form-label" for="test-pass">Pass mark</label>
                        <input id="test-pass" class="form-control" type="number" name="pass_mark" min="1" required value="{{ old('pass_mark', $test->pass_mark) }}">
                    </div>
                    <div id="skill-total" class="trn-meta">Pass mark must be the total of these marks, or less.</div>
                </div>
            </div>
        </div>
    </div>
</form>
@push('script')
    <script>
        (function () {
            const scenarios = @json($scenarioChoices);
            const skills = @json($skillChoices);
            const pickedPrompts = @json($pickedPrompts);
            const pickedSkills = @json(array_values($pickedSkills));
            const pickedMarks = @json($pickedMarks);
            const skillList = document.getElementById('skill-list');
            const scenarioPicker = document.getElementById('scenario-picker');
            const scenarioPrompt = document.getElementById('scenario-prompt');
            const scenarioSummary = document.getElementById('scenario-summary');
            const skillPicker = document.getElementById('skill-picker');
            const promptDraft = Object.assign({}, pickedPrompts);

            function emptyNote(list, note) {
                note.hidden = list.querySelector('[data-id]') !== null;
            }

            function chosen(list) {
                return Array.prototype.map.call(list.querySelectorAll('[data-id]'), function (row) {
                    return row.dataset.id;
                });
            }

            function fill(select, items, used, placeholder) {
                const current = select.value;
                select.innerHTML = '';
                const blank = document.createElement('option');
                blank.value = '';
                blank.textContent = placeholder;
                select.appendChild(blank);
                items.forEach(function (item) {
                    if (used.indexOf(item.id) !== -1) return;
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    select.appendChild(option);
                });
                if (current && used.indexOf(current) === -1) select.value = current;
            }

            function showScenario() {
                const item = scenarios.find(function (row) { return row.id === scenarioPicker.value; });
                if (!item) {
                    scenarioPicker.setCustomValidity('Choose a scenario.');
                    scenarioPrompt.removeAttribute('name');
                    scenarioPrompt.required = false;
                    scenarioPrompt.value = '';
                    scenarioSummary.textContent = 'This call uses one scenario.';
                    return;
                }
                scenarioPicker.setCustomValidity('');
                scenarioPrompt.name = 'scenario_prompts[' + item.id + ']';
                scenarioPrompt.required = true;
                const saved = promptDraft[item.id];
                scenarioPrompt.value = saved && String(saved).trim() !== '' ? saved : (item.prompt || '');
                scenarioSummary.textContent = item.summary;
            }

            function skillRow(item, marks) {
                const row = document.createElement('div');
                row.className = 'call-added-row is-skill';
                row.dataset.id = item.id;
                row.innerHTML = '<div><b></b><span></span></div><label class="call-marks">Marks<input type="number" min="1" max="100" required></label><button class="btn btn-outline-secondary btn-sm" type="button">Remove</button><input type="hidden" name="skills[]">';
                row.querySelector('b').textContent = item.name;
                row.querySelector('span').textContent = item.blurb;
                const hidden = row.querySelector('input[type="hidden"]');
                const number = row.querySelector('input[type="number"]');
                hidden.value = item.id;
                number.name = 'skill_marks[' + item.id + ']';
                number.value = String(marks || 10);
                number.addEventListener('input', refreshPass);
                row.querySelector('button').addEventListener('click', function () {
                    row.remove();
                    fill(skillPicker, skills, chosen(skillList), 'Add a skill');
                    emptyNote(skillList, document.getElementById('skill-empty'));
                    refreshPass();
                });
                return row;
            }

            showScenario();
            scenarioPicker.addEventListener('change', showScenario);
            scenarioPrompt.addEventListener('input', function () {
                if (scenarioPicker.value) promptDraft[scenarioPicker.value] = scenarioPrompt.value;
            });
            pickedSkills.forEach(function (id) {
                const item = skills.find(function (row) { return row.id === id; });
                if (item) skillList.appendChild(skillRow(item, pickedMarks[id] || 10));
            });
            fill(skillPicker, skills, chosen(skillList), 'Add a skill');
            emptyNote(skillList, document.getElementById('skill-empty'));
            refreshPass();
            document.getElementById('test-pass').addEventListener('input', refreshPass);

            document.getElementById('skill-add').addEventListener('click', function () {
                const item = skills.find(function (row) { return row.id === skillPicker.value; });
                if (!item) return;
                skillList.appendChild(skillRow(item, 10));
                fill(skillPicker, skills, chosen(skillList), 'Add a skill');
                emptyNote(skillList, document.getElementById('skill-empty'));
                refreshPass();
            });

            function skillTotal() {
                let total = 0;
                skillList.querySelectorAll('input[type="number"]').forEach(function (input) {
                    const value = parseInt(input.value, 10);
                    if (!Number.isNaN(value)) total += Math.max(1, Math.min(100, value));
                });
                return total;
            }

            function refreshPass() {
                const passInput = document.getElementById('test-pass');
                const note = document.getElementById('skill-total');
                const total = skillTotal();
                const pass = parseInt(passInput.value, 10);
                if (total < 1) {
                    passInput.removeAttribute('max');
                    note.textContent = 'Add skills first. Pass mark cannot be higher than their total marks.';
                    passInput.setCustomValidity('Add at least one skill before setting the pass mark.');
                    return;
                }
                passInput.max = String(total);
                note.textContent = 'Total marks ' + total + '. Pass mark must be ' + total + ' or less.';
                if (Number.isNaN(pass) || pass < 1) {
                    passInput.setCustomValidity('Enter a pass mark of at least 1.');
                } else if (pass > total) {
                    passInput.setCustomValidity('Pass mark must be ' + total + ' or less.');
                } else {
                    passInput.setCustomValidity('');
                }
            }
        })();
    </script>
@endpush
