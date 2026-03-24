<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Support\Collection;
use Modules\ProviderManagement\Entities\FeedbackTagConfig;

class FeedbackScoreConfigService
{
    public const ENTITY_PROVIDER = 'provider';
    public const ENTITY_CUSTOMER = 'customer';

    public const FEEDBACK_TYPE_COMPLAINT = 'complaint';
    public const FEEDBACK_TYPE_POSITIVE = 'positive_feedback';
    public const FEEDBACK_TYPE_NON_COMPLAINT = 'non_complaint';

    public function getActiveTags(string $entityType, string $feedbackType): Collection
    {
        return FeedbackTagConfig::query()
            ->where('entity_type', $entityType)
            ->where('feedback_type', $feedbackType)
            ->where('is_active', 1)
            ->orderBy('label')
            ->get();
    }

    public function getAllowedTagKeys(string $entityType, string $feedbackType): array
    {
        return $this->getActiveTags($entityType, $feedbackType)
            ->pluck('tag_key')
            ->all();
    }

    public function calculateScoreDelta(string $entityType, string $feedbackType, array $tags): int
    {
        if (empty($tags)) {
            return 0;
        }

        $scoreMap = FeedbackTagConfig::query()
            ->where('entity_type', $entityType)
            ->where('feedback_type', $feedbackType)
            ->where('is_active', 1)
            ->whereIn('tag_key', $tags)
            ->pluck('score', 'tag_key');

        $score = 0;
        foreach ($tags as $tag) {
            $score += (int) ($scoreMap[$tag] ?? 0);
        }

        return $score;
    }
}

