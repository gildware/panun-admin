<div class="top-nav-link-row">
    <a href="{{ $href }}"
       class="{{ (!empty($active) || (!empty($adminNavMatch['url']) && rtrim($adminNavMatch['url'], '/') === rtrim($href, '/'))) ? 'active-menu' : '' }}"
       @if(!empty($fullPage)) data-turbo="false"
       @elseif(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        {{ $label }}
        @if(!empty($count))<span class="badge-count">{{ $count }}</span>@endif
    </a>
</div>
