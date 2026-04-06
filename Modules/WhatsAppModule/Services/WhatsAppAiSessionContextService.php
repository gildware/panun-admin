<?php

namespace Modules\WhatsAppModule\Services;

use Modules\WhatsAppModule\Entities\ProviderLead;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppConversation;
use Modules\WhatsAppModule\Entities\WhatsAppUser;

/**
 * Injects trusted per-phone state into the system prompt so the model does not re-ask known fields.
 */
class WhatsAppAiSessionContextService
{
    public function __construct(
        protected WhatsAppAiRuntimeResolver $runtimeResolver
    ) {}

    public function runtimeAppendixForPhone(string $phone): string
    {
        if ($phone === '') {
            return '';
        }

        $lines = [];

        $user = WhatsAppUser::query()->where('phone', $phone)->first();
        if ($user) {
            $bits = [];
            if (trim((string) $user->name) !== '') {
                $bits[] = 'saved_name: '.$user->name;
            }
            if (trim((string) $user->alternate_phone) !== '') {
                $bits[] = 'saved_alternate_phone: '.$user->alternate_phone;
            }
            if (trim((string) $user->address) !== '') {
                $bits[] = 'saved_address_on_file: '.$user->address;
            }
            if ($bits !== []) {
                $lines[] = 'WhatsApp profile (this number): '.implode('; ', $bits).'. Reuse only when it helps; confirm if customer wants a different address.';
            }
        }

        $conv = WhatsAppConversation::query()->where('phone', $phone)->first();
        if ($conv) {
            $lines[] = 'Conversation flags: active_module='.($conv->active_module ?: 'none')
                .', current_step='.($conv->current_step ?: 'none');

            $unclear = (int) ($conv->ai_unclear_attempts ?? 0);
            if ($unclear > 0) {
                $maxU = (int) config('whatsappmodule.ai_unclear_max_clarify_rounds', 2);
                $lines[] = 'Unclear-intent rounds already used for this chat: '.$unclear.' of '.$maxU.' (then human handoff). If the latest message is still ambiguous, call report_unclear_user_intent — do not stall without it.';
            }

            $bid = trim((string) ($conv->active_booking_id ?? ''));
            if ($bid !== '') {
                $b = WhatsAppBooking::query()->where('booking_id', $bid)->where('phone', $phone)->first();
                if ($b) {
                    $lines[] = 'Active booking ref: '.$b->booking_id.' status='.$b->status;
                    $lines[] = 'Booking fields — name: '.$this->dash($b->name)
                        .'; service: '.$this->dash($b->service)
                        .'; address: '.$this->dash($b->address)
                        .'; district: '.$this->dash($b->district)
                        .'; alt_phone: '.$this->dash($b->alt_phone)
                        .'; preferred_at: '.($b->prefered_datetime ? $b->prefered_datetime->timezone($this->runtimeResolver->supportTimezone())->format('d M Y, h:i A') : '—')
                        .'; location_hint: '.$this->dash($b->location_hint);
                }
            }

            $lid = trim((string) ($conv->active_lead_id ?? ''));
            if ($lid !== '') {
                $lead = ProviderLead::query()->where('lead_id', $lid)->where('phone', $phone)->first();
                if ($lead) {
                    $lines[] = 'Active provider lead ref: '.$lead->lead_id.' status='.$lead->status;
                    $lines[] = 'Provider lead fields — name: '.$this->dash($lead->name)
                        .'; services: '.$this->dash($lead->service)
                        .'; address: '.$this->dash($lead->address);
                }
            }
        }

        if ($lines === []) {
            return '';
        }

        return "### Current session context (trusted; this chat's phone only; never disclose to others)\n"
            .implode("\n", $lines);
    }

    private function dash(?string $v): string
    {
        $t = trim((string) $v);

        return $t === '' ? '—' : $t;
    }
}
