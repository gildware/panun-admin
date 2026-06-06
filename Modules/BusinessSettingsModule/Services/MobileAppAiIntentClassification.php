<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Structured NLU / rule classification result.
 */
final class MobileAppAiIntentClassification
{
    /**
     * @param  array<string, mixed>  $entities
     */
    public function __construct(
        public readonly string $intent,
        public readonly float $confidence,
        public readonly string $source,
        public readonly array $entities = [],
    ) {}

    public function entityString(string $key): string
    {
        $v = $this->entities[$key] ?? '';

        return is_string($v) ? trim($v) : '';
    }

    public function entityInt(string $key): int
    {
        return (int) ($this->entities[$key] ?? 0);
    }

    /**
     * @return list<string>
     */
    public function entityStringList(string $key): array
    {
        $v = $this->entities[$key] ?? [];
        if (! is_array($v)) {
            return [];
        }

        $out = [];
        foreach ($v as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return array_values(array_unique($out));
    }

    public function usedAi(): bool
    {
        return str_contains($this->source, 'ai');
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'intent' => $this->intent,
            'confidence' => round($this->confidence, 3),
            'source' => $this->source,
            'entities' => $this->entities,
        ];
    }
}
