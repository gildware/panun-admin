@extends('adminmodule::layouts.master')

@php
    $waMktCh = 'whatsapp';
@endphp

@section('title', $campaign->name)

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <div>
                    <h2 class="page-title">{{ $campaign->name }}</h2>
                    <p class="text-muted mb-0">{{ translate('Templates') }}: {{ $campaign->template?->name }}
                        ({{ $campaign->template?->language }})</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('whatsapp_marketing_campaign_view')
                        <a href="{{ route('admin.whatsapp.marketing.campaigns.export', ['channel' => $waMktCh, 'id' => $campaign->id]) }}"
                           class="btn btn--secondary btn-sm">
                            <span class="material-icons">file_download</span> {{ translate('Export_CSV') }}
                        </a>
                    @endcan
                    @can('whatsapp_marketing_campaign_update')
                        <form action="{{ route('admin.whatsapp.marketing.campaigns.retry-failed', ['channel' => $waMktCh, 'id' => $campaign->id]) }}"
                              method="post" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn--primary btn-sm">
                                <span class="material-icons">replay</span> {{ translate('Retry_Failed') }}
                            </button>
                        </form>
                    @endcan
                    @can('whatsapp_marketing_bulk_view')
                        <a href="{{ route('admin.whatsapp.marketing.campaigns.duplicate', ['channel' => $waMktCh, 'id' => $campaign->id]) }}"
                           class="btn btn-outline-primary btn-sm">
                            {{ translate('Duplicate_Campaign') }}
                        </a>
                    @endcan
                    <a href="{{ route('admin.whatsapp.marketing.campaigns.index', ['channel' => $waMktCh]) }}"
                       class="btn btn-outline-secondary btn-sm">{{ translate('back') }}</a>
                </div>
            </div>

            <div class="mb-4">
                <ul class="nav nav--tabs nav--tabs__style2 flex-wrap">
                    @php
                        $tabs = [
                            'overview' => translate('Overview'),
                            'sent' => translate('Sent'),
                            'delivered' => translate('Delivered'),
                            'read' => translate('Read'),
                            'failed' => translate('Failed'),
                            'replied' => translate('Replied'),
                        ];
                    @endphp
                    @foreach($tabs as $key => $label)
                        <li class="nav-item">
                            <a href="{{ route('admin.whatsapp.marketing.campaigns.show', ['channel' => $waMktCh, 'id' => $campaign->id, 'tab' => $key, 'search' => request('search')]) }}"
                               class="nav-link {{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($tab === 'overview')
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card h-100"><div class="card-body py-3 text-center">
                            <div class="text-muted small">{{ translate('Total') }}</div>
                            <h4 class="mb-0">{{ $overviewStats['total'] }}</h4>
                        </div></div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card h-100"><div class="card-body py-3 text-center">
                            <div class="text-muted small">{{ translate('Sent') }}</div>
                            <h4 class="mb-0">{{ $overviewStats['sent'] }}</h4>
                        </div></div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card h-100"><div class="card-body py-3 text-center">
                            <div class="text-muted small">{{ translate('Delivered') }}</div>
                            <h4 class="mb-0">{{ $overviewStats['delivered'] }}</h4>
                        </div></div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card h-100"><div class="card-body py-3 text-center">
                            <div class="text-muted small">{{ translate('Read') }}</div>
                            <h4 class="mb-0">{{ $overviewStats['read'] }}</h4>
                        </div></div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card h-100"><div class="card-body py-3 text-center">
                            <div class="text-muted small">{{ translate('Failed') }}</div>
                            <h4 class="mb-0">{{ $overviewStats['failed'] }}</h4>
                        </div></div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card h-100"><div class="card-body py-3 text-center">
                            <div class="text-muted small">{{ translate('Replied') }}</div>
                            <h4 class="mb-0">{{ $overviewStats['replied'] }}</h4>
                        </div></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">{{ translate('Campaign') }}</h5>
                        <dl class="row mb-0">
                            <dt class="col-sm-3">{{ translate('status') }}</dt>
                            <dd class="col-sm-9"><span class="badge bg-secondary">{{ $campaign->status }}</span></dd>
                            <dt class="col-sm-3">{{ translate('Audience') }}</dt>
                            <dd class="col-sm-9">{{ \Modules\WhatsAppModule\Entities\WhatsAppMarketingCampaign::audienceLabel($campaign->audience_type) }}</dd>
                            @if($campaign->category)
                                <dt class="col-sm-3">{{ translate('category') }}</dt>
                                <dd class="col-sm-9">{{ $campaign->category->name }}</dd>
                            @endif
                            <dt class="col-sm-3">{{ translate('Scheduled_at') }}</dt>
                            <dd class="col-sm-9">{{ $campaign->scheduled_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                            <dt class="col-sm-3">{{ translate('created_at') }}</dt>
                            <dd class="col-sm-9">{{ $campaign->created_at?->format('Y-m-d H:i') }}</dd>
                        </dl>
                    </div>
                </div>
            @else
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="get" action="{{ route('admin.whatsapp.marketing.campaigns.show', ['channel' => $waMktCh, 'id' => $campaign->id]) }}"
                              class="search-form search-form_style-two d-flex flex-wrap gap-2 align-items-end">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <div class="form-floating flex-grow-1" style="min-width: 220px;">
                                <input type="search" name="search" class="form-control" id="search_recipient"
                                       value="{{ $search }}" placeholder="{{ translate('Search') }}">
                                <label for="search_recipient">{{ translate('Search') }} ({{ translate('name') }} / {{ translate('phone') }})</label>
                            </div>
                            <button type="submit" class="btn btn--primary">{{ translate('filter') }}</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th>{{ translate('SL') }}</th>
                                    <th>{{ translate('name') }}</th>
                                    <th>{{ translate('phone') }}</th>
                                    <th>{{ translate('status') }}</th>
                                    <th>{{ translate('Sent') }}</th>
                                    <th>{{ translate('failure_reason') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($messages as $key => $m)
                                    <tr>
                                        <td>{{ $key + $messages->firstItem() }}</td>
                                        <td>{{ $m->recipient_name ?? '—' }}</td>
                                        <td><code>{{ $m->phone_e164 }}</code></td>
                                        <td><span class="badge bg-light text-dark">{{ $m->status }}</span></td>
                                        <td>{{ $m->sent_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                        <td class="small text-danger">{{ \Illuminate\Support\Str::limit($m->failure_reason ?? '', 120) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ translate('no_data_found') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end">
                            {!! $messages->links() !!}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
