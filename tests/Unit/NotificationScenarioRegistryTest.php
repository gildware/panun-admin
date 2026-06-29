<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NotificationScenarioRegistryTest extends TestCase
{
    public function test_scenario_registry_is_non_empty_and_grouped(): void
    {
        $registry = notification_scenario_registry();
        $this->assertNotEmpty($registry);

        $grouped = group_notification_scenarios_by_module();
        $this->assertNotEmpty($grouped);
        $this->assertArrayHasKey('booking_creation', $grouped);
        $this->assertArrayHasKey('booking_update', $grouped);
    }

    public function test_each_scenario_has_required_fields_and_audiences(): void
    {
        foreach (notification_scenario_registry() as $scenario) {
            $this->assertNotEmpty($scenario['id']);
            $this->assertNotEmpty($scenario['module']);
            $this->assertNotEmpty($scenario['title']);
            $this->assertNotEmpty($scenario['trigger_actor']);
            $this->assertNotEmpty($scenario['trigger_action']);
            $this->assertNotEmpty($scenario['audiences']);

            foreach ($scenario['audiences'] as $audience) {
                $this->assertNotEmpty($audience['audience']);
                $this->assertNotEmpty($audience['channel']);
                $this->assertArrayHasKey('wired', $audience);

                if (($audience['key'] ?? null) && ($audience['settings_type'] ?? null)) {
                    $definition = notification_definition_for_key($audience['key']);
                    $this->assertNotNull(
                        $definition,
                        "Scenario {$scenario['id']} references unknown key {$audience['key']}"
                    );
                }
            }
        }
    }

    public function test_module_labels_cover_all_registry_modules(): void
    {
        $modules = array_unique(array_column(notification_scenario_registry(), 'module'));

        foreach ($modules as $module) {
            $this->assertArrayHasKey($module, NOTIFICATION_SCENARIO_MODULE_LABELS, "Missing module label: {$module}");
        }

        $this->assertCount(61, notification_scenario_registry());
    }
}
