@if(empty($user))
    <span class="text-muted">—</span>
@else
    <div class="d-flex flex-column">
        @if(!empty($user['profile_url']))
            <a href="{{ $user['profile_url'] }}" class="c1 fw-medium">{{ $user['name'] }}</a>
        @else
            <span class="fw-medium">{{ $user['name'] }}</span>
        @endif
        <span class="small text-muted">
            {{ $user['user_type_label'] }}
            @if(!empty($user['phone']))
                · {{ $user['phone'] }}
            @endif
        </span>
    </div>
@endif
