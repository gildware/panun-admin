@php
    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $period)->format('F Y');
    $drafts = $payslips->where('status', 'draft')->where('held', false)->count();
    $held = $payslips->where('held', true)->count();
    $net = $payslips->where('status', 'published')->sum('net');
@endphp
<div class="people-ws-head">
    <div>
        <h1>People & HR</h1>
        <p>Company files, holidays, leave, attendance, and pay. An employee still uses My workspace for their own requests.</p>
    </div>
</div>
<div class="people-ws-grid-4">
    <article class="people-ws-card people-ws-stat"><div class="label">People</div><div class="value">{{ $staff->count() }}</div><div class="sub">Active sign-ins</div></article>
    <article class="people-ws-card people-ws-stat"><div class="label">Documents missing</div><div class="value">{{ $missingDocuments }}</div><div class="sub">Asked, not uploaded</div></article>
    <article class="people-ws-card people-ws-stat"><div class="label">Leave waiting</div><div class="value">{{ $pendingLeave }}</div><div class="sub">Not decided yet</div></article>
    <article class="people-ws-card people-ws-stat"><div class="label">{{ $monthLabel }}</div><div class="value">{{ $run ? $workspace->statusLabel($run->status) : 'Not built' }}</div><div class="sub">{{ $drafts }} drafts · {{ $held }} held · ₹{{ number_format((float) $net, 0) }} published</div></article>
</div>
