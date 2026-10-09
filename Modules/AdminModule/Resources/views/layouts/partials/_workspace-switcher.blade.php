@php
    $workspace = admin_workspace();
@endphp
<div class="dropdown top-utility-item workspace-switch">
    <button type="button"
            class="workspace-switch-btn dropdown-toggle"
            data-bs-toggle="dropdown"
            data-bs-offset="0,6"
            data-bs-popper-config='{"strategy":"fixed"}'
            aria-expanded="false">
        <span class="workspace-switch-dot" aria-hidden="true"></span>
        <span>{{ \App\Support\AdminWorkspace::label($workspace) }}</span>
    </button>
    <ul class="dropdown-menu workspace-switch-menu py-1">
        @foreach(\App\Support\AdminWorkspace::visibleKeys() as $key)
            <li>
                <a class="dropdown-item {{ $workspace === $key ? 'active' : '' }}"
                   href="{{ route('admin.workspace.enter', $key) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-turbo="false">
                    {{ \App\Support\AdminWorkspace::label($key) }}
                </a>
            </li>
        @endforeach
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="{{ route('admin.workspace.choose') }}" target="_blank" rel="noopener noreferrer" data-turbo="false">All workspaces</a>
        </li>
    </ul>
</div>
