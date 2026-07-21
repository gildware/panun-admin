<div id="day-detail-sections" class="row g-3">
    @foreach($sectionDefs as $section)
        @php
            $rows = $sections[$section['key']] ?? [];
            $count = is_countable($rows) ? count($rows) : 0;
        @endphp
        <div class="col-lg-6">
            <div class="card day-detail-section">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $section['title'] }}</h5>
                    <span class="badge bg-secondary">{{ $count }}</span>
                </div>
                <div class="card-body p-0">
                    @if($count > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle day-detail-table">
                                <thead>
                                    <tr>
                                        @foreach($section['columns'] as $col)
                                            <th>{{ $col['label'] }}</th>
                                        @endforeach
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $row)
                                        <tr>
                                            @foreach($section['columns'] as $col)
                                                <td>{{ $row[$col['key']] ?? '—' }}</td>
                                            @endforeach
                                            <td class="text-end pe-3">
                                                @if(!empty($row['url']))
                                                    <a href="{{ $row['url'] }}" class="btn btn-sm btn--primary" target="_blank" rel="noopener">
                                                        {{ translate('View') }}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="day-detail-empty">{{ translate('No_records_in_section') }}</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
