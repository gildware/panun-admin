<?php

namespace Tests\Unit;

use Modules\LeadManagement\Services\LeadHuntingBoardService;
use Tests\TestCase;

class LeadHuntingBoardServiceTest extends TestCase
{
    public function test_hunt_ready_requires_all_job_fields(): void
    {
        $service = new LeadHuntingBoardService();

        $this->assertFalse($service->isHuntReady([]));
        $this->assertFalse($service->isHuntReady([
            'service_subcategory' => 'sub-1',
            'zone_id' => 'zone-1',
            'area_id' => '1',
            'estimated_service_at' => '2026-04-06 10:00:00',
        ]));

        $this->assertTrue($service->isHuntReady([
            'service_subcategory' => 'sub-1',
            'zone_id' => 'zone-1',
            'area_id' => '1',
            'estimated_service_at' => '2026-04-06 10:00:00',
            'service_description' => 'Kitchen sink blocked',
        ]));

        $this->assertTrue($service->isHuntReady([
            'service_subcategory' => 'sub-1',
            'zone_id' => 'zone-1',
            'area_id' => '1',
            'estimated_service_at' => '2026-04-06 10:00:00',
            'service_name' => 'svc-1',
        ]));
    }

    public function test_normalize_platforms_keeps_allowed_keys_only(): void
    {
        $this->assertSame(
            ['hunting_board', 'whatsapp'],
            LeadHuntingBoardService::normalizePlatforms(['hunting_board', 'whatsapp', 'tiktok', 'whatsapp'])
        );
        $this->assertSame(['facebook'], LeadHuntingBoardService::normalizePlatforms('facebook, instagramx'));
        $this->assertSame([], LeadHuntingBoardService::normalizePlatforms(null));
    }

    public function test_unpublish_reasons(): void
    {
        $this->assertSame(
            ['found_provider', 'cancelled'],
            LeadHuntingBoardService::unpublishReasons()
        );
    }
}
