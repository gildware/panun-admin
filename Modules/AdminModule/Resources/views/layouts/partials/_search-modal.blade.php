<div class="modal fade removeSlideDown"
     id="staticBackdrop"
     tabindex="-1"
     aria-labelledby="staticBackdropLabel"
     aria-hidden="true"
     data-recent-search-url="{{ route('admin.recent.search') }}"
     data-loading-text="{{ translate('Loading recent searches') }}..."
     data-searching-text="{{ translate('Searching....') }}"
     data-min-chars-text="{{ translate('Write a minimum of two characters.') }}"
     data-empty-text="{{ translate('It appears that you have not yet searched.') }}."
     data-error-text="{{ translate('Error loading recent searches') }}.">
    <div class="modal-dialog">
        <div class="modal-content modal-content__search border-0 {{ env('APP_ENV') == 'demo' ? 'mt-5' : '' }}">
            <div class="d-flex flex-column gap-3">
                <div class="d-flex gap-2 align-items-center rounded bg-card py-2 px-3">
                    <form class="flex-grow-1" id="searchForm" action="{{ route('admin.search.routing') }}">
                        @csrf
                        <div class="d-flex align-items-center global-search-container">
                            <span class="material-symbols-outlined">search</span>
                            <input class="form-control flex-grow-1 border-0 search-input" id="searchInput" name="search" type="search" placeholder="{{ translate('Search') }}" aria-label="{{ translate('Search') }}" autofocus autocomplete="off">
                        </div>
                    </form>
                    <button class="border-0 rounded-3 px-2 py-1" type="button" data-bs-dismiss="modal">{{ translate('Esc') }}</button>
                </div>

                <div class="bg-card p-4 rounded-3 min-h-350">
                    <div class="search-result position-relative" id="searchResults">
                        <div class="text-center text-muted py-5">{{ translate('It appears that you have not yet searched.') }}.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
