<?php

namespace Modules\TaskBoardModule\Services;

use App\Support\AdminHeaderTaskBoardCounts;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\AdminModule\Entities\UserNotification;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Modules\ChattingModule\Services\StaffChatMessageParser;
use Modules\TaskBoardModule\Entities\TaskColumn;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\TaskBoardModule\Entities\TaskTicketAttachment;
use Modules\TaskBoardModule\Entities\TaskTicketComment;
use Modules\TaskBoardModule\Entities\TaskTicketLink;
use Modules\UserManagement\Entities\User;

class TaskBoardService
{
    public function __construct(
        private readonly TaskActivityLogger $activityLogger,
        private readonly StaffChatMessageParser $messageParser,
        private readonly AdminInboxNotificationService $notificationService,
    ) {
    }

    public function ensureDefaultColumns(): void
    {
        if (TaskColumn::query()->withTrashed()->exists()) {
            return;
        }

        foreach (config('taskboardmodule.default_columns', []) as $column) {
            TaskColumn::query()->create($column);
        }
    }

    /**
     * @param  array{
     *   search?: string|null,
     *   assignee_ids?: array<int, string>,
     *   my_tickets?: bool,
     *   overdue?: bool,
     *   link_type?: string|null,
     *   link_id?: string|null,
     *   sort?: string|null,
     * }  $filters
     */
    public function boardPayload(array $filters = []): array
    {
        $this->ensureDefaultColumns();

        $columns = TaskColumn::query()
            ->orderBy('position')
            ->with([
                'tickets' => function ($query) use ($filters) {
                    $this->applyTicketFilters($query, $filters);
                    $this->applyTicketSort($query, $filters['sort'] ?? 'position');
                    $query->with([
                        'assignees:id,first_name,last_name,email,user_type,profile_image',
                        'creator:id,first_name,last_name,email,user_type,profile_image',
                        'links',
                        'attachments',
                    ])->withCount(['comments', 'attachments']);
                },
            ])
            ->get();

        return [
            'columns' => $columns,
            'employees' => $this->employeeOptions(),
            'filters' => $filters,
            'canRestore' => $this->canRestore(),
            'myAssignedCounts' => \App\Support\AdminHeaderTaskBoardCounts::assignedCounts(auth()->user()),
        ];
    }

    public function employeeOptions(): Collection
    {
        return User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email', 'user_type', 'profile_image']);
    }

    public function canRestore(): bool
    {
        return auth()->check() && auth()->user()->user_type === 'super-admin';
    }

    public function createColumn(array $data): TaskColumn
    {
        $position = (int) (TaskColumn::query()->max('position') ?? -1) + 1;

        $column = TaskColumn::query()->create([
            'name' => $data['name'],
            'color' => $data['color'] ?? '#64748b',
            'position' => $position,
        ]);

        $this->activityLogger->log(
            action: 'column_created',
            subjectType: TaskColumn::class,
            subjectId: $column->id,
            newValues: $column->only(['name', 'color', 'position']),
        );

        return $column;
    }

    public function updateColumn(TaskColumn $column, array $data): TaskColumn
    {
        $old = $column->only(['name', 'color']);
        $column->fill([
            'name' => $data['name'] ?? $column->name,
            'color' => $data['color'] ?? $column->color,
        ])->save();

        $this->activityLogger->log(
            action: 'column_updated',
            subjectType: TaskColumn::class,
            subjectId: $column->id,
            oldValues: $old,
            newValues: $column->only(['name', 'color']),
        );

        return $column->fresh();
    }

    public function deleteColumn(TaskColumn $column): void
    {
        DB::transaction(function () use ($column) {
            $column->tickets()->each(function (TaskTicket $ticket) {
                $this->deleteTicket($ticket);
            });

            $column->delete();

            $this->activityLogger->log(
                action: 'column_deleted',
                subjectType: TaskColumn::class,
                subjectId: $column->id,
                oldValues: $column->only(['name', 'color', 'position']),
            );
        });
    }

    public function reorderColumns(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            TaskColumn::query()->where('id', $id)->update(['position' => $index]);
        }

