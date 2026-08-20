@php
    $section = $section ?? 'all';
    $lastConversation = $chat->channelLastConversation ?? null;
    $preview = '';
    $isOutgoingLast = false;
    $recipientChannelUsers = collect();
    $lastMessageTime = '';

    if ($lastConversation) {
        $files = $lastConversation->relationLoaded('conversationLastFiles')
            ? $lastConversation->conversationLastFiles
            : collect();
        $text = trim(strip_tags((string) $lastConversation->message));

        if ($text !== '') {
            $preview = \Illuminate\Support\Str::limit($text, 72);
        } elseif ($files->isNotEmpty()) {
            $fileType = strtolower((string) ($files->last()->file_type ?? ''));
            $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'image'];
            $preview = in_array($fileType, $imageTypes, true)
                ? translate('Image')
                : translate('Attachment');
        }

        $lastMessageTime = $lastConversation->created_at
            ? $lastConversation->created_at->format('g:i A')
            : '';

        $isOutgoingLast = $lastConversation->user
            && (string) $lastConversation->user->id === (string) auth()->id();

        if ($isOutgoingLast) {
            $recipientChannelUsers = $chat->channelUsers
                ->filter(function ($channelUser) use ($chat) {
                    if ((string) $channelUser->user_id === (string) auth()->id()) {
                        return false;
                    }

                    if (is_support_channel_reference_type($chat->reference_type ?? null)) {
                        $type = $channelUser->user->user_type ?? null;

                        return $type && ! in_array($type, ADMIN_USER_TYPES, true);
                    }

                    return true;
                })
                ->values();
        }
    }
@endphp

@if($section === 'preview')
    @if($preview !== '')
        <span class="fz-12 text-muted line-limit-1 chat-list-last-message">{{ $preview }}</span>
    @else
        <span class="chat-list-last-message chat-list-last-message--empty"></span>
    @endif
@elseif($section === 'meta')
    @if($lastMessageTime !== '' || $isOutgoingLast)
        <span class="chat-list-last-meta">
            @if($isOutgoingLast)
                @include('chattingmodule::admin.partials._chat-message-status', [
                    'chat' => $lastConversation,
                    'recipientChannelUsers' => $recipientChannelUsers,
                    'list' => true,
                ])
            @endif
            @if($lastMessageTime !== '')
                <span class="chat-list-last-time">{{ $lastMessageTime }}</span>
            @endif
        </span>
    @endif
@elseif($preview !== '' || $lastMessageTime !== '')
    <div class="chat-list-last-message-row">
        @if($preview !== '')
            <span class="fz-12 text-muted line-limit-1 chat-list-last-message">{{ $preview }}</span>
        @else
            <span class="chat-list-last-message chat-list-last-message--empty"></span>
        @endif
        @if($lastMessageTime !== '' || $isOutgoingLast)
            <span class="chat-list-last-meta">
                @if($isOutgoingLast)
                    @include('chattingmodule::admin.partials._chat-message-status', [
                        'chat' => $lastConversation,
                        'recipientChannelUsers' => $recipientChannelUsers,
                        'list' => true,
                    ])
                @endif
                @if($lastMessageTime !== '')
                    <span class="chat-list-last-time">{{ $lastMessageTime }}</span>
                @endif
            </span>
        @endif
    </div>
@endif
