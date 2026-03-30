@php
    /**
     * @var list<array{id: string, name: string, children: list}> $nodes
     * @var int $level
     * @var list<string|int> $selectedZoneIds
     */
    $level = $level ?? 0;
    $selectedStr = array_map('strval', $selectedZoneIds ?? []);
@endphp

@foreach($nodes as $node)
    @php($nodeId = (string) ($node['id'] ?? ''))
    @if(! empty($node['children']))
        <div class="service-zone-price-tree-item border-bottom border-light" style="padding-left: {{ $level * 0.85 }}rem;">
            <div class="d-flex align-items-center gap-2 py-1">
                <button
                    type="button"
                    class="service-zone-price-tree-toggle btn btn-light border-0 flex-shrink-0 rounded-0 py-2 px-0 d-flex align-items-center justify-content-center"
                    style="width: 34px; background: #f8f9fa;"
                    aria-expanded="false">
                    <span class="material-icons service-zone-price-chevron text-muted"
                          style="font-size: 1.05rem; border: 1px solid rgba(0,0,0,.125); border-radius: 4px; width: 22px; height: 22px; display:flex; align-items:center; justify-content:center;">add</span>
                </button>

                <div class="form-check py-0 mb-0" style="width: 26px;">
                    <input
                        type="checkbox"
                        class="form-check-input service-zone-price-node-cb"
                        data-zone-id="{{ $nodeId }}"
                        value="{{ $nodeId }}"
                        {{ in_array($nodeId, $selectedStr, true) ? 'checked' : '' }}>
                </div>

                <label class="service-zone-price-node-label fw-medium text-muted mb-0" style="flex: 1 1 auto;">
                    {{ $node['name'] }}
                </label>

                <input
                    type="number"
                    min="0"
                    step="any"
                    class="form-control form-control-sm service-zone-price-input"
                    data-zone-id="{{ $nodeId }}"
                    value=""
                    style="width: 120px;">
            </div>

            <div class="service-zone-price-tree-children d-none border-top border-light" style="background: rgba(0,0,0,.02);">
                <div class="py-2 px-2">
                    @include('servicemanagement::admin.partials._service-zone-price-tree-branch', [
                        'nodes' => $node['children'],
                        'level' => $level + 1,
                        'selectedZoneIds' => $selectedZoneIds,
                    ])
                </div>
            </div>
        </div>
    @else
        <div class="service-zone-price-tree-item border-bottom border-light" style="padding-left: {{ $level * 0.85 }}rem;">
            <div class="d-flex align-items-center gap-2 py-1">
                {{-- placeholder for alignment --}}
                <div style="width: 34px; flex-shrink: 0;"></div>

                <div class="form-check py-0 mb-0" style="width: 26px;">
                    <input
                        type="checkbox"
                        class="form-check-input service-zone-price-node-cb"
                        data-zone-id="{{ $nodeId }}"
                        value="{{ $nodeId }}"
                        {{ in_array($nodeId, $selectedStr, true) ? 'checked' : '' }}>
                </div>

                <label class="service-zone-price-node-label text-muted mb-0" style="flex: 1 1 auto;">
                    {{ $node['name'] }}
                </label>

                <input
                    type="number"
                    min="0"
                    step="any"
                    class="form-control form-control-sm service-zone-price-input"
                    data-zone-id="{{ $nodeId }}"
                    value=""
                    style="width: 120px;">
            </div>
        </div>
    @endif
@endforeach

