(function () {
    'use strict';

    var recentSearchRequest = null;

    function getModal() {
        return document.getElementById('staticBackdrop');
    }

    function getConfig() {
        var modal = getModal();
        if (!modal) {
            return null;
        }

        return {
            recentUrl: modal.getAttribute('data-recent-search-url') || '',
            loadingText: modal.getAttribute('data-loading-text') || 'Loading...',
            searchingText: modal.getAttribute('data-searching-text') || 'Searching...',
            minCharsText: modal.getAttribute('data-min-chars-text') || 'Write a minimum of two characters.',
            emptyText: modal.getAttribute('data-empty-text') || 'It appears that you have not yet searched.',
            errorText: modal.getAttribute('data-error-text') || 'Error loading recent searches.',
        };
    }

    function getSearchResultsEl() {
        return document.getElementById('searchResults');
    }

    function setSearchResults(html) {
        var el = getSearchResultsEl();
        if (el) {
            el.innerHTML = html;
        }
    }

    function abortRecentSearch() {
        if (recentSearchRequest && recentSearchRequest.readyState !== 4) {
            recentSearchRequest.abort();
        }
        recentSearchRequest = null;
    }

    function focusSearchInput() {
        var modal = getModal();
        if (!modal) {
            return;
        }

        var input = modal.querySelector('#searchForm input[type=search]');
        if (input) {
            input.focus();
        }
    }

    function loadRecentSearches() {
        var config = getConfig();
        if (!config || !config.recentUrl || !window.jQuery) {
            return;
        }

        var modal = getModal();
        if (modal) {
            var input = modal.querySelector('#searchForm input[type=search]');
            if (input) {
                input.value = '';
            }
        }

        setSearchResults('<div class="text-center text-muted py-5">' + config.loadingText + '</div>');
        abortRecentSearch();

        recentSearchRequest = window.jQuery.ajax({
            type: 'GET',
            url: config.recentUrl,
            success: function (response) {
                if (response && response.htmlView) {
                    setSearchResults(response.htmlView);
                }
            },
            error: function (xhr, status) {
                if (status === 'abort') {
                    return;
                }
                console.error(xhr.responseText);
                setSearchResults('<div class="text-center text-muted py-5">' + config.errorText + '</div>');
            },
            complete: function () {
                recentSearchRequest = null;
                focusSearchInput();
            },
        });
    }

    function performSearch(keyword) {
        var form = document.getElementById('searchForm');
        var config = getConfig();
        if (!form || !window.jQuery) {
            return;
        }

        var trimmed = (keyword || '').trim();
        if (trimmed.length < 2) {
            setSearchResults('<div class="text-center text-muted py-5">' + (config ? config.minCharsText : '') + '</div>');
            return;
        }

        setSearchResults('<div class="text-center text-muted py-5">' + (config ? config.searchingText : 'Searching...') + '</div>');

        window.jQuery.ajax({
            type: 'POST',
            url: form.getAttribute('action'),
            data: {
                search: trimmed,
                _token: form.querySelector('input[name="_token"]') ? form.querySelector('input[name="_token"]').value : '',
            },
            success: function (response) {
                if (response && response.htmlView) {
                    setSearchResults(response.htmlView);
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
            },
        });
    }

    function openSearchModal() {
        var opener = document.getElementById('modalOpener');
        if (opener) {
            opener.click();
            return;
        }

        var modal = getModal();
        if (modal && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    }

    function resetSearchResults() {
        var config = getConfig();
        if (config) {
            setSearchResults('<div class="text-center text-muted py-5">' + config.emptyText + '</div>');
        }
    }

    function bindGlobalSearch() {
        document.removeEventListener('shown.bs.modal', onSearchModalShown);
        document.removeEventListener('hidden.bs.modal', onSearchModalHidden);
        document.addEventListener('shown.bs.modal', onSearchModalShown);
        document.addEventListener('hidden.bs.modal', onSearchModalHidden);

        if (!window.jQuery) {
            return;
        }

        window.jQuery(document)
            .off('.adminGlobalSearch')
            .on('keyup.adminGlobalSearch', '#searchForm input[name="search"]', function () {
                performSearch(this.value);
            })
            .on('search.adminGlobalSearch', '#searchInput', function () {
                if (!this.value.trim()) {
                    loadRecentSearches();
                }
            })
            .on('submit.adminGlobalSearch', '#searchForm', function (event) {
                event.preventDefault();
            });
    }

    function onSearchModalShown(event) {
        if (!event.target || event.target.id !== 'staticBackdrop') {
            return;
        }
        loadRecentSearches();
    }

    function onSearchModalHidden(event) {
        if (!event.target || event.target.id !== 'staticBackdrop') {
            return;
        }
        abortRecentSearch();
        resetSearchResults();
    }

    document.addEventListener('keydown', function (event) {
        var isMac = navigator.platform.toLowerCase().includes('mac');
        var modifier = isMac ? event.metaKey : event.ctrlKey;
        if (modifier && (event.key === 'k' || event.key === 'K')) {
            event.preventDefault();
            openSearchModal();
        }
    });

    document.addEventListener('admin:chrome-updated', function () {
        abortRecentSearch();

        var modal = getModal();
        if (modal && modal.classList.contains('show') && window.bootstrap && window.bootstrap.Modal) {
            loadRecentSearches();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindGlobalSearch);
    } else {
        bindGlobalSearch();
    }
})();
