@extends('adminmodule::layouts.master')

@section('title', translate('Templates'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                <h2 class="page-title">{{ translate('WhatsApp_Marketing') }} — {{ translate('Templates') }}</h2>
                @can('whatsapp_marketing_template_update')
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.whatsapp.marketing.templates.create') }}" class="btn btn--primary">
                            <span class="material-icons">add</span>
                            {{ translate('Create_Template') }}
                        </a>
                        <form action="{{ route('admin.whatsapp.marketing.templates.sync') }}" method="post" class="m-0">
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
                                <th>{{ translate('action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($templates as $key => $tpl)
                                <tr>
                                    <td>{{ $key + $templates->firstItem() }}</td>
                                    <td class="fw-medium">{{ $tpl->name }}</td>
                                    <td>{{ $tpl->category ?? '—' }}</td>
                                    <td><code>{{ $tpl->language }}</code></td>
                                    <td style="max-width: 420px;">
                                        <small class="text-break">{{ \Illuminate\Support\Str::limit($tpl->preview_text ?? '', 200) }}</small>
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
                                    <td>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary wa-marketing-tpl-preview"
                                                data-preview-url="{{ route('admin.whatsapp.marketing.templates.preview', $tpl) }}">
                                            {{ translate('Open_preview') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">{{ translate('no_data_found') }}</td>
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

    <div class="modal fade" id="waMarketingTplPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="waMarketingTplPreviewBody"></div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict';
        document.querySelectorAll('.wa-marketing-tpl-preview').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-preview-url');
                var bodyEl = document.getElementById('waMarketingTplPreviewBody');
                var modalEl = document.getElementById('waMarketingTplPreviewModal');
                if (!url || !bodyEl || !modalEl) {
                    return;
                }
                bodyEl.innerHTML = '<p class="text-muted text-center py-4 mb-0">{{ translate('Loading...') }}</p>';
                var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                fetch(url, {
                    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    credentials: 'same-origin'
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (d) {
                        bodyEl.innerHTML = d.html || '';
                    })
                    .catch(function () {
                        bodyEl.innerHTML = '<p class="text-danger mb-0">{{ translate('Something_went_wrong') }}</p>';
                    });
            });
        });
    </script>
@endpush
