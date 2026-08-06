<?php

namespace Modules\BookingModule\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\BookingModule\Entities\WebBooking;
use Modules\BookingModule\Services\WebBookingSubmissionService;
use Modules\LeadManagement\Entities\CustomerLeadStatus;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadTypeHistory;

/**
 * Seeds demo web bookings with linked customer leads for local UI testing.
 *
 * Run: php artisan db:seed --class=Modules\\BookingModule\\Database\\Seeders\\WebBookingDemoSeeder
 */
class WebBookingDemoSeeder extends Seeder
{
    private const PHONE_PREFIX = '9876500';

    /** @var array<int, array{name:string,phone:string,service:string,area:string,message:string,preferred_date?:string|null}> */
    private const DEMO_SUBMISSIONS = [
        [
            'name' => 'Rahul Sharma',
            'phone' => self::PHONE_PREFIX . '001',
            'service' => 'AC Repair',
            'area' => 'Srinagar — Lal Chowk',
            'message' => 'Split AC not cooling. Need service this week.',
            'preferred_date' => 'Tomorrow 11:00 AM',
        ],
        [
            'name' => 'Aisha Khan',
            'phone' => self::PHONE_PREFIX . '002',
            'service' => 'Plumbing',
            'area' => 'Baramulla',
            'message' => 'Kitchen tap leaking badly. Please call before visiting.',
            'preferred_date' => 'Saturday 3:00 PM',
        ],
        [
            'name' => 'Imran Dar',
            'phone' => self::PHONE_PREFIX . '003',
            'service' => 'Electrical',
            'area' => 'Anantnag',
            'message' => 'MCB tripping frequently in the main panel.',
            'preferred_date' => null,
        ],
        [
            'name' => 'Priya Mehta',
            'phone' => self::PHONE_PREFIX . '004',
            'service' => 'Home Cleaning',
            'area' => 'Jammu — Gandhi Nagar',
            'message' => 'Deep cleaning for 3BHK apartment before guests arrive.',
            'preferred_date' => 'Next Monday 10:00 AM',
        ],
        [
            'name' => 'Omar Bhat',
            'phone' => self::PHONE_PREFIX . '005',
            'service' => 'Carpentry',
            'area' => 'Sopore',
            'message' => 'Need wardrobe door hinge repair and shelf adjustment.',
            'preferred_date' => 'Flexible — any evening',
        ],
    ];

    public function run(): void
    {
        if (!Schema::hasTable('web_bookings')) {
            $this->command?->warn('web_bookings table is missing; run migrations first.');

            return;
        }

        $this->purgeDemoData();

        /** @var WebBookingSubmissionService $service */
        $service = app(WebBookingSubmissionService::class);

        $created = [];
        foreach (self::DEMO_SUBMISSIONS as $payload) {
            $created[] = $service->submit($payload);
        }

        $this->applyLeadStatusVariety($created);

        $this->command?->info(sprintf(
            'Seeded %d web booking demo record(s). View at /admin/booking/web-bookings',
            count($created)
        ));
    }

    private function purgeDemoData(): void
    {
        $phones = array_map(
            fn (array $row) => substr(preg_replace('/\D+/', '', $row['phone']) ?? '', -10),
            self::DEMO_SUBMISSIONS
        );

        $bookingIds = WebBooking::query()->whereIn('phone', $phones)->pluck('id');
        if ($bookingIds->isNotEmpty()) {
            WebBooking::query()->whereIn('id', $bookingIds)->delete();
            $this->command?->line('Removed existing demo web bookings.');
        }
    }

    /**
     * @param  array<int, WebBooking>  $bookings
     */
    private function applyLeadStatusVariety(array $bookings): void
    {
        $statuses = CustomerLeadStatus::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->keyBy('base_type');

        $pending = $statuses->get('pending');
        $booked = $statuses->first(fn ($s) => strtolower((string) $s->base_type) === 'booked')
            ?? $statuses->get('completed');
        $cancel = $statuses->first(fn ($s) => in_array(strtolower((string) $s->base_type), ['cancel', 'cancelled'], true));

        $assignments = [
            0 => $pending,
            1 => $booked,
            2 => $pending,
            3 => $booked,
            4 => $cancel,
        ];

        foreach ($bookings as $index => $booking) {
            $lead = $booking->lead;
            if (!$lead instanceof Lead) {
                continue;
            }

            $targetStatus = $assignments[$index] ?? $pending;
            if (!$targetStatus) {
                continue;
            }

            $history = LeadTypeHistory::query()
                ->where('lead_id', $lead->id)
                ->where('type', Lead::TYPE_CUSTOMER)
                ->latest()
                ->first();

            if (!$history) {
                continue;
            }

            $data = is_array($history->data) ? $history->data : [];
            $data['customer_lead_status_id'] = $targetStatus->id;
            $history->update(['data' => $data]);
        }
    }
}
