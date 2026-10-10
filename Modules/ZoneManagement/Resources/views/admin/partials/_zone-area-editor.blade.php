@php
    $zoneAreaNames = old('area_names');
    if (! is_array($zoneAreaNames)) {
        $zoneAreaNames = [];
        if (isset($zone) && $zone->relationLoaded('areas')) {
            $zoneAreaNames = $zone->areas->pluck('name')->filter()->values()->all();
        }
        if ($zoneAreaNames === [] && isset($zone) && filled($zone->areas_encompassed ?? null)) {
            $zoneAreaNames = array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', (string) $zone->areas_encompassed) ?: []
            )));
        }
    }
    $zoneAreaNames = array_values(array_filter($zoneAreaNames, fn ($name) => trim((string) $name) !== ''));
    $zoneAreaViewOnly = $viewOnly ?? false;
    if ($zoneAreaNames === [] && ! $zoneAreaViewOnly) {
        $zoneAreaNames = [''];
    }
@endphp
<label class="input-label d-block mb-1">{{ translate('Area_Encompassed_Mohalla') }}</label>
<div class="zone-area-editor" id="zone-area-editor" @if($zoneAreaViewOnly) data-view-only="1" @endif>
    @forelse($zoneAreaNames as $areaName)
        <div class="zone-area-row">
            <input type="text"
                   name="area_names[]"
                   class="form-control theme-input-style"
                   maxlength="255"
                   value="{{ $areaName }}"
                   placeholder="{{ translate('Area') }}"
                   @readonly($zoneAreaViewOnly)>
            @unless($zoneAreaViewOnly)
                <button type="button" class="zone-area-remove" data-zone-area-remove aria-label="{{ translate('Remove') }}">
                    <span class="material-icons" aria-hidden="true">close</span>
                </button>
            @endunless
        </div>
    @empty
        <p class="text-muted mb-0">—</p>
    @endforelse
</div>
@unless($zoneAreaViewOnly)
    <button type="button" class="btn btn-sm btn--light-primary mt-2" data-zone-area-add="zone-area-editor">
        <span class="material-icons align-middle" aria-hidden="true">add</span>
        {{ translate('Add_area') }}
    </button>
    <small class="text-muted d-block mt-1">{{ translate('Each_area_is_saved_separately') }}</small>
@endunless

@once
    @push('script')
        <script>
            (function () {
                if (window.__zoneAreaEditorBound) {
                    return;
                }
                window.__zoneAreaEditorBound = true;

                function areaRowHtml() {
                    return '<input type="text" name="area_names[]" class="form-control theme-input-style" maxlength="255" placeholder="{{ translate('Area') }}">'
                        + '<button type="button" class="zone-area-remove" data-zone-area-remove aria-label="{{ translate('Remove') }}"><span class="material-icons" aria-hidden="true">close</span></button>';
                }

                document.addEventListener('click', function (event) {
                    var addButton = event.target.closest('[data-zone-area-add]');
                    if (addButton) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        var list = document.getElementById(addButton.getAttribute('data-zone-area-add'));
                        if (!list || list.getAttribute('data-view-only') === '1') {
                            return;
                        }
                        var row = document.createElement('div');
                        row.className = 'zone-area-row';
                        row.innerHTML = areaRowHtml();
                        list.appendChild(row);
                        var input = row.querySelector('input');
                        if (input) {
                            input.focus();
                        }
                        return;
                    }

                    var removeButton = event.target.closest('[data-zone-area-remove]');
                    if (!removeButton) {
                        return;
                    }
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    var lockedList = removeButton.closest('[data-view-only="1"]');
                    if (lockedList) {
                        return;
                    }
                    var areaRow = removeButton.closest('.zone-area-row');
                    var areaList = areaRow ? areaRow.parentElement : null;
                    if (areaRow) {
                        areaRow.remove();
                    }
                    if (areaList && !areaList.querySelector('.zone-area-row')) {
                        var emptyRow = document.createElement('div');
                        emptyRow.className = 'zone-area-row';
                        emptyRow.innerHTML = areaRowHtml();
                        areaList.appendChild(emptyRow);
                    }
                });
            })();
        </script>
    @endpush
@endonce
