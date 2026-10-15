<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Support\AdminWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\AdminInboxNotificationService;

class WorkspaceController extends Controller
{
    public function choose(AdminInboxNotificationService $inboxNotificationService): View
    {
        $user = auth()->user();
        $profileName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($profileName === '') {
            $profileName = $user->email ?? 'Signed in';
        }

        if ($user->user_type === 'super-admin') {
            $roleLabel = 'Super admin';
        } elseif ($user->user_type === 'admin-employee') {
            $roleLabel = $user->roles->pluck('role_name')->filter()->implode(', ') ?: 'Employee';
        } else {
            $roleLabel = ucwords(str_replace('-', ' ', (string) $user->user_type));
        }

        return view('adminmodule::workspace-choose', [
            'signedInUser' => $user,
            'profileName' => $profileName,
            'profileImage' => admin_nav_image_src($user->profile_image_full_path, 'profile'),
            'profileFallback' => admin_nav_placeholder('profile'),
            'roleLabel' => $roleLabel,
            'workspaceNotificationCounts' => $inboxNotificationService->unreadCountsByWorkspace((string) $user->id),
        ]);
    }

    public function enter(string $workspace): RedirectResponse
    {
        abort_unless(in_array($workspace, AdminWorkspace::keys(), true), 404);
        abort_unless(AdminWorkspace::userCanEnter($workspace), 403);

        session(['admin_workspace' => $workspace]);

        return redirect()->to(AdminWorkspace::landing($workspace));
    }
}
