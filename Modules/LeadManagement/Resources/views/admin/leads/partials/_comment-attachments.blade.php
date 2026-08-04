@if(($comment->attachments ?? collect())->isNotEmpty())
    <div class="comment-attachments-list">
        @foreach($comment->attachments as $attachment)
            @if($attachment->isImage())
                <a class="comment-attachment comment-attachment--image"
                   href="{{ $attachment->url }}"
                   target="_blank"
                   rel="noopener"
                   title="{{ $attachment->original_name }}">
                    <img src="{{ $attachment->url }}" alt="{{ $attachment->original_name }}" data-no-img-fallback="1">
                    <span class="comment-attachment__name">{{ $attachment->original_name ?: translate('Image') }}</span>
                </a>
            @elseif($attachment->isVideo())
                <div class="comment-attachment comment-attachment--video">
                    <video controls preload="metadata" src="{{ $attachment->url }}"></video>
                    <a class="comment-attachment__download small"
                       href="{{ $attachment->url }}"
                       target="_blank"
                       rel="noopener">{{ $attachment->original_name }}</a>
                </div>
            @elseif($attachment->isAudio())
                <div class="comment-attachment comment-attachment--audio">
                    <audio controls preload="metadata" src="{{ $attachment->url }}"></audio>
                    <a class="comment-attachment__download small"
                       href="{{ $attachment->url }}"
                       target="_blank"
                       rel="noopener">{{ $attachment->original_name }}</a>
                </div>
            @else
                <a class="comment-attachment comment-attachment--file"
                   href="{{ $attachment->url }}"
                   target="_blank"
                   rel="noopener">
                    <span class="material-icons" aria-hidden="true">draft</span>
                    <span>{{ $attachment->original_name ?: translate('File') }}</span>
                </a>
            @endif
        @endforeach
    </div>
@endif
