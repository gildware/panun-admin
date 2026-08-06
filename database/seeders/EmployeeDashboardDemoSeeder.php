<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\BookingModule\Entities\BookingStatusHistory;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadTypeHistory;
use Modules\LeadManagement\Entities\Source;
use Modules\ProviderManagement\Entities\Provider;
use Modules\TaskBoardModule\Entities\TaskColumn;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Entities\WhatsAppChatStatus;
use Modules\WhatsAppModule\Entities\WhatsAppChatThreadMeta;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Seeds demo data for the Employee dashboard and related admin lists.
 *
 * Run: php artisan db:seed --class=EmployeeDashboardDemoSeeder
 */
class EmployeeDashboardDemoSeeder extends Seeder
{
    private const DEMO_EMAIL = 'employee.demo@panunkaergar.com';

    private const PHONE_PREFIX = '+19970000';

    private const BOOKING_MARKER = '[EMP-DEMO]';

    private const TASK_MARKER = '[EMP-DEMO]';

    public function run(): void
    {
        if (! Schema::hasTable('leads')) {
            $this->command?->warn('Leads table missing; skipping EmployeeDashboardDemoSeeder.');

            return;
        }

        $demoEmployee = User::query()->where('email', self::DEMO_EMAIL)->first();
        if (! $demoEmployee) {
            $this->command?->error('Demo employee not found. Run the Employee role migration first.');

            return;
        }

        $otherEmployees = User::query()
            ->where('user_type', 'admin-employee')
            ->where('is_active', 1)
            ->where('id', '!=', $demoEmployee->id)
            ->orderBy('email')
            ->limit(2)
            ->get();

        $this->purgeDemoData();

        $sourceId = Source::query()->where('is_active', true)->orderBy('id')->value('id');
        $pendingStatusId = CustomerLeadStatus::defaultPendingStatusId();
        $zoneId = Zone::query()->ofStatus(1)->orderBy('name')->value('id');
        $provider = Provider::query()->first();
        $customer = User::query()->inCustomerDirectory()->first();
        $now = Carbon::now();
        $today = Carbon::today();

        DB::transaction(function () use (
            $demoEmployee,
            $otherEmployees,
            $sourceId,
            $pendingStatusId,
            $zoneId,
            $provider,
            $customer,
            $now,
            $today,
        ) {
            $this->seedLeads($demoEmployee, $sourceId, $pendingStatusId, $zoneId, $today, $now);
            $this->seedBookings($demoEmployee, $otherEmployees, $provider, $customer, $zoneId, $today, $now);
            $this->seedTasks($demoEmployee, $today, $now);
            $this->seedWhatsApp($demoEmployee, $now);
            $this->seedTodayActivity($demoEmployee, $today, $now);
        });

        $service = app(\Modules\AdminModule\Services\EmployeeDashboardService::class);
        $dashboard = $service->build($demoEmployee);

        $this->command?->info(sprintf(
            'Employee dashboard demo ready for %s — overdue: %d, due today: %d, upcoming: %d, open assigned: %d',
            self::DEMO_EMAIL,
            $dashboard['pulse']['overdue'],
            $dashboard['pulse']['due_today'],
            $dashboard['pulse']['upcoming'],
            $dashboard['pulse']['open_assigned'],
        ));
        $this->command?->info('Login: /admin/auth/login — Email: '.self::DEMO_EMAIL.' / Password: Employee@2026');
    }

