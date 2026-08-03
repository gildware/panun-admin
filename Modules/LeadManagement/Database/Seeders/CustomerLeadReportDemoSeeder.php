<?php

namespace Modules\LeadManagement\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadCancellationReason;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Services\LeadFollowupService;

class CustomerLeadReportDemoSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            'kamran' => 'c0fe84cc-aa67-41ad-80c3-89a718d60728',
            'aalim' => '22111b58-c92d-47ec-8a19-a359b951c166',
            'mehak' => '2252eedf-85a2-49a2-9b9f-80993e51de76',
            'mariya' => '5bc3e98f-0417-4320-bce4-f0843cdd5995',
        ];

        $categories = [
            'carpentry' => '5e5c0fdb-9ad7-4075-bcc1-d7523efde8c6',
            'plumbing' => '5c3bb171-b9eb-4b74-b455-1853da3b4887',
            'appliances' => '028602bc-174a-41f9-b583-ae8f4850e646',
            'painting' => '2d92c399-0709-481d-b499-e6921f3d9217',
        ];

        $zones = [
            'srinagar' => '05a30f62-9fb1-42b5-bb39-d05507166c8c',
            'world' => 'a1614dbe-4732-11ee-9702-dee6e8d77be4',
        ];

        $pendingId = (string) CustomerLeadStatus::defaultPendingStatusId();
        $bookedId = (string) (CustomerLeadStatus::query()->where('base_type', 'completed')->value('id') ?? 2);
        $cancelId = (string) (CustomerLeadStatus::query()->where('base_type', 'cancel')->value('id') ?? 3);

        $reasonPrice = (string) LeadCancellationReason::query()->where('name', 'Price Mismatch')->value('id');
        $reasonAdvance = (string) LeadCancellationReason::query()->where('name', 'like', '%Advance%')->value('id');
        $reasonVisiting = (string) LeadCancellationReason::query()->where('name', 'like', '%Visiting%')->value('id');

        $reasonNoResponse = LeadCancellationReason::firstOrCreate(
            ['name' => 'No Response From Customer'],
            ['description' => 'Customer stopped responding after follow-ups', 'is_active' => true]
        );
        $reasonNoResponseId = (string) $reasonNoResponse->id;

        $followupService = app(LeadFollowupService::class);
        $creator = $staff['kamran'];

        $scenarios = [
            // Carpentry — cancelled mix
            ['name' => 'Demo Carpentry Cancel 1', 'phone' => '9900000001', 'days_ago' => 28, 'handler' => 'aalim', 'cat' => 'carpentry', 'zone' => 'srinagar', 'outcome' => 'cancelled', 'reason' => $reasonPrice, 'remarks' => 'Quoted 8500, customer wanted 5000', 'followups' => [['hours_after' => 4, 'on_time' => true], ['hours_after' => 30, 'on_time' => false]]],
            ['name' => 'Demo Carpentry Cancel 2', 'phone' => '9900000002', 'days_ago' => 25, 'handler' => 'aalim', 'cat' => 'carpentry', 'zone' => 'srinagar', 'outcome' => 'cancelled', 'reason' => $reasonNoResponseId, 'remarks' => 'Called 3 times, no answer', 'followups' => [['hours_after' => 30, 'on_time' => false]]],
            ['name' => 'Demo Carpentry Cancel 3', 'phone' => '9900000003', 'days_ago' => 22, 'handler' => 'kamran', 'cat' => 'carpentry', 'zone' => 'world', 'outcome' => 'cancelled', 'reason' => $reasonAdvance, 'remarks' => 'Refused advance payment', 'followups' => [['hours_after' => 6, 'on_time' => true], ['hours_after' => 48, 'on_time' => true]]],
            ['name' => 'Demo Carpentry Cancel 4', 'phone' => '9900000004', 'days_ago' => 18, 'handler' => 'mehak', 'cat' => 'carpentry', 'zone' => 'srinagar', 'outcome' => 'cancelled', 'reason' => $reasonNoResponseId, 'remarks' => 'Never picked up', 'followups' => []],
            ['name' => 'Demo Carpentry Cancel 5', 'phone' => '9900000005', 'days_ago' => 14, 'handler' => 'mehak', 'cat' => 'carpentry', 'zone' => 'srinagar', 'outcome' => 'cancelled', 'reason' => $reasonPrice, 'remarks' => 'Price too high for wardrobe work', 'followups' => [['hours_after' => 8, 'on_time' => true]]],

            // Carpentry — booked
            ['name' => 'Demo Carpentry Booked 1', 'phone' => '9900000010', 'days_ago' => 20, 'handler' => 'kamran', 'cat' => 'carpentry', 'zone' => 'srinagar', 'outcome' => 'booked', 'followups' => [['hours_after' => 3, 'on_time' => true], ['hours_after' => 24, 'on_time' => true]]],
            ['name' => 'Demo Carpentry Booked 2', 'phone' => '9900000011', 'days_ago' => 12, 'handler' => 'aalim', 'cat' => 'carpentry', 'zone' => 'srinagar', 'outcome' => 'booked', 'followups' => [['hours_after' => 5, 'on_time' => true]]],
            ['name' => 'Demo Carpentry Pending 1', 'phone' => '9900000012', 'days_ago' => 3, 'handler' => 'mariya', 'cat' => 'carpentry', 'zone' => 'world', 'outcome' => 'pending', 'followups' => [['hours_after' => 10, 'on_time' => true]]],

            // Plumbing
            ['name' => 'Demo Plumbing Cancel 1', 'phone' => '9900000020', 'days_ago' => 26, 'handler' => 'mehak', 'cat' => 'plumbing', 'zone' => 'srinagar', 'outcome' => 'cancelled', 'reason' => $reasonVisiting, 'remarks' => 'Did not want visiting charges', 'followups' => [['hours_after' => 2, 'on_time' => true]]],
            ['name' => 'Demo Plumbing Cancel 2', 'phone' => '9900000021', 'days_ago' => 16, 'handler' => 'aalim', 'cat' => 'plumbing', 'zone' => 'srinagar', 'outcome' => 'cancelled', 'reason' => $reasonNoResponseId, 'remarks' => 'WhatsApp seen, no reply', 'followups' => [['hours_after' => 36, 'on_time' => false]]],
            ['name' => 'Demo Plumbing Booked 1', 'phone' => '9900000022', 'days_ago' => 10, 'handler' => 'kamran', 'cat' => 'plumbing', 'zone' => 'srinagar', 'outcome' => 'booked', 'followups' => [['hours_after' => 4, 'on_time' => true]]],
            ['name' => 'Demo Plumbing Booked 2', 'phone' => '9900000023', 'days_ago' => 7, 'handler' => 'mariya', 'cat' => 'plumbing', 'zone' => 'world', 'outcome' => 'booked', 'followups' => [['hours_after' => 6, 'on_time' => true], ['hours_after' => 20, 'on_time' => true]]],
            ['name' => 'Demo Plumbing Pending 1', 'phone' => '9900000024', 'days_ago' => 2, 'handler' => 'aalim', 'cat' => 'plumbing', 'zone' => 'srinagar', 'outcome' => 'pending', 'followups' => []],

            // Home appliances
            ['name' => 'Demo Appliance Cancel 1', 'phone' => '9900000030', 'days_ago' => 24, 'handler' => 'mariya', 'cat' => 'appliances', 'zone' => 'srinagar', 'outcome' => 'cancelled', 'reason' => $reasonPrice, 'remarks' => 'AC repair quote rejected', 'followups' => [['hours_after' => 12, 'on_time' => true]]],
            ['name' => 'Demo Appliance Cancel 2', 'phone' => '9900000031', 'days_ago' => 15, 'handler' => 'kamran', 'cat' => 'appliances', 'zone' => 'world', 'outcome' => 'cancelled', 'reason' => $reasonNoResponseId, 'remarks' => 'No callback after quote', 'followups' => [['hours_after' => 28, 'on_time' => false]]],
            ['name' => 'Demo Appliance Booked 1', 'phone' => '9900000032', 'days_ago' => 9, 'handler' => 'mehak', 'cat' => 'appliances', 'zone' => 'srinagar', 'outcome' => 'booked', 'followups' => [['hours_after' => 3, 'on_time' => true]]],
            ['name' => 'Demo Appliance Booked 2', 'phone' => '9900000033', 'days_ago' => 5, 'handler' => 'aalim', 'cat' => 'appliances', 'zone' => 'srinagar', 'outcome' => 'booked', 'followups' => [['hours_after' => 7, 'on_time' => true]]],
            ['name' => 'Demo Appliance Pending 1', 'phone' => '9900000034', 'days_ago' => 1, 'handler' => 'kamran', 'cat' => 'appliances', 'zone' => 'srinagar', 'outcome' => 'pending', 'followups' => [['hours_after' => 5, 'on_time' => true]]],

            // Painting
            ['name' => 'Demo Painting Cancel 1', 'phone' => '9900000040', 'days_ago' => 21, 'handler' => 'aalim', 'cat' => 'painting', 'zone' => 'world', 'outcome' => 'cancelled', 'reason' => $reasonAdvance, 'remarks' => 'Would not pay booking amount', 'followups' => [['hours_after' => 5, 'on_time' => true], ['hours_after' => 26, 'on_time' => true]]],
            ['name' => 'Demo Painting Cancel 2', 'phone' => '9900000041', 'days_ago' => 11, 'handler' => 'mehak', 'cat' => 'painting', 'zone' => 'srinagar', 'outcome' => 'cancelled', 'reason' => $reasonNoResponseId, 'remarks' => 'Stopped responding mid-quote', 'followups' => []],
            ['name' => 'Demo Painting Booked 1', 'phone' => '9900000042', 'days_ago' => 8, 'handler' => 'kamran', 'cat' => 'painting', 'zone' => 'srinagar', 'outcome' => 'booked', 'followups' => [['hours_after' => 4, 'on_time' => true]]],
            ['name' => 'Demo Painting Pending 1', 'phone' => '9900000043', 'days_ago' => 4, 'handler' => 'mariya', 'cat' => 'painting', 'zone' => 'srinagar', 'outcome' => 'pending', 'followups' => [['hours_after' => 15, 'on_time' => true]]],
        ];

        foreach ($scenarios as $index => $scenario) {
            $receivedAt = Carbon::now()->subDays($scenario['days_ago'])->setTime(9 + ($index % 8), ($index * 7) % 60, 0);

            $lead = Lead::create([
                'name' => $scenario['name'],
                'phone_number' => $scenario['phone'],
                'source_id' => ($index % 3) + 1,
                'ad_source_id' => ($index % 2) + 1,
                'lead_type' => Lead::TYPE_CUSTOMER,
                'date_time_of_lead_received' => $receivedAt,
                'handled_by' => $staff[$scenario['handler']],
                'remarks' => 'Demo lead for report preview — ' . $scenario['name'],
                'next_followup_at' => $scenario['outcome'] === 'pending'
                    ? $followupService->defaultNextFollowupAt()
                    : null,
                'created_by' => $creator,
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            $statusId = match ($scenario['outcome']) {
                'booked' => $bookedId,
                'cancelled' => $cancelId,
                default => $pendingId,
            };

            $data = [
                'customer_lead_status_id' => $statusId,
                'booking_status' => match ($scenario['outcome']) {
                    'booked' => 'booked',
                    'cancelled' => 'cancelled',
                    default => 'pending',
                },
                'service_category' => $categories[$scenario['cat']],
                'zone_id' => $zones[$scenario['zone']],
                'service_description' => 'Demo service request for ' . $scenario['cat'],
            ];

            if ($scenario['outcome'] === 'cancelled') {
                $data['cancellation_reason_id'] = $scenario['reason'];
                $data['cancellation_remarks'] = $scenario['remarks'] ?? null;
            }

            LeadTypeHistory::create([
                'lead_id' => $lead->id,
                'type' => Lead::TYPE_CUSTOMER,
                'data' => $data,
                'created_by' => $staff[$scenario['handler']],
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            $slaDue = $followupService->defaultNextFollowupAt($receivedAt);
            $previousDue = $slaDue;

            foreach ($scenario['followups'] ?? [] as $fuIndex => $fu) {
                $followupAt = $receivedAt->copy()->addHours($fu['hours_after']);
                $nextDue = $followupAt->copy()->addDay()->setTime(10, 0, 0);

                LeadFollowup::create([
                    'lead_id' => $lead->id,
                    'followup_at' => $followupAt,
                    'remarks' => 'Demo follow-up #' . ($fuIndex + 1) . ' — called customer',
                    'urgency' => LeadFollowup::URGENCY_MEDIUM,
                    'next_followup_at' => $nextDue,
                    'created_by' => $staff[$scenario['handler']],
                    'created_at' => $followupAt,
                    'updated_at' => $followupAt,
                ]);

                $previousDue = $nextDue;
            }
        }

        $this->command?->info('Seeded ' . count($scenarios) . ' demo customer leads for reports.');
    }
}
