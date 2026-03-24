<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProviderManagement\Services\ProviderPerformanceService;

return new class extends Migration
{
    /**
     * Normalize legacy enum-only rows (NO_SHOW, SUCCESSFUL_JOB, etc.) into
     * COMPLAINT / NON_COMPLAINT + JSON tags + action_type where applicable.
     */
    public function up(): void
    {
        if (!Schema::hasTable('provider_incidents') || !Schema::hasColumn('provider_incidents', 'tags')) {
            return;
        }

        $complaint = ProviderPerformanceService::INCIDENT_COMPLAINT;
        $nonComplaint = ProviderPerformanceService::INCIDENT_NON_COMPLAINT;
        $actionCompleted = ProviderPerformanceService::ACTION_COMPLETED;

        $legacyComplaints = [
            'NO_SHOW' => ['no_show'],
            'UNRESPONSIVE' => ['no_response'],
            'POOR_SERVICE' => ['poor_service'],
            'LATE_ARRIVAL' => ['late_arrival'],
        ];

        foreach ($legacyComplaints as $oldType => $tags) {
            DB::table('provider_incidents')
                ->where('incident_type', $oldType)
                ->update([
                    'incident_type' => $complaint,
                    'tags' => json_encode(array_values($tags)),
                ]);
        }

        DB::table('provider_incidents')
            ->where('incident_type', 'POSITIVE_FEEDBACK')
            ->update([
                'incident_type' => $nonComplaint,
                'tags' => json_encode(['positive_feedback']),
            ]);

        DB::table('provider_incidents')
            ->where('incident_type', $nonComplaint)
            ->where('tags', json_encode(['positive_feedback']))
            ->where(function ($q) {
                $q->whereNull('action_type')->orWhere('action_type', '');
            })
            ->update(['action_type' => $actionCompleted]);

        DB::table('provider_incidents')
            ->where('incident_type', 'SUCCESSFUL_JOB')
            ->update([
                'incident_type' => $nonComplaint,
                'tags' => json_encode(['successful_job']),
            ]);

        DB::table('provider_incidents')
            ->where('incident_type', $nonComplaint)
            ->where('tags', json_encode(['successful_job']))
            ->where(function ($q) {
                $q->whereNull('action_type')->orWhere('action_type', '');
            })
            ->update(['action_type' => $actionCompleted]);

        $service = app(ProviderPerformanceService::class);
        $providerIds = DB::table('provider_incidents')->distinct()->pluck('provider_id');
        foreach ($providerIds as $providerId) {
            $service->evaluateAndUpdateProviderPerformanceStatus((string) $providerId);
        }
    }

    /**
     * Best-effort rollback: only rows that still match migrated tag payloads.
     */
    public function down(): void
    {
        if (!Schema::hasTable('provider_incidents') || !Schema::hasColumn('provider_incidents', 'tags')) {
            return;
        }

        $complaint = ProviderPerformanceService::INCIDENT_COMPLAINT;
        $nonComplaint = ProviderPerformanceService::INCIDENT_NON_COMPLAINT;

        $maps = [
            [$complaint, json_encode(['no_show']), 'NO_SHOW'],
            [$complaint, json_encode(['no_response']), 'UNRESPONSIVE'],
            [$complaint, json_encode(['poor_service']), 'POOR_SERVICE'],
            [$complaint, json_encode(['late_arrival']), 'LATE_ARRIVAL'],
            [$nonComplaint, json_encode(['positive_feedback']), 'POSITIVE_FEEDBACK'],
            [$nonComplaint, json_encode(['successful_job']), 'SUCCESSFUL_JOB'],
        ];

        foreach ($maps as [$incident, $tagsJson, $legacyType]) {
            DB::table('provider_incidents')
                ->where('incident_type', $incident)
                ->where('tags', $tagsJson)
                ->update([
                    'incident_type' => $legacyType,
                    'tags' => null,
                    'action_type' => null,
                ]);
        }
    }
};
