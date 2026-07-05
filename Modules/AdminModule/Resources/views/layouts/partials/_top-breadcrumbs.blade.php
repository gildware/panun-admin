@php($adminBreadcrumbs = $adminBreadcrumbs ?? \App\Support\AdminBreadcrumb::resolve())

@if(!empty($adminBreadcrumbs))
    <nav class="top-sub-nav-breadcrumbs" aria-label="{{ translate('breadcrumb') }}">
        <ol class="breadcrumb top-sub-nav-breadcrumb mb-0">
            @foreach($adminBreadcrumbs as $index => $crumb)
                @if($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
                @else
                    <li class="breadcrumb-item">
                        @if(!empty($crumb['url']))
                            <a href="{{ $crumb['url'] }}"
                               @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                {{ $crumb['label'] }}
                            </a>
                        @else
                            {{ $crumb['label'] }}
                        @endif
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
