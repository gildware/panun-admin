<?php

namespace Modules\WhatsAppModule\Support;

/**
 * When active, {@see \Modules\BookingModule\Observers\BookingObserver} skips outbound booking WhatsApp
 * so the admin UI can show a preview modal first; the controller then queues a prompt or runs send after confirmation.
 */
final class BookingWhatsAppAutomationDeferral
{
    private int $depth = 0;

    public function begin(): void
    {
        $this->depth++;
    }

    public function end(): void
    {
        $this->depth = max(0, $this->depth - 1);
    }

    public function isDeferring(): bool
    {
        return $this->depth > 0;
    }
}
