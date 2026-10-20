<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\SuperfoneCall;
use Modules\LeadManagement\Services\SuperfoneCallService;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('superfone_calls')) {
            return;
        }

        SuperfoneCall::query()->orderBy('id')->chunkById(100, function ($calls): void {
            foreach ($calls as $call) {
                $payloads = is_array($call->payloads) ? $call->payloads : [];
                $previous = [
                    $this->stamp($call->getRawOriginal('started_at')),
                    $this->stamp($call->getRawOriginal('ended_at')),
                ];

                $this->applyInstant($call, 'started_at', SuperfoneCallService::instantFromPayloads($payloads, 'cdr_start'));
                $this->applyInstant($call, 'answered_at', SuperfoneCallService::instantFromPayloads($payloads, 'cdr_answer'));
                $this->applyInstant($call, 'ended_at', SuperfoneCallService::instantFromPayloads($payloads, 'cdr_end'));
                $this->applyInstant($call, 'recording_url_expires_at', SuperfoneCallService::instantFromPayloads($payloads, 'cdr_recording_url_expires_at'));
                $this->applyInstant($call, 'recording_file_expires_at', SuperfoneCallService::instantFromPayloads($payloads, 'cdr_recording_expire_at'));

                if ($call->isDirty()) {
                    $call->save();
                }

                $when = $call->ended_at ?? $call->started_at;
                $this->shiftFollowup(LeadFollowup::class, $call->lead_followup_id, $previous, $when, false);
                $this->shiftFollowup(BookingFollowup::class, $call->booking_followup_id, $previous, $when, true);
            }
        });
    }

    public function down(): void
    {
        // Original UTC instants stay in the stored Superfone payloads.
    }

    private function applyInstant(SuperfoneCall $call, string $attribute, mixed $instant): void
    {
        if ($instant !== null) {
            $call->{$attribute} = $instant;
        }
    }

    /**
     * @param  class-string<LeadFollowup|BookingFollowup>  $class
     * @param  array<int, string|null>  $previous
     */
    private function shiftFollowup(string $class, mixed $id, array $previous, mixed $when, bool $alsoDate): void
    {
        if ($id === null || $when === null) {
            return;
        }

        $followup = $class::query()->find($id);
        if ($followup === null) {
            return;
        }

        $known = array_values(array_filter($previous));
        if (! in_array($this->stamp($followup->getRawOriginal('followup_at')), $known, true)) {
            return;
        }

        $followup->followup_at = $when;
        if ($alsoDate && in_array($this->stamp($followup->getRawOriginal('date')), $known, true)) {
            $followup->date = $when;
        }
        $followup->save();
    }

    private function stamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
};
