<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Support\AdminSettingsRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\CustomerModule\Services\CustomerHomeCacheWarmState;

class SettingsHubController extends Controller
{
    public function index(?string $section = null): View|RedirectResponse
    {
        $sections = AdminSettingsRegistry::visibleSections();

        if ($sections === []) {
            abort(403);
        }

        $sectionKey = $section ?: $sections[0]['key'];
        $activeSection = collect($sections)->firstWhere('key', $sectionKey);

        if (! $activeSection) {
            return redirect()->route('admin.settings.index', ['section' => $sections[0]['key']]);
        }

        return view('adminmodule::settings.index', [
            'settingsSections' => $sections,
            'activeSettingsSection' => $activeSection,
        ]);
    }

    public function homeCache(): View|RedirectResponse
    {
        if (! is_super_admin()) {
            abort(403);
        }

        $sections = AdminSettingsRegistry::visibleSections();

        if ($sections === []) {
            abort(403);
        }

        return view('adminmodule::settings.home-cache', [
            'settingsSections' => $sections,
            'activeSettingsSectionKey' => 'system',
            'homeCacheNeedsReset' => CustomerHomeCacheWarmState::needsAdminReminder(),
        ]);
    }
}
