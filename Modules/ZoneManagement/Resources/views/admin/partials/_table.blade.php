<div class="table-responsive">
    <table id="example" class="table align-middle zone-list-table">
        <thead>
        <tr>
            <th class="zone-col-name">{{translate('zone_name')}}</th>
            <th class="zone-col-fit">{{translate('Parent_zone')}}</th>
            <th class="zone-col-areas">{{translate('Area_Encompassed_Mohalla')}}</th>
            <th class="zone-col-fit">Children</th>
            <th class="zone-col-fit">{{translate('providers')}}</th>
            <th class="zone-col-fit">{{translate('Category')}}</th>
            @can('zone_manage_status')
                <th class="zone-col-fit">{{translate('status')}}</th>
            @endcan
            <th class="zone-col-fit">{{translate('action')}}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($zones as $zone)
            @include('zonemanagement::admin.partials._zone-table-tree-rows', [
                'zone' => $zone,
            ])
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted">{{ translate('no_data_found') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-end">
    {!! $zones->links() !!}
</div>
