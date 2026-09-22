@php
    $type = $question['type'] ?? 'text';
    $qid = $question['id'];
@endphp

<div class="pk-q-block">
    <div class="pk-q-label">{{ $question['label'] }}</div>

    @if($type === 'textarea')
        <textarea class="form-control" name="answers[{{ $qid }}]" rows="3" maxlength="{{ (int) ($question['max'] ?? 1000) }}">{{ $val($qid) }}</textarea>
    @elseif($type === 'text')
        <input type="text" class="form-control" name="answers[{{ $qid }}]" value="{{ $val($qid) }}" maxlength="{{ (int) ($question['max'] ?? 255) }}">
    @elseif($type === 'text_pair')
        <div class="row g-3">
            @foreach($question['fields'] as $field)
                <div class="col-md-6">
                    <label class="form-label" for="q-{{ $field['id'] }}">{{ $field['label'] }}</label>
                    <input type="text" class="form-control" id="q-{{ $field['id'] }}" name="answers[{{ $field['id'] }}]" value="{{ $val($field['id']) }}" maxlength="{{ (int) ($field['max'] ?? 255) }}">
                </div>
            @endforeach
        </div>
    @elseif($type === 'radio' || $type === 'checkbox')
        <div class="pk-q-options">
            @foreach($question['options'] as $option)
                @php
                    $optionId = 'q-'.$qid.'-'.$option['value'];
                    $isChecked = $type === 'checkbox'
                        ? in_array($option['value'], $vals($qid), true)
                        : (string) $val($qid) === (string) $option['value'];
                @endphp
                @if($type === 'checkbox')
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="{{ $optionId }}" name="answers[{{ $qid }}][]" value="{{ $option['value'] }}" {{ $isChecked ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $optionId }}">{{ $option['label'] }}</label>
                    </div>
                @else
                    <div class="custom-radio">
                        <input type="radio" id="{{ $optionId }}" name="answers[{{ $qid }}]" value="{{ $option['value'] }}" {{ $isChecked ? 'checked' : '' }}>
                        <label for="{{ $optionId }}">{{ $option['label'] }}</label>
                    </div>
                @endif
            @endforeach
        </div>
        @if(!empty($question['other_key']))
            @php
                $otherSelected = $type === 'checkbox'
                    ? in_array('other', $vals($qid), true)
                    : (string) $val($qid) === 'other';
            @endphp
            <div class="pk-q-other {{ $otherSelected ? '' : 'd-none' }}">
                <input type="text" class="form-control" name="answers[{{ $question['other_key'] }}]" value="{{ $val($question['other_key']) }}" placeholder="{{ translate('Other') }}" maxlength="255" {{ $otherSelected ? '' : 'disabled' }}>
            </div>
        @endif
        @if(!empty($question['followup']))
            @php
                $followup = $question['followup'];
                $showFollowup = (string) $val($qid) === (string) ($followup['show_when'] ?? '');
            @endphp
            <div class="mt-3 {{ $showFollowup ? '' : 'd-none' }}" data-q-followup="{{ $followup['id'] }}" data-show-when="{{ $followup['show_when'] }}">
                <label class="form-label" for="q-{{ $followup['id'] }}">{{ $followup['label'] }}</label>
                <textarea class="form-control" id="q-{{ $followup['id'] }}" name="answers[{{ $followup['id'] }}]" rows="2" maxlength="{{ (int) ($followup['max'] ?? 1000) }}" {{ $showFollowup ? '' : 'disabled' }}>{{ $val($followup['id']) }}</textarea>
            </div>
        @endif
    @elseif($type === 'time_range')
        <div class="row g-3 align-items-end">
            <div class="col-sm-3 col-md-2">
                <label class="form-label" for="q-{{ $question['from_key'] }}">{{ translate('From') }}</label>
                <input type="text" class="form-control" id="q-{{ $question['from_key'] }}" name="answers[{{ $question['from_key'] }}]" value="{{ $val($question['from_key']) }}" placeholder="9:00" maxlength="20">
            </div>
            <div class="col-sm-2 col-md-2">
                <label class="form-label" for="q-{{ $question['from_period_key'] }}">AM/PM</label>
                <select class="form-control" id="q-{{ $question['from_period_key'] }}" name="answers[{{ $question['from_period_key'] }}]">
                    <option value="AM" {{ $val($question['from_period_key'], 'AM') === 'AM' ? 'selected' : '' }}>AM</option>
                    <option value="PM" {{ $val($question['from_period_key']) === 'PM' ? 'selected' : '' }}>PM</option>
                </select>
            </div>
            <div class="col-sm-3 col-md-2">
                <label class="form-label" for="q-{{ $question['to_key'] }}">{{ translate('To') }}</label>
                <input type="text" class="form-control" id="q-{{ $question['to_key'] }}" name="answers[{{ $question['to_key'] }}]" value="{{ $val($question['to_key']) }}" placeholder="6:00" maxlength="20">
            </div>
            <div class="col-sm-2 col-md-2">
                <label class="form-label" for="q-{{ $question['to_period_key'] }}">AM/PM</label>
                <select class="form-control" id="q-{{ $question['to_period_key'] }}" name="answers[{{ $question['to_period_key'] }}]">
                    <option value="AM" {{ $val($question['to_period_key']) === 'AM' ? 'selected' : '' }}>AM</option>
                    <option value="PM" {{ $val($question['to_period_key'], 'PM') === 'PM' ? 'selected' : '' }}>PM</option>
                </select>
            </div>
        </div>
    @elseif($type === 'money_pair')
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="q-{{ $question['daily_key'] }}">{{ translate('Daily') }} ₹</label>
                <input type="text" class="form-control" id="q-{{ $question['daily_key'] }}" name="answers[{{ $question['daily_key'] }}]" value="{{ $val($question['daily_key']) }}" maxlength="40">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="q-{{ $question['monthly_key'] }}">{{ translate('Monthly') }} ₹</label>
                <input type="text" class="form-control" id="q-{{ $question['monthly_key'] }}" name="answers[{{ $question['monthly_key'] }}]" value="{{ $val($question['monthly_key']) }}" maxlength="40">
            </div>
        </div>
    @endif
</div>
