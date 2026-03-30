@php
    /** @var list<array{id: string, name: string, children: list}> $nodes */
    /** @var int $level */
    /** @var list<string|int> $selectedZoneIds */
    $level = $level ?? 0;
    $selectedStr = array_map('strval', $selectedZoneIds ?? []);
@endphp
@foreach($nodes as $node)
    @if(! empty($node['children']))
        <div class="provider-zone-tree-item border-bottom border-light" style="padding-left: {{ $level * 0.85 }}rem;">
            <div class="d-flex align-items-center gap-2">
                <button
                    type="button"
                    class="provider-zone-tree-toggle btn btn-light border-0 flex-shrink-0 rounded-0 py-2 px-0 d-flex align-items-center justify-content-center"
                    style="width: 34px; background: #f8f9fa;"
                    aria-expanded="false">
                    <span class="material-icons provider-zone-chevron text-muted"
                          style="font-size: 1.05rem; border: 1px solid rgba(0,0,0,.125); border-radius: 4px; width: 22px; height: 22px; display:flex; align-items:center; justify-content:center;">add</span>
                </button>

                <div class="form-check py-2 mb-0 flex-shrink-0" style="width: 28px;">
                    <input
                        type="checkbox"
                        class="form-check-input provider-zone-parent-cb"
                        id="zone_cb_{{ $node['id'] }}"
                        value="{{ $node['id'] }}"
                        style="margin-left: 0 !important;">
                </div>

                <label class="form-check-label provider-zone-node-label fw-medium text-muted mb-0"
                       for="zone_cb_{{ $node['id'] }}" style="flex: 1 1 auto; margin-left: 0.35rem;">{{ $node['name'] }}</label>
            </div>
            <div class="provider-zone-tree-children d-none border-top border-light" style="background: rgba(0,0,0,.02);">
                <div class="py-2 px-2">
                    @include('providermanagement::admin.provider.partials.provider-zone-tree-branch', [
                        'nodes' => $node['children'],
                        'level' => $level + 1,
                        'selectedZoneIds' => $selectedZoneIds,
                    ])
                </div>
            </div>
        </div>
    @else
        <div class="provider-zone-tree-item border-bottom border-light" style="padding-left: {{ $level * 0.85 }}rem;">
            <div class="d-flex align-items-center gap-2">
                {{-- Placeholder to keep leaf alignment with parent toggle column --}}
                <div style="width: 34px; flex-shrink: 0;"></div>

                <div class="form-check py-2 mb-0 flex-shrink-0" style="width: 28px;">
                    <input
                        type="checkbox"
                        name="zone_ids[]"
                        value="{{ $node['id'] }}"
                        class="form-check-input provider-zone-leaf-cb"
                        id="zone_cb_{{ $node['id'] }}"
                        {{ in_array((string) $node['id'], $selectedStr, true) ? 'checked' : '' }}
                        style="margin-left: 0 !important;">
                </div>

                <label class="form-check-label provider-zone-node-label text-muted mb-0"
                       for="zone_cb_{{ $node['id'] }}" style="flex: 1 1 auto; margin-left: 0.35rem;">{{ $node['name'] }}</label>
            </div>
        </div>
    @endif
@endforeach
