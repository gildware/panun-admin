<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\AdminInboxNotificationService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationController extends Controller
{
    public function index(Request $request, AdminInboxNotificationService $inboxNotificationService): View
    {
        $userId = (string) $request->user()->id;
        $filter = $request->query('filter');

        if (! in_array($filter, ['unread', 'read', null], true)) {
            $filter = null;
        }

        return view('adminmodule::admin.notifications.index', [
            'notifications' => $inboxNotificationService->paginated($userId, $filter),
            'filter' => $filter,
            'unreadCount' => $inboxNotificationService->unreadCount($userId),
            'readCount' => $inboxNotificationService->readCount($userId),
        ]);
    }

    public function show(Request $request, string $id, AdminInboxNotificationService $inboxNotificationService): View
    {
        $userId = (string) $request->user()->id;
        $notification = $inboxNotificationService->findForUser($id, $userId);

        if (! $notification) {
            throw new NotFoundHttpException();
        }

        if ($notification->isUnread()) {
            $inboxNotificationService->markAsRead($id, $userId);
            $notification->refresh();
        }

        return view('adminmodule::admin.notifications.show', [
            'notification' => $notification,
        ]);
    }

    public function markAllRead(Request $request, AdminInboxNotificationService $inboxNotificationService): RedirectResponse
    {
        $inboxNotificationService->markAllAsRead((string) $request->user()->id);
        Toastr::success(translate(DEFAULT_UPDATE_200['message']));

        return redirect()->route('admin.notifications.index', $request->only('filter'));
    }
}
