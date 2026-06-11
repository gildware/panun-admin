<?php

namespace Modules\WhatsAppModule\Services;

use Modules\LeadManagement\Entities\Lead;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Modules\WhatsAppModule\Support\WhatsAppActiveChatsListCache;

/**
 * Keeps CRM lead assignee and WhatsApp inbox assignee aligned for the same phone.
 *
 * Rules:
 * - Lead assigned to a human → chat goes to the same human (when a thread exists).
 * - Human takes / replies on a chat → open CRM lead for that phone gets the same assignee.
 * - Chat handed back to AI → CRM lead assignee is left unchanged.
 * - Lead closed (completed / cancelled / invalid / future) → chat returns to AI.
 */
class LeadWhatsAppAssignmentSyncService
{
    private static bool $syncing = false;

    public function __construct(
        protected WhatsAppLeadLifecycleService $leadLifecycle,
    ) {}

    public function onLeadSaved(Lead $lead): void
    {
        if (self::$syncing) {
            return;
        }

        if ($this->leadIsClosed($lead)) {
            $this->releaseChatToAi($lead);

            return;
        }

        $this->syncChatHandlerFromLead($lead);
    }

    /**
     * Call after a chat thread assignee changes (take, reply, template send, etc.).
     */
    public function onChatHandlerAssigned(string $phone, ?string $chatHandledBy): void
    {
        if (self::$syncing || !Lead::assigneeIsHuman($chatHandledBy)) {
            return;
        }

        $leadPhone = $this->leadLifecycle->normalizeLeadPhone($phone);
        if (!$leadPhone) {
            return;
        }

        $openLead = $this->findPrimaryOpenLeadForPhone($leadPhone);
        if (!$openLead || (string) ($openLead->handled_by ?? '') === (string) $chatHandledBy) {
            return;
        }

        self::$syncing = true;
        try {
            $openLead->handled_by = (string) $chatHandledBy;
            $openLead->save();
        } finally {
            self::$syncing = false;
        }
    }

    public static function chatHandlerForLead(?string $leadHandledBy): string
    {
        return Lead::assigneeIsHuman($leadHandledBy) ? (string) $leadHandledBy : Lead::HANDLED_BY_AI;
    }

    protected function syncChatHandlerFromLead(Lead $lead): void
    {
        $threadPhone = $this->resolveThreadPhoneForLead($lead);
        if (!$threadPhone) {
            return;
        }

        $handler = self::chatHandlerForLead($lead->handled_by);

        self::$syncing = true;
        try {
            $waUser = WhatsAppUser::firstOrNew([
                'phone' => $threadPhone,
                'channel' => SocialInboxChannel::WHATSAPP,
            ]);
            if (empty($waUser->channel)) {
                $waUser->channel = SocialInboxChannel::WHATSAPP;
            }
            if ((string) ($waUser->handled_by ?? '') === $handler) {
                return;
            }
            $waUser->handled_by = $handler;
            $waUser->save();
            $this->forgetChatCaches($threadPhone);
        } finally {
            self::$syncing = false;
        }
    }

    protected function releaseChatToAi(Lead $lead): void
    {
        $threadPhone = $this->resolveThreadPhoneForLead($lead);
        if (!$threadPhone) {
            return;
        }

        self::$syncing = true;
        try {
            $waUser = WhatsAppUser::query()
                ->where('phone', $threadPhone)
                ->where('channel', SocialInboxChannel::WHATSAPP)
                ->first();

            if (!$waUser || (string) ($waUser->handled_by ?? '') === Lead::HANDLED_BY_AI) {
                return;
            }

            $waUser->handled_by = Lead::HANDLED_BY_AI;
            $waUser->save();
            $this->forgetChatCaches($threadPhone);
        } finally {
            self::$syncing = false;
        }
    }

    protected function leadIsClosed(Lead $lead): bool
    {
        return ! $this->leadLifecycle->isLeadOpen($lead);
    }

    protected function findPrimaryOpenLeadForPhone(string $leadPhone): ?Lead
    {
        return Lead::query()
            ->where(function ($query) use ($leadPhone) {
                $query->where('phone_number', $leadPhone)
                    ->orWhere('phone_number', 'like', '%' . $leadPhone);
            })
            ->orderByDesc('id')
            ->get()
            ->first(fn (Lead $lead) => $this->leadLifecycle->isLeadOpen($lead));
    }

    protected function resolveThreadPhoneForLead(Lead $lead): ?string
    {
        $last10 = $this->leadLifecycle->normalizeLeadPhone($lead->phone_number);
        if (!$last10) {
            return null;
        }

        return SocialInboxChannel::using(SocialInboxChannel::WHATSAPP, function () use ($last10) {
            $candidates = WhatsAppUser::query()
                ->where('channel', SocialInboxChannel::WHATSAPP)
                ->where('phone', 'like', '%' . $last10)
                ->orderByDesc('updated_at')
                ->pluck('phone');

            foreach ($candidates as $phone) {
                if ($phone !== '' && WhatsAppMessage::query()->where('phone', $phone)->exists()) {
                    return $phone;
                }
            }

            return WhatsAppMessage::query()
                ->where('phone', 'like', '%' . $last10)
                ->orderByDesc('created_at')
                ->value('phone');
        });
    }

    protected function forgetChatCaches(string $threadPhone): void
    {
        WhatsAppActiveChatsListCache::forgetAll();
        WhatsAppActiveChatsListCache::forgetChatFull($threadPhone);
    }
}
