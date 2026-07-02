<span class="chat-message-meta chat-message-meta--inline">
    @include('chattingmodule::admin.partials._chat-message-status', [
        'chat' => $chat,
        'recipientChannelUsers' => $recipientChannelUsers ?? collect(),
        'inline' => true,
    ])
    <span class="time_date mb-0">{{ date('g:i a', strtotime($chat->created_at)) }}</span>
</span>