    private function seedLeads(
        User $employee,
        ?int $sourceId,
        ?int $pendingStatusId,
        ?string $zoneId,
        Carbon $today,
        Carbon $now,
    ): void {
        $employeeId = (string) $employee->id;
        $plans = [
            ['suffix' => '001', 'name' => 'Emp Demo — Missed AC Repair', 'followup' => $today->copy()->subDays(2)->setTime(10, 0)],
            ['suffix' => '002', 'name' => 'Emp Demo — Missed Plumbing', 'followup' => $today->copy()->subDay()->setTime(15, 30)],
            ['suffix' => '003', 'name' => 'Emp Demo — Today Geyser', 'followup' => $today->copy()->setTime(11, 0)],
            ['suffix' => '004', 'name' => 'Emp Demo — Today Cleaning', 'followup' => $today->copy()->setTime(16, 0)],
            ['suffix' => '005', 'name' => 'Emp Demo — Upcoming Painting', 'followup' => $today->copy()->addDays(2)->setTime(12, 0)],
            ['suffix' => '006', 'name' => 'Emp Demo — Upcoming CCTV', 'followup' => $today->copy()->addDays(5)->setTime(9, 30)],
            ['suffix' => '007', 'name' => 'Emp Demo — Pending No Followup', 'followup' => null],
            ['suffix' => '008', 'name' => 'Emp Demo — Pending Callback', 'followup' => null],
            ['suffix' => '009', 'name' => '', 'followup' => $today->copy()->addDay()->setTime(14, 0), 'missing' => 'name'],
            ['suffix' => '010', 'name' => 'Emp Demo — Missing Phone', 'followup' => null, 'missing' => 'phone', 'phone' => ''],
            ['suffix' => '013', 'name' => 'Emp Demo — Missing Source', 'followup' => null, 'missing' => 'source'],
            ['suffix' => '011', 'name' => 'Emp Demo — Unassigned Pool A', 'followup' => $today->copy()->addDay(), 'unassigned' => true],
            ['suffix' => '012', 'name' => 'Emp Demo — Unassigned Pool B', 'followup' => null, 'unassigned' => true],
        ];

        foreach ($plans as $index => $plan) {
            $phone = array_key_exists('phone', $plan)
                ? $plan['phone']
                : self::PHONE_PREFIX.str_pad($plan['suffix'], 4, '0', STR_PAD_LEFT);
            $receivedAt = $now->copy()->subDays(3 + ($index % 5))->setTime(9 + ($index % 6), 0);
            $handledBy = ! empty($plan['unassigned']) ? null : $employeeId;

            $leadId = DB::table('leads')->insertGetId([
                'name' => ($plan['missing'] ?? '') === 'name' ? null : $plan['name'],
                'phone_number' => ($plan['missing'] ?? '') === 'phone' ? '' : $phone,
                'source_id' => ($plan['missing'] ?? '') === 'source' ? null : $sourceId,
                'lead_type' => Lead::TYPE_CUSTOMER,
                'date_time_of_lead_received' => $receivedAt,
                'handled_by' => $handledBy,
                'remarks' => 'Employee dashboard demo lead.',
                'next_followup_at' => $plan['followup'] ?? null,
                'created_by' => $employeeId,
                'created_at' => $receivedAt,
                'updated_at' => $now,
            ]);

            if ($pendingStatusId && Schema::hasTable('lead_type_histories')) {
                LeadTypeHistory::create([
                    'lead_id' => $leadId,
                    'type' => Lead::TYPE_CUSTOMER,
                    'data' => [
                        'customer_lead_status_id' => $pendingStatusId,
                        'booking_status' => 'pending',
                        'zone_id' => $zoneId,
                        'service_description' => 'Demo open customer lead for employee dashboard.',
                    ],
                    'created_by' => $employeeId,
                ]);
            }
        }
    }

