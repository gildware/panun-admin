@foreach($data as $key => $value)
    @if($key === 'ac_line_amount' || $key === 'advance_method_fields' || is_array($value))
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
