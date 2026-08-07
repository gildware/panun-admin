@if($tab === 'daily' && ! empty($detail))
    <p style="font-size:12px;color:#64748b;margin-bottom:12px">{{ translate('Daily_report_for') }} <strong>{{ $dateLabel }}</strong></p>
    @if(! empty($metricColumns))
        <div class="metric-grid" style="margin-bottom:14px">
            @foreach($metricColumns as $column)
                <div class="metric-card">
                    <div class="mc-top">
                        <div><div class="mc-lbl">{{ $column['label'] }}</div><div class="mc-val">{{ (int) ($detail['totals'][$column['key']] ?? 0) }}</div></div>
                        <div class="mc-icon">@include('adminmodule::partials._material-icon', ['name' => 'event'])</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <div class="chart-card">
        <div class="chart-card-head"><h3>{{ translate('Bookings_Created') }}</h3></div>
        <div class="chart-card-body" style="padding:0">
            @include('adminmodule::admin.report.partials.daily-employee-detail-sections', [
                'sectionDefs' => $sectionDefs,
                'sections' => $detail['sections'],
            ])
        </div>
    </div>
@else
    <p style="font-size:12px;color:#64748b;margin-bottom:12px">{{ translate('Monthly_report_for') }} <strong>{{ $periodLabel }}</strong></p>
    @if(! empty($monthly['stats']))
        <div class="metric-grid" style="margin-bottom:14px">
            @foreach($monthly['stats'] as $stat)
                <div class="metric-card">
                    <div class="mc-top">
                        <div><div class="mc-lbl">{{ $stat['label'] ?? '' }}</div><div class="mc-val">{{ $stat['value'] ?? 0 }}</div></div>
                        <div class="mc-icon">@include('adminmodule::partials._material-icon', ['name' => $stat['icon'] ?? 'insights'])</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <div class="chart-card">
        <div class="chart-card-head"><h3>{{ translate('Daily_activity_breakdown') }}</h3></div>
        <div class="chart-card-body" style="padding:0">
            <div class="data-table-wrap" style="max-height:none;border:none;border-radius:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ translate('Date') }}</th>
                            @foreach($metricColumns as $column)
                                <th title="{{ $column['label'] }}">{{ $column['short'] }}</th>
                            @endforeach
                            <th>{{ translate('Online') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyRows as $row)
                            <tr>
                                <td><a href="{{ route('admin.my-progress', array_merge($employeeQuery ?? [], ['tab' => 'daily', 'date' => $row['date'], 'section' => 'reports'])) }}" style="color:#43466e;font-weight:700;text-decoration:none">{{ $row['date_label'] }}</a></td>
                                @foreach($metricColumns as $column)
                                    @php $val = (int) ($row[$column['key']] ?? 0); @endphp
                                    <td style="{{ $val === 0 ? 'color:#cbd5e1' : '' }}">{{ $val }}</td>
                                @endforeach
                                <td>{{ $row['online_hours'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($metricColumns) + 2 }}" style="text-align:center;color:#64748b;padding:24px">{{ translate('No_activity_in_period') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
