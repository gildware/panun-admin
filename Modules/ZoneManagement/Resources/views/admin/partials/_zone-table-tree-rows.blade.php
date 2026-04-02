@php
    $children = $zone->childZones ?? collect();
    $hasChildren = $children->isNotEmpty();
    $isTopLevel = $depth === 0;
    $childCount = $hasChildren ? $children->count() : (int) ($zone->child_zones_count ?? 0);
    $openRootId = $defaultOpenRootZoneId ?? null;
    $isDefaultOpenRoot = $isTopLevel && $hasChildren && $openRootId !== null && (string) $openRootId === (string) $zone->id;
    $showNestedByDefault = $openRootId !== null && $depth === 1 && $parentZoneId !== null && (string) $parentZoneId === (string) $openRootId;
    $nestedHidden = ! $isTopLevel && ! $showNestedByDefault;
    $treeBranchRootId = $branchRootId ?? $zone->id;
@endphp
<tr class="zone-list-tree-row align-middle @if($isTopLevel) zone-list-top-level @endif @if(!$isTopLevel) zone-list-nested-row @endif @if($hasChildren) zone-list-expandable @endif @if($isDefaultOpenRoot) zone-children-open @endif @if($nestedHidden) d-none @endif"
    data-zone-id="{{ $zone->id }}"
    data-branch-root="{{ $treeBranchRootId }}"
    @if($parentZoneId !== null) data-child-of="{{ $parentZoneId }}" @endif
    data-depth="{{ $depth }}"
    data-has-children="{{ $hasChildren ? '1' : '0' }}">
    <td>@if($isTopLevel){{ $slIndex }}@endif</td>
    <td class="@if(!$isTopLevel) zone-list-tree-name-cell @endif"
        @if(!$isTopLevel) style="padding-left: {{ 1.25 + max(0, $depth - 1) * 1.15 }}rem;" @endif>
        @if(!$isTopLevel)
            <span class="zone-list-tree-glyph text-muted" aria-hidden="true">└</span>
        @endif
        <span class="zone-list-tree-name">{{ $zone->name }}</span>
    </td>
    <td>
        @if(isset($zone->parentZone) && $zone->parentZone)
            {{ $zone->parentZone->name }}
        @else
            {{ translate('No_parent_root_zone') }}
        @endif
    </td>
    <td>
        @if($childCount > 0)
            <span class="badge bg-light text-dark">{{ $childCount }}</span>
        @else
            —
        @endif
    </td>
    <td>{{ $zone->providers_count }}</td>
    <td>{{ $zone->categories_count }}</td>
    @can('zone_manage_status')
        <td>
            <label class="switcher">
                <input class="switcher_input status-update"
                       data-id="{{ $zone->id }}"
                       type="checkbox" {{ $zone->is_active ? 'checked' : '' }}>
                <span class="switcher_control"></span>
            </label>
        </td>
    @endcan
    <td>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @can('zone_update')
                <a href="{{ route('admin.zone.edit', [$zone->id]) }}"
                   class="action-btn btn--light-primary demo_check">
                    <span class="material-icons">edit</span>
                </a>
            @endcan
            @can('zone_delete')
                <button type="button"
                        data-id="delete-{{ $zone->id }}"
                        data-message="{{ translate('want_to_delete_this_zone') }}?"
                        class="action-btn btn--danger {{ env('APP_ENV') != 'demo' ? 'form-alert' : 'demo_check' }}"
                        style="--size: 30px">
                    <span class="material-symbols-outlined">delete</span>
                </button>
                <form
                    action="{{ route('admin.zone.delete', [$zone->id]) }}"
                    method="post" id="delete-{{ $zone->id }}"
                    class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
            @if($hasChildren)
                <button type="button"
                        class="btn btn-sm rounded-pill zone-toggle-children px-3 py-1 text-nowrap d-inline-flex align-items-center {{ $isDefaultOpenRoot ? 'zone-toggle-children--hide' : 'zone-toggle-children--view' }}"
                        data-label-show="{{ translate('View_children') }}"
                        data-label-hide="{{ translate('Hide') }}"
                        aria-expanded="{{ $isDefaultOpenRoot ? 'true' : 'false' }}">
                    <span class="material-icons zone-toggle-children__icon" aria-hidden="true">{{ $isDefaultOpenRoot ? 'expand_less' : 'expand_more' }}</span>
                    <span class="zone-toggle-children__label">{{ $isDefaultOpenRoot ? translate('Hide') : translate('View_children') }}</span>
                </button>
            @endif
        </div>
    </td>
</tr>
@foreach($children as $child)
    @include('zonemanagement::admin.partials._zone-table-tree-rows', [
        'zone' => $child,
        'depth' => $depth + 1,
        'parentZoneId' => $zone->id,
        'slIndex' => null,
        'defaultOpenRootZoneId' => $openRootId,
        'branchRootId' => $treeBranchRootId,
    ])
@endforeach
