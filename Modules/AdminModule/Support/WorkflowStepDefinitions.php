<?php

namespace Modules\AdminModule\Support;

/**
 * Single source of truth for lead & booking workflow steps.
 * Training guides and live panel guidance both read from here.
 */
class WorkflowStepDefinitions
{
    public const ACTION_LEAD_TYPE_CHANGE = 'lead.type_change';

    public const ACTION_LEAD_PANEL_UPDATED = 'lead.panel_updated';

    public const ACTION_LEAD_CREATE_BOOKING = 'lead.create_booking';

    public const ACTION_LEAD_STATUS_BOOKED = 'lead.status_booked';

    public const ACTION_BOOKING_COMPLETED = 'booking.completed';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function steps(): array
    {
        return [
            // —— Unknown lead ——
            'lead.unknown.call' => [
                'label' => 'Outbound call — ask what they need',
                'detail' => 'Run the lead qualifier on the call. Do not leave as Unknown after a successful call.',
                'manual' => true,
                'auto' => 'lead_has_outbound_contact',
                'gates' => [self::ACTION_LEAD_TYPE_CHANGE],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-unknowns'],
            ],
            'lead.unknown.panel_whatsapp' => [
                'label' => 'WhatsApp customer — same minute as panel update',
                'detail' => 'You updated type and remarks — now send the WhatsApp summary before moving to the next lead.',
                'manual' => true,
                'gates' => [self::ACTION_LEAD_PANEL_UPDATED],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-unknowns'],
            ],
            'lead.unknown.log_followup' => [
                'label' => 'Log follow-up in Activity tab + set next date',
                'detail' => 'Add follow-up → Taken (or Reschedule). Set Followup On on the lead if another touch is needed.',
                'manual' => false,
                'auto' => 'lead_has_followup_logged',
                'gates' => [self::ACTION_LEAD_PANEL_UPDATED],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-unknowns'],
            ],
            'lead.unknown.reclassify' => [
                'label' => 'Update panel — type + remarks from call',
                'detail' => 'Mark as Customer, Provider, Future, or Invalid and fill remarks from your call notes. Do this right after hang-up.',
                'manual' => false,
                'auto' => 'lead_not_unknown',
                'gates' => [self::ACTION_LEAD_TYPE_CHANGE],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-unknowns'],
            ],

            // —— Customer lead (shared) ——
            'lead.customer.call' => [
                'label' => 'Call customer — service, problem, address, date/time',
                'detail' => 'Collect full job details on the call. Notes on paper only during call.',
                'manual' => false,
                'auto' => 'lead_has_qualification_data',
                'gates' => [self::ACTION_LEAD_CREATE_BOOKING, self::ACTION_LEAD_STATUS_BOOKED],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-customers'],
            ],
            'lead.customer.panel_whatsapp' => [
                'label' => 'Update panel + WhatsApp summary to customer',
                'detail' => 'Customer type, full remarks, Followup On if waiting on provider.',
                'manual' => true,
                'gates' => [self::ACTION_LEAD_CREATE_BOOKING],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-customers'],
            ],
            'lead.customer.path_decided' => [
                'label' => 'Path A or Path B decided and noted in remarks',
                'detail' => 'Path A = direct booking. Path B = customer wants provider discussion first.',
                'manual' => false,
                'auto' => 'lead_path_decided',
                'gates' => [self::ACTION_LEAD_CREATE_BOOKING],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-customers'],
            ],
            'lead.customer.provider_group' => [
                'label' => 'Posted in provider WhatsApp group',
                'detail' => 'Standard English format with Lead ID. 10-minute SLA for reply.',
                'manual' => true,
                'gates' => [self::ACTION_LEAD_CREATE_BOOKING, self::ACTION_LEAD_STATUS_BOOKED],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-customers'],
            ],
            'lead.customer.advance_100' => [
                'label' => '₹100 collected from customer',
                'detail' => 'Collect before Create Booking — no exceptions on Path A/B.',
                'manual' => true,
                'gates' => [self::ACTION_LEAD_CREATE_BOOKING],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-customers'],
            ],
            'lead.customer.path_b_discussion' => [
                'label' => 'Provider discussion / conference call done',
                'detail' => 'Path B only — customer spoke with provider before booking.',
                'manual' => true,
                'scenarios' => ['lead.customer.path_b'],
                'gates' => [self::ACTION_LEAD_CREATE_BOOKING],
                'training' => ['guide' => 'lead-qualification', 'slide' => 'handling-customers'],
            ],
            'lead.customer.create_booking' => [
                'label' => 'Create Booking for this Lead',
                'detail' => 'Preview → verify zone, provider, cart → Store.',
                'manual' => false,
                'auto' => 'lead_has_booking',
                'gates' => [self::ACTION_LEAD_STATUS_BOOKED],
                'training' => ['guide' => 'booking-followup', 'slide' => 'create-booking'],
            ],

            // —— Booking active ——
            'booking.post_create.confirm_whatsapp' => [
                'label' => 'Confirm WhatsApp sent to customer and provider',
                'detail' => 'Both parties must know booking ID, time, and address.',
                'manual' => true,
                'gates' => [],
                'training' => ['guide' => 'booking-followup', 'slide' => 'create-booking'],
            ],
            'booking.post_create.first_followup' => [
                'label' => 'Add first follow-up in panel',
                'detail' => 'Same day as create — date, For (customer/provider), reason.',
                'manual' => false,
                'auto' => 'booking_has_followup',
                'gates' => [],
                'training' => ['guide' => 'booking-followup', 'slide' => 'create-booking'],
            ],
            'booking.touchpoint.booking_day' => [
                'label' => 'Touchpoint 1 — confirm at booking (same day)',
                'detail' => 'Call to confirm date, time, address, provider. WhatsApp both parties.',
                'manual' => true,
                'gates' => [],
                'training' => ['guide' => 'booking-followup', 'slide' => 'follow-up'],
            ],
            'booking.touchpoint.day_before' => [
                'label' => 'Touchpoint 2 — one day before service',
                'detail' => 'Provider first: still available? Customer only if needed.',
                'manual' => true,
                'gates' => [],
                'training' => ['guide' => 'booking-followup', 'slide' => 'follow-up'],
            ],
            'booking.touchpoint.service_day' => [
                'label' => 'Touchpoint 3 — service day check',
                'detail' => 'Morning + ~1 hr before: provider reached/started → Ongoing.',
                'manual' => true,
                'gates' => [],
                'training' => ['guide' => 'booking-followup', 'slide' => 'follow-up'],
            ],

            // —— Booking close ——
            'booking.close.provider_bill' => [
                'label' => 'Full bill breakdown from provider',
                'detail' => 'Service charge + each part name and charge — nothing vague.',
                'manual' => true,
                'gates' => [self::ACTION_BOOKING_COMPLETED],
                'training' => ['guide' => 'booking-followup', 'slide' => 'payment-checklist'],
            ],
            'booking.close.panel_bill' => [
                'label' => 'Bill entered correctly in panel',
                'detail' => 'Due balance must reflect real invoice before Completed.',
                'manual' => true,
                'gates' => [self::ACTION_BOOKING_COMPLETED],
                'training' => ['guide' => 'booking-followup', 'slide' => 'payment-checklist'],
            ],
            'booking.close.customer_confirm' => [
                'label' => 'Customer confirmed billing matches',
                'detail' => 'Call customer — confirm total, service, and parts charges.',
                'manual' => true,
                'gates' => [self::ACTION_BOOKING_COMPLETED],
                'training' => ['guide' => 'booking-followup', 'slide' => 'payment-checklist'],
            ],
            'booking.close.due_zero' => [
                'label' => 'Due balance is zero',
                'detail' => 'All payments recorded — company vs provider split correct.',
                'manual' => false,
                'auto' => 'booking_due_zero',
                'gates' => [self::ACTION_BOOKING_COMPLETED],
                'training' => ['guide' => 'booking-followup', 'slide' => 'payment-checklist'],
            ],
        ];
    }