        $this->activityLogger->log(
            action: 'columns_reordered',
            subjectType: TaskColumn::class,
            newValues: ['order' => array_values($orderedIds)],
        );
    }

    public function createTicket(array $data): TaskTicket
    {
        return DB::transaction(function () use ($data) {
            $columnId = $data['column_id'];
            $position = (int) (TaskTicket::query()->where('column_id', $columnId)->max('position') ?? -1) + 1;

            $ticket = TaskTicket::query()->create([
                'column_id' => $columnId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'position' => $position,
                'created_by' => auth()->id(),
            ]);

            $this->syncAssignees($ticket, $data['assignee_ids'] ?? []);
            $this->syncLinks($ticket, $data['booking_ids'] ?? [], $data['lead_ids'] ?? []);
            $this->storeAttachments($ticket, $data['images'] ?? []);

            $this->activityLogger->log(
                action: 'created',
                ticket: $ticket,
                newValues: $this->ticketSnapshot($ticket->fresh(['assignees', 'links', 'attachments'])),
            );

            return $ticket->fresh(['assignees', 'links', 'attachments', 'comments', 'column']);
        });
    }

    public function updateTicket(TaskTicket $ticket, array $data): TaskTicket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $old = $this->ticketSnapshot($ticket->load(['assignees', 'links']));

            $ticket->fill([
                'title' => $data['title'] ?? $ticket->title,
                'description' => array_key_exists('description', $data) ? $data['description'] : $ticket->description,
                'start_date' => array_key_exists('start_date', $data) ? $data['start_date'] : $ticket->start_date,
                'end_date' => array_key_exists('end_date', $data) ? $data['end_date'] : $ticket->end_date,
                'column_id' => $data['column_id'] ?? $ticket->column_id,
            ])->save();

            if (array_key_exists('assignee_ids', $data)) {
                $this->syncAssignees($ticket, $data['assignee_ids'] ?? []);
            }

            if (array_key_exists('booking_ids', $data) || array_key_exists('lead_ids', $data)) {
                $this->syncLinks(
                    $ticket,
                    $data['booking_ids'] ?? $ticket->links->where('linkable_type', 'booking')->pluck('linkable_id')->all(),
                    $data['lead_ids'] ?? $ticket->links->where('linkable_type', 'lead')->pluck('linkable_id')->all(),
                );
            }

            if (! empty($data['images'])) {
                $this->storeAttachments($ticket, $data['images']);
            }

            $fresh = $ticket->fresh(['assignees', 'links', 'attachments', 'comments', 'column']);

            $this->activityLogger->log(
                action: 'updated',
                ticket: $fresh,
                oldValues: $old,
                newValues: $this->ticketSnapshot($fresh),
            );

            return $fresh;
        });
    }

    public function moveTicket(TaskTicket $ticket, string $columnId, int $position, array $orderedTicketIds = []): TaskTicket
    {
        return DB::transaction(function () use ($ticket, $columnId, $position, $orderedTicketIds) {
            $old = [
                'column_id' => $ticket->column_id,
                'position' => $ticket->position,
            ];

            $ticket->column_id = $columnId;
            $ticket->position = $position;
            $ticket->save();

            if ($orderedTicketIds !== []) {
                foreach ($orderedTicketIds as $index => $id) {
                    TaskTicket::query()
                        ->where('id', $id)
                        ->where('column_id', $columnId)
                        ->update(['position' => $index]);
                }
            }

            $fresh = $ticket->fresh(['assignees', 'links', 'attachments', 'column']);

            $this->activityLogger->log(
                action: 'moved',
                ticket: $fresh,
                oldValues: $old,
                newValues: [
                    'column_id' => $fresh->column_id,
                    'position' => $fresh->position,
                ],
            );

            $this->forgetAssignedCountCache($fresh->assignees->pluck('id')->all());

            return $fresh;
        });
    }

    public function deleteTicket(TaskTicket $ticket): void
    {
        $snapshot = $this->ticketSnapshot($ticket->load(['assignees', 'links']));
        $assigneeIds = $ticket->assignees->pluck('id')->all();
        $ticket->delete();

        $this->activityLogger->log(
            action: 'deleted',
            ticket: $ticket,
            oldValues: $snapshot,
        );

        $this->forgetAssignedCountCache($assigneeIds);
    }

    public function restoreTicket(TaskTicket $ticket): TaskTicket
    {
        if (! $this->canRestore()) {
            abort(403, translate('You_are_not_authorized_to_restore_tickets'));
        }

        $ticket->restore();

        $fresh = $ticket->fresh(['assignees', 'links', 'attachments', 'column']);

        $this->activityLogger->log(
            action: 'restored',
            ticket: $ticket,
            newValues: $this->ticketSnapshot($fresh),
        );

        $this->forgetAssignedCountCache($fresh->assignees->pluck('id')->all());

        return $fresh;
    }

    public function addComment(TaskTicket $ticket, string $body, array $files = []): TaskTicketComment
    {
        $authorId = auth()->id();
        $mentionedUserIds = $this->filterActiveAdminIds(
            $this->messageParser->extractStaffMentionIds($body)
        );

        $comment = TaskTicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $authorId,
            'body' => $body,
            'mentioned_user_ids' => $mentionedUserIds,
        ]);

        $storedCount = $this->storeAttachments($ticket, $files, $comment->id);

        if ($files !== [] && $storedCount === 0) {
            $comment->delete();
            throw ValidationException::withMessages([
                'files' => [translate('Failed_to_upload_attachments')],
            ]);
        }

        $this->activityLogger->log(
            action: 'commented',
            ticket: $ticket,
            subjectType: TaskTicketComment::class,
            subjectId: $comment->id,
            newValues: [
                'body' => $body,
                'files' => count($files),
            ],
        );

        $this->notifyCommentRecipients(
            $ticket->fresh(['assignees']),
            $comment,
            $authorId ? (string) $authorId : null,
        );

        return $comment->load(['user', 'attachments']);
    }

    private function notifyCommentRecipients(TaskTicket $ticket, TaskTicketComment $comment, ?string $authorId): void
    {
        if (! $authorId) {
            return;
        }

        $recipientIds = [];

        foreach ($ticket->assignees as $assignee) {
            if ((string) $assignee->id !== $authorId) {
                $recipientIds[] = (string) $assignee->id;
            }
        }

        foreach ($comment->mentioned_user_ids ?? [] as $userId) {
            if ((string) $userId !== $authorId) {
                $recipientIds[] = (string) $userId;
            }
        }

        $recipientIds = array_values(array_unique($recipientIds));
        if ($recipientIds === []) {
            return;
        }

        $author = User::query()->find($authorId);
        $authorName = $author
            ? trim(($author->first_name ?? '').' '.($author->last_name ?? '')) ?: (string) ($author->email ?? translate('Staff'))
            : translate('Staff');

        $preview = $this->messageParser->plainPreview($comment->body, 140);
        $ticketLabel = $ticket->title ?: translate('Ticket');
        $actionUrl = route('admin.task-board.index').'?ticket='.$ticket->id;

        foreach ($recipientIds as $userId) {
            $this->notificationService->notifyUser(
                $userId,
                UserNotification::TYPE_TICKET_COMMENT,
                translate('New_comment_on_ticket').' — '.$ticketLabel,
                $authorName.': '.$preview,
                $actionUrl,
                'ticket_comment',
                (string) $comment->id.':'.$userId,
            );
        }
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

    public function deleteComment(TaskTicketComment $comment): void
    {
        $comment->delete();

        $this->activityLogger->log(
            action: 'comment_deleted',
            ticket: $comment->ticket,
            subjectType: TaskTicketComment::class,
            subjectId: $comment->id,
            oldValues: ['body' => $comment->body],
        );
    }

    public function deleteAttachment(TaskTicketAttachment $attachment): void
    {
        $attachment->delete();

        $this->activityLogger->log(
            action: 'attachment_deleted',
            ticket: $attachment->ticket,
            subjectType: TaskTicketAttachment::class,
            subjectId: $attachment->id,
            oldValues: [
                'original_name' => $attachment->original_name,
                'stored_name' => $attachment->stored_name,
            ],
        );
    }

    public function serializeTicket(TaskTicket $ticket): array
    {
        $ticket->loadMissing(['assignees', 'creator', 'links', 'attachments', 'comments.user', 'comments.attachments', 'column', 'activityLogs.actor']);

        $creator = $ticket->creator;
        $creatorPayload = null;
        if ($creator) {
            $creatorName = trim(($creator->first_name ?? '').' '.($creator->last_name ?? ''));
            if ($creatorName === '') {
                $creatorName = (string) ($creator->email ?? $creator->id);
            }
            $creatorPayload = [
                'id' => $creator->id,
                'name' => $creatorName,
                'photo' => $this->userPhotoUrl($creator),
                'initials' => $this->userInitials($creator),
            ];
        }

        return [
            'id' => $ticket->id,
            'column_id' => $ticket->column_id,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'start_date' => optional($ticket->start_date)?->format('Y-m-d'),
            'end_date' => optional($ticket->end_date)?->format('Y-m-d'),
            'position' => $ticket->position,
            'deleted_at' => optional($ticket->deleted_at)?->toDateTimeString(),
            'created_by' => $creatorPayload,
            'assignees' => $ticket->assignees->map(function (User $user) {
                $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                if ($name === '') {
                    $name = (string) ($user->email ?? $user->id);
                }

                return [
                    'id' => $user->id,
                    'name' => $name,
                    'photo' => $this->userPhotoUrl($user),
                    'initials' => $this->userInitials($user),
                ];
            })->values(),
            'assignee_ids' => $ticket->assignees->pluck('id')->values(),
            'links' => $ticket->links->map(fn (TaskTicketLink $link) => [
                'id' => $link->id,
                'type' => $link->linkable_type,
                'linkable_id' => $link->linkable_id,
                'label' => $link->resolveLabel(),
                'url' => $link->resolveUrl(),
            ])->values(),
            'booking_ids' => $ticket->links->where('linkable_type', 'booking')->pluck('linkable_id')->values(),
            'lead_ids' => $ticket->links->where('linkable_type', 'lead')->pluck('linkable_id')->values(),
            'attachments' => $ticket->attachments->map(fn (TaskTicketAttachment $file) => $this->serializeAttachment($file))->values(),
            'comments' => $ticket->comments->map(fn (TaskTicketComment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => trim(($comment->user?->first_name ?? '').' '.($comment->user?->last_name ?? '')),
                'created_at' => optional($comment->created_at)?->diffForHumans(),
                'attachments' => $comment->attachments->map(fn (TaskTicketAttachment $file) => $this->serializeAttachment($file))->values(),
            ])->values(),
            'activity' => $ticket->activityLogs->take(50)->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor' => trim(($log->actor?->first_name ?? '').' '.($log->actor?->last_name ?? '')) ?: translate('System'),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'created_at' => optional($log->created_at)?->toDateTimeString(),
            ])->values(),
        ];
    }

    private function userPhotoUrl(User $user): ?string
    {
        $stored = trim((string) ($user->profile_image ?? ''));
        if ($stored === '') {
            return null;
        }

        $storedLower = mb_strtolower($stored);
        if (
            $storedLower === 'default.png'
            || str_contains($storedLower, 'placeholder')
            || str_contains($storedLower, 'customer.png')
            || str_contains($storedLower, 'user2x.png')
        ) {
            return null;
        }

        $path = (string) ($user->profile_image_full_path ?? '');
        if ($path === '') {
            return null;
        }

        $pathLower = mb_strtolower($path);
        if (
            str_contains($pathLower, 'placeholder')
            || str_contains($pathLower, '/customer.png')
            || str_contains($pathLower, '/user2x.png')
            || str_contains($pathLower, '/default.png')
        ) {
            return null;
        }

        return $path;
    }

    private function userInitials(User $user): string
    {
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($fullName === '') {
            $fullName = trim((string) ($user->email ?? ''));
        }

        $words = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) >= 2) {
            $letters = '';
            foreach ($words as $word) {
                $letters .= mb_substr($word, 0, 1);
            }

            return mb_strtoupper($letters);
        }

        if (count($words) === 1) {
            $word = $words[0];

            return mb_strtoupper(mb_substr($word, 0, min(2, mb_strlen($word))));
        }

        return 'E';
    }

    public function serializeAttachment(TaskTicketAttachment $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->original_name,
            'url' => $file->url,
            'file_type' => $file->file_type,
            'is_image' => $file->isImage(),
            'is_video' => $file->isVideo(),
            'is_audio' => $file->isAudio(),
        ];
    }

    private function applyTicketFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if (! empty($filters['my_tickets'])) {
            $userId = auth()->id();
            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)
                    ->orWhereHas('assignees', fn ($assigneeQuery) => $assigneeQuery->where('users.id', $userId));
            });
        }

        $assigneeIds = array_values(array_unique(array_filter($filters['assignee_ids'] ?? [])));
        if ($assigneeIds !== []) {
            $query->whereHas('assignees', fn ($q) => $q->whereIn('users.id', $assigneeIds));
        }

        if (! empty($filters['overdue'])) {
            $query->whereNotNull('end_date')
                ->whereDate('end_date', '<', now()->toDateString());
        }

        if (! empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }

        if (! empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }

        if (! empty($filters['end_date_from'])) {
            $query->whereDate('end_date', '>=', $filters['end_date_from']);
        }

        if (! empty($filters['end_date_to'])) {
            $query->whereDate('end_date', '<=', $filters['end_date_to']);
        }

        if (! empty($filters['link_type']) && ! empty($filters['link_id'])) {
            $query->whereHas('links', function ($q) use ($filters) {
                $q->where('linkable_type', $filters['link_type'])
                    ->where('linkable_id', $filters['link_id']);
            });
        }
    }

    private function applyTicketSort($query, ?string $sort): void
    {
        match ($sort) {
            'newest' => $query->reorder()->orderByDesc('created_at'),
            'oldest' => $query->reorder()->orderBy('created_at'),
            'due_date' => $query->reorder()->orderByRaw('end_date is null')->orderBy('end_date')->orderBy('position'),
            'title' => $query->reorder()->orderBy('title'),
            default => $query->reorder()->orderBy('position')->orderBy('created_at'),
        };
    }

    private function syncAssignees(TaskTicket $ticket, array $assigneeIds): void
    {
        $previousAssigneeIds = $ticket->assignees()->pluck('users.id')->map(fn ($id) => (string) $id)->all();

        $assigneeIds = array_values(array_unique(array_filter($assigneeIds)));
        $validIds = User::query()
            ->whereIn('user_type', ADMIN_USER_TYPES)
            ->whereIn('id', $assigneeIds)
            ->pluck('id')
            ->all();

        $ticket->assignees()->sync($validIds);

        $this->forgetAssignedCountCache(array_merge($previousAssigneeIds, array_map('strval', $validIds)));

        $newAssigneeIds = array_values(array_diff(
            array_map('strval', $validIds),
            $previousAssigneeIds,
        ));

        if ($newAssigneeIds !== [] && function_exists('admin_inbox_notify_ticket_assigned')) {
            $actor = auth()->user();
            $freshTicket = $ticket->fresh(['column']);
            foreach ($newAssigneeIds as $assigneeId) {
                admin_inbox_notify_ticket_assigned($assigneeId, $freshTicket, $actor);
            }
        }
    }

    private function syncLinks(TaskTicket $ticket, array $bookingIds, array $leadIds): void
    {
        $ticket->links()->delete();

        foreach (array_unique(array_filter($bookingIds)) as $bookingId) {
            TaskTicketLink::query()->create([
                'ticket_id' => $ticket->id,
                'linkable_type' => 'booking',
                'linkable_id' => (string) $bookingId,
            ]);
        }

        foreach (array_unique(array_filter($leadIds)) as $leadId) {
            TaskTicketLink::query()->create([
                'ticket_id' => $ticket->id,
                'linkable_type' => 'lead',
                'linkable_id' => (string) $leadId,
            ]);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(TaskTicket $ticket, array $files, ?string $commentId = null): int
    {
        $storedCount = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $isImage = str_starts_with((string) $file->getMimeType(), 'image/');
            $format = $isImage ? APPLICATION_IMAGE_FORMAT : $extension;
            $stored = $this->storeCommentFile('task-board/', $format, $file);
            if (! $stored) {
                continue;
            }

            TaskTicketAttachment::query()->create([
                'ticket_id' => $ticket->id,
                'comment_id' => $commentId,
                'uploaded_by' => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $stored,
                'file_type' => $file->getMimeType(),
                'disk' => getDisk(),
            ]);
            $storedCount++;
        }

        return $storedCount;
    }

    private function storeCommentFile(string $directory, string $format, UploadedFile $file): ?string
    {
        $stored = file_uploader($directory, $format, $file);

        if ($stored && $stored !== 'def.png') {
            return $stored;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');

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

    private function ticketSnapshot(TaskTicket $ticket): array
    {
        return [
            'title' => $ticket->title,
            'description' => $ticket->description,
            'column_id' => $ticket->column_id,
            'start_date' => optional($ticket->start_date)?->format('Y-m-d'),
            'end_date' => optional($ticket->end_date)?->format('Y-m-d'),
            'assignee_ids' => $ticket->relationLoaded('assignees')
                ? $ticket->assignees->pluck('id')->values()->all()
                : [],
            'links' => $ticket->relationLoaded('links')
                ? $ticket->links->map(fn ($l) => [
                    'type' => $l->linkable_type,
                    'id' => $l->linkable_id,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @param  array<int, string|int>  $userIds
     */
    private function forgetAssignedCountCache(array $userIds): void
    {
        foreach (array_unique(array_filter(array_map('strval', $userIds))) as $userId) {
            AdminHeaderTaskBoardCounts::forgetForUser($userId);
        }
    }
}
