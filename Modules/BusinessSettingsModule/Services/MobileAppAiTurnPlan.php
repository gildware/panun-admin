<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * One customer turn: primary intent, optional multi-intent chain, domain, routing mode.
 */
final class MobileAppAiTurnPlan
{
    public const ROUTE_EXECUTE = 'execute';

    public const ROUTE_CLARIFY = 'clarify';

    public const ROUTE_GEMINI = 'gemini';

    /**
     * @param  list<MobileAppAiIntentClassification>  $intents  Ordered: read-only first, then mutations
     */
    public function __construct(
        public readonly MobileAppAiIntentClassification $primary,
        public readonly array $intents = [],
        public readonly string $routingMode = self::ROUTE_EXECUTE,
        public readonly string $clarificationQuestion = '',
        public readonly string $domain = '',
    ) {}

    public function primaryIntent(): string
    {
        return $this->primary->intent;
    }

    /**
     * @return list<MobileAppAiIntentClassification>
     */
    public function orderedIntents(): array
    {
        if ($this->intents !== []) {
            return $this->intents;
        }

        return [$this->primary];
    }
}
