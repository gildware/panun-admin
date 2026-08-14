@php
    $section = $section ?? [];
    $rows = $section['rows'] ?? [];
    $columns = $section['columns'] ?? [];
    $count = (int) ($section['count'] ?? count($rows));
@endphp
@if($count > 0 && $rows !== [])
    <div class="data-table-wrap rank-metric-table-wrap rank-metric-table-scroll">
        <table class="data-table rank-metric-table">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column['label'] ?? '' }}</th>
                    @endforeach
                    <th class="rm-col-action"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($columns as $column)
                            <td class="rm-col-{{ $column['key'] ?? 'field' }}">
                                @include('adminmodule::partials._employee-progress-rank-metric-cell', [
                                    'column' => $column,
                                    'row' => $row,
                                ])
                            </td>
                        @endforeach
                        <td class="rm-col-action text-end">
                            @if(! empty($row['url']))
                                <a href="{{ $row['url'] }}" class="rm-view-btn" target="_blank" rel="noopener">
                                    {{ translate('View') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="rank-metric-empty">
        <span class="material-symbols-outlined">inbox</span>
        {{ translate('No_records_in_section') ?? 'No records in this period.' }}
    </div>
@endif
