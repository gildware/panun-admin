<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use App\Support\SeekableRecordingResponse;
use App\Support\StoragePathPrefix;
use Illuminate\Routing\Controller;
use Modules\BookingModule\Entities\BookingFollowup;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadFollowup;
use Modules\LeadManagement\Entities\LeadOutboundEnquiry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StoredRecordingController extends Controller
{
    public function leadFollowup(LeadFollowup $followup): BinaryFileResponse
    {
        abort_unless($followup->hasRecording(), 404);

        return SeekableRecordingResponse::fromDisk(
            $followup->recording_disk ?: getDisk(),
            StoragePathPrefix::apply('lead-followups/'.$followup->recording_path),
            $followup->recording_mime,
            $followup->recording_original_name
        );
    }

    public function initialCall(Lead $lead): BinaryFileResponse
    {
        abort_unless($lead->hasInitialCallRecording(), 404);

        return SeekableRecordingResponse::fromDisk(
            $lead->initial_call_recording_disk ?: getDisk(),
            StoragePathPrefix::apply('lead-initial-calls/'.$lead->initial_call_recording_path),
            $lead->initial_call_recording_mime,
            $lead->initial_call_recording_original_name
        );
    }

    public function outboundEnquiry(LeadOutboundEnquiry $enquiry): BinaryFileResponse
    {
        abort_unless($enquiry->hasRecording(), 404);

        return SeekableRecordingResponse::fromDisk(
            $enquiry->recording_disk ?: getDisk(),
            StoragePathPrefix::apply('lead-outbound-enquiries/'.$enquiry->recording_path),
            $enquiry->recording_mime,
            $enquiry->recording_original_name
        );
    }

    public function bookingFollowup(BookingFollowup $followup): BinaryFileResponse
    {
        abort_unless($followup->hasRecording(), 404);

        return SeekableRecordingResponse::fromDisk(
            $followup->recording_disk ?: getDisk(),
            StoragePathPrefix::apply('booking-followups/'.$followup->recording_path),
            $followup->recording_mime,
            $followup->recording_original_name
        );
    }
}
