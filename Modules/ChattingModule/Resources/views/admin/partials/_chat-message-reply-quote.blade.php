@php
    $reply = $reply ?? null;
    $parser = app(\Modules\ChattingModule\Services\StaffChatMessageParser::class);
@endphp
@if($reply)
    @php
        $replyAuthor = $reply->user
            ? trim(($reply->user->first_name ?? '').' '.($reply->user->last_name ?? ''))
            : translate('no_user_found');
        $replyPreview = $parser->plainPreview($reply->message, 120);
        if ($replyPreview === '' && $reply->conversationFiles?->isNotEmpty()) {
            $replyPreview = translate('Attachment');
        }
    @endphp
    <div class="chat-reply-quote border-start border-3 border-primary ps-2 mb-2">
        <div class="fz-11 fw-semibold text-primary">{{ $replyAuthor }}</div>
        <div class="fz-12 text-muted text-truncate">{{ $replyPreview }}</div>
    </div>
@endif
