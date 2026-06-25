@if(!empty($pinKey))
    <button type="button"
            class="top-nav-pin-btn"
            data-pin-key="{{ $pinKey }}"
            title="{{ translate('Pin_to_shortcuts') }}"
            aria-label="{{ translate('Pin') }}"
            aria-pressed="false">
        <span class="material-icons" aria-hidden="true">push_pin</span>
    </button>
@endif
