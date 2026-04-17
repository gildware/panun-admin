@extends('adminmodule::layouts.master')

@php
    $waMktCh = 'whatsapp';
@endphp

@section('title', translate('Templates'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <h2 class="page-title">{{ translate('WhatsApp_Marketing') }} — {{ translate('Templates') }}</h2>
                @can('whatsapp_marketing_template_update')
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ config('whatsappmodule.meta_message_templates_url') }}"
                           target="_blank" rel="noopener noreferrer" class="btn btn--primary">
                            <span class="material-icons">open_in_new</span>
                            {{ translate('Create_Template') }}
                        </a>
                        <form action="{{ route('admin.whatsapp.marketing.templates.sync', ['channel' => $waMktCh]) }}" method="post" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn--secondary">
                                <span class="material-icons">sync</span>
                                {{ translate('Sync_Templates') }}
                            </button>
                        </form>
                    </div>
                @endcan
            </div>

            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-2">{{ translate('Templates_list_hint') }}</p>
                    <p class="text-muted small mb-3">{{ translate('Template_media_meta_hint') }}</p>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('name') }}</th>
                                <th>{{ translate('category') }}</th>
                                <th>{{ translate('language') }}</th>
                                <th>{{ translate('preview') }}</th>
                                <th>{{ translate('status') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($templates as $key => $tpl)
                                @php $waMktPreviewCollapseId = 'wa-mkt-tpl-preview-' . $tpl->id; @endphp
                                <tr>
                                    <td>{{ $key + $templates->firstItem() }}</td>
                                    <td class="fw-medium">{{ $tpl->name }}</td>
                                    <td>{{ $tpl->category ?? '—' }}</td>
                                    <td><code>{{ $tpl->language }}</code></td>
                                    <td style="max-width: 420px;">
                                        <small class="text-break d-block">{{ \Illuminate\Support\Str::limit($tpl->preview_text ?? '', 200) }}</small>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary mt-2 d-inline-flex align-items-center gap-1"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $waMktPreviewCollapseId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $waMktPreviewCollapseId }}">
                                            <span class="material-icons" style="font-size: 1rem;">expand_more</span>
                                            {{ translate('Open_preview') }}
                                        </button>
                                    </td>
                                    <td>
                                        @php
                                            $st = strtoupper((string) $tpl->status);
                                            $badgeClass = match ($st) {
                                                'APPROVED' => 'bg-success',
                                                'PENDING' => 'bg-warning text-dark',
                                                'REJECTED', 'DISABLED' => 'bg-danger',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $tpl->status }}</span>
                                    </td>
                                </tr>
                                <tr class="p-0 border-0">
                                    <td colspan="6" class="p-0 border-0">
                                        <div class="collapse wa-mkt-tpl-preview-collapse"
                                             id="{{ $waMktPreviewCollapseId }}"
                                             data-preview-url="{{ route('admin.whatsapp.marketing.templates.preview', ['channel' => $waMktCh, 'template' => $tpl]) }}">
                                            <div class="px-3 py-3 bg-body-secondary border-bottom">
                                                <div class="wa-mkt-tpl-preview-host"></div>
                                            </div>
                                        </div>
                                    </td>
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
                        {!! $templates->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict';
        document.querySelectorAll('.wa-mkt-tpl-preview-collapse').forEach(function (collapseEl) {
            collapseEl.addEventListener('shown.bs.collapse', function () {
                var url = collapseEl.getAttribute('data-preview-url');
                var host = collapseEl.querySelector('.wa-mkt-tpl-preview-host');
                if (!url || !host || host.dataset.loaded === '1') {
                    return;
                }
                host.innerHTML = '<p class="text-muted text-center py-4 mb-0">{{ translate('Loading...') }}</p>';
                fetch(url, {
                    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    credentials: 'same-origin'
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (d) {
                        host.innerHTML = d.html || '';
                        host.dataset.loaded = '1';
                    })
                    .catch(function () {
                        host.innerHTML = '<p class="text-danger mb-0">{{ translate('Something_went_wrong') }}</p>';
                    });
            });
        });
    </script>
@endpush
