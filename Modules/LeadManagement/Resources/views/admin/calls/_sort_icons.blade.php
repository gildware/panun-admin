<span class="calls-th-sort">
    <a href="{{ $sortUrl($column, 'asc') }}"
       class="{{ $sort === $column && $dir === 'asc' ? 'is-active' : '' }}"
       title="{{ translate('Ascending') }}"
       aria-label="{{ translate('Ascending') }}">
        <span class="material-icons">arrow_upward</span>
    </a>
    <a href="{{ $sortUrl($column, 'desc') }}"
       class="{{ $sort === $column && $dir === 'desc' ? 'is-active' : '' }}"
       title="{{ translate('Descending') }}"
       aria-label="{{ translate('Descending') }}">
        <span class="material-icons">arrow_downward</span>
    </a>
</span>
