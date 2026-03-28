@extends('adminmodule::layouts.new-master')

@section('title', translate('Data_Transfer'))

@push('css_or_js')
    <style>
        .dt-dropzone-hit {
            min-height: 200px;
            transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
        }
        .dt-dropzone.is-dragover .dt-dropzone-hit {
            border-color: var(--bs-primary) !important;
            background-color: rgba(var(--bs-primary-rgb), 0.06) !important;
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.15);
        }
        .dt-dropzone.is-loading .dt-dropzone-hit {
            pointer-events: none;
            opacity: 0.65;
        }
        .dt-file-input {
            cursor: pointer;
            z-index: 2;
        }
        .import-catalog-tree ul {
            list-style: none;
            padding-left: 1rem;
            margin: 0.25rem 0 0.5rem;
            border-left: 1px dashed rgba(0, 0, 0, 0.12);
        }
        [data-bs-theme="dark"] .import-catalog-tree ul {
            border-left-color: rgba(255, 255, 255, 0.15);
        }
        .import-catalog-tree > ul {
            padding-left: 0;
            border-left: 0;
            margin-top: 0;
        }
        .import-catalog-tree .tree-node-cat {
            font-weight: 600;
        }
        .import-catalog-tree .tree-node-sub {
            font-weight: 600;
            color: var(--bs-body-color);
        }
        .import-catalog-tree .tree-node-svc {
            font-weight: 500;
        }
        .import-catalog-tree .tree-node-var {
            font-size: 0.875rem;
            color: var(--bs-secondary);
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-start gap-3 mb-3">
                        <div>
                            <h2 class="page-title mb-1">{{ translate('Data_Transfer') }}</h2>
                        </div>
                    </div>

                    @if (count($domains) === 0)
                        <div class="alert alert-warning">{{ translate('no_access') ?? 'No permitted data domains.' }}</div>
                    @else
                        @if (count($domains) > 1)
                            <ul class="nav nav--tabs mb-3" id="dataTransferTabs" role="tablist">
                                @foreach ($domains as $d)
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                                           id="dt-tab-{{ $d }}"
                                           data-bs-toggle="tab"
                                           data-bs-target="#dt-pane-{{ $d }}"
                                           role="tab"
                                           aria-controls="dt-pane-{{ $d }}"
                                           aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                           href="#dt-pane-{{ $d }}">
                                            {{ $domainLabels[$d] ?? ucfirst($d) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="tab-content" id="dataTransferTabContent">
                            @foreach ($domains as $d)
                                <div class="tab-pane fade @if (count($domains) > 1) {{ $loop->first ? 'show active' : '' }} @else show active @endif"
                                     id="dt-pane-{{ $d }}"
                                     role="tabpanel"
                                     @if (count($domains) > 1) aria-labelledby="dt-tab-{{ $d }}" @else aria-label="{{ $domainLabels[$d] ?? ucfirst($d) }}" @endif
                                     tabindex="0">

                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                        <div>
                                            <p class="mb-0 text-muted fz-13">{{ translate('Export_all_service_catalog') }}</p>
                                        </div>
                                        <a class="btn btn--primary d-inline-flex align-items-center gap-2"
                                           href="{{ route('admin.data-transfer.export', ['domain' => $d]) }}">
                                            <span class="material-icons fz-20">download</span>
                                            {{ translate('Export') }}
                                        </a>
                                    </div>

                                    <div class="dt-dropzone card border-0 shadow-sm mb-4" data-domain="{{ $d }}">
                                        <div class="dt-dropzone-hit position-relative rounded-3 border border-dashed p-4 p-lg-5 text-center mx-3 mx-md-4 mt-4 mb-0 bg-body-secondary bg-opacity-25">
                                            <input type="file"
                                                   class="dt-file-input position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                                   accept=".json,application/json,text/plain"
                                                   data-domain="{{ $d }}"
                                                   aria-label="{{ translate('Drop_JSON_here') }}">
                                            <div class="position-relative" style="z-index: 0; pointer-events: none;">
                                                <img src="{{ asset('assets/admin-module/img/drop-upload-cloud.png') }}"
                                                     alt="" width="64" height="64" class="mb-3 opacity-90">
                                                <h5 class="fw-normal mb-2">
                                                    <span class="text-primary fw-semibold">{{ translate('Drop_JSON_here') }}</span>
                                                </h5>
                                                <p class="text-muted fz-12 mb-1">{{ translate('or_browse_file') }}</p>
                                                <p class="text-muted fz-11 mb-0">{{ translate('JSON_max_size') }}</p>
                                            </div>
                                        </div>
                                        <div class="dt-file-strip card-body border-top d-none py-3">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                                <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                                                    <span class="material-icons text-primary flex-shrink-0">description</span>
                                                    <div class="min-w-0">
                                                        <div class="dt-file-name text-truncate fw-semibold"></div>
                                                        <div class="dt-file-size text-muted fz-12"></div>
                                                    </div>
                                                </div>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary dt-remove-file flex-shrink-0">
                                                    {{ translate('Remove_file') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="dt-analyzing d-none text-center py-4 border-top">
                                            <div class="spinner-border text-primary mb-2" role="status"
                                                 aria-hidden="true"></div>
                                            <div class="text-muted small">{{ translate('Analyzing_file') }}</div>
                                        </div>
                                    </div>

                                    <div class="dt-result d-none mb-4" data-domain="{{ $d }}">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header border-bottom bg-light py-3 d-flex align-items-center gap-2">
                                                <span class="material-icons text-primary fz-20">account_tree</span>
                                                <span class="fw-semibold">{{ translate('Import_preview_tree') }}</span>
                                            </div>
                                            <div class="card-body">
                                                <p class="small text-muted mb-3 dt-upload-line d-none"></p>
                                                <div class="dt-service-tree"
                                                     style="max-height: 520px; overflow: auto;"></div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end mt-4">
                                            <button type="button"
                                                    class="btn btn--primary d-inline-flex align-items-center gap-2 dt-btn-confirm"
                                                    disabled
                                                    data-domain="{{ $d }}">
                                                <span class="material-icons fz-20">check_circle</span>
                                                {{ translate('Confirm_import_to_database') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const previewUrl = @json(route('admin.data-transfer.preview'));
            const importUrl = @json(route('admin.data-transfer.import'));
            const confirmMsg = @json(translate('Data_transfer_import_confirm'));
            const treeSyntheticBadge = @json(translate('Import_tree_synthetic_badge'));
            const treeEmptyMsg = @json(translate('Import_tree_empty'));

            function escapeHtml(s) {
                if (s == null) return '';
                const d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function formatBytes(n) {
                if (n == null || isNaN(n)) return '';
                const u = ['B', 'KB', 'MB', 'GB'];
                let i = 0;
                let v = n;
                while (v >= 1024 && i < u.length - 1) {
                    v /= 1024;
                    i++;
                }
                return (i === 0 ? v : v.toFixed(v >= 10 || i === 0 ? 0 : 1)) + ' ' + u[i];
            }

            function showToast(type, message) {
                if (typeof toastr !== 'undefined') {
                    toastr[type](message);
                } else {
                    alert(message);
                }
            }

            function renderServiceTree(container, tree) {
                if (!container) return;

                function renderVariations(vars) {
                    if (!vars || !vars.length) {
                        return '<ul class="mb-0"><li class="tree-node-var fst-italic">' + @json(translate('no_data_found')) + '</li></ul>';
                    }
                    let h = '<ul class="mb-0">';
                    vars.forEach(function (v) {
                        h += '<li class="tree-node-var mb-1">';
                        const lbl = v.label ? escapeHtml(v.label) : escapeHtml(@json(translate('Catalog_variation')));
                        h += lbl + ' — <span class="text-dark fw-medium">' + escapeHtml(String(v.price ?? '')) + '</span>';
                        if (v.zone_label) {
                            h += ' <span class="text-muted">(' + escapeHtml(v.zone_label) + ')</span>';
                        }
                        h += '</li>';
                    });
                    return h + '</ul>';
                }

                function renderNodes(nodes) {
                    if (!nodes || !nodes.length) {
                        return '';
                    }
                    let h = '<ul>';
                    nodes.forEach(function (n) {
                        if (n.type === 'category') {
                            h += '<li class="mb-2"><span class="tree-node-cat text-primary">' + escapeHtml(n.name) + '</span>';
                            h += renderNodes(n.children || []);
                            h += '</li>';
                        } else if (n.type === 'subcategory') {
                            h += '<li class="mb-2"><span class="tree-node-sub">' + escapeHtml(n.name) + '</span>';
                            if (n.synthetic) {
                                h += ' <span class="badge rounded-pill bg-secondary bg-opacity-15 text-secondary fz-11 align-middle">'
                                    + escapeHtml(treeSyntheticBadge) + '</span>';
                            }
                            h += renderNodes(n.children || []);
                            h += '</li>';
                        } else if (n.type === 'service') {
                            h += '<li class="mb-1"><span class="tree-node-svc">' + escapeHtml(n.name) + '</span>';
                            h += renderVariations(n.children || []);
                            h += '</li>';
                        }
                    });
                    return h + '</ul>';
                }

                if (!tree || !tree.length) {
                    container.innerHTML = '<p class="text-muted small mb-0">' + escapeHtml(treeEmptyMsg) + '</p>';
                    return;
                }
                container.innerHTML = '<div class="import-catalog-tree">' + renderNodes(tree) + '</div>';
            }

            async function postFile(domain, url, file) {
                const fd = new FormData();
                fd.append('domain', domain);
                fd.append('file', file);
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const body = await res.json().catch(function () {
                    return {};
                });
                if (!res.ok) {
                    showToast('error', body.message || 'Request failed');
                    return null;
                }
                return body;
            }

            function setLoading(zone, on) {
                zone.classList.toggle('is-loading', !!on);
                zone.querySelector('.dt-analyzing')?.classList.toggle('d-none', !on);
            }

            function resetDomain(domain) {
                const zone = document.querySelector('.dt-dropzone[data-domain="' + domain + '"]');
                const input = zone?.querySelector('.dt-file-input');
                const strip = zone?.querySelector('.dt-file-strip');
                const result = document.querySelector('.dt-result[data-domain="' + domain + '"]');
                const confirmBtn = document.querySelector('.dt-btn-confirm[data-domain="' + domain + '"]');
                if (input) input.value = '';
                strip?.classList.add('d-none');
                result?.classList.add('d-none');
                if (confirmBtn) confirmBtn.disabled = true;
                setLoading(zone, false);
            }

            async function analyzeFile(domain, file) {
                const zone = document.querySelector('.dt-dropzone[data-domain="' + domain + '"]');
                const strip = zone?.querySelector('.dt-file-strip');
                const result = document.querySelector('.dt-result[data-domain="' + domain + '"]');
                const confirmBtn = document.querySelector('.dt-btn-confirm[data-domain="' + domain + '"]');

                if (!file) return;
                const name = file.name || '';
                const lower = name.toLowerCase();
                if (!lower.endsWith('.json') && file.type !== 'application/json' && file.type !== 'text/plain') {
                    showToast('warning', 'Please choose a .json export file.');
                    return;
                }

                strip?.querySelector('.dt-file-name') && (strip.querySelector('.dt-file-name').textContent = name);
                strip?.querySelector('.dt-file-size') && (strip.querySelector('.dt-file-size').textContent = formatBytes(file.size));
                strip?.classList.remove('d-none');

                result?.classList.add('d-none');
                if (confirmBtn) confirmBtn.disabled = true;

                setLoading(zone, true);
                const data = await postFile(domain, previewUrl, file);
                setLoading(zone, false);

                if (!data?.preview) return;

                result?.classList.remove('d-none');
                const uploadLine = result.querySelector('.dt-upload-line');
                if (uploadLine) {
                    if (data.upload?.name) {
                        uploadLine.innerHTML = '<strong>File:</strong> ' + escapeHtml(data.upload.name)
                            + (data.upload.size != null
                                ? ' <span class="text-muted">(' + formatBytes(data.upload.size) + ')</span>'
                                : '');
                        uploadLine.classList.remove('d-none');
                    } else {
                        uploadLine.innerHTML = '';
                        uploadLine.classList.add('d-none');
                    }
                }
                renderServiceTree(result.querySelector('.dt-service-tree'), data.preview.tree || []);
                if (confirmBtn) confirmBtn.disabled = false;
                showToast('success', @json(translate('Preview_ready')));
            }

            document.querySelectorAll('.dt-dropzone').forEach(function (zone) {
                const domain = zone.getAttribute('data-domain');
                const input = zone.querySelector('.dt-file-input');
                const hit = zone.querySelector('.dt-dropzone-hit');
                let dragDepth = 0;

                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (ev) {
                    hit.addEventListener(ev, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                });
                hit.addEventListener('dragenter', function () {
                    dragDepth++;
                    zone.classList.add('is-dragover');
                });
                hit.addEventListener('dragleave', function () {
                    dragDepth = Math.max(0, dragDepth - 1);
                    if (dragDepth === 0) zone.classList.remove('is-dragover');
                });
                hit.addEventListener('drop', function (e) {
                    dragDepth = 0;
                    zone.classList.remove('is-dragover');
                    const f = e.dataTransfer?.files?.[0];
                    if (!f) return;
                    const dt = new DataTransfer();
                    dt.items.add(f);
                    input.files = dt.files;
                    analyzeFile(domain, f);
                });

                input.addEventListener('change', function () {
                    const f = input.files?.[0];
                    if (f) analyzeFile(domain, f);
                });

                zone.querySelector('.dt-remove-file')?.addEventListener('click', function () {
                    resetDomain(domain);
                });
            });

            document.querySelectorAll('.dt-btn-confirm').forEach(function (btn) {
                btn.addEventListener('click', async function () {
                    const domain = btn.getAttribute('data-domain');
                    const input = document.querySelector('.dt-file-input[data-domain="' + domain + '"]');
                    const file = input?.files?.[0];
                    if (!file) {
                        showToast('warning', 'Choose a file first.');
                        return;
                    }
                    if (!confirm(confirmMsg)) return;

                    btn.disabled = true;
                    const data = await postFile(domain, importUrl, file);
                    btn.disabled = false;

                    if (!data?.result) return;

                    const r = data.result;
                    let msg = @json(translate('Import_completed'));
                    if (r.imported) msg += ' ' + JSON.stringify(r.imported);
                    if (r.warnings?.length) msg += ' — ' + r.warnings.join(' | ');
                    showToast('success', msg);

                    btn.disabled = true;

                    const treeBox = document.querySelector('.dt-result[data-domain="' + domain + '"] .dt-service-tree');
                    if (treeBox) {
                        treeBox.innerHTML = '<div class="alert alert-success fz-12 py-2 mb-3">' + escapeHtml(msg)
                            + '</div><pre class="small mb-0 bg-light p-2 rounded border">'
                            + escapeHtml(JSON.stringify(r, null, 2)) + '</pre>';
                    }
                });
            });
        })();
    </script>
@endpush
