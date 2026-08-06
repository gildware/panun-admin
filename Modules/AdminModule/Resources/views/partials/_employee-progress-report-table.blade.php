<div class="report-data-card">
    <div class="report-data-card-header">
        <span>{{ $title ?? '' }}</span>
        <span class="report-badge {{ ($count ?? 0) > 0 ? 'report-badge--brand' : '' }}">{{ $count ?? 0 }}</span>
    </div>
    <div class="report-data-card-body">
        @if(($items ?? collect())->isNotEmpty())
            <div class="report-table-scroll report-table-scroll--compact">
                <table class="report-detail-table">
                    <thead>
                        <tr>
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Date_time') }}</th>
                            <th>{{ translate('Urgency') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr class="{{ ! empty($item['is_overdue']) ? 'is-overdue' : '' }}">
                                <td>
                                    <a href="{{ $item['url'] ?? '#' }}" class="report-row-link">
                                        <span class="cell-primary">{{ $item['name'] ?? '—' }}</span>
                                        @if(! empty($item['name_sub']))
                                            <span class="cell-secondary">{{ $item['name_sub'] }}</span>
                                        @endif
                                    </a>
                                </td>
                                <td><span class="type-pill">{{ $item['type'] ?? '—' }}</span></td>
                                <td>{{ $item['datetime_display'] ?? '—' }}</td>
                                <td>
                                    <span class="urgency-pill urgency-{{ $item['urgency'] ?? 'medium' }}">
                                        {{ $item['urgency_label'] ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(($count ?? 0) > count($items))
                <div class="report-data-card-footer">
                    <a href="{{ $viewAllUrl ?? '#' }}">{{ translate('view_all') }} ({{ $count }})</a>
                </div>
            @endif
        @else
            <div class="report-empty">
                @include('adminmodule::partials._material-icon', ['name' => 'inbox'])
                <span>{{ $emptyLabel ?? translate('no_data_available') }}</span>
            </div>
        @endif
    </div>
</div>
