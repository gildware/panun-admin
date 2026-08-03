<?php

namespace App\Console\Commands;

use App\Support\StoragePathPrefix;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingFollowup;

class SeedBookingFollowupDemoCommand extends Command
{
    protected $signature = 'booking:seed-followup-demo {bookingId : Booking UUID}';

    protected $description = 'Seed completed booking follow-ups with dummy call recordings for UI demo';

    public function handle(): int
    {
        $booking = Booking::query()->find($this->argument('bookingId'));
        if (! $booking) {
            $this->error('Booking not found.');

            return self::FAILURE;
        }

        $adminId = auth()->id();
        if (! $adminId) {
            $adminId = \Modules\UserManagement\Entities\User::query()
                ->whereIn('user_type', ['super-admin', 'admin-employee'])
                ->value('id');
        }

        $now = Carbon::now();
        $demos = [
            [
                'for' => 'customer',
                'days_ago' => 5,
                'on_time' => true,
                'channel' => BookingFollowup::CHANNEL_CALL,
                'urgency' => BookingFollowup::URGENCY_HIGH,
                'remarks' => 'Confirmed service date and address with customer.',
                'reason' => 'Pre-visit confirmation',
                'with_recording' => true,
                'with_transcript' => true,
                'summary' => 'Customer confirmed the plumbing visit for Saturday morning and asked to call 30 minutes before arrival. Address verified in Rajbagh. No changes to booked services.',
                'transcript' => "Support: Hello, this is Panun Kaergar calling about your booking PK16JUL26002.\nUser: Yes, I was expecting your call.\nSupport: We wanted to confirm tomorrow's visit at 10 AM.\nUser: That works. Please call when the technician is on the way.\nSupport: Noted. We will share the provider contact as well.",
            ],
            [
                'for' => 'provider',
                'days_ago' => 3,
                'on_time' => false,
                'channel' => BookingFollowup::CHANNEL_CALL,
                'urgency' => BookingFollowup::URGENCY_MEDIUM,
                'remarks' => 'Provider asked for customer gate pass details.',
                'reason' => 'Provider coordination',
                'with_recording' => true,
                'with_transcript' => true,
                'summary' => 'Provider confirmed assignment and requested customer phone number for gate entry. Support shared contact and visiting charges reminder.',
                'transcript' => "Support: Hi, calling from Panun Kaergar about booking PK16JUL26002.\nUser: Yes, we can take this job.\nSupport: Customer prefers a call 30 minutes before arrival.\nUser: Understood. Please share the contact number.\nSupport: I will WhatsApp the details after this call.",
            ],
            [
                'for' => 'customer',
                'days_ago' => 1,
                'on_time' => true,
                'channel' => BookingFollowup::CHANNEL_WHATSAPP,
                'urgency' => BookingFollowup::URGENCY_LOW,
                'remarks' => 'Sent payment reminder on WhatsApp.',
                'reason' => 'Payment reminder',
                'with_recording' => false,
                'with_transcript' => false,
                'summary' => null,
                'transcript' => null,
            ],
        ];

        $created = 0;
        foreach ($demos as $index => $demo) {
            $scheduledFor = $now->copy()->subDays($demo['days_ago'])->setTime(10, 0, 0);
            $takenAt = $demo['on_time']
                ? $scheduledFor->copy()->subMinutes(15)
                : $scheduledFor->copy()->addHours(2);

            $recordingPath = null;
            $recordingDisk = null;
            $recordingMime = null;
            $recordingOriginal = null;

            if ($demo['with_recording']) {
                [$recordingPath, $recordingDisk, $recordingMime, $recordingOriginal] = $this->storeDummyRecording($index + 1);
            }

            BookingFollowup::query()->create([
                'booking_id' => $booking->id,
                'date' => $scheduledFor,
                'due_followup_at' => $scheduledFor,
                'followup_at' => $takenAt,
                'next_followup_at' => $index === count($demos) - 1 ? null : $scheduledFor->copy()->addDay()->setTime(11, 0),
                'reason' => $demo['reason'],
                'for' => $demo['for'],
                'status' => 'completed',
                'remarks' => $demo['remarks'],
                'contact_channel' => $demo['channel'],
                'urgency' => $demo['urgency'],
                'recording_path' => $recordingPath,
                'recording_disk' => $recordingDisk,
                'recording_mime' => $recordingMime,
                'recording_original_name' => $recordingOriginal,
                'recording_transcript' => $demo['with_transcript'] ? BookingFollowup::formatTranscript((string) $demo['transcript']) : null,
                'recording_summary' => $demo['summary'],
                'transcribed_at' => $demo['with_transcript'] ? $takenAt->copy()->addMinutes(5) : null,
                'created_by' => $adminId,
            ]);
            $created++;
        }

        $this->info("Created {$created} demo follow-up(s) with recordings for booking {$booking->readable_id}.");

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function storeDummyRecording(int $index): array
    {
        $disk = getDisk();
        $dir = StoragePathPrefix::apply('booking-followups/');
        $filename = now()->toDateString().'-demo-followup-'.$index.'-'.uniqid().'.wav';
        $wavBytes = $this->buildDemoWav(2, 440 + ($index * 40));

        if (! Storage::disk($disk)->exists($dir)) {
            Storage::disk($disk)->makeDirectory($dir);
        }

        Storage::disk($disk)->put($dir.$filename, $wavBytes);

        return [$filename, $disk, 'audio/wav', 'demo-followup-'.$index.'.wav'];
    }

    private function buildDemoWav(int $seconds, int $frequencyHz = 440): string
    {
        $sampleRate = 8000;
        $numSamples = $sampleRate * max(1, $seconds);
        $data = '';

        for ($i = 0; $i < $numSamples; $i++) {
            $sample = (int) round(sin(2 * M_PI * $frequencyHz * $i / $sampleRate) * 6000);
            $data .= pack('v', $sample);
        }

        $dataSize = strlen($data);
        $byteRate = $sampleRate * 2;
        $blockAlign = 2;

        return 'RIFF'
            .pack('V', 36 + $dataSize)
            .'WAVE'
            .'fmt '
            .pack('V', 16)
            .pack('v', 1)
            .pack('v', 1)
            .pack('V', $sampleRate)
            .pack('V', $byteRate)
            .pack('v', $blockAlign)
            .pack('v', 16)
            .'data'
            .pack('V', $dataSize)
            .$data;
    }
}
