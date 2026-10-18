<?php

namespace Modules\LeadManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Services\SuperfoneCallService;

class DownloadSuperfoneRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $callId)
    {
    }

    public function handle(SuperfoneCallService $service): void
    {
        $call = SuperfoneCall::query()->find($this->callId);
        if (! $call) {
            return;
        }

        $service->downloadRecording($call);
    }
}
