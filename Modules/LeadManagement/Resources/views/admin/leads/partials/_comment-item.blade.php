@php
    $commentAuthor = $comment->createdBy;
    $commentAuthorName = '—';
    $commentInitials = '?';
    if ($commentAuthor) {
        $caName = trim(($commentAuthor->first_name ?? '') . ' ' . ($commentAuthor->last_name ?? ''));
        $commentAuthorName = $caName ?: ($commentAuthor->email ?? '—');
        $nameParts = preg_split('/\s+/', trim($commentAuthorName)) ?: [];
        $commentInitials = strtoupper(
            substr($nameParts[0] ?? '?', 0, 1)
            . substr($nameParts[1] ?? '', 0, 1)
        ) ?: '?';
    }
    $canDeleteComment = auth()->user()
        && ((string) $comment->created_by === (string) auth()->id()
            || auth()->user()->user_type === 'super-admin');
@endphp
<div class="lead-comment-item {{ !empty($comment->is_pinned) ? 'is-pinned' : '' }}"
     data-comment-id="{{ $comment->id }}">
    <div class="lead-comment-avatar" title="{{ $commentAuthorName }}">
        {{ $commentInitials }}
    </div>
    <div class="lead-comment-card">
        <div class="lead-comment-card-header">
            <div class="lead-comment-meta">
                <strong>{{ $commentAuthorName }}</strong>
                <span class="lead-comment-time">{{ $comment->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
                @if(!empty($comment->is_pinned))
                    <span class="lead-comment-pin-badge" title="{{ translate('Pinned') }}">
                        <span class="material-icons">push_pin</span>
                        {{ translate('Pinned') }}
                    </span>
                @endif
            </div>
            @can('lead_update')
                <div class="lead-comment-actions">
                    <button type="button"
                            class="btn btn-sm btn-link lead-comment-pin-btn p-0"
                            data-url="{{ route('admin.lead.comments.pin', $comment->id) }}"
                            title="{{ !empty($comment->is_pinned) ? translate('Unpin') : translate('Pin') }}">
                        <span class="material-icons">{{ !empty($comment->is_pinned) ? 'push_pin' : 'push_pin' }}</span>
                    </button>
                    @if($canDeleteComment)
                        <button type="button"
                                class="btn btn-sm btn-link text-danger lead-comment-delete-btn p-0"
                                data-url="{{ route('admin.lead.comments.destroy', $comment->id) }}"
                                title="{{ translate('Delete') }}">
                            <span class="material-icons">delete_outline</span>
                        </button>
                    @endif
                </div>
            @endcan
        </div>
        <div class="lead-comment-body">{!! $commentParser->format($comment->body) !!}</div>
    </div>
</div>
