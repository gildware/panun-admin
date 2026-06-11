<?php

namespace Modules\LeadManagement\Services;

use App\Models\User;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\ProviderLeadStatus;
use Modules\LeadManagement\Entities\WhatsAppVoiceFollowupAutomationRule;
use Modules\LeadManagement\Support\VoiceCronWaAiFlow;
use Modules\WhatsAppModule\Entities\WhatsAppChatTag;

class VoiceCronFilterSummaryBuilder
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{include: array<int, string>, exclude: array<int, string>, global: array<int, string>}
     */
    public function build(array $filters): array
    {
        return [
            'include' => $this->buildIncludeLines($filters),
            'exclude' => $this->buildExcludeLines($filters),
            'global' => $this->buildGlobalLines($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    private function buildIncludeLines(array $filters): array
    {
        $lines = [];
        $leadTypeLabels = Lead::leadTypes();

        $types = array_values(array_filter((array) ($filters['lead_types'] ?? [])));
        if ($types !== []) {
            $labels = array_map(fn (string $type) => $leadTypeLabels[$type] ?? ucfirst($type), $types);
            $lines[] = translate('Lead_type') . ': ' . implode(', ', $labels);
        }

        $lines = array_merge($lines, $this->statusLines(
            (array) ($filters['customer_lead_status_ids'] ?? []),
            CustomerLeadStatus::class,
            translate('Customer_Lead_Status')
        ));

        $lines = array_merge($lines, $this->statusLines(
            (array) ($filters['provider_lead_status_ids'] ?? []),
            ProviderLeadStatus::class,
            translate('Provider_Lead_Status')
        ));

        $leadOpen = (string) ($filters['lead_open'] ?? '');
        if ($leadOpen !== '') {
            $lines[] = translate('Lead') . ' ' . translate('Status') . ': ' . ($leadOpen === 'open' ? translate('Open') : translate('Closed'));
        }

        $waBucket = (string) ($filters['wa_chat_bucket'] ?? '');
        if ($waBucket !== '') {
            $lines[] = translate('WhatsApp') . ' ' . translate('Status') . ': '
                . ($waBucket === 'open' ? translate('whatsapp_bucket_open') : translate('whatsapp_bucket_closed'));
        }

        $lines = array_merge($lines, $this->tagLines(
            (array) ($filters['wa_chat_tag_ids'] ?? []),
            translate('Voice_cron_wa_chat_tags_label')
        ));

        $handledBy = (string) ($filters['handled_by'] ?? '');
        if ($handledBy === 'ai') {
            $lines[] = translate('Handled_By') . ': AI';
        } elseif ($handledBy === 'human') {
            $employeeLine = $this->employeeLine((array) ($filters['handled_by_employee_ids'] ?? []));
            $lines[] = translate('Handled_By') . ': ' . ($employeeLine !== '' ? $employeeLine : translate('name_of_employee'));
        }

        if (($filters['human_support'] ?? '') === 'only') {
            $lines[] = translate('Human_support') . ': ' . translate('Human_support_only');
        }

        $lines = array_merge($lines, $this->waAiFlowLines((array) ($filters['wa_ai_flows'] ?? [])));

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    private function buildExcludeLines(array $filters): array
    {
        $lines = [];
        $leadTypeLabels = Lead::leadTypes();

        $types = array_values(array_filter((array) ($filters['exclude_lead_types'] ?? [])));
        if ($types !== []) {
            $labels = array_map(fn (string $type) => $leadTypeLabels[$type] ?? ucfirst($type), $types);
            $lines[] = translate('Lead_type') . ': ' . implode(', ', $labels);
        }

        $lines = array_merge($lines, $this->statusLines(
            (array) ($filters['exclude_customer_lead_status_ids'] ?? []),
            CustomerLeadStatus::class,
            translate('Customer_Lead_Status')
        ));

        $lines = array_merge($lines, $this->statusLines(
            (array) ($filters['exclude_provider_lead_status_ids'] ?? []),
            ProviderLeadStatus::class,
            translate('Provider_Lead_Status')
        ));

        $leadOpen = (string) ($filters['exclude_lead_open'] ?? '');
        if ($leadOpen !== '') {
            $lines[] = translate('Lead') . ' ' . translate('Status') . ': ' . ($leadOpen === 'open' ? translate('Open') : translate('Closed'));
        }

        $waBucket = (string) ($filters['exclude_wa_chat_bucket'] ?? '');
        if ($waBucket !== '') {
            $lines[] = translate('WhatsApp') . ' ' . translate('Status') . ': '
                . ($waBucket === 'open' ? translate('whatsapp_bucket_open') : translate('whatsapp_bucket_closed'));
        }

        $lines = array_merge($lines, $this->tagLines(
            (array) ($filters['exclude_wa_chat_tag_ids'] ?? []),
            translate('Voice_cron_wa_chat_tags_label')
        ));

        $handledBy = (string) ($filters['exclude_handled_by'] ?? '');
        if ($handledBy === 'ai') {
            $lines[] = translate('Handled_By') . ': AI';
        } elseif ($handledBy === 'human') {
            $employeeLine = $this->employeeLine((array) ($filters['exclude_handled_by_employee_ids'] ?? []));
            $lines[] = translate('Handled_By') . ': ' . ($employeeLine !== '' ? $employeeLine : translate('name_of_employee'));
        }

        if (($filters['exclude_human_support'] ?? '') === 'exclude') {
            $lines[] = translate('Human_support') . ': ' . translate('Exclude_human_support');
        }

        $lines = array_merge($lines, $this->waAiFlowLines((array) ($filters['exclude_wa_ai_flows'] ?? [])));

        return $lines;
    }

    /**
     * @param  array<int, mixed>  $flows
     * @return array<int, string>
     */
    private function waAiFlowLines(array $flows): array
    {
        $flows = array_values(array_filter($flows));
        if ($flows === []) {
            return [];
        }

        $labels = array_map(
            fn (string $flow) => VoiceCronWaAiFlow::label($flow),
            array_map('strval', $flows)
        );

        return [translate('Voice_cron_wa_ai_flow_label') . ': ' . implode(', ', $labels)];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    private function buildGlobalLines(array $filters): array
    {
        $lines = [];

        $silentValue = (int) ($filters['silent_min_value'] ?? 0);
        $silentUnit = (string) ($filters['silent_min_unit'] ?? 'hours');
        if ($silentValue > 0) {
            $lines[] = translate('Silent_at_least') . ': ' . $silentValue . ' ' . translate($silentUnit);
        }

        $silentMax = $filters['silent_max_hours'] ?? null;
        if ($silentMax !== null && (int) $silentMax > 0) {
            $lines[] = translate('Silent_at_most') . ': ' . (int) $silentMax . 'h';
        }

        $excludeCalled = (int) ($filters['exclude_called_within_hours'] ?? 0);
        if ($excludeCalled > 0) {
            $lines[] = translate('Exclude_called_within') . ': ' . $excludeCalled . 'h';
        }

        $otherMode = (string) ($filters['other_cron_job_mode'] ?? '');
        if ($otherMode === 'exclude_all_active') {
            $lines[] = translate('Voice_cron_other_jobs_exclude_all_active');
        } elseif ($otherMode === 'include' || $otherMode === 'exclude') {
            $ruleIds = array_map('intval', array_filter((array) ($filters['other_cron_job_ids'] ?? [])));
            $ruleNames = $ruleIds !== []
                ? WhatsAppVoiceFollowupAutomationRule::query()->whereIn('id', $ruleIds)->orderBy('name')->pluck('name')->all()
                : [];
            $prefix = $otherMode === 'include'
                ? translate('Voice_cron_other_jobs_include')
                : translate('Voice_cron_other_jobs_exclude');
            $lines[] = $prefix . ($ruleNames !== [] ? ': ' . implode(', ', $ruleNames) : '');
        }

        return $lines;
    }

    /**
     * @param  array<int, mixed>  $ids
     * @param  class-string  $modelClass
     * @return array<int, string>
     */
    private function statusLines(array $ids, string $modelClass, string $label): array
    {
        $ids = array_map('intval', array_filter($ids));
        if ($ids === []) {
            return [];
        }

        $names = $modelClass::query()->whereIn('id', $ids)->orderBy('name')->pluck('name')->all();
        if ($names === []) {
            return [];
        }

        return [$label . ': ' . implode(', ', $names)];
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, string>
     */
    private function tagLines(array $ids, string $label): array
    {
        $ids = array_map('intval', array_filter($ids));
        if ($ids === []) {
            return [];
        }

        $names = WhatsAppChatTag::query()->whereIn('id', $ids)->orderBy('name')->pluck('name')->all();
        if ($names === []) {
            return [];
        }

        return [$label . ': ' . implode(', ', $names)];
    }

    /**
     * @param  array<int, mixed>  $ids
     */
    private function employeeLine(array $ids): string
    {
        $ids = array_values(array_filter(array_map('strval', $ids)));
        if ($ids === []) {
            return '';
        }

        $users = User::query()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name', 'email']);
        $names = $users->map(function (User $user) {
            $full = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            return $full !== '' ? $full : (string) ($user->email ?? $user->id);
        })->filter()->values()->all();

        return implode(', ', $names);
    }
}
