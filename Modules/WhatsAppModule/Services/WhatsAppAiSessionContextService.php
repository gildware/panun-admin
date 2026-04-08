<?php

namespace Modules\WhatsAppModule\Services;

use Carbon\Carbon;
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

        $tz = $this->runtimeResolver->supportTimezone();
        $now = Carbon::now($tz);
        $lines = [
            'Server clock (authoritative for "today", "tomorrow", and weekday; answer date questions with this — never say you cannot access today\'s date): '
                .$now->format('l, j F Y').', '.$now->format('h:i A').' '.$tz,
        ];
        $nameForPersonalization = '';

        $user = WhatsAppUser::query()->where('phone', $phone)->first();
        if ($user) {
            $bits = [];
            if (trim((string) $user->name) !== '') {
                $bits[] = 'saved_name: '.$user->name;
                $nameForPersonalization = trim((string) $user->name);
            }
            if (trim((string) $user->alternate_phone) !== '') {
                $bits[] = 'saved_alternate_phone: '.$user->alternate_phone;
            }
            if (trim((string) $user->address) !== '') {
                $bits[] = 'saved_address_on_file: '.$user->address;
            }
            if ($bits !== []) {
                $lines[] = 'WhatsApp profile (this number): '.implode('; ', $bits).'. Reuse for bookings without re-asking; only change address/name in tools when the customer explicitly asks.';
            }
        }

        $conv = WhatsAppConversation::query()->where('phone', $phone)->first();
        if ($conv) {
            $lines[] = 'Conversation flags: active_module='.($conv->active_module ?: 'none')
                .', current_step='.($conv->current_step ?: 'none');

            $unclear = (int) ($conv->ai_unclear_attempts ?? 0);
            if ($unclear > 0) {
                $maxU = (int) config('whatsappmodule.ai_unclear_max_clarify_rounds', 2);
                $lines[] = 'Unclear-intent rounds already used for this chat: '.$unclear.' of '.$maxU.' (then a short closing message). Use report_unclear_user_intent only if the latest message is still genuinely unintelligible — not for normal questions you can answer in text or with tools.';
            }

            $bid = trim((string) ($conv->active_booking_id ?? ''));
            if ($bid !== '') {
                $b = WhatsAppBooking::query()->where('booking_id', $bid)->where('phone', $phone)->first();
                if ($b) {
                    if ($nameForPersonalization === '' && trim((string) $b->name) !== '') {
                        $nameForPersonalization = trim((string) $b->name);
                    }
                    $lines[] = 'Active booking ref: '.$b->booking_id.' status='.$b->status;
                    $lines[] = 'Booking fields — name: '.$this->dash($b->name)
                        .'; service: '.$this->dash($b->service)
                        .'; address: '.$this->dash($b->address)
                        .'; district: '.$this->dash($b->district)
                        .'; alt_phone: '.$this->dash($b->alt_phone)
                        .'; preferred_at: '.($b->prefered_datetime ? $b->prefered_datetime->timezone($this->runtimeResolver->supportTimezone())->format('d M Y, h:i A') : '—')
                        .'; location_hint: '.$this->dash($b->location_hint);
                    if ($b->status === WhatsAppBooking::STATUS_TENTATIVE_PENDING_HUMAN) {
                        $lines[] = 'Pending confirmation: staff have not necessarily assigned a provider yet — do not say someone failed to arrive or missed a visit; say the request is waiting for team confirmation and offer the support phone if they need urgency.';
                    }
                }
            }

            $lid = trim((string) ($conv->active_lead_id ?? ''));
            if ($lid !== '') {
                $lead = ProviderLead::query()->where('lead_id', $lid)->where('phone', $phone)->first();
                if ($lead) {
                    if ($nameForPersonalization === '' && trim((string) $lead->name) !== '') {
                        $nameForPersonalization = trim((string) $lead->name);
                    }
                    $lines[] = 'Active provider lead ref: '.$lead->lead_id.' status='.$lead->status;
                    $lines[] = 'Provider lead fields — name: '.$this->dash($lead->name)
                        .'; services: '.$this->dash($lead->service)
                        .'; address: '.$this->dash($lead->address);
                }
            }
        }

        if ($nameForPersonalization !== '') {
            $lines[] = '**Personalisation:** Greet using **'.$nameForPersonalization.'** when natural. **Always pass this name** into **upsert_my_draft_booking** (`name`) — **do not** ask whether to use it or a different name for the booking. **Only** if the customer explicitly asks to change or correct their name: use the new name in **upsert_my_draft_booking** so it saves on file.';
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
