@php
    $marks = $marks ?? [];
@endphp
@if($marks !== [])
    <div class="rank-marks">
        <table class="rank-marks-table">
            <colgroup>
                <col class="col-type">
                <col class="col-qty">
                <col class="col-marks">
                <col class="col-total">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">{{ translate('Type') ?? 'Type' }}</th>
                    <th scope="col">{{ translate('Qty') ?? 'Qty' }}</th>
                    <th scope="col">{{ translate('Marks') ?? 'Marks' }}</th>
                    <th scope="col">{{ translate('Total') ?? 'Total' }}</th>
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
