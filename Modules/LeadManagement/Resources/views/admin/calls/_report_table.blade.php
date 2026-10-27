<div class="table-responsive calls-report-scroll">
    <table class="calls-report-table">
        <thead>
        <tr>
            <th rowspan="2">{{ $nameHeading }}</th>
            <th colspan="{{ count($columns) }}" class="is-today">{{ translate('Today') }}</th>
            <th colspan="{{ count($columns) }}" class="is-overall is-overall-start">{{ translate('Overall') }}</th>
        </tr>
        <tr>
            @foreach($columns as $column)
                <th class="is-today {{ isset($column['tone']) ? 'calls-tone-'.$column['tone'] : '' }}" title="{{ $column['title'] }}">{{ $column['label'] }}</th>
            @endforeach
            @foreach($columns as $index => $column)
                <th class="is-overall {{ $index === 0 ? 'is-overall-start' : '' }} {{ isset($column['tone']) ? 'calls-tone-'.$column['tone'] : '' }}" title="{{ $column['title'] }}">{{ $column['label'] }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <th>{{ $row['label'] }}</th>
                @foreach(['today', 'overall'] as $period)
                    @foreach($columns as $index => $column)
                        @php
                            $value = $row[$period][$column['key']];
                            $isZero = $value === 0 || $value === '0' || $value === '0s';
                            $tone = isset($column['tone']) ? 'calls-tone-'.$column['tone'] : '';
                        @endphp
                        <td class="{{ $period === 'today' ? 'is-today' : 'is-overall' }} {{ $period === 'overall' && $index === 0 ? 'is-overall-start' : '' }}">
                            <span class="calls-count {{ $tone }} {{ $isZero ? 'is-zero' : '' }}">{{ $value }}</span>
                        </td>
                    @endforeach
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ 1 + (count($columns) * 2) }}" class="calls-report-empty">{{ translate('No_calls_yet') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
