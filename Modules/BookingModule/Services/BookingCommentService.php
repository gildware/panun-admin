<?php

namespace Modules\BookingModule\Services;

use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingComment;
use Modules\ChattingModule\Services\StaffChatMessageParser;
use Modules\UserManagement\Entities\User;

class BookingCommentService
{
    public function __construct(
        private readonly AdminInboxNotificationService $notificationService,
        private readonly StaffChatMessageParser $messageParser,
    ) {}

    public function addComment(Booking $booking, string $body, User $author): BookingComment
    {
        $mentionedUserIds = $this->messageParser->extractStaffMentionIds($body);
        $mentionedUserIds = $this->filterActiveAdminIds($mentionedUserIds);

        $comment = BookingComment::create([
            'booking_id' => $booking->id,
            'created_by' => $author->id,
            'body' => $body,
            'mentioned_user_ids' => $mentionedUserIds,
        ]);

        $this->notifyRecipients($booking, $comment, $author);

        return $comment;
    }

    public function togglePin(BookingComment $comment, User $actor): BookingComment
    {
        $comment->is_pinned = ! $comment->is_pinned;
        $comment->pinned_at = $comment->is_pinned ? now() : null;
        $comment->pinned_by = $comment->is_pinned ? $actor->id : null;
        $comment->save();

        return $comment->fresh(['createdBy', 'pinnedByUser']);
    }

    /**
     * @param  array<int, string>  $userIds
     * @return array<int, string>
     */
    private function filterActiveAdminIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    private function notifyRecipients(Booking $booking, BookingComment $comment, User $author): void
    {
        $recipientIds = [];

        if ($booking->assignee_id && (string) $booking->assignee_id !== (string) $author->id) {
            $recipientIds[] = (string) $booking->assignee_id;
        }

        foreach ($comment->mentioned_user_ids ?? [] as $userId) {
            if ((string) $userId !== (string) $author->id) {
                $recipientIds[] = (string) $userId;
            }
        }

        $recipientIds = array_values(array_unique($recipientIds));
        if ($recipientIds === []) {
            return;
        }

        $bookingLabel = translate('Booking').' #'.($booking->readable_id ?? $booking->id);

        $authorName = trim(($author->first_name ?? '').' '.($author->last_name ?? ''));
        $authorName = $authorName !== '' ? $authorName : (string) ($author->email ?? translate('Staff'));

        $preview = $this->messageParser->plainPreview($comment->body, 140);
        $actionUrl = route('admin.booking.details', [$booking->id, 'web_page' => 'comments']).'#booking-comments';

        foreach ($recipientIds as $userId) {
            $this->notificationService->notifyUser(
                $userId,
                UserNotification::TYPE_BOOKING_COMMENT,
                translate('New_comment_on_booking').' — '.$bookingLabel,
                $authorName.': '.$preview,
                $actionUrl,
                'booking_comment',
                (string) $comment->id.':'.$userId,
            );
        }
    }
}
