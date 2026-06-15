@php
    $reactionGroups = ($chat->reactions ?? collect())->groupBy('emoji');
    $currentUserId = auth()->id();
@endphp
<div class="chat-message-reactions d-flex flex-wrap gap-1 mt-1" data-conversation-id="{{ $chat->id }}">
    @foreach($reactionGroups as $emoji => $group)
        @php
            $mine = $group->firstWhere('user_id', $currentUserId) !== null;
            $names = $group->map(function ($r) {
                return $r->user
                    ? trim(($r->user->first_name ?? '').' '.($r->user->last_name ?? ''))
                    : translate('no_user_found');
            })->filter()->implode(', ');
        @endphp
        <button type="button"
                class="chat-reaction-chip {{ $mine ? 'reacted' : '' }}"
                data-conversation-id="{{ $chat->id }}"
                data-emoji="{{ $emoji }}"
                title="{{ $names }}">
            <span class="chat-reaction-emoji">{{ $emoji }}</span>
            <span class="chat-reaction-count">{{ $group->count() }}</span>
        </button>
    @endforeach
</div>