    /**
     * Ordered step keys per workflow scenario.
     *
     * @return array<string, array<int, string>>
     */
    public static function scenarios(): array
    {
        return [
            'lead.unknown' => [
                'lead.unknown.call',
                'lead.unknown.reclassify',
                'lead.unknown.panel_whatsapp',
                'lead.unknown.log_followup',
            ],
            'lead.customer.path_a' => [
                'lead.customer.call',
                'lead.customer.panel_whatsapp',
                'lead.customer.path_decided',
                'lead.customer.provider_group',
                'lead.customer.advance_100',
                'lead.customer.create_booking',
            ],
            'lead.customer.path_b' => [
                'lead.customer.call',
                'lead.customer.panel_whatsapp',
                'lead.customer.path_decided',
                'lead.customer.provider_group',
                'lead.customer.path_b_discussion',
                'lead.customer.advance_100',
                'lead.customer.create_booking',
            ],
            'lead.customer.booked' => [
                'lead.customer.create_booking',
            ],
            'booking.active' => [
                'booking.post_create.confirm_whatsapp',
                'booking.post_create.first_followup',
                'booking.touchpoint.booking_day',
                'booking.touchpoint.day_before',
                'booking.touchpoint.service_day',
            ],
            'booking.close' => [
                'booking.close.provider_bill',
                'booking.close.panel_bill',
                'booking.close.customer_confirm',
                'booking.close.due_zero',
            ],
        ];
    }

