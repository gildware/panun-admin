<?php

namespace Modules\CustomerModule\Observers;

/**
 * Previously bumped the global home content version on every Banner/Service/etc save.
 *
 * Home cache is now manual-rebuild only: API keeps serving the last built payload
 * until an admin clicks "Reset home cache". Observers are no-ops so edits never
 * invalidate or empty the shared home bundle on Hostinger file cache.
 */
class CustomerHomeContentVersionObserver
{
    public function saved(): void
    {
        // Manual rebuild only — do not bump or warm.
    }

    public function deleted(): void
    {
        // Manual rebuild only — do not bump or warm.
    }
}
