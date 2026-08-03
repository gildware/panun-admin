<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Entities\BookingComment;
use Modules\BookingModule\Services\BookingCommentService;
use Modules\ChattingModule\Services\StaffChatMessageParser;

class BookingCommentController extends Controller
{
    public function store(Request $request, string $booking): RedirectResponse|JsonResponse
    {
        $booking = Booking::findOrFail($booking);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'redirect_web_page' => 'nullable|in:details,comments',
        ]);

        $author = Auth::user();
        $comment = app(BookingCommentService::class)->addComment(
            $booking,
            trim($validated['body']),
            $author
        );

        $comment->load(['createdBy', 'pinnedByUser']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $this->serializeComment($comment),
            ]);
        }

        toastr()->success(translate('Comment_added_successfully'));

        return redirect($this->commentRedirectUrl($booking->id, $validated['redirect_web_page'] ?? 'details'));
    }

    public function togglePin(Request $request, int $commentId): JsonResponse
    {
        $comment = BookingComment::with('booking')->findOrFail($commentId);
        $comment = app(BookingCommentService::class)->togglePin($comment, Auth::user());

        return response()->json([
            'success' => true,
            'comment' => $this->serializeComment($comment),
        ]);
    }

    public function destroy(Request $request, int $commentId): RedirectResponse|JsonResponse
    {
        $comment = BookingComment::with('booking')->findOrFail($commentId);
        $user = Auth::user();

        if ((string) $comment->created_by !== (string) $user->id && $user->user_type !== 'super-admin') {
            if ($request->expectsJson()) {
                return response()->json(['message' => translate('Unauthorized')], 403);
            }

            abort(403);
        }

        $bookingId = $comment->booking_id;
        $comment->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        toastr()->success(translate('Comment_deleted_successfully'));

        return redirect($this->commentRedirectUrl(
            $bookingId,
            $request->input('redirect_web_page', 'details')
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(BookingComment $comment): array
    {
        $author = $comment->createdBy;
        $authorName = '—';
        if ($author) {
            $fullName = trim(($author->first_name ?? '').' '.($author->last_name ?? ''));
            $authorName = $fullName ?: ($author->email ?? '—');
        }

        return [
            'id' => $comment->id,
            'body_html' => app(StaffChatMessageParser::class)->format($comment->body),
            'author_name' => $authorName,
            'created_at' => $comment->created_at?->format('d M Y, h:i A') ?? '—',
            'is_pinned' => (bool) $comment->is_pinned,
            'can_delete' => (string) $comment->created_by === (string) Auth::id()
                || Auth::user()?->user_type === 'super-admin',
        ];
    }

    private function commentRedirectUrl(string $bookingId, ?string $webPage): string
    {
        $webPage = in_array($webPage, ['details', 'comments'], true) ? $webPage : 'details';
        $hash = $webPage === 'comments' ? '#booking-comments' : '#booking-activity';

        return route('admin.booking.details', [$bookingId, 'web_page' => $webPage]).$hash;
    }
}