    /**
     * Required step keys before an action (hard = must be done; soft = confirm in modal).
     *
     * @return array<string, array{hard: array<int, string>, soft: array<int, string>}>
     */
    public static function actionRequirements(): array
    {
        return [
            self::ACTION_LEAD_TYPE_CHANGE => [
                'hard' => ['lead.unknown.call'],
                'soft' => [],
            ],
            self::ACTION_LEAD_PANEL_UPDATED => [
                'hard' => [],
                'soft' => ['lead.unknown.panel_whatsapp', 'lead.unknown.log_followup'],
            ],
            self::ACTION_LEAD_CREATE_BOOKING => [
                'hard' => ['lead.customer.call', 'lead.customer.path_decided'],
                'soft' => ['lead.customer.panel_whatsapp', 'lead.customer.provider_group', 'lead.customer.advance_100'],
            ],
            self::ACTION_LEAD_STATUS_BOOKED => [
                'hard' => ['lead.customer.create_booking'],
                'soft' => ['lead.customer.provider_group'],
            ],
            self::ACTION_BOOKING_COMPLETED => [
                'hard' => ['booking.close.due_zero'],
                'soft' => ['booking.close.provider_bill', 'booking.close.panel_bill', 'booking.close.customer_confirm'],
            ],
        ];
    }

