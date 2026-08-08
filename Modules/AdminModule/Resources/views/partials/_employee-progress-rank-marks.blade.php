@php
    $marks = $marks ?? [];
@endphp
@if($marks !== [])
    <div class="rank-marks">
        <table class="rank-marks-table">
            <thead>
                <tr>
                    <th>{{ translate('Type') ?? 'Type' }}</th>
                    <th>{{ translate('Qty') ?? 'Qty' }}</th>
                    <th>{{ translate('Marks') ?? 'Marks' }}</th>
                    <th>{{ translate('Total') ?? 'Total' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($marks as $mark)
                    @php
                        $isPlus = ! empty($mark['positive']);
                        $unit = (int) ($mark['unit_points'] ?? 0);
                        $points = (int) ($mark['points'] ?? 0);
                        $unitDisplay = ($isPlus ? '+' : '−').abs($unit);
                        $pointsDisplay = ($points > 0 ? '+' : ($points < 0 ? '' : '')).$points;
                    @endphp
                    <tr class="{{ $isPlus ? 'is-plus' : 'is-minus' }}">
                        <td class="rank-mark-type">{{ $mark['label'] ?? '' }}</td>
                        <td class="rank-mark-qty">{{ (int) ($mark['count'] ?? 0) }}</td>
                        <td class="rank-mark-unit">{{ $unitDisplay }}</td>
                        <td class="rank-mark-total">{{ $pointsDisplay }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
