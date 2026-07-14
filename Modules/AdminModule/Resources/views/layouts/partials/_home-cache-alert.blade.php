{{-- Persistent home-cache error; shown by reset button script, dismissed manually. --}}
<div id="js-home-cache-alert"
     class="home-cache-alert d-none"
     role="alert"
     aria-live="assertive"
     hidden>
    <div class="home-cache-alert-inner">
        <span class="material-symbols-outlined home-cache-alert-icon" aria-hidden="true">error</span>
        <p class="js-home-cache-alert-message home-cache-alert-message mb-0"></p>
        <button type="button"
                class="js-home-cache-alert-dismiss home-cache-alert-dismiss"
                aria-label="{{ translate('Dismiss') }}"
                title="{{ translate('Dismiss') }}">
            <span class="material-symbols-outlined" aria-hidden="true">close</span>
        </button>
    </div>
</div>
