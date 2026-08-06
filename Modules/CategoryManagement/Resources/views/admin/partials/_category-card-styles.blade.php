<style>
    #ListTableContainer a.category-list-name-link:hover,
    #ListTableContainer a.category-list-name-link:focus,
    #SubCategoryListTableContainer a.category-list-name-link:hover,
    #SubCategoryListTableContainer a.category-list-name-link:focus,
    #ServiceListTableContainer a.category-list-name-link:hover,
    #ServiceListTableContainer a.category-list-name-link:focus {
        color: var(--bs-primary) !important;
    }

    .category-page-toolbar__filter-badge {
        display: inline-flex;
        align-items: center;
        max-width: 10rem;
        padding: 0.1875rem 0.5rem;
        border-radius: 999px;
        background: rgba(var(--bs-primary-rgb), 0.08);
        color: var(--bs-primary);
        font-size: 0.625rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .category-card-list {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 0.5rem;
    }

    @media (max-width: 1199.98px) {
        .category-card-list {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .category-card-list {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .category-card-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .category-page-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.875rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.875rem;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.75rem;
        --toolbar-control-height: 2.625rem;
        --toolbar-control-font-size: 0.875rem;
        --toolbar-control-radius: 0.5rem;
    }

    .category-page-toolbar__start {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
        min-width: 0;
    }

    .category-page-toolbar__tabs {
        flex-shrink: 0;
        margin-inline-start: auto;
    }

    .category-page-toolbar__end {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        flex: 1 1 100%;
        width: 100%;
        min-width: 0;
        margin-inline-start: 0;
    }

    .category-page-toolbar__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }

    .category-page-toolbar__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.5rem;
        height: 1.5rem;
        padding: 0 0.375rem;
        border-radius: 999px;
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        font-size: 0.6875rem;
        font-weight: 700;
        line-height: 1;
    }

    .category-page-toolbar__tabs {
        flex-shrink: 0;
        margin-inline-start: auto;
    }

    .category-page-toolbar__tabs .nav--tabs {
        display: inline-flex;
        margin: 0;
        padding: 0.1875rem;
        border: 0;
        border-radius: 999px;
        background: var(--bs-tertiary-bg);
        gap: 0.125rem;
    }

    .category-page-toolbar__tabs .nav-item {
        margin: 0;
    }

    .category-page-toolbar__tabs .nav-link {
        padding: 0.3125rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        border-radius: 999px;
        border: 0;
        color: var(--bs-secondary-color);
        transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
    }

    .category-page-toolbar__tabs .nav-link:hover {
        color: var(--bs-body-color);
    }

    .category-page-toolbar__tabs .nav-link.active {
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.12);
    }

    .category-page-toolbar__tabs .nav-link.active[data-status="all"] {
        background: var(--bs-primary);
        color: #fff;
    }

    .category-page-toolbar__tabs .nav-link.active[data-status="active"] {
        background: var(--bs-success);
        color: #fff;
    }

    .category-page-toolbar__tabs .nav-link.active[data-status="inactive"] {
        background: #64748b;
        color: #fff;
    }

    .category-page-toolbar__end {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-inline-start: auto;
        flex-shrink: 1;
        min-width: 0;
    }

    .category-page-toolbar__controls {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        flex: 1 1 auto;
        min-width: 0;
        justify-content: flex-start;
    }

    .category-page-toolbar__actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .category-page-toolbar__actions .btn {
        min-height: var(--toolbar-control-height);
        height: var(--toolbar-control-height);
        font-size: var(--toolbar-control-font-size);
        padding: 0.375rem 0.75rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border-radius: var(--toolbar-control-radius);
    }

    .category-page-toolbar__actions .btn .material-icons,
    .category-page-toolbar__actions .btn .material-symbols-outlined {
        font-size: 1.125rem;
        line-height: 1;
    }

    .category-page-toolbar__search {
        display: flex;
        align-items: center;
        min-width: 0;
        flex: 1 1 14rem;
        min-width: 14rem;
        max-width: none;
    }

    .category-page-toolbar__search .search-form {
        width: 100%;
        margin: 0;
        min-width: 0;
    }

    .category-page-toolbar__filter {
        display: flex;
        align-items: center;
        min-width: 0;
        width: min(20rem, 100%);
        flex: 0 1 20rem;
        min-width: 14rem;
    }

    .category-page-toolbar__filter .select2-container {
        width: 100% !important;
        min-width: 0;
    }

    .category-page-toolbar__filter .select2-container--default .select2-selection--single {
        height: var(--toolbar-control-height);
        min-height: var(--toolbar-control-height);
        border-color: var(--bs-border-color);
        border-radius: var(--toolbar-control-radius);
        background: var(--bs-body-bg);
    }

    .category-page-toolbar__filter .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(var(--toolbar-control-height) - 2px);
        font-size: var(--toolbar-control-font-size);
        padding-left: 0.75rem;
        padding-right: 2.25rem;
        color: var(--bs-body-color);
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .category-page-toolbar__filter .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: var(--bs-secondary-color);
    }

    .category-page-toolbar__filter .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(var(--toolbar-control-height) - 2px);
        right: 0.5rem;
    }

    .category-page-toolbar__filter .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 0.25rem;
        font-size: 1rem;
        line-height: calc(var(--toolbar-control-height) - 2px);
    }

    .category-page-toolbar__filter .select2-container--default.select2-container--focus .select2-selection--single,
    .category-page-toolbar__filter .select2-container--default.select2-container--open .select2-selection--single {
        border-color: rgba(var(--bs-primary-rgb), 0.65);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.12);
    }

    .category-page-toolbar__filter-select {
        height: var(--toolbar-control-height);
        width: 100%;
        min-width: 0;
        font-size: var(--toolbar-control-font-size);
        padding: 0.5rem 2rem 0.5rem 0.75rem;
        border-radius: var(--toolbar-control-radius);
    }

    .category-page-toolbar__search .search-form__input_group {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
        min-height: var(--toolbar-control-height);
        border-radius: var(--toolbar-control-radius);
        overflow: hidden;
    }

    .category-page-toolbar__search .search-form__input {
        min-height: var(--toolbar-control-height);
        height: var(--toolbar-control-height);
        font-size: var(--toolbar-control-font-size);
        padding: 0.5rem 0.75rem;
    }

    .category-page-toolbar__search .search-form__icon {
        padding: 0 0.5rem;
        min-width: 2.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .category-page-toolbar__search .search-form__icon .material-icons {
        font-size: 1.125rem;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        min-height: 2.375rem;
        font-size: var(--toolbar-control-font-size, 0.875rem);
        padding: 0.4375rem 0.75rem;
        border-radius: 0.4375rem;
        border-color: var(--bs-border-color);
    }

    .select2-container--default .select2-results__option {
        font-size: var(--toolbar-control-font-size, 0.875rem);
        padding: 0.5625rem 0.875rem;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: rgba(var(--bs-primary-rgb), 0.12);
        color: var(--bs-body-color);
    }

    .select2-dropdown {
        border-color: var(--bs-border-color);
        border-radius: var(--toolbar-control-radius, 0.5rem);
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    }

    .category-page-panel .card-body {
        padding: 0.875rem;
    }

    @media (max-width: 991.98px) {
        .category-page-toolbar__tabs {
            margin-inline-start: 0;
            width: 100%;
        }

        .category-page-toolbar__end {
            flex-wrap: wrap;
        }

        .category-page-toolbar__controls {
            width: 100%;
        }

        .category-page-toolbar__search,
        .category-page-toolbar__filter {
            flex: 1 1 100%;
            width: 100%;
            min-width: 0;
            max-width: none;
        }

        .category-page-toolbar__actions {
            margin-inline-start: auto;
        }
    }

    @media (max-width: 575.98px) {
        .category-page-toolbar__tabs {
            width: 100%;
        }

        .category-page-toolbar__tabs .nav--tabs {
            width: 100%;
            justify-content: space-between;
        }
    }

    .category-card {
        display: flex;
        flex-direction: column;
        min-height: 13.75rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        background: var(--bs-body-bg);
        overflow: hidden;
        min-width: 0;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .category-card:hover {
        border-color: rgba(var(--bs-primary-rgb), 0.35);
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
    }

    .category-card__media {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 5.75rem;
        padding: 0.625rem;
        background: linear-gradient(180deg, var(--bs-tertiary-bg) 0%, var(--bs-body-bg) 100%);
        border-bottom: 1px solid var(--bs-border-color);
    }

    .category-card__media img {
        width: auto;
        height: auto;
        max-width: 3.5rem;
        max-height: 3.5rem;
        object-fit: contain;
    }

    .category-card__body {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 0.625rem 0.6875rem 0.6875rem;
        flex: 1;
        min-width: 0;
    }

    .category-card__head {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
        min-width: 0;
    }

    .category-card__name {
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1.3;
        color: var(--bs-body-color);
        text-decoration: none;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.6em;
    }

    .category-card__stats {
        display: flex;
        flex-direction: column;
        gap: 0.3125rem;
        margin-top: 0.125rem;
    }

    .category-card__meta-line {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.25rem;
        font-size: 0.6875rem;
        line-height: 1.35;
        color: var(--bs-secondary-color);
        position: relative;
    }

    .category-card__meta-label {
        flex: 1 1 auto;
        min-width: 0;
    }

    .category-card__meta-view-wrap {
        position: relative;
        margin-inline-start: auto;
        flex-shrink: 0;
    }

    .category-card__meta-view {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.375rem;
        height: 1.375rem;
        padding: 0;
        border: 0;
        border-radius: 999px;
        background: rgba(var(--bs-primary-rgb), 0.08);
        color: var(--bs-primary);
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }

    .category-card__meta-view .material-icons {
        font-size: 0.875rem;
        line-height: 1;
    }

    .category-card__meta-view:hover,
    .category-card__meta-view.is-open {
        background: var(--bs-primary);
        color: #fff;
    }

    .category-card__meta-panel {
        display: none;
        position: absolute;
        right: 0;
        bottom: calc(100% + 0.375rem);
        z-index: 20;
        min-width: 10.5rem;
        max-width: 14rem;
        max-height: 11rem;
        overflow: auto;
        padding: 0.5rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        background: var(--bs-body-bg);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14);
    }

    .category-card__meta-panel.is-open {
        display: block;
    }

    .category-card__meta-panel-head {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--bs-secondary-color);
        margin-bottom: 0.375rem;
        padding-bottom: 0.3125rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .category-card__meta-panel-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .category-card__meta-panel-list li {
        font-size: 0.6875rem;
        line-height: 1.35;
        padding: 0.25rem 0;
        color: var(--bs-body-color);
        word-break: break-word;
    }

    .category-card__meta-panel-list li + li {
        border-top: 1px solid var(--bs-border-color-translucent, rgba(0, 0, 0, 0.06));
    }

    .category-card__meta-panel-list a {
        color: inherit;
        text-decoration: none;
    }

    .category-card__meta-panel-list a:hover {
        color: var(--bs-primary);
    }

    .category-card__meta-value {
        font-weight: 700;
        color: var(--bs-body-color);
        flex-shrink: 0;
    }

    .category-card__meta-line--warn .category-card__meta-value {
        color: var(--bs-warning-text-emphasis, #664d03);
    }

    .category-card__meta-line--warn {
        color: var(--bs-warning-text-emphasis, #664d03);
    }

    .category-card__warn-icon {
        font-size: 0.8125rem !important;
        flex-shrink: 0;
        vertical-align: middle;
    }

    .category-card__footer {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.4375rem;
        margin-top: auto;
        padding-top: 0.4375rem;
        border-top: 1px solid var(--bs-border-color);
    }

    .category-card__toggles {
        display: flex;
        flex-direction: column;
        gap: 0.3125rem;
        padding: 0.375rem;
        border-radius: 0.375rem;
        background: var(--bs-tertiary-bg);
    }

    .category-card__toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.375rem;
    }

    .category-card__toggle-label {
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--bs-secondary-color);
        line-height: 1.2;
    }

    .category-card__toggle {
        margin: 0;
        flex-shrink: 0;
        inline-size: 1.875rem;
        block-size: 0.9375rem;
    }

    .category-card__toggle .switcher_control {
        inline-size: 1.875rem;
        block-size: 1rem;
    }

    .category-card__toggle .switcher_control::after {
        inline-size: 0.875rem;
        block-size: 0.875rem;
    }

    .category-card__toggle .switcher_input:checked ~ .switcher_control:after {
        inset-inline-start: 0.875rem;
    }

    .category-card__actions {
        display: flex;
        flex-direction: column;
        gap: 0.3125rem;
    }

    .category-card__action-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.3125rem;
    }

    .category-card__action-row .category-card__action:only-child {
        grid-column: 1 / -1;
    }

    .category-card__action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.1875rem;
        width: 100%;
        min-height: 1.75rem;
        padding: 0.25rem 0.3125rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
        background: var(--bs-body-bg);
        color: var(--bs-body-color);
        font-size: 0.625rem;
        font-weight: 600;
        line-height: 1.15;
        text-decoration: none;
        text-align: center;
        cursor: pointer;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }

    .category-card__action span:last-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .category-card__action .material-icons,
    .category-card__action .material-symbols-outlined {
        font-size: 0.875rem;
        line-height: 1;
    }

    .category-card__action:hover,
    .category-card__action:focus {
        background: rgba(var(--bs-primary-rgb), 0.06);
        border-color: rgba(var(--bs-primary-rgb), 0.35);
        color: var(--bs-primary);
    }

    .category-card__action--danger {
        width: 100%;
        align-self: stretch;
        padding: 0.25rem 0.375rem;
        border-color: rgba(var(--bs-danger-rgb), 0.25);
        background: rgba(var(--bs-danger-rgb), 0.04);
        color: var(--bs-danger);
    }

    .category-card__action--danger:hover,
    .category-card__action--danger:focus {
        background: rgba(var(--bs-danger-rgb), 0.08);
        border-color: rgba(var(--bs-danger-rgb), 0.2);
        color: var(--bs-danger);
    }

    .category-card__action--disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .category-card-empty {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 2.5rem 1rem;
        color: var(--bs-secondary-color);
        border: 1px dashed var(--bs-border-color);
        border-radius: 0.625rem;
    }

    .category-card-empty .material-icons {
        font-size: 2rem;
        opacity: 0.35;
    }

    .category-card-empty p {
        margin: 0;
        font-size: 0.875rem;
    }

    .category-card-pagination {
        margin-top: 1rem;
    }
</style>
