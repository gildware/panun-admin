@foreach($data as $key => $value)
    @if($key === 'ac_line_amount' || is_array($value))
        @continue
    @endif
    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
@endforeach
@if(!empty($data['ac_line_amount']) && is_array($data['ac_line_amount']))
    @foreach($data['ac_line_amount'] as $acTypeId => $acAmt)
        <input type="hidden" name="ac_line_amount[{{ $acTypeId }}]" value="{{ $acAmt }}">
    @endforeach
@endif