    private function seedBookings(
        User $demoEmployee,
        $otherEmployees,
        ?Provider $provider,
        ?User $customer,
        ?string $zoneId,
        Carbon $today,
        Carbon $now,
    ): void {
        if (! $provider || ! $customer || ! $zoneId) {
            $this->command?->warn('Skipping booking demo rows (missing provider, customer, or zone).');

            return;
        }

        $demoId = (string) $demoEmployee->id;
        $bookingPlans = [
            ['tag' => 'missed-followup', 'status' => 'ongoing', 'assignee' => $demoId, 'followup' => $today->copy()->subDays(2)],
            ['tag' => 'today-followup', 'status' => 'accepted', 'assignee' => $demoId, 'followup' => $today->copy()],
            ['tag' => 'upcoming-followup', 'status' => 'pending', 'assignee' => $demoId, 'followup' => $today->copy()->addDays(3)],
            ['tag' => 'active-no-followup', 'status' => 'on_hold', 'assignee' => $demoId, 'followup' => null],
            ['tag' => 'unassigned-pool', 'status' => 'pending', 'assignee' => null, 'followup' => $today->copy()->addDay()],
            ['tag' => 'completed-mtd', 'status' => 'completed', 'assignee' => $demoId, 'followup' => null],
            ['tag' => 'cancelled-mtd', 'status' => 'canceled', 'assignee' => $demoId, 'followup' => null],
        ];

        foreach ($bookingPlans as $index => $plan) {
            $booking = Booking::query()->create([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'assignee_id' => $plan['assignee'],
                'booking_status' => $plan['status'],
                'payment_method' => 'cash_after_service',
                'is_paid' => $plan['status'] === 'completed' ? 1 : 0,
                'total_booking_amount' => 1500 + ($index * 250),
                'total_tax_amount' => 0,
                'total_discount_amount' => 0,
                'service_location' => 'customer',
                'service_schedule' => $now->copy()->addDays($index)->setTime(10, 0),
                'service_description' => self::BOOKING_MARKER.' '.$plan['tag'],
                'booking_source' => 'admin_seed',
                'is_verified' => 1,
                'is_checked' => 1,
                'created_at' => $now->copy()->subDays(5 - min(4, $index)),
                'updated_at' => in_array($plan['status'], ['completed', 'canceled'], true)
                    ? $now->copy()->subDays(1)
                    : $now,
            ]);

            BookingStatusHistory::query()->create([
                'booking_id' => $booking->id,
                'changed_by' => $demoId,
                'booking_status' => $plan['status'],
                'status_change_remarks' => 'Employee dashboard demo booking.',
            ]);

            if ($plan['followup']) {
                BookingFollowup::query()->create([
                    'booking_id' => $booking->id,
                    'date' => $plan['followup']->toDateString(),
                    'followup_at' => $plan['followup'],
                    'status' => 'scheduled',
                    'reason' => 'Demo scheduled follow-up',
                    'for' => 'customer',
                    'urgency' => BookingFollowup::URGENCY_MEDIUM,
                    'created_by' => $plan['assignee'] ?? $demoId,
                    'created_at' => $now->copy()->subDay(),
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ($otherEmployees as $peer) {
            Booking::query()->create([
                'customer_id' => $customer->id,
                'provider_id' => $provider->id,
                'zone_id' => $zoneId,
                'assignee_id' => (string) $peer->id,
                'booking_status' => 'completed',
                'payment_method' => 'digital_payment',
                'is_paid' => 1,
                'total_booking_amount' => 900,
                'total_tax_amount' => 0,
                'total_discount_amount' => 0,
                'service_location' => 'customer',
                'service_schedule' => $now->copy()->subDays(2),
                'service_description' => self::BOOKING_MARKER.' peer-completed',
                'booking_source' => 'admin_seed',
                'is_verified' => 1,
                'is_checked' => 1,
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDay(),
            ]);
        }
    }

    private function seedTasks(User $employee, Carbon $today, Carbon $now): void
    {
        if (! Schema::hasTable('task_tickets')) {
            return;
        }

        $todoColumn = TaskColumn::query()
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%done%'])
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%complete%'])
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%closed%'])
            ->orderBy('position')
            ->first();

        if (! $todoColumn) {
            return;
        }

        $employeeId = (string) $employee->id;
        $taskPlans = [
            ['title' => 'Call missed AC lead', 'end' => $today->copy()->subDays(2)],
            ['title' => 'Confirm geyser visit slot', 'end' => $today->copy()],
            ['title' => 'Update provider onboarding docs', 'end' => $today->copy()->addDays(4)],
        ];

        foreach ($taskPlans as $index => $plan) {
            $ticket = TaskTicket::query()->create([
                'column_id' => $todoColumn->id,
                'title' => self::TASK_MARKER.' '.$plan['title'],
                'description' => 'Demo task for employee dashboard.',
                'start_date' => $today->copy()->subDay(),
                'end_date' => $plan['end']->toDateString(),
                'position' => $index,
                'created_by' => $employeeId,
            ]);

            DB::table('task_ticket_assignees')->insert([
                'ticket_id' => $ticket->id,
                'user_id' => $employeeId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedWhatsApp(User $employee, Carbon $now): void
    {
        if (! Schema::hasTable('whatsapp_users') || ! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        $employeeId = (string) $employee->id;
        $openStatusId = WhatsAppChatStatus::query()->where('bucket', '!=', 'closed')->orderBy('id')->value('id');

        $threads = [
            ['suffix' => '101', 'name' => 'Emp Demo — Pending Reply'],
            ['suffix' => '102', 'name' => 'Emp Demo — Quote Follow-up'],
            ['suffix' => '103', 'name' => 'Emp Demo — Reschedule Request'],
        ];

        foreach ($threads as $thread) {
            $phone = self::PHONE_PREFIX.str_pad($thread['suffix'], 4, '0', STR_PAD_LEFT);
            $digits = ltrim($phone, '+');

            WhatsAppUser::query()->updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $thread['name'],
                    'handled_by' => $employeeId,
                    'updated_at' => $now,
                ]
            );

            if (! WhatsAppMessage::query()->where('phone', $digits)->exists()) {
                WhatsAppMessage::query()->create([
                    'channel' => 'whatsapp',
                    'phone' => $digits,
                    'message_text' => 'Hi, I need help with a service booking.',
                    'direction' => 'IN',
                    'message_type' => 'text',
                    'wa_message_id' => 'emp-demo-in-'.$thread['suffix'],
                    'created_at' => $now->copy()->subHours(3),
                ]);
            }

            if ($openStatusId && Schema::hasTable('whatsapp_chat_thread_meta')) {
                WhatsAppChatThreadMeta::query()->updateOrCreate(
                    ['phone' => $phone],
                    [
                        'whatsapp_chat_status_id' => $openStatusId,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    private function seedTodayActivity(User $employee, Carbon $today, Carbon $now): void
    {
        if (! Schema::hasTable('lead_followups')) {
            return;
        }

        $employeeId = (string) $employee->id;
        $leadIds = Lead::query()
            ->where('handled_by', $employeeId)
            ->where('phone_number', 'like', self::PHONE_PREFIX.'%')
            ->orderBy('id')
            ->limit(3)
            ->pluck('id');

        foreach ($leadIds as $leadId) {
            LeadFollowup::query()->create([
                'lead_id' => $leadId,
                'followup_at' => $today->copy()->setTime(10, 30),
                'remarks' => 'Demo follow-up logged today.',
                'followup_status' => LeadFollowup::STATUS_TAKEN,
                'contact_channel' => LeadFollowup::CHANNEL_CALL,
                'created_by' => $employeeId,
                'created_at' => $today->copy()->setTime(10, 35),
                'updated_at' => $today->copy()->setTime(10, 35),
            ]);
        }

        $booking = Booking::query()
            ->where('assignee_id', $employeeId)
            ->where('service_description', 'like', self::BOOKING_MARKER.'%')
            ->first();

        if ($booking) {
            BookingFollowup::query()->create([
                'booking_id' => $booking->id,
                'date' => $today->toDateString(),
                'followup_at' => $today->copy()->setTime(14, 0),
                'status' => BookingFollowup::ACTION_TAKEN,
                'reason' => 'Called customer — confirmed visit time.',
                'for' => 'customer',
                'contact_channel' => BookingFollowup::CHANNEL_CALL,
                'created_by' => $employeeId,
                'created_at' => $today->copy()->setTime(14, 5),
                'updated_at' => $today->copy()->setTime(14, 5),
            ]);
        }
    }

    private function purgeDemoData(): void
    {
        $leadIds = DB::table('leads')
            ->where(function ($query) {
                $query->where('phone_number', 'like', self::PHONE_PREFIX.'%')
                    ->orWhere('remarks', 'Employee dashboard demo lead.');
            })
            ->pluck('id')
            ->all();

        if ($leadIds !== []) {
            if (Schema::hasTable('lead_followups')) {
                DB::table('lead_followups')->whereIn('lead_id', $leadIds)->delete();
            }
            if (Schema::hasTable('lead_type_histories')) {
                DB::table('lead_type_histories')->whereIn('lead_id', $leadIds)->delete();
            }
            DB::table('leads')->whereIn('id', $leadIds)->delete();
        }

        $bookingIds = Booking::query()
            ->where('service_description', 'like', self::BOOKING_MARKER.'%')
            ->pluck('id')
            ->all();

        if ($bookingIds !== []) {
            if (Schema::hasTable('booking_followups')) {
                DB::table('booking_followups')->whereIn('booking_id', $bookingIds)->delete();
            }
            if (Schema::hasTable('booking_status_histories')) {
                DB::table('booking_status_histories')->whereIn('booking_id', $bookingIds)->delete();
            }
            Booking::query()->whereIn('id', $bookingIds)->delete();
        }

        if (Schema::hasTable('task_tickets')) {
            $ticketIds = DB::table('task_tickets')
                ->where('title', 'like', self::TASK_MARKER.'%')
                ->pluck('id')
                ->all();

            if ($ticketIds !== []) {
                DB::table('task_ticket_assignees')->whereIn('ticket_id', $ticketIds)->delete();
                DB::table('task_tickets')->whereIn('ticket_id', $ticketIds)->delete();
            }
        }

        if (Schema::hasTable('whatsapp_users')) {
            $phones = DB::table('whatsapp_users')
                ->where('phone', 'like', self::PHONE_PREFIX.'%')
                ->pluck('phone')
                ->all();

            if ($phones !== []) {
                if (Schema::hasTable('whatsapp_chat_thread_meta')) {
                    DB::table('whatsapp_chat_thread_meta')->whereIn('phone', $phones)->delete();
                }
                if (Schema::hasTable('whatsapp_messages')) {
                    foreach ($phones as $phone) {
                        DB::table('whatsapp_messages')->where('phone', ltrim($phone, '+'))->delete();
                    }
                }
                DB::table('whatsapp_users')->whereIn('phone', $phones)->delete();
            }
        }
    }
}