    public static function step(string $key): ?array
    {
        $steps = self::steps();

        return $steps[$key] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function scenarioStepKeys(string $scenario): array
    {
        $keys = self::scenarios()[$scenario] ?? [];
        $filtered = [];
        foreach ($keys as $key) {
            $def = self::step($key);
            if ($def === null) {
                continue;
            }
            $only = $def['scenarios'] ?? null;
            if ($only !== null && ! in_array($scenario, $only, true)) {
                continue;
            }
            $filtered[] = $key;
        }

        return $filtered;
    }

    /**
     * Build training path_steps groups — keeps training in sync with live workflow.
     *
     * @return array<int, array{label: string, steps: array<int, array<string, mixed>>}>
     */
    public static function trainingPathSteps(string $scenario): array
    {
        $groups = self::trainingPathGroups()[$scenario] ?? null;
        if ($groups === null) {
            return [];
        }

        $out = [];
        foreach ($groups as $group) {
            $steps = [];
            foreach ($group['step_keys'] as $key) {
                $def = self::step($key);
                if ($def === null) {
                    continue;
                }
                $steps[] = self::toTrainingStep($key, $def);
            }
            if ($steps === []) {
                continue;
            }
            $out[] = [
                'label' => $group['label'],
                'steps' => $steps,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array<int, array{label: string, step_keys: array<int, string>}>>
     */
    private static function trainingPathGroups(): array
    {
        return [
            'lead.customer.path_a' => [
                [
                    'label' => 'Path A — Direct booking (from workflow)',
                    'step_keys' => [
                        'lead.customer.call',
                        'lead.customer.panel_whatsapp',
                        'lead.customer.path_decided',
                        'lead.customer.provider_group',
                        'lead.customer.advance_100',
                        'lead.customer.create_booking',
                    ],
                ],
            ],
            'lead.customer.path_b' => [
                [
                    'label' => 'Path B — Discussion first (from workflow)',
                    'step_keys' => [
                        'lead.customer.call',
                        'lead.customer.panel_whatsapp',
                        'lead.customer.path_decided',
                        'lead.customer.provider_group',
                        'lead.customer.path_b_discussion',
                        'lead.customer.advance_100',
                        'lead.customer.create_booking',
                    ],
                ],
            ],
            'booking.post_create' => [
                [
                    'label' => 'Immediately after save (from workflow)',
                    'step_keys' => [
                        'booking.post_create.confirm_whatsapp',
                        'booking.post_create.first_followup',
                    ],
                ],
            ],
            'booking.touchpoints' => [
                [
                    'label' => 'Three touchpoints (from workflow)',
                    'step_keys' => [
                        'booking.touchpoint.booking_day',
                        'booking.touchpoint.day_before',
                        'booking.touchpoint.service_day',
                    ],
                ],
            ],
            'booking.close' => [
                [
                    'label' => 'Before Completed (from workflow)',
                    'step_keys' => [
                        'booking.close.provider_bill',
                        'booking.close.panel_bill',
                        'booking.close.customer_confirm',
                        'booking.close.due_zero',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array{text: string, detail?: string}
     */
    public static function toTrainingStep(string $key, array $def): array
    {
        $step = ['text' => $def['label'] ?? $key];
        if (! empty($def['detail'])) {
            $step['detail'] = $def['detail'];
        }
        if (! empty($def['manual'])) {
            $step['detail'] = trim(($step['detail'] ?? '').' Panel checkbox on lead/booking page.');
        }

        return $step;
    }

    /**
     * Flat checklist items for training slides of type checklist.
     *
     * @return array<int, array{title: string, body: string}>
     */
    public static function trainingChecklistItems(string $scenario): array
    {
        $items = [];
        foreach (self::scenarioStepKeys($scenario) as $key) {
            $def = self::step($key);
            if ($def === null) {
                continue;
            }
            $items[] = [
                'title' => $def['label'],
                'body' => $def['detail'] ?? '',
            ];
        }

        return $items;
    }

    /**
     * Gate confirmation prompts keyed by action.
     *
     * @return array<int, array{key: string, label: string, detail: string, hard: bool}>
     */
    public static function confirmationPrompts(string $action, array $pendingStepKeys): array
    {
        $reqs = self::actionRequirements()[$action] ?? ['hard' => [], 'soft' => []];
        $prompts = [];
        foreach ($pendingStepKeys as $key) {
            $def = self::step($key);
            if ($def === null) {
                continue;
            }
            $prompts[] = [
                'key' => $key,
                'label' => $def['label'],
                'detail' => $def['detail'] ?? '',
                'hard' => in_array($key, $reqs['hard'], true),
            ];
        }

        return $prompts;
    }
}
