<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;

/**
 * NLU facade — delegates to MobileAppAiUnderstandingService (AI-primary).
 */
class MobileAppAiIntentClassifier
{
    public function __construct(
        protected MobileAppAiUnderstandingService $understanding,
    ) {}

    /**
     * @param  array<string, mixed>  $draft
     */
    public function classify(
        User $user,
        string $text,
        array $draft = [],
        ?MobileAppAiConversation $conversation = null,
    ): MobileAppAiIntentClassification {
        return $this->understanding->understand($user, $text, $draft, $conversation);
    }
}
