@php
    $trainingSetupTabs = [
        ['route' => 'admin.training.manage', 'active' => request()->routeIs('admin.training.manage', 'admin.training.manage.edit', 'admin.training.manage.curriculum'), 'label' => 'Programs'],
        ['route' => 'admin.training.assign', 'active' => request()->routeIs('admin.training.assign'), 'label' => 'Assign'],
        ['route' => 'admin.training.live', 'active' => request()->routeIs('admin.training.live'), 'label' => 'Live'],
        ['route' => 'admin.training.calls.manage', 'active' => request()->routeIs('admin.training.calls.manage', 'admin.training.calls.manage.create', 'admin.training.calls.manage.edit', 'admin.training.calls.manage.assign'), 'label' => 'Call tests'],
    ];
@endphp
<nav class="trn-tabs" aria-label="Training setup">
    @foreach($trainingSetupTabs as $tab)
        <a class="{{ $tab['active'] ? 'is-on' : '' }}" href="{{ route($tab['route']) }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>{{ $tab['label'] }}</a>
    @endforeach
</nav>
