@extends('adminmodule::layouts.master')

@php($waMktCh = 'whatsapp')

@section('title', translate('campaigns'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <h2 class="page-title">{{ translate('WhatsApp_Marketing') }} — {{ translate('campaigns') }}</h2>
                @can('whatsapp_marketing_bulk_view')
                    <a href="{{ route('admin.whatsapp.marketing.bulk.create', ['channel' => $waMktCh]) }}" class="btn btn--primary">
                        <span class="material-icons">add</span>
                        {{ translate('Send_Bulk_Message') }}
                    </a>
                @endcan
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Campaign_name') }}</th>
                                <th>{{ translate('Templates') }}</th>
                                <th>{{ translate('Audience') }}</th>
                                <th>{{ translate('Total') }}</th>
                                <th>{{ translate('Sent') }}</th>
                                <th>{{ translate('Delivered') }}</th>
                                <th>{{ translate('Read') }}</th>
                                <th>{{ translate('Failed') }}</th>
                                <th>{{ translate('status') }}</th>
                                <th>{{ translate('created_at') }}</th>
                                <th class="text-end">{{ translate('action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($campaigns as $key => $c)
                                <tr>
                                    <td>{{ $key + $campaigns->firstItem() }}</td>
                                    <td class="fw-medium">{{ $c->name }}</td>
                                    <td>{{ $c->template?->name }} <small class="text-muted">({{ $c->template?->language }})</small></td>
                                    <td>{{ \Modules\WhatsAppModule\Entities\WhatsAppMarketingCampaign::audienceLabel($c->audience_type) }}</td>
                                    <td>{{ $c->total_recipients_count }}</td>
                                    <td>{{ $c->sent_count }}</td>
                                    <td>{{ $c->delivered_count }}</td>
                                    <td>{{ $c->read_count }}</td>
                                    <td>{{ $c->failed_count }}</td>
                                    <td><span class="badge bg-secondary text-uppercase">{{ $c->status }}</span></td>
                                    <td>{{ $c->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="text-end">
                                        @can('whatsapp_marketing_campaign_view')
                                            <a href="{{ route('admin.whatsapp.marketing.campaigns.show', ['channel' => $waMktCh, 'id' => $c->id]) }}"
                                               class="btn btn-sm btn--primary">{{ translate('View') }}</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">{{ translate('no_data_found') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {!! $campaigns->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
