<?php

namespace Modules\AdminModule\Support;

use Illuminate\Support\Collection;
use Modules\AdminModule\Entities\TrainingMarkingPoint;

class TrainingAnswerScorer
{
    /**
     * Each marking point passes when every comma-separated group matches.
     * A group matches when any |-separated phrase is found in the answer.
     *
     * @param  Collection<int, TrainingMarkingPoint>  $points
     * @return array<int, array{label: string, pass: bool}>
     */
    public static function score(string $answer, Collection $points): array
    {
        $text = mb_strtolower($answer);
        $hits = [];
        foreach ($points as $point) {
            $hits[] = [
                'label' => $point->label,
                'pass' => self::matches($text, (string) $point->keywords),
            ];
        }

        return $hits;
    }

    public static function matches(string $text, string $keywords): bool
    {
        $groups = array_values(array_filter(array_map('trim', explode(',', $keywords)), fn ($group) => $group !== ''));
        if ($groups === []) {
            return false;
        }

        foreach ($groups as $group) {
            $options = array_values(array_filter(array_map(
                fn ($option) => mb_strtolower(trim($option)),
                explode('|', $group)
            ), fn ($option) => $option !== ''));
            $hit = false;
            foreach ($options as $option) {
                if (str_contains($text, $option)) {
                    $hit = true;
                    break;
                }
            }
            if (! $hit) {
                return false;
            }
        }

        return true;
    }
}
