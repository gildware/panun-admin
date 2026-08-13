<?php

namespace Modules\TaskBoardModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ChattingModule\Services\StaffChatMessageParser;
use Modules\TaskBoardModule\Entities\TaskTicket;
use Modules\TaskBoardModule\Entities\TaskTicketAttachment;
use Modules\TaskBoardModule\Entities\TaskTicketComment;
use Modules\TaskBoardModule\Services\TaskBoardService;

class TaskTicketController extends Controller
{
    public function __construct(
        private readonly TaskBoardService $boardService,
        private readonly StaffChatMessageParser $messageParser,
    ) {
    }

    public function show(string $id): JsonResponse
    {
        $ticket = TaskTicket::withTrashed()
            ->with(['assignees', 'creator', 'links', 'attachments', 'comments.user', 'comments.attachments', 'column', 'activityLogs.actor'])
            ->findOrFail($id);

        $payload = $this->boardService->serializeTicket($ticket);
        $payload['description_html'] = $this->messageParser->format($ticket->description);
        $payload['comments'] = collect($payload['comments'])->map(function (array $comment) {
            $raw = TaskTicketComment::withTrashed()->find($comment['id']);
            $comment['body_html'] = $this->messageParser->format($raw?->body);

            return $comment;
        })->values();

        return response()->json(['success' => true, 'ticket' => $payload]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validatedTicket($request);
        $ticket = $this->boardService->createTicket($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'ticket' => $this->boardService->serializeTicket($ticket),
            ]);
        }

        Toastr::success(translate('Ticket_created_successfully'));

        return back();
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $ticket = TaskTicket::query()->findOrFail($id);
        $data = $this->validatedTicket($request, updating: true);
        $ticket = $this->boardService->updateTicket($ticket, $data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'ticket' => $this->boardService->serializeTicket($ticket),
            ]);
        }

        Toastr::success(translate('Ticket_updated_successfully'));

        return back();
    }

    public function move(Request $request, string $id): JsonResponse
    {
        $ticket = TaskTicket::query()->findOrFail($id);
        $data = $request->validate([
            'column_id' => 'required|uuid|exists:task_columns,id',
            'position' => 'required|integer|min:0',
            'ordered_ids' => 'nullable|array',
            'ordered_ids.*' => 'uuid',
        ]);

        $ticket = $this->boardService->moveTicket(
            $ticket,
            $data['column_id'],
            (int) $data['position'],
            $data['ordered_ids'] ?? [],
        );

        return response()->json([
            'success' => true,
            'ticket' => $this->boardService->serializeTicket($ticket),
        ]);
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $ticket = TaskTicket::query()->findOrFail($id);
        $this->boardService->deleteTicket($ticket);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        Toastr::success(translate('Ticket_deleted_successfully'));

        return back();
    }

    public function restore(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $ticket = TaskTicket::onlyTrashed()->findOrFail($id);
        $this->boardService->restoreTicket($ticket);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'ticket' => $this->boardService->serializeTicket($ticket->fresh(['assignees', 'links', 'attachments', 'column'])),
            ]);
        }

        Toastr::success(translate('Ticket_restored_successfully'));

        return redirect()->route('admin.task-board.index');
    }

    public function storeComment(Request $request, string $id): JsonResponse
    {
        $ticket = TaskTicket::query()->findOrFail($id);
        $data = $request->validate([
            'body' => 'nullable|string|max:5000',
            'files' => 'nullable|array',
            'files.*' => 'file|max:'.uploadMaxFileSizeInKB('file'),
        ]);

        $files = $request->file('files', []);
        $body = trim((string) ($data['body'] ?? ''));

        if ($body === '' && empty($files)) {
            return response()->json([
                'success' => false,
                'message' => translate('Please_write_a_comment_or_attach_a_file'),
            ], 422);
        }

        $comment = $this->boardService->addComment($ticket, $body, $files);

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'body_html' => $this->messageParser->format($comment->body),
                'user' => trim(($comment->user?->first_name ?? '').' '.($comment->user?->last_name ?? '')),
                'created_at' => optional($comment->created_at)?->diffForHumans(),
                'attachments' => $comment->attachments->map(
                    fn ($file) => $this->boardService->serializeAttachment($file)
                )->values(),
            ],
        ]);
    }

    public function destroyComment(string $id): JsonResponse
    {
        $comment = TaskTicketComment::query()->findOrFail($id);
        $this->boardService->deleteComment($comment);

        return response()->json(['success' => true]);
    }

    public function destroyAttachment(string $id): JsonResponse
    {
        $attachment = TaskTicketAttachment::query()->findOrFail($id);
        $this->boardService->deleteAttachment($attachment);

        return response()->json(['success' => true]);
    }

    private function validatedTicket(Request $request, bool $updating = false): array
    {
        $rules = [
            'column_id' => ($updating ? 'nullable' : 'required').'|uuid|exists:task_columns,id',
            'title' => ($updating ? 'nullable' : 'required').'|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'uuid',
            'booking_ids' => 'nullable|array',
            'booking_ids.*' => 'string|max:64',
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'string|max:64',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
        ];

        return $request->validate($rules);
    }
}
