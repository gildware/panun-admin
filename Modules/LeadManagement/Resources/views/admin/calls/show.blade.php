@extends('adminmodule::layouts.new-master')

@section('title', translate('Call_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <h2 class="page-title mb-0">{{ translate('Call_details') }}</h2>
                <a href="{{ route('admin.calls.index') }}" class="btn btn-outline-secondary">{{ translate('Back') }}</a>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">{{ translate('Time') }}</dt>
                                <dd class="col-sm-8">{{ $call->formatIst('started_at') }}</dd>

                                <dt class="col-sm-4">{{ translate('Direction') }}</dt>
                                <dd class="col-sm-8">{{ $call->directionLabel() }}</dd>

                                <dt class="col-sm-4">{{ translate('Phone') }}</dt>
                                <dd class="col-sm-8">{{ $call->customer_phone ?: '—' }}</dd>

                                <dt class="col-sm-4">{{ translate('Superfone_number') }}</dt>
                                <dd class="col-sm-8">{{ $call->superfone_number ?: '—' }}</dd>

                                <dt class="col-sm-4">{{ translate('Result') }}</dt>
                                <dd class="col-sm-8">{{ $call->outcomeLabel() }} @if($call->disposition)<span class="text-muted">({{ $call->disposition }})</span>@endif</dd>

                                <dt class="col-sm-4">{{ translate('Duration') }}</dt>
                                <dd class="col-sm-8">{{ $call->durationLabel() }}</dd>

                                <dt class="col-sm-4">{{ translate('Answered_at') }}</dt>
                                <dd class="col-sm-8">{{ $call->formatIst('answered_at') }}</dd>

                                <dt class="col-sm-4">{{ translate('Ended_at') }}</dt>
                                <dd class="col-sm-8">{{ $call->formatIst('ended_at') }}</dd>

                                <dt class="col-sm-4">{{ translate('Employee') }}</dt>
                                <dd class="col-sm-8">
                                    {{ $call->staff_name ?: '—' }}
                                    @if($call->staff_phone)
                                        <span class="text-muted">· {{ $call->staff_phone }}</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">{{ translate('Lead') }}</dt>
                                <dd class="col-sm-8">
                                    @include('leadmanagement::admin.calls._lead_cell', [
                                        'call' => $call,
                                        'openBookings' => $bookingChoices ?? collect(),
                                    ])
                                </dd>

                                <dt class="col-sm-4">{{ translate('Booking') }}</dt>
                                <dd class="col-sm-8">
                                    @if($call->booking)
                                        <a href="{{ route('admin.booking.details', [$call->booking_id, 'web_page' => 'details']) }}">
                                            {{ $call->booking->readable_id ?: $call->booking_id }}
                                        </a>
                                        <span class="text-muted">· {{ ucfirst(str_replace('_', ' ', (string) $call->booking->booking_status)) }}</span>
                                    @else
                                        —
                                    @endif
                                </dd>

                                    <dt class="col-sm-4">{{ translate('Actions') }}</dt>
                                    <dd class="col-sm-8">
                                        @if($call->lead || $call->booking)
                                            <form method="post" action="{{ route('admin.calls.unmatch', $call) }}" class="d-inline" onsubmit="return confirm(@json(translate('Unmatch_this_call')));">
                                                @csrf
                                                <button type="submit" class="btn btn--danger calls-unmatch-btn">{{ translate('Unmatch') }}</button>
                                            </form>
                                        @else
                                        @php
                                            $linkProvider = trim((string) ($call->provider->company_name ?? $call->provider->contact_person_name ?? ''));
                                            $linkCustomer = trim((string) (($call->customer->first_name ?? '').' '.($call->customer->last_name ?? '')));
                                            if ($call->provider) {
                                                $linkParty = 'provider';
                                                $linkPartyName = $linkProvider !== '' ? $linkProvider : translate('Provider');
                                            } elseif ($call->customer) {
                                                $linkParty = 'customer';
                                                $linkPartyName = $linkCustomer !== '' ? $linkCustomer : translate('Customer');
                                            } else {
                                                $linkParty = 'none';
                                                $linkPartyName = '';
                                            }
                                        @endphp
                                        <button type="button"
                                                class="btn btn--primary calls-link-lead-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#call-link-lead-modal"
                                                data-phone="{{ $call->customer_phone }}"
                                                data-party="{{ $linkParty }}"
                                                data-party-name="{{ $linkPartyName }}"
                                                data-options-url="{{ route('admin.calls.link-options', $call) }}"
                                                data-lead-action="{{ route('admin.calls.link', $call) }}"
                                                data-booking-action="{{ route('admin.calls.booking', $call) }}">
                                            {{ translate('Link_call') }}
                                        </button>
                                        @endif
                                    </dd>

                                <dt class="col-sm-4">{{ translate('Call_id') }}</dt>
                                <dd class="col-sm-8"><code>{{ $call->cdr_uuid }}</code></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="h5">{{ translate('Recording') }}</h3>
                            @if($call->hasRecording())
                                <audio controls preload="none" class="w-100" src="{{ route('admin.calls.recording', $call) }}">
                                    <source src="{{ route('admin.calls.recording', $call) }}" type="{{ $call->recording_mime ?: 'audio/mpeg' }}">
                                </audio>
                            @elseif($call->recording_url)
                                <p class="text-muted">{{ $call->recording_error ?: translate('Recording_is_not_saved_yet') }}</p>
                                <form method="post" action="{{ route('admin.calls.recording.download', $call) }}">
                                    @csrf
                                    <button type="submit" class="btn btn--primary">{{ translate('Download_recording') }}</button>
                                </form>
                            @else
                                <p class="text-muted mb-0">{{ translate('No_recording') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h3 class="h5">{{ translate('AI_summary') }}</h3>
                            @if($call->summary_text)
                                <p class="mb-0" style="white-space: pre-wrap;">{{ $call->summary_text }}</p>
                            @else
                                <p class="text-muted mb-0">{{ translate('No_summary_yet') }}</p>
                            @endif
                            @if(is_array($call->summary_insights) && $call->summary_insights !== [])
                                <ul class="mt-3 mb-0">
                                    @foreach($call->summary_insights as $insight)
                                        <li>{{ is_scalar($insight) ? $insight : json_encode($insight) }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
