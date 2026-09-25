<div class="people-ws-head"><div><h1>People</h1><p>Open a person to see their file: details, documents, leave, salary, and payslips.</p></div></div>
<article class="people-ws-card people-ws-scroll">
    <table>
        <thead><tr><th>Person</th><th>Code</th><th>Seat</th><th>Department</th><th>Status</th><th>Manager</th></tr></thead>
        <tbody>
        @foreach($staff as $person)
            @php
                $profile = $profiles->get($person->id);
                $href = route('admin.hr.index', ['section' => 'person', 'user' => $person->id]);
            @endphp
            <tr class="people-ws-person" data-href="{{ $href }}">
                <td><a href="{{ $href }}">{{ $workspace->displayName($person) }}</a></td>
                <td>{{ $profile->employee_code ?? '—' }}</td>
                <td>{{ $profile->job_title ?? 'Employee' }}</td>
                <td>{{ $profile->department ?: '—' }}</td>
                <td>@include('adminmodule::admin.people._badge', ['status' => $profile->employment_status ?? 'active'])</td>
                <td>{{ $profile && $profile->manager ? $workspace->displayName($profile->manager) : 'Not set' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</article>
<script>
    document.querySelectorAll('.people-ws-person').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea')) return;
            window.location = row.dataset.href;
        });
    });
</script>
