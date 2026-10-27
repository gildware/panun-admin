<div class="table-responsive calls-report-scroll">
    <table class="calls-report-table">
        <thead>
        <tr>
            <th>{{ $nameHeading }}</th>
            @foreach($columns as $column)
                <th class="{{ isset($column['tone']) ? 'calls-tone-'.$column['tone'] : '' }}">{{ $column['label'] }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <th>{{ $row['label'] }}</th>
                @foreach($columns as $column)
                    @php
                        $value = $row[$column['key']];
                        $isZero = $value === 0 || $value === '0' || $value === '0s';
                    @endphp
                    <td>
                        <span class="calls-count {{ isset($column['tone']) ? 'calls-tone-'.$column['tone'] : '' }} {{ $isZero ? 'is-zero' : '' }}">{{ $value }}</span>
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ 1 + count($columns) }}" class="calls-report-empty">{{ translate('No_calls_yet') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
