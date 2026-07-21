@php
    $isOverdue = $ticket->end_date && $ticket->end_date->isPast();
    $isDueToday = $ticket->end_date && $ticket->end_date->isToday();
    $ticketCode = 'TB-'.strtoupper(substr(str_replace('-', '', (string) $ticket->id), 0, 4));

    $assigneeInitials = function ($assignee) {
        $fullName = trim(($assignee->first_name ?? '').' '.($assignee->last_name ?? ''));
        if ($fullName === '') {
            $fullName = trim((string) ($assignee->email ?? ''));
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

            return mb_strtoupper(mb_substr($word, 0, min(2, mb_strlen($word))));
        }

        return 'E';
    };

    $assigneeLetterClass = function (string $initialsText): string {
        $letter = mb_strtolower(mb_substr(preg_replace('/[^A-Za-z]/', '', $initialsText) ?: 'e', 0, 1));
        if ($letter < 'a' || $letter > 'z') {
            $letter = 'e';
        }

        return 'day-detail-avatar-letter-'.$letter;
    };

    $assigneePhoto = function ($assignee) {
        $stored = trim((string) ($assignee->profile_image ?? ''));
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

        $path = (string) ($assignee->profile_image_full_path ?? '');
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
<article class="task-card {{ $isOverdue ? 'task-card-overdue' : '' }}"
         data-ticket-id="{{ $ticket->id }}"
         data-column-id="{{ $ticket->column_id }}">
    <div class="task-card-top">
        <h4 class="task-card-title">{{ $ticket->title }}</h4>
        @if($ticket->assignees->isNotEmpty())
            <div class="task-card-assignees" title="{{ translate('Assignees') }}">
                @foreach($ticket->assignees->take(3) as $assignee)
                    @php
                        $name = trim(($assignee->first_name ?? '').' '.($assignee->last_name ?? ''));
                        if ($name === '') {
                            $name = (string) ($assignee->email ?? $assignee->id);
                        }
                        $img = $assigneePhoto($assignee);
                        $initialsText = $assigneeInitials($assignee);
                        $letterClass = $assigneeLetterClass($initialsText);
                    @endphp
                    @if($img)
                        <img class="task-card-avatar"
                             src="{{ $img }}"
                             alt="{{ $name }}"
                             title="{{ $name }}"
                             loading="lazy"
                             onerror="window.taskBoardCardAvatarError && window.taskBoardCardAvatarError(this)">
                    @else
                        <span class="task-card-avatar task-card-avatar-fallback {{ $letterClass }}"
                              title="{{ $name }}">{{ $initialsText }}</span>
                    @endif
                @endforeach
                @if($ticket->assignees->count() > 3)
                    <span class="task-card-avatar task-card-avatar-more"
                          title="+{{ $ticket->assignees->count() - 3 }}">+{{ $ticket->assignees->count() - 3 }}</span>
                @endif
            </div>
        @endif
    </div>

    @if($ticket->creator)
        @php
            $creator = $ticket->creator;
            $creatorName = trim(($creator->first_name ?? '').' '.($creator->last_name ?? ''));
            if ($creatorName === '') {
                $creatorName = (string) ($creator->email ?? $creator->id);
            }
            $creatorImg = $assigneePhoto($creator);
            $creatorInitials = $assigneeInitials($creator);
            $creatorLetterClass = $assigneeLetterClass($creatorInitials);
        @endphp
        <div class="task-card-creator" title="{{ translate('Created_By') }}: {{ $creatorName }}">
            @if($creatorImg)
                <img class="task-card-creator-avatar"
                     src="{{ $creatorImg }}"
                     alt="{{ $creatorName }}"
                     loading="lazy"
                     onerror="window.taskBoardCardAvatarError && window.taskBoardCardAvatarError(this)">
            @else
                <span class="task-card-creator-avatar task-card-avatar-fallback {{ $creatorLetterClass }}">{{ $creatorInitials }}</span>
            @endif
            <span class="task-card-creator-text">
                <span class="task-card-creator-label">{{ translate('Created_By') }}</span>
                <span class="task-card-creator-name">{{ $creatorName }}</span>
            </span>
        </div>
    @endif

    @if($ticket->links->isNotEmpty())
        <div class="task-card-links">
            @foreach($ticket->links->take(2) as $link)
                <span class="badge bg-light text-dark border">{{ ucfirst($link->linkable_type) }}: {{ $link->resolveLabel() }}</span>
            @endforeach
        </div>
    @endif

    @if($ticket->start_date || $ticket->end_date)
        <div class="task-card-dates">
            @if($ticket->start_date)
                <div class="task-date-block">
                    <span class="task-date-pill" title="{{ translate('Start_date') }}">
                        <span class="material-symbols-outlined" aria-hidden="true">event_available</span>
                        {{ $ticket->start_date->format('d M') }}
                    </span>
                    <span class="task-date-pill-label">{{ translate('Start_date') }}</span>
                </div>
            @endif
            @if($ticket->end_date)
                <div class="task-date-block">
                    <span class="task-date-pill {{ $isOverdue ? 'is-overdue' : ($isDueToday ? 'is-today' : '') }}" title="{{ translate('End_date') }}">
                        <span class="material-symbols-outlined" aria-hidden="true">schedule</span>
                        @if($isDueToday)
                            {{ translate('Today') }}
                        @else
                            {{ $ticket->end_date->format('d M') }}
                        @endif
                    </span>
                    <span class="task-date-pill-label">{{ translate('End_date') }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="task-card-footer">
        <span class="task-card-code">{{ $ticketCode }}</span>
        <div class="task-card-footer-right">
            @if(($ticket->comments_count ?? 0) > 0)
                <span class="task-card-stat" title="{{ translate('Comments') }}">
                    <span class="material-symbols-outlined" aria-hidden="true">chat_bubble</span>
                    {{ $ticket->comments_count }}
                </span>
            @endif
        </div>
    </div>
</article>
