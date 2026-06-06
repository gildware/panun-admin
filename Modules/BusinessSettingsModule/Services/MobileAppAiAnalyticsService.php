<?php

namespace Modules\BusinessSettingsModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\UserManagement\Entities\User;

/**
 * Funnel + intent analytics (structured logs — dashboard-ready).
 */
class MobileAppAiAnalyticsService
{
    public function track(string $event, User $user, array $props = []): void
    {
        if (! config('mobile_app_ai_production.analytics.enabled', true)) {
            return;
        }

        Log::info('mobile_app_ai.analytics', array_merge([
            'event' => $event,
            'user_id' => $user->id,
            'at' => now()->toIso8601String(),
        ], $props));
    }

    public function aiOpened(User $user): void
    {
        $this->track('ai_opened', $user);
    }

    public function intentRouted(User $user, MobileAppAiIntentClassification $c, string $handler, string $routingMode): void
    {
        $this->track('intent_routed', $user, array_merge($c->toLogArray(), [
            'handler' => $handler,
            'routing_mode' => $routingMode,
            'domain' => MobileAppAiIntentDomainCatalog::domainForIntent($c->intent),
            'impact' => MobileAppAiActionImpactCatalog::impactForIntent($c->intent),
        ]));
    }

    public function geminiTriggered(User $user, string $domain): void
    {
        $this->track('gemini_triggered', $user, ['domain' => $domain]);
    }

    public function fallbackTriggered(User $user, string $intent): void
    {
        $this->track('fallback_triggered', $user, ['intent' => $intent]);
    }

    public function escalationTriggered(User $user, string $reason): void
    {
        $this->track('human_handoff', $user, ['reason' => $reason]);
    }

    public function ambiguityShown(User $user, string $domain): void
    {
        $this->track('ambiguity_clarification', $user, ['domain' => $domain]);
    }
}
