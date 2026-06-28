<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotificationScenarioTriggerTest extends TestCase
{
    public function test_trigger_map_covers_all_registry_scenarios(): void
    {
        $registryIds = array_column(notification_scenario_registry(), 'id');
        $mapIds = array_keys(notification_scenario_trigger_map());

        sort($registryIds);
        sort($mapIds);

        $this->assertSame($registryIds, $mapIds, 'Trigger map must include every scenario in the registry');
    }

    public function test_each_module_has_expected_scenario_count(): void
    {
        $byModule = [];
        foreach (notification_scenario_trigger_map() as $scenarioId => $entry) {
            $byModule[$entry['module']][] = $scenarioId;
        }

        $this->assertCount(3, $byModule['booking_creation']);
        $this->assertCount(18, $byModule['booking_update']);
        $this->assertCount(4, $byModule['payments']);
        $this->assertCount(6, $byModule['provider_payments']);
        $this->assertCount(2, $byModule['review']);
        $this->assertCount(5, $byModule['loyalty_points']);
        $this->assertCount(2, $byModule['wallet']);
        $this->assertCount(2, $byModule['refund']);
        $this->assertCount(1, $byModule['communication']);
        $this->assertCount(2, $byModule['service_requests']);
        $this->assertCount(2, $byModule['provider_account']);
        $this->assertCount(5, $byModule['advertisement']);
        $this->assertCount(5, $byModule['admin_alerts']);
    }

    public function test_all_scenario_triggers_are_wired_in_codebase(): void
    {
        $audit = audit_notification_scenario_triggers();

        $failures = array_filter($audit['results'], fn (array $row) => ! $row['ok']);

        $messages = [];
        foreach ($failures as $failure) {
            $messages[] = sprintf(
                '%s [%s] %s — missing: %s',
                $failure['scenario_id'],
                $failure['module'],
                $failure['label'],
                implode(', ', $failure['missing'])
            );
        }

        $this->assertSame(
            0,
            $audit['failed'],
            "Scenario trigger audit failures:\n" . implode("\n", $messages)
        );
    }

    /**
     * @dataProvider scenarioProvider
     */
    public function test_individual_scenario_trigger(string $scenarioId, string $module): void
    {
        $map = notification_scenario_trigger_map();
        $this->assertArrayHasKey($scenarioId, $map);

        $audit = audit_notification_scenario_triggers();
        $scenarioResults = array_filter(
            $audit['results'],
            fn (array $row) => $row['scenario_id'] === $scenarioId
        );

        $this->assertNotEmpty($scenarioResults, "No trigger checks for {$scenarioId}");

        foreach ($scenarioResults as $result) {
            $this->assertTrue(
                $result['ok'],
                "{$scenarioId} ({$module}) — {$result['label']} missing: " . implode(', ', $result['missing'])
            );
        }
    }

    public static function scenarioProvider(): array
    {
        $cases = [];
        foreach (notification_scenario_trigger_map() as $scenarioId => $entry) {
            $cases[$scenarioId] = [$scenarioId, $entry['module']];
        }

        return $cases;
    }
}
