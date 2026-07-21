<?php

namespace Modules\WhatsAppModule\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WhatsAppModule\Services\MetaConversionsApiService;

class SendMetaCtwaConversionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array{currency?: string, value?: float|int|string}  $customData
     */
    public function __construct(
        public string $phone,
        public string $eventName,
        public string $ctwaClid,
        public string $eventId,
        public ?int $leadId = null,
        public ?string $bookingId = null,
        public array $customData = []
    ) {}

    public function handle(MetaConversionsApiService $capi): void
    {
        $capi->sendEvent(
            $this->phone,
            $this->eventName,
            $this->ctwaClid,
            $this->eventId,
            $this->leadId,
            $this->bookingId,
            $this->customData
        );
    }
}
