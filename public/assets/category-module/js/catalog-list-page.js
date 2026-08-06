(function (window, $) {
    'use strict';

    if (!$ || !$.fn.select2) {
        return;
    }

    function getRoot(root) {
        if (root && root.querySelector) {
            return root;
        }

        return document.getElementById('admin-main') || document;
    }

    function initToolbarSelect($select) {
        if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
            return;
        }

        var placeholder = $select.find('option[value=""]').first().text() || 'All';

        $select.select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownParent: $('body'),
        });
        $select.prop('disabled', $select.is(':disabled'));
    }

    function syncToolbarValuesFromUrl(root) {
        root = getRoot(root);
        var params = new URLSearchParams(window.location.search);
        var $root = $(root);

        var parentId = params.get('parent_id');
        var $parentFilter = $root.find('#sub-category-parent-filter');
        if (parentId && $parentFilter.length) {
            $parentFilter.val(parentId).trigger('change.select2');
        }

        var categoryId = params.get('category_id');
        var subCategoryId = params.get('sub_category_id');
        var $categorySelect = $root.find('#service_list_category_select');
        var $subCategorySelect = $root.find('#service_list_sub_category_select');

        if (categoryId && $categorySelect.length) {
            $categorySelect.val(categoryId).trigger('change.select2');
        }
        if (subCategoryId && $subCategorySelect.length && !$subCategorySelect.is(':disabled')) {
            $subCategorySelect.val(subCategoryId).trigger('change.select2');
        }

        var search = params.get('search');
        var $search = $root.find('#catalog-toolbar-search-input');
        if (search && $search.length) {
            $search.val(search);
        }
    }

    function bootCatalogToolbar(root) {
        root = getRoot(root);

        if (!root.querySelector('.category-page-toolbar')) {
            return;
        }

        initToolbarSelect($(root).find('#sub-category-parent-filter'));
        initToolbarSelect($(root).find('#service_list_category_select'));
        initToolbarSelect($(root).find('#service_list_sub_category_select'));
        syncToolbarValuesFromUrl(root);
    }

    if (!window.__pkCatalogToolbarBootBound) {
        window.__pkCatalogToolbarBootBound = true;

        document.addEventListener('admin:page-loaded', function (event) {
            bootCatalogToolbar(event.detail && event.detail.root ? event.detail.root : document);
        });

        $(function () {
            bootCatalogToolbar(document);
        });
    }
})(window, window.jQuery);
