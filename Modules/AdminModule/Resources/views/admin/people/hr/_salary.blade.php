@php
    $salaryEditing = $errors->any() && old('effective_from');
    $dateLabel = fn ($value) => $value ? \Carbon\Carbon::parse(strlen($value) === 7 ? $value.'-01' : $value)->format('j F Y') : 'Not set';
@endphp
<div class="people-ws-head">
    <div>
        <h1>Salary</h1>
        <p>An appraisal is a new salary from a chosen date. Every earlier salary stays on file, and payroll for a past month still uses the salary that was in force then.</p>
    </div>
    <button class="btn-pw" type="button" id="salary-edit" @if($salaryEditing) hidden @endif>Edit</button>
</div>
<article class="people-ws-card people-ws-scroll" id="salary-preview" @if($salaryEditing) hidden @endif>
    <h2>Salary history</h2>
    <table>
        <thead><tr><th>Person</th><th>From</th><th>Until</th><th>Basic</th><th>HRA</th><th>Special allowance</th><th>Gross</th></tr></thead>
        <tbody>
        @php $shown = false; @endphp
        @foreach($staff as $person)
            @php $history = ($structures->get($person->id) ?? collect())->values(); @endphp
            @foreach($history as $structure)
                @php
                    $shown = true;
                    $newer = $loop->first ? null : $history[$loop->index - 1];
                    $until = $newer
                        ? \Carbon\Carbon::parse(strlen($newer->effective_from) === 7 ? $newer->effective_from.'-01' : $newer->effective_from)->subDay()->format('j F Y')
                        : 'Current';
                @endphp
                <tr>
                    <td>{{ $workspace->displayName($person) }}</td>
                    <td>{{ $dateLabel($structure->effective_from) }}</td>
                    <td>{{ $until }}</td>
                    <td>₹{{ number_format((float) $structure->basic, 0) }}</td>
                    <td>₹{{ number_format((float) $structure->hra, 0) }}</td>
                    <td>₹{{ number_format((float) $structure->special_allowance, 0) }}</td>
                    <td>₹{{ number_format($structure->gross(), 0) }}</td>
                </tr>
            @endforeach
        @endforeach
        @if(! $shown)
            <tr><td colspan="7" class="people-ws-note">No salary saved yet.</td></tr>
        @endif
        </tbody>
    </table>
</article>
<form class="people-ws-card" id="salary-form" method="post" action="{{ route('admin.hr.salary') }}" @if(! $salaryEditing) hidden @endif>
    @csrf
    <h2>Pay structure</h2>
    <p class="people-ws-note">Choose a new date to record an appraisal. That keeps every earlier salary. Choosing a date already on file replaces only that date.</p>
    <div class="people-ws-form-grid">
        <div class="field"><label for="salary_user">Person</label>
            <select id="salary_user" name="user_id" required>
                @foreach($staff as $person)
                    <option value="{{ $person->id }}" @selected(old('user_id') === $person->id)>{{ $workspace->displayName($person) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="effective_from">Effective from</label><input id="effective_from" class="people-date-field" name="effective_from" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('effective_from', $period.'-01') }}" required readonly></div>
        <div class="field"><label for="basic">Basic</label><input id="basic" name="basic" type="number" min="0" step="0.01" value="{{ old('basic') }}" required></div>
        <div class="field"><label for="hra">HRA</label><input id="hra" name="hra" type="number" min="0" step="0.01" value="{{ old('hra', 0) }}" required></div>
        <div class="field"><label for="special_allowance">Special allowance</label><input id="special_allowance" name="special_allowance" type="number" min="0" step="0.01" value="{{ old('special_allowance', 0) }}" required></div>
        <div class="field"><label for="pf_employee">Provident fund, employee</label><input id="pf_employee" name="pf_employee" type="number" min="0" step="0.01" value="{{ old('pf_employee', 0) }}" required></div>
        <div class="field"><label for="pf_employer">Provident fund, employer</label><input id="pf_employer" name="pf_employer" type="number" min="0" step="0.01" value="{{ old('pf_employer', 0) }}" required></div>
        <div class="field"><label for="professional_tax">Professional tax</label><input id="professional_tax" name="professional_tax" type="number" min="0" step="0.01" value="{{ old('professional_tax', 0) }}" required></div>
        <div class="field"><label for="tds">Tax deducted at source</label><input id="tds" name="tds" type="number" min="0" step="0.01" value="{{ old('tds', 0) }}" required></div>
        <div class="field"><label for="other_deduction">Other deduction</label><input id="other_deduction" name="other_deduction" type="number" min="0" step="0.01" value="{{ old('other_deduction', 0) }}" required></div>
    </div>
    <div class="people-ws-actions">
        <button class="btn-pw" type="button" id="salary-cancel">Cancel</button>
        <button class="btn-pw primary" type="submit">Save structure</button>
    </div>
</form>
<script>
    (function () {
        var preview = document.getElementById('salary-preview');
        var form = document.getElementById('salary-form');
        var edit = document.getElementById('salary-edit');
        var cancel = document.getElementById('salary-cancel');
        function showEdit(on) {
            if (preview) preview.hidden = on;
            if (form) form.hidden = !on;
            if (edit) edit.hidden = on;
        }
        if (edit) edit.addEventListener('click', function () { showEdit(true); });
        if (cancel) cancel.addEventListener('click', function () { showEdit(false); });
    })();
</script>
