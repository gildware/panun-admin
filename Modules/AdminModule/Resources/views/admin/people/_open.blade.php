@php
    $links = $links ?? [];
    $baseUrl = $baseUrl ?? route('admin.people.index');
    $desk = $desk ?? 'mine';
    $hrTabs = [
        'home' => ['Overview', route('admin.hr.index', ['section' => 'home'])],
        'people' => ['People', route('admin.hr.index', ['section' => 'people'])],
        'departments' => ['Departments', route('admin.hr.index', ['section' => 'departments'])],
        'holidays' => ['Holidays', route('admin.people.holidays')],
        'leave' => ['Leave', route('admin.hr.index', ['section' => 'leave'])],
        'attendance' => ['Attendance', route('admin.hr.index', ['section' => 'attendance'])],
        'salary' => ['Salary', route('admin.hr.index', ['section' => 'salary'])],
        'payroll' => ['Payroll', route('admin.hr.index', ['section' => 'payroll'])],
    ];
    $showModeTabs = $desk !== 'hr' && ! empty($canManageTeam);
@endphp
<div class="people-ws">
    @if($desk === 'hr')
        <nav class="people-ws-tabs">
            @foreach($hrTabs as $key => [$label, $href])
                <a class="{{ ($section ?? '') === $key ? 'is-on' : '' }}" href="{{ $href }}">{{ $label }}</a>
            @endforeach
        </nav>
    @elseif($showModeTabs)
        <div class="people-ws-switch">
            <a class="{{ ($mode ?? '') === 'mine' ? 'is-on' : '' }}" href="{{ route('admin.people.index') }}">My file</a>
            <a class="{{ ($mode ?? '') === 'team' ? 'is-on' : '' }}" href="{{ route('admin.people.team') }}">Team</a>
        </div>
    @endif
    @if(count($links))
        <nav class="people-ws-tabs">
            @foreach($links as $key => $label)
                <a class="{{ ($section ?? '') === $key ? 'is-on' : '' }}" href="{{ $baseUrl }}?section={{ $key }}">{{ $label }}</a>
            @endforeach
        </nav>
    @endif
    <div class="people-ws-main">
