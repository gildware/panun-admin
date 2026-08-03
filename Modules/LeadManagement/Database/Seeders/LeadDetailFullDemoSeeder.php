<?php

namespace Modules\LeadManagement\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadComment;
use Modules\WhatsAppModule\Entities\WhatsAppBooking;
use Modules\WhatsAppModule\Entities\WhatsAppMessage;

/**
 * Enriches the "Demo Appliance Pending 1" lead so every block on the redesigned
 * lead details page has visible data (AI booking banner, chat, tags, follow-ups, etc.).
 */
class LeadDetailFullDemoSeeder extends Seeder
{
    public const DEMO_LEAD_PHONE = '9900000034';

    public const DEMO_BOOKING_ID = 'PK01AUG26006';

    public function run(): void
    {
        $lead = Lead::query()
            ->where('phone_number', self::DEMO_LEAD_PHONE)
            ->where('lead_type', Lead::TYPE_CUSTOMER)
            ->orderByDesc('id')
            ->first();

        if (!$lead) {
            $this->command?->error('Demo lead not found. Run CustomerLeadReportDemoSeeder first.');

            return;
        }

        $now = Carbon::now();
        $receivedAt = $now->copy()->subDays(3)->setTime(10, 30, 0);

        $lead->update([
            'name' => 'Demo Appliance Pending 1',
            'date_time_of_lead_received' => $receivedAt,
            'next_followup_at' => $now->copy()->addDay()->setTime(11, 0, 0),
            'remarks' => 'Full UI preview lead — appliance repair enquiry via Instagram. Customer asked for geyser service quote.',
            'updated_at' => $now,
        ]);

        WhatsAppBooking::query()->updateOrCreate(
            ['booking_id' => self::DEMO_BOOKING_ID],
            [
                'channel' => 'whatsapp',
                'phone' => '919900000034',
                'name' => $lead->name,
                'service' => 'Home Appliances',
                'service_description' => 'Geyser not heating — demo AI draft booking',
                'district' => 'Srinagar',
                'prefered_datetime' => $now->copy()->addDays(2)->setTime(14, 0, 0),
                'status' => WhatsAppBooking::STATUS_TENTATIVE_PENDING_HUMAN,
                'lead_id' => $lead->id,
                'system_booking_id' => null,
            ]
        );

        $chatPhone = '919900000034';
        if (!WhatsAppMessage::query()->where('phone', $chatPhone)->exists()) {
            WhatsAppMessage::query()->create([
                'channel' => 'whatsapp',
                'phone' => $chatPhone,
                'message_text' => 'Hi, I need geyser repair at home. Can someone come tomorrow?',
                'direction' => 'IN',
                'message_type' => 'text',
                'wa_message_id' => 'demo-wa-in-'.self::DEMO_BOOKING_ID,
                'created_at' => $receivedAt->copy()->addMinutes(5),
            ]);
            WhatsAppMessage::query()->create([
                'channel' => 'whatsapp',
                'phone' => $chatPhone,
                'message_text' => 'Sure! I can help book a geyser repair visit. Which area are you in?',
                'direction' => 'OUT',
                'message_type' => 'text',
                'wa_message_id' => 'demo-wa-out-'.self::DEMO_BOOKING_ID,
                'created_at' => $receivedAt->copy()->addMinutes(6),
            ]);
        }

        if ($lead->comments()->count() < 2) {
            $staffId = $lead->handled_by ?: $lead->created_by;
            LeadComment::query()->firstOrCreate(
                ['lead_id' => $lead->id, 'body' => 'Called customer — wants quote before confirming visit.'],
                ['created_by' => $staffId, 'created_at' => $receivedAt->copy()->addHours(2)]
            );
            LeadComment::query()->firstOrCreate(
                ['lead_id' => $lead->id, 'body' => 'Shared estimated price range on WhatsApp. Awaiting reply.'],
                ['created_by' => $staffId, 'created_at' => $receivedAt->copy()->addHours(5)]
            );
        }

        $url = url('/admin/lead/'.$lead->id);
        $this->command?->info("Full demo lead ready: #{$lead->id} — {$lead->name}");
        $this->command?->info("Open: {$url}");
    }
}
