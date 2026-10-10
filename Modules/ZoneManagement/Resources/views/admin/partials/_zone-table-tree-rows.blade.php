@php
    $childCount = (int) ($zone->child_zones_count ?? ($zone->relationLoaded('childZones') ? $zone->childZones->count() : 0));
@endphp
<tr class="zone-list-tree-row align-middle zone-list-top-level" data-zone-id="{{ $zone->id }}">
    <td class="zone-col-name">
        <a href="{{ route('admin.zone.view', [$zone->id]) }}" class="zone-list-tree-name">{{ $zone->name }}</a>
    </td>
    <td class="zone-col-fit">
        @if(isset($zone->parentZone) && $zone->parentZone)
            {{ $zone->parentZone->name }}
        @else
            {{ translate('No_parent_root_zone') }}
        @endif
    </td>
    <td class="zone-col-areas zone-list-areas">
        @php($areaNameList = $zone->relationLoaded('areas') ? $zone->areas->pluck('name')->filter()->values() : collect())
        @if($areaNameList->isEmpty() && filled($zone->areas_encompassed ?? null))
            @php($areaNameList = collect(preg_split('/\r\n|\r|\n/', (string) $zone->areas_encompassed))->map(fn ($name) => trim($name))->filter()->values())
        @endif
        @if($areaNameList->isNotEmpty())
            <span class="zone-list-area-preview" title="{{ $areaNameList->implode(', ') }}">{{ $areaNameList->implode(', ') }}</span>
        @else
            —
        @endif
    </td>
    <td class="zone-col-fit">
        @if($childCount > 0)
            <span class="badge bg-light text-dark">{{ $childCount }}</span>
        @else
            —
        @endif
    </td>
    <td class="zone-col-fit">{{ $zone->providers_count }}</td>
    <td class="zone-col-fit">{{ $zone->categories_count }}</td>
    @can('zone_manage_status')
        <td class="zone-col-fit">
            <label class="switcher">
                <input class="switcher_input status-update"
                       data-id="{{ $zone->id }}"
                       type="checkbox" {{ $zone->is_active ? 'checked' : '' }}>
                <span class="switcher_control"></span>
            </label>
        </td>
    @endcan
    <td class="zone-col-fit">
        <div class="zone-list-actions">
            <span class="zone-action-slot">
                @canany(['zone_view', 'zone_update'])
                    <a href="{{ route('admin.zone.view', [$zone->id]) }}"
                       class="action-btn btn--light-primary"
                       title="{{ translate('View') }}">
                        <span class="material-icons">visibility</span>
                    </a>
                @endcanany
            </span>
            <span class="zone-action-slot">
                @can('zone_update')
                    <a href="{{ route('admin.zone.edit', [$zone->id]) }}"
                       class="action-btn btn--light-primary demo_check"
                       title="{{ translate('edit') }}">
                        <span class="material-icons">edit</span>
                    </a>
                @endcan
            </span>
            <span class="zone-action-slot">
                @can('zone_delete')
                    <button type="button"
                            data-id="delete-{{ $zone->id }}"
                            data-message="{{ translate('want_to_delete_this_zone') }}?"
                            class="action-btn btn--danger {{ env('APP_ENV') != 'demo' ? 'form-alert' : 'demo_check' }}"
                            style="--size: 30px">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                @endcan
            </span>
            <span class="zone-action-children">
                @if($childCount > 0)
                    <a href="{{ route('admin.zone.children', $zone->id) }}"
                       class="btn btn-sm rounded-pill zone-toggle-children zone-toggle-children--view px-3 py-1 text-nowrap d-inline-flex align-items-center">
                        <span class="material-icons zone-toggle-children__icon" aria-hidden="true">chevron_right</span>
                        <span class="zone-toggle-children__label">{{ translate('View_children') }}</span>
                    </a>
                @endif
            </span>
        </div>
        @can('zone_delete')
            <form
                action="{{ route('admin.zone.delete', [$zone->id]) }}"
                method="post" id="delete-{{ $zone->id }}"
                class="d-none">
                @csrf
                @method('DELETE')
            </form>
        @endcan
    </td>
</tr>
