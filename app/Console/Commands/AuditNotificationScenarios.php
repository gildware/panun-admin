<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditNotificationScenarios extends Command
{
    protected $signature = 'notifications:audit-scenarios {--module= : Filter by module key}';

    protected $description = 'Audit all notification scenarios: messages, triggers, and wiring';

    public function handle(): int
    {
        $messageIssues = validate_notification_scenario_messages();
        $triggerAudit = audit_notification_scenario_triggers();
        $moduleFilter = $this->option('module');

        $this->info('Notification Scenario Audit');
        $this->newLine();

        $grouped = group_notification_scenarios_by_module();
        foreach ($grouped as $module => $scenarios) {
            if ($moduleFilter && $moduleFilter !== $module) {
                continue;
            }

            $label = NOTIFICATION_SCENARIO_MODULE_LABELS[$module] ?? $module;
            $this->line("<fg=cyan>{$label}</> (" . count($scenarios) . ' scenarios)');

            foreach ($scenarios as $scenario) {
                $scenarioId = $scenario['id'];
                $triggerRows = array_filter(
                    $triggerAudit['results'],
                    fn (array $row) => $row['scenario_id'] === $scenarioId
                );
                $triggerOk = $triggerRows !== [] && collect($triggerRows)->every(fn (array $row) => $row['ok']);

                $audienceKeys = collect($scenario['audiences'])
                    ->filter(fn (array $a) => ($a['key'] ?? null) !== null)
                    ->map(fn (array $a) => ($a['audience'] ?? '?') . ':' . ($a['key'] ?? ''))
                    ->implode(', ');

                $msgOk = collect($messageIssues)->every(
                    fn (array $issue) => ! collect($scenario['audiences'])->contains(
                        fn (array $a) => ($a['key'] ?? '') === $issue['key']
                            && ($a['settings_type'] ?? '') === $issue['settings_type']
                    )
                );

                $status = ($triggerOk && $msgOk) ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
                $this->line("  {$status} {$scenario['title']} [{$scenarioId}]");
                $this->line("       Audiences: {$audienceKeys}");

                if (! $triggerOk) {
                    foreach ($triggerRows as $row) {
                        if (! $row['ok']) {
                            $this->warn("       Trigger: {$row['label']} — missing " . implode(', ', $row['missing']));
                        }
                    }
                }
            }

            $this->newLine();
        }

        $this->info("Trigger checks: {$triggerAudit['passed']} passed, {$triggerAudit['failed']} failed");
        $this->info('Message issues: ' . count($messageIssues));

        return ($triggerAudit['failed'] === 0 && $messageIssues === []) ? self::SUCCESS : self::FAILURE;
    }
}
