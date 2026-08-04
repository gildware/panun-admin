<?php

namespace Modules\BookingModule\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingComment;
use Modules\BookingModule\Entities\BookingCommentAttachment;
use Modules\ChattingModule\Services\StaffChatMessageParser;
use Modules\UserManagement\Entities\User;

class BookingCommentService
{
    public function __construct(
        private readonly AdminInboxNotificationService $notificationService,
        private readonly StaffChatMessageParser $messageParser,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function addComment(Booking $booking, string $body, User $author, array $files = []): BookingComment
    {
        $mentionedUserIds = $this->messageParser->extractStaffMentionIds($body);
        $mentionedUserIds = $this->filterActiveAdminIds($mentionedUserIds);

        $comment = BookingComment::create([
            'booking_id' => $booking->id,
            'created_by' => $author->id,
            'body' => $body,
            'mentioned_user_ids' => $mentionedUserIds,
        ]);

        $storedCount = $this->storeAttachments($comment, $files, $author);

        if ($files !== [] && $storedCount === 0) {
            $comment->delete();
            throw ValidationException::withMessages([
                'files' => [translate('Failed_to_upload_attachments')],
            ]);
        }

        $this->notifyRecipients($booking, $comment, $author);

        return $comment->load(['createdBy', 'pinnedByUser', 'attachments']);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(BookingComment $comment, array $files, User $author): int
    {
        $storedCount = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $stored = $this->storeCommentFile('booking-comments/', $file);
            if (! $stored) {
                continue;
            }

            BookingCommentAttachment::create([
                'booking_comment_id' => $comment->id,
                'uploaded_by' => $author->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $stored,
                'file_type' => $file->getMimeType(),
                'disk' => getDisk(),
            ]);
            $storedCount++;
        }

        return $storedCount;
    }

    private function storeCommentFile(string $directory, UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $isImage = str_starts_with((string) $file->getMimeType(), 'image/');
        $format = $isImage ? APPLICATION_IMAGE_FORMAT : $extension;
        $stored = file_uploader($directory, $format, $file);

        if ($stored && $stored !== 'def.png') {
            return $stored;
        }

        return $this->storeRawCommentFile($directory, $file, $extension);
    }

    private function storeRawCommentFile(string $directory, UploadedFile $file, string $extension): ?string
    {
        $disk = getDisk();
        $dir = \App\Support\StoragePathPrefix::apply(rtrim($directory, '/').'/');
        $storedName = now()->toDateString().'-'.uniqid().'.'.($extension ?: 'bin');

        try {
            if (! Storage::disk($disk)->exists($dir)) {
                Storage::disk($disk)->makeDirectory($dir);
            }

            $contents = file_get_contents($file->getRealPath() ?: (string) $file->getPathname());
            if ($contents === false) {
                return null;
            }

            Storage::disk($disk)->put($dir.$storedName, $contents);

            return $storedName;
        } catch (\Throwable) {
            return null;
        }
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
        if ($preview === '') {
            $preview = translate('Attachment');
        }
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
