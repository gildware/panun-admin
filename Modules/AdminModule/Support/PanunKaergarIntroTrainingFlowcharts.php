<?php

namespace Modules\AdminModule\Support;

class PanunKaergarIntroTrainingFlowcharts
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function all(): array
    {
        return [
            'job-model' => [
                ['kind' => 'start', 'label' => 'You tell us the job'],
                ['kind' => 'action', 'label' => 'We analyse the request'],
                ['kind' => 'action', 'label' => 'You get an estimate'],
                ['kind' => 'action', 'label' => 'We send the right professional'],
                ['kind' => 'action', 'label' => 'The work gets done — payment through Panun Kaergar'],
                ['kind' => 'end', 'label' => 'We check the quality', 'tone' => 'success'],
            ],
            'who-owns' => [
                ['kind' => 'start', 'label' => 'Household needs work at home'],
                ['kind' => 'decision', 'label' => 'Who did they hire?'],
                ['kind' => 'fork', 'branches' => [
                    ['label' => 'Panun Kaergar', 'tone' => 'success', 'to' => 'We manage the experience. A team or partner does the visit.'],
                    ['label' => 'On your own', 'tone' => 'warn', 'to' => 'They hunt numbers, wait, and chase the job'],
                    ['label' => 'A marketplace', 'tone' => 'neutral', 'to' => 'They still pick and manage who comes'],
                ]],
            ],
        ];
    }

    /** @return array<int, array{key: string, title: string}> */
    public static function referenceCharts(): array
    {
        return [
            ['key' => 'job-model', 'title' => 'How we help'],
            ['key' => 'who-owns', 'title' => 'Who they hired'],
        ];
    }

    /** @return array<int, array<string, mixed>>|null */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
