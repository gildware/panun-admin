@php
    $avatarLimit = 4;
    $employeeList = $employees ?? collect();
    $visibleEmployees = $employeeList->take($avatarLimit);
    $overflowEmployees = $employeeList->slice($avatarLimit);
    $overflowCount = $overflowEmployees->count();
    $selectedAssigneeIds = array_map('strval', $filters['assignee_ids'] ?? []);
    $isAllSelected = $selectedAssigneeIds === [];

    $initials = function ($employee) {
        $fullName = trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''));
        if ($fullName === '') {
            $fullName = trim((string) ($employee->email ?? ''));
        }
        $words = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) >= 2) {
            $letters = '';
            foreach ($words as $word) {
                $letters .= mb_substr($word, 0, 1);
            }

            return mb_strtoupper($letters);
        }
        if (count($words) === 1) {
            $word = $words[0];
            $take = min(2, mb_strlen($word));

            return mb_strtoupper(mb_substr($word, 0, $take));
        }

        return 'E';
    };

    $avatarLetterClass = function (string $initialsText): string {
        $letter = mb_strtolower(mb_substr(preg_replace('/[^A-Za-z]/', '', $initialsText) ?: 'e', 0, 1));
        if ($letter < 'a' || $letter > 'z') {
            $letter = 'e';
        }

        return 'day-detail-avatar-letter-'.$letter;
    };

    $employeeLabel = function ($employee) {
        $fullName = trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''));

        return $fullName !== '' ? $fullName : ($employee->email ?? (string) $employee->id);
    };

    $employeePhoto = function ($employee) {
        $stored = trim((string) ($employee->profile_image ?? ''));
        if ($stored === '') {
            return null;
        }

        $storedLower = mb_strtolower($stored);
        if (
            $storedLower === 'default.png'
            || str_contains($storedLower, 'placeholder')
            || str_contains($storedLower, 'customer.png')
            || str_contains($storedLower, 'user2x.png')
        ) {
            return null;
        }

        $path = (string) ($employee->profile_image_full_path ?? '');
        if ($path === '') {
            return null;
        }

        $pathLower = mb_strtolower($path);
        if (
            str_contains($pathLower, 'placeholder')
            || str_contains($pathLower, '/customer.png')
            || str_contains($pathLower, '/user2x.png')
            || str_contains($pathLower, '/default.png')
        ) {
            return null;
        }

        return $path;
    };
@endphp

<div class="day-detail-avatar-group task-board-assignee-avatars"
     id="taskBoardAssigneeAvatars"
     data-selected='@json($selectedAssigneeIds)'>
    <button type="button"
            class="day-detail-avatar is-all {{ $isAllSelected ? 'is-active' : '' }}"
            data-employee-id="all"
            title="{{ translate('All') }}">
        {{ translate('All') }}
    </button>

    @foreach($visibleEmployees as $employee)
        @php
            $isActive = in_array((string) $employee->id, $selectedAssigneeIds, true);
            $label = $employeeLabel($employee);
            $img = $employeePhoto($employee);
            $initialsText = $initials($employee);
            $letterClass = $avatarLetterClass($initialsText);
        @endphp
        <button type="button"
                class="day-detail-avatar {{ $isActive ? 'is-active' : '' }} {{ $img ? '' : 'has-initials '.$letterClass }}"
                data-employee-id="{{ $employee->id }}"
                title="{{ $label }}">
            @if($img)
                <img src="{{ $img }}"
                     alt="{{ $label }}"
                     class="day-detail-avatar-img"
                     loading="lazy"
                     onerror="window.taskBoardAvatarImgError && window.taskBoardAvatarImgError(this)">
            @else
                <span class="day-detail-avatar-initials">{{ $initialsText }}</span>
            @endif
        </button>
    @endforeach

    @if($overflowCount > 0)
        <div class="dropdown d-inline-flex">
            <button type="button"
                    class="day-detail-avatar is-more"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    title="{{ translate('More') }}">
                +{{ $overflowCount }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end day-detail-more-menu shadow">
                @foreach($overflowEmployees as $employee)
                    @php
                        $isActive = in_array((string) $employee->id, $selectedAssigneeIds, true);
                        $label = $employeeLabel($employee);
                        $img = $employeePhoto($employee);
                        $initialsText = $initials($employee);
                        $letterClass = $avatarLetterClass($initialsText);
                    @endphp
                    <li>
                        <button type="button"
                                class="dropdown-item {{ $isActive ? 'active' : '' }}"
                                data-employee-id="{{ $employee->id }}"
                                title="{{ $label }}">
                            @if($img)
                                <img src="{{ $img }}"
                                     alt="{{ $label }}"
                                     class="day-detail-avatar-img"
                                     loading="lazy"
                                     onerror="window.taskBoardAvatarImgError && window.taskBoardAvatarImgError(this)">
                            @else
                                <span class="day-detail-mini-avatar day-detail-avatar-initials {{ $letterClass }}">{{ $initialsText }}</span>
                            @endif
                            <span>{{ $label }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<div id="taskBoardAssigneeInputs">
    @foreach($selectedAssigneeIds as $assigneeId)
        <input type="hidden" name="assignee_ids[]" value="{{ $assigneeId }}">
    @endforeach
</div>
