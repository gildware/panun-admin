<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Result of resolving which account entities an action targets (never guess).
 */
final class MobileAppAiActionResolutionResult
{
    public const RESOLVED = 'RESOLVED';

    public const AMBIGUOUS = 'AMBIGUOUS';

    public const NOT_FOUND = 'NOT_FOUND';

    /**
     * @param  list<array<string, mixed>>  $matchedEntities
     */
    public function __construct(
        public readonly string $status,
        public readonly array $matchedEntities = [],
        public readonly string $clarificationQuestion = '',
        public readonly string $domain = '',
    ) {}

    public function isResolved(): bool
    {
        return $this->status === self::RESOLVED;
    }

    public function isAmbiguous(): bool
    {
        return $this->status === self::AMBIGUOUS;
    }

    /**
     * @return list<string>
     */
    public function matchedLineIds(): array
    {
        $ids = [];
        foreach ($this->matchedEntities as $entity) {
            $id = (string) ($entity['cart_line_id'] ?? '');
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
