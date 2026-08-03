<?php

namespace Modules\LeadManagement\Services;

use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\ChattingModule\Services\StaffChatMessageParser;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadComment;
use Modules\UserManagement\Entities\User;

class LeadCommentService
{
    public function __construct(
        private readonly AdminInboxNotificationService $notificationService,
        private readonly StaffChatMessageParser $messageParser,
    ) {}

    public function addComment(Lead $lead, string $body, User $author): LeadComment
    {
        $mentionedUserIds = $this->messageParser->extractStaffMentionIds($body);
        $mentionedUserIds = $this->filterActiveAdminIds($mentionedUserIds);

        $comment = LeadComment::create([
            'lead_id' => $lead->id,
            'created_by' => $author->id,
            'body' => $body,
            'mentioned_user_ids' => $mentionedUserIds,
        ]);

        $this->notifyRecipients($lead, $comment, $author);

        return $comment;
    }

    public function togglePin(LeadComment $comment, User $actor): LeadComment
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

    private function notifyRecipients(Lead $lead, LeadComment $comment, User $author): void
    {
        $recipientIds = [];

        if (Lead::assigneeIsHuman($lead->handled_by) && (string) $lead->handled_by !== (string) $author->id) {
            $recipientIds[] = (string) $lead->handled_by;
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

        $leadLabel = trim((string) ($lead->name ?? ''));
        if ($leadLabel === '') {
            $leadLabel = translate('Lead_ID').' #'.$lead->id;
        }

        $authorName = trim(($author->first_name ?? '').' '.($author->last_name ?? ''));
        $authorName = $authorName !== '' ? $authorName : (string) ($author->email ?? translate('Staff'));

        $preview = $this->messageParser->plainPreview($comment->body, 140);
        $actionUrl = route('admin.lead.show', $lead->id).'#lead-comments';

        foreach ($recipientIds as $userId) {
            $this->notificationService->notifyUser(
                $userId,
                UserNotification::TYPE_LEAD_COMMENT,
                translate('New_comment_on_lead').' — '.$leadLabel,
                $authorName.': '.$preview,
                $actionUrl,
                'lead_comment',
                (string) $comment->id,
            );
        }
    }
}
