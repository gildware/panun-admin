<?php

namespace Modules\LeadManagement\Services;

use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Support\VoiceCronWaAiFlow;

class VoiceCronMatchConditionConflictValidator
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    public function conflicts(array $filters): array
    {
        $errors = [];

        $leadTypeLabels = Lead::leadTypes();
        $includeTypes = array_values(array_filter((array) ($filters['lead_types'] ?? [])));
        $excludeTypes = array_values(array_filter((array) ($filters['exclude_lead_types'] ?? [])));
        foreach (array_intersect($includeTypes, $excludeTypes) as $type) {
            $label = $leadTypeLabels[$type] ?? ucfirst((string) $type);
            $errors[] = sprintf(translate('Voice_cron_conflict_lead_type'), $label);
        }

        $includeLeadOpen = (string) ($filters['lead_open'] ?? '');
        $excludeLeadOpen = (string) ($filters['exclude_lead_open'] ?? '');
        if ($includeLeadOpen !== '' && $includeLeadOpen === $excludeLeadOpen) {
            $status = $includeLeadOpen === 'open' ? translate('Open') : translate('Closed');
            $errors[] = sprintf(translate('Voice_cron_conflict_lead_open'), $status);
        }

        $includeWaBucket = (string) ($filters['wa_chat_bucket'] ?? '');
        $excludeWaBucket = (string) ($filters['exclude_wa_chat_bucket'] ?? '');
        if ($includeWaBucket !== '' && $includeWaBucket === $excludeWaBucket) {
            $status = $includeWaBucket === 'open'
                ? translate('whatsapp_bucket_open')
                : translate('whatsapp_bucket_closed');
            $errors[] = sprintf(translate('Voice_cron_conflict_wa_bucket'), $status);
        }

        $includeHandledBy = (string) ($filters['handled_by'] ?? '');
        $excludeHandledBy = (string) ($filters['exclude_handled_by'] ?? '');
        if ($includeHandledBy !== '' && $includeHandledBy === $excludeHandledBy) {
            if ($includeHandledBy === 'ai') {
                $errors[] = translate('Voice_cron_conflict_handled_by_ai');
            } elseif ($includeHandledBy === 'human') {
                $errors = array_merge($errors, $this->humanHandledByConflicts($filters));
            }
        }

        if (($filters['human_support'] ?? '') === 'only'
            && ($filters['exclude_human_support'] ?? '') === 'exclude') {
            $errors[] = translate('Voice_cron_conflict_human_support');
        }

        $waTagErrors = $this->tagIdConflicts(
            (array) ($filters['wa_chat_tag_ids'] ?? []),
            (array) ($filters['exclude_wa_chat_tag_ids'] ?? []),
            translate('Voice_cron_wa_chat_tags_label')
        );
        $errors = array_merge($errors, $waTagErrors);

        $errors = array_merge($errors, $this->tagIdConflicts(
            (array) ($filters['customer_lead_status_ids'] ?? []),
            (array) ($filters['exclude_customer_lead_status_ids'] ?? []),
            translate('Customer_Lead_Status')
        ));

        $errors = array_merge($errors, $this->tagIdConflicts(
            (array) ($filters['provider_lead_status_ids'] ?? []),
            (array) ($filters['exclude_provider_lead_status_ids'] ?? []),
            translate('Provider_Lead_Status')
        ));

        $includeFlows = array_values(array_filter((array) ($filters['wa_ai_flows'] ?? [])));
        $excludeFlows = array_values(array_filter((array) ($filters['exclude_wa_ai_flows'] ?? [])));
        foreach (array_intersect($includeFlows, $excludeFlows) as $flow) {
            $errors[] = sprintf(translate('Voice_cron_conflict_wa_ai_flow'), VoiceCronWaAiFlow::label((string) $flow));
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    private function humanHandledByConflicts(array $filters): array
    {
        $errors = [];
        $includeEmployees = array_values(array_filter((array) ($filters['handled_by_employee_ids'] ?? [])));
        $excludeEmployees = array_values(array_filter((array) ($filters['exclude_handled_by_employee_ids'] ?? [])));

        if ($includeEmployees === [] && $excludeEmployees === []) {
            return [translate('Voice_cron_conflict_handled_by_human_all')];
        }

        if ($excludeEmployees === []) {
            return [translate('Voice_cron_conflict_handled_by_human_exclude_all')];
        }

        foreach (array_intersect($includeEmployees, $excludeEmployees) as $employeeId) {
            $errors[] = sprintf(translate('Voice_cron_conflict_handled_by_employee'), $employeeId);
        }

        return $errors;
    }

    /**
     * @param  array<int, mixed>  $includeIds
     * @param  array<int, mixed>  $excludeIds
     * @return array<int, string>
     */
    private function tagIdConflicts(array $includeIds, array $excludeIds, string $fieldLabel): array
    {
        $includeIds = array_map('intval', array_filter($includeIds));
        $excludeIds = array_map('intval', array_filter($excludeIds));
        $errors = [];

        foreach (array_intersect($includeIds, $excludeIds) as $tagId) {
            $errors[] = sprintf(translate('Voice_cron_conflict_tag'), $fieldLabel, (string) $tagId);
        }

        return $errors;
    }
}
