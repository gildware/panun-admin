<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\StaffPresenceService;

class StaffPresenceController extends Controller
{
    public function __construct(private readonly StaffPresenceService $presenceService)
    {
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $this->presenceService->heartbeat($user, $request->input('page'));

        return response()->json([
            'status' => 1,
            'data' => [
                'presence_status' => $this->presenceService->resolveDisplayStatus($user->fresh()),
                'presence_label' => $this->presenceService->statusLabel(
                    $this->presenceService->resolveDisplayStatus($user->fresh())
                ),
            ],
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:'.implode(',', StaffPresenceService::statuses()),
        ]);

        $this->presenceService->setStatus($request->user(), $request->input('status'));

        $fresh = $request->user()->fresh();
        $displayStatus = $this->presenceService->resolveDisplayStatus($fresh);

        return response()->json([
            'status' => 1,
            'message' => translate('Status_updated_successfully'),
            'data' => [
                'presence_status' => $displayStatus,
                'presence_label' => $this->presenceService->statusLabel($displayStatus),
            ],
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $staff = $this->presenceService->listStaffPresence();

        return response()->json([
            'status' => 1,
            'data' => [
                'staff' => $staff,
                'summary' => [
                    'online' => $staff->where('presence_status', StaffPresenceService::STATUS_ONLINE)->count(),
                    'away' => $staff->where('presence_status', StaffPresenceService::STATUS_AWAY)->count(),
                    'on_break' => $staff->where('presence_status', StaffPresenceService::STATUS_ON_BREAK)->count(),
                    'offline' => $staff->where('presence_status', StaffPresenceService::STATUS_OFFLINE)->count(),
                    'total' => $staff->count(),
                ],
            ],
        ]);
    }
}
