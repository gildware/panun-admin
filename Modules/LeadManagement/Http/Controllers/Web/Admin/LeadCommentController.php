<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\LeadComment;
use Modules\LeadManagement\Services\LeadCommentService;

class LeadCommentController extends Controller
{
    public function store(Request $request, int $lead): RedirectResponse|JsonResponse
    {
        $lead = Lead::findOrFail($lead);

        $validated = $request->validate([
            'body' => 'nullable|string|max:5000',
            'files' => 'nullable|array',
            'files.*' => 'file|max:'.uploadMaxFileSizeInKB('file'),
        ]);

        $files = $request->file('files', []);
        $body = trim((string) ($validated['body'] ?? ''));

        if ($body === '' && empty($files)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Please_write_a_comment_or_attach_a_file'),
                ], 422);
            }

            return back()->withErrors(['body' => translate('Please_write_a_comment_or_attach_a_file')]);
        }

        $author = Auth::user();
        $comment = app(LeadCommentService::class)->addComment(
            $lead,
            $body,
            $author,
            $files
        );

        $comment->load(['createdBy', 'pinnedByUser', 'attachments']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $this->serializeComment($comment),
            ]);
        }

        toastr()->success(translate('Comment_added_successfully'));

        $url = route('admin.lead.show', $lead->id).'?activity=comment#lead-activity';
        if ($request->boolean('in_modal')) {
            $url = route('admin.lead.show', $lead->id).'?in_modal=1&activity=comment#lead-activity';
        }

        return redirect($url);
    }

    public function togglePin(Request $request, int $commentId): JsonResponse
    {
        $comment = LeadComment::with('lead')->findOrFail($commentId);
        $comment = app(LeadCommentService::class)->togglePin($comment, Auth::user());

        return response()->json([
            'success' => true,
            'comment' => $this->serializeComment($comment),
        ]);
    }

    public function destroy(Request $request, int $commentId): RedirectResponse|JsonResponse
    {
        $comment = LeadComment::with('lead')->findOrFail($commentId);
        $user = Auth::user();

        if ((string) $comment->created_by !== (string) $user->id && $user->user_type !== 'super-admin') {
            if ($request->expectsJson()) {
                return response()->json(['message' => translate('Unauthorized')], 403);
            }

            abort(403);
        }

        $leadId = $comment->lead_id;
        $comment->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        toastr()->success(translate('Comment_deleted_successfully'));

        $url = route('admin.lead.show', $leadId).'?activity=comment#lead-activity';
        if ($request->boolean('in_modal')) {
            $url = route('admin.lead.show', $leadId).'?in_modal=1&activity=comment#lead-activity';
        }

        return redirect($url);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(LeadComment $comment): array
    {
        $author = $comment->createdBy;
        $authorName = '—';
        if ($author) {
            $fullName = trim(($author->first_name ?? '').' '.($author->last_name ?? ''));
            $authorName = $fullName ?: ($author->email ?? '—');
        }

        return [
            'id' => $comment->id,
            'body_html' => app(\Modules\ChattingModule\Services\StaffChatMessageParser::class)->format($comment->body),
            'author_name' => $authorName,
            'created_at' => $comment->created_at?->format('d M Y, h:i A') ?? '—',
            'is_pinned' => (bool) $comment->is_pinned,
            'can_delete' => (string) $comment->created_by === (string) Auth::id()
                || Auth::user()?->user_type === 'super-admin',
            'attachments' => $comment->relationLoaded('attachments')
                ? $comment->attachments->map(fn ($file) => $this->serializeAttachment($file))->values()->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAttachment(\Modules\LeadManagement\Entities\LeadCommentAttachment $file): array
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
}
