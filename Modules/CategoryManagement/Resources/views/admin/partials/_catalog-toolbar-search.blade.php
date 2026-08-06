<div class="category-page-toolbar__search">
    <div class="search-form search-form_style-two">
        <div class="input-group search-form__input_group">
            <span class="search-form__icon">
                <span class="material-icons">search</span>
            </span>
            <input type="search"
                   id="catalog-toolbar-search-input"
                   class="theme-input-style search-form__input catalog-toolbar-search-input"
                   value="{{ $search ?? '' }}"
                   name="search"
                   autocomplete="off"
                   placeholder="{{ translate('search_here') }}">
        </div>
    </div>
</div>
