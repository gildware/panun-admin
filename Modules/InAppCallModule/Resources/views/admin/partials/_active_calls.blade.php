@if(empty($activeCalls))
    <div class="text-center text-muted py-4">
        <span class="material-icons d-block mb-2" style="font-size: 36px; opacity: .35;">phone_disabled</span>
        {{ translate('No_active_calls_right_now') }}
    </div>
@else
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="align-middle text-nowrap">
            <tr>
                <th>{{ translate('Caller') }}</th>
                <th>{{ translate('Callee') }}</th>
                <th>{{ translate('status') }}</th>
                <th>{{ translate('Connection') }}</th>
                <th>{{ translate('Elapsed') }}</th>
                <th>{{ translate('Started_At') }}</th>
                <th>{{ translate('Booking_ID') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($activeCalls as $call)
                <tr data-call-status="{{ $call['status'] }}">
                    <td>@include('inappcallmodule::admin.partials._user_cell', ['user' => $call['caller']])</td>
                    <td>@include('inappcallmodule::admin.partials._user_cell', ['user' => $call['callee']])</td>
                    <td>
                        <span class="badge badge-{{ $call['status_badge'] }}">
                            @if($call['status'] === 'ringing')
                                <span class="in-app-call-live-dot me-1"></span>
                            @endif
                            {{ $call['status_label'] }}
                        </span>
                    </td>
                    <td class="text-muted">{{ $call['connection_label'] }}</td>
                    <td class="text-nowrap" data-elapsed-seconds="{{ $call['elapsed_seconds'] }}">
                        {{ $call['elapsed_label'] }}
                    </td>
                    <td class="text-nowrap text-muted">{{ $call['started_at'] }}</td>
                    <td>
                        @if($call['booking_url'])
                            <a href="{{ $call['booking_url'] }}" class="c1">#{{ $call['reference_id'] }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
