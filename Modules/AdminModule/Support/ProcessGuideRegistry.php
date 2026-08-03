<?php

namespace Modules\AdminModule\Support;

class ProcessGuideRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'lead-qualification' => [
                'key' => 'lead-qualification',
                'title' => 'Lead Qualification Flow',
                'training_subtitle' => 'Lead qualification training — aligned with the official process flowchart.',
                'training_guide' => LeadQualificationTrainingGuide::class,
                'flowcharts' => LeadQualificationTrainingFlowcharts::class,
                'text_guide' => LeadQualificationTextGuide::class,
                'miro_board_id' => 'uXjVH2L4j28=',
                'miro_share_link_id' => '342998623562',
                'has_flowchart_board' => true,
            ],
            'booking-followup' => [
                'key' => 'booking-followup',
                'title' => 'Booking Follow-up Flow',
                'training_subtitle' => 'Full booking training — prerequisites through payments, same depth as lead qualification.',
                'training_guide' => BookingFollowupTrainingGuide::class,
                'flowcharts' => BookingFollowupTrainingFlowcharts::class,
                'text_guide' => BookingFollowupTextGuide::class,
                'has_flowchart_board' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function default(): array
    {
        return self::all()['lead-qualification'];
    }
}
