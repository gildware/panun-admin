@php
    $compact = $compact ?? false;
    $inline = $inline ?? false;
    $list = $list ?? false;
    $status = app(\Modules\ChattingModule\Services\ChatMessageStatusResolver::class)
        ->resolve($chat->created_at, $recipientChannelUsers ?? collect());
    $statusLabel = app(\Modules\ChattingModule\Services\ChatMessageStatusResolver::class)->label($status);
    $iconSizeClass = $inline || $compact || $list ? 'fs-12' : 'fs-14';
@endphp
<span class="chat-message-status chat-message-status--{{ $status }}{{ $compact ? ' chat-message-status--compact' : '' }}{{ $inline ? ' chat-message-status--inline' : '' }}{{ $list ? ' chat-message-status--list' : '' }}" title="{{ $statusLabel }}">
    @if($status === 'seen')
        <span class="material-symbols-outlined {{ $iconSizeClass }}" aria-hidden="true">done_all</span>
    @elseif($status === 'delivered')
        <span class="material-symbols-outlined {{ $iconSizeClass }}" aria-hidden="true">done_all</span>
    @else
        <span class="material-symbols-outlined {{ $iconSizeClass }}" aria-hidden="true">check</span>
    @endif
    @unless($compact)
        <span class="chat-message-status-label">{{ $statusLabel }}</span>
    @endunless</span>
