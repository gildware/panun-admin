<div class="top-sub-nav-bar">
    <div class="top-sub-nav-start">
        <div class="top-pinned-header">
            <span class="top-sub-nav-label">{{ translate('Pinned') }}</span>
            <button type="button"
                    class="top-pins-edit-btn"
                    id="admin-pins-edit-btn"
                    title="{{ translate('Edit_pinned_shortcuts') }}"
                    aria-label="{{ translate('Edit') }}">
                <span class="material-icons" aria-hidden="true">edit</span>
            </button>
            <button type="button"
                    class="top-pins-save-btn"
                    id="admin-pins-save-btn"
                    hidden
                    title="{{ translate('Save') }}"
                    aria-label="{{ translate('Save') }}">
                <span class="material-icons" aria-hidden="true">save</span>
                <span class="top-pins-save-label">{{ translate('Save') }}</span>
            </button>
        </div>

        <div id="admin-pinned-mount"
             class="top-pinned-links"
             data-empty-hint="{{ translate('Pin_links_from_the_menu') }}"
             data-save-url="{{ route('admin.pinned-nav.save') }}"></div>
    </div>

    @include('adminmodule::layouts.partials._top-breadcrumbs')
</div>

<script type="application/json" id="admin-pinned-catalog">@json($adminPinnedCatalog ?? [])</script>
<script type="application/json" id="admin-pinned-user">@json($adminUserPinnedKeys ?? [])</script>
