<div class="alert alert-light border mb-3 small">
    <div class="text-muted text-uppercase fs-12 mb-1">{{ __('whatsapp_ai.support_availability_title') }}</div>
    <div class="mb-1"><strong>{schedule}</strong> — {{ $placeholderResolved['schedule'] }}</div>
    <div><strong>{phone}</strong> — {{ $placeholderResolved['phone'] !== '' ? $placeholderResolved['phone'] : '—' }}</div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-body border-bottom py-3">
        <strong>{{ __('whatsapp_ai.business_config_placeholders_section') }}</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('whatsapp_ai.business_config_table_token') }}</th>
                        <th>{{ __('whatsapp_ai.business_config_table_meaning') }}</th>
                        <th>{{ __('whatsapp_ai.business_config_table_default') }}</th>
                        <th>{{ __('whatsapp_ai.business_config_table_overridden') }}</th>
                        <th class="pe-4">{{ __('whatsapp_ai.business_config_table_effective') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($businessConfigRows as $bcRow)
                        <tr>
                            <td class="ps-4 font-monospace">@if($bcRow['token'] === '—')<span class="text-muted">—</span>@else<code>{{ $bcRow['token'] }}</code>@endif</td>
                            <td>{{ __($bcRow['meaning_key']) }}</td>
                            <td class="text-break">{{ \Illuminate\Support\Str::limit($bcRow['default_value'], 100) }}</td>
                            <td>
                                @if($bcRow['is_overridden'])
                                    <span class="badge bg-info text-dark">{{ __('whatsapp_ai.business_config_badge_overridden') }}</span>
                                    <div class="small text-break">{{ \Illuminate\Support\Str::limit($bcRow['override_display'], 80) }}</div>
                                @else
                                    <span class="badge bg-secondary">{{ __('whatsapp_ai.business_config_badge_auto') }}</span>
                                @endif
                            </td>
                            <td class="text-break pe-4">{{ \Illuminate\Support\Str::limit($bcRow['effective_value'], 100) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="small text-muted px-4 py-3 mb-0 border-top">{{ __('whatsapp_ai.placeholders_ai_hint') }}</p>
    </div>
</div>
