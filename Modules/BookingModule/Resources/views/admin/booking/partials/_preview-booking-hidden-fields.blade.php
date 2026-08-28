@foreach($data as $key => $value)
    @if($key === 'ac_line_amount' || $key === 'advance_method_fields' || $key === 'repeat_weekdays' || $key === 'repeat_custom_dates' || is_array($value))
        @continue
    @endif
    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
@endforeach
@foreach((array) ($data['advance_method_fields'] ?? []) as $fn => $fv)
    @if($fn === '' || (! is_string($fn) && ! is_int($fn)))
        @continue
    @endif
    <input type="hidden" name="advance_method_fields[{{ $fn }}]" value="{{ is_scalar($fv) ? $fv : '' }}">
@endforeach
@if(!empty($data['ac_line_amount']) && is_array($data['ac_line_amount']))
    @foreach($data['ac_line_amount'] as $acTypeId => $acAmt)
        <input type="hidden" name="ac_line_amount[{{ $acTypeId }}]" value="{{ $acAmt }}">
    @endforeach
@endif
@foreach((array) ($data['repeat_weekdays'] ?? []) as $weekday)
    <input type="hidden" name="repeat_weekdays[]" value="{{ $weekday }}">
@endforeach
@foreach((array) ($data['repeat_month_days'] ?? []) as $monthDay)
    @if((int) $monthDay < 1)
        @continue
    @endif
    <input type="hidden" name="repeat_month_days[]" value="{{ (int) $monthDay }}">
@endforeach
@foreach((array) ($data['repeat_custom_dates'] ?? []) as $customDate)
    @if(trim((string) $customDate) === '')
        @continue
    @endif
    <input type="hidden" name="repeat_custom_dates[]" value="{{ $customDate }}">
@endforeach

@foreach((array) ($data['advance_method_fields'] ?? []) as $fn => $fv)
    @if($fn === '' || (! is_string($fn) && ! is_int($fn)))
        @continue
    @endif
    <input type="hidden" name="advance_method_fields[{{ $fn }}]" value="{{ is_scalar($fv) ? $fv : '' }}">
@endforeach
@if(!empty($data['ac_line_amount']) && is_array($data['ac_line_amount']))
    @foreach($data['ac_line_amount'] as $acTypeId => $acAmt)
        <input type="hidden" name="ac_line_amount[{{ $acTypeId }}]" value="{{ $acAmt }}">
    @endforeach
@endif
