@php
    $defaultOpenRootZoneId = null;
    foreach ($zones as $z) {
        if (($z->parent_id ?? null) !== null && ($z->parent_id ?? '') !== '') {
            continue;
        }
        if (($z->childZones ?? collect())->isNotEmpty()) {
            $defaultOpenRootZoneId = $z->id;
            break;
        }
    }
@endphp
<div class="table-responsive">
    <table id="example" class="table align-middle zone-list-table">
        <thead>
        <tr>
            <th>{{translate('SL')}}</th>
            <th>{{translate('zone_name')}}</th>
            <th>{{translate('Parent_zone')}}</th>
            <th>Children</th>
            <th>{{translate('providers')}}</th>
            <th>{{translate('Category')}}</th>
            @can('zone_manage_status')
                <th>{{translate('status')}}</th>
            @endcan
            <th>{{translate('action')}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($zones as $key => $zone)
            @include('zonemanagement::admin.partials._zone-table-tree-rows', [
                'zone' => $zone,
                'depth' => 0,
                'parentZoneId' => null,
                'slIndex' => $key + $zones->firstItem(),
                'defaultOpenRootZoneId' => $defaultOpenRootZoneId,
                'branchRootId' => $zone->id,
            ])
        @endforeach
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-end">
    {!! $zones->links() !!}
</div>
