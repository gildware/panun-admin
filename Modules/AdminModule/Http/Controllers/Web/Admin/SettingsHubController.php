<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Support\AdminSettingsRegistry;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Entities\PeopleTimesheetSetting;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\CustomerModule\Services\CustomerHomeCacheWarmState;
use Modules\UserManagement\Entities\User;
use Illuminate\Validation\Rule;

class SettingsHubController extends Controller
{
    public function __construct(
        private PeopleWorkspace $workspace,
    ) {
    }

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

    public function timesheet(): View|RedirectResponse
    {
        $this->requireTimesheetSettingsAccess();

        $sections = AdminSettingsRegistry::visibleSections();
        if ($sections === []) {
            abort(403);
        }

        $settings = $this->workspace->timesheetSettings();
        $interval = $settings->popupIntervalParts();

        return view('adminmodule::settings.timesheet', [
            'settingsSections' => $sections,
            'activeSettingsSectionKey' => 'people',
            'timesheetSettings' => $settings,
            'popupEnabled' => $settings->popupIsEnabled(),
            'popupIntervalValue' => $interval['value'],
            'popupIntervalUnit' => $interval['unit'],
        ]);
    }

    public function saveTimesheet(Request $request): RedirectResponse
    {
        $this->requireTimesheetSettingsAccess();

        $data = $request->validate([
            'popup_enabled' => ['nullable', 'boolean'],
            'popup_interval_value' => ['required', 'integer', 'min:1', 'max:86400'],
            'popup_interval_unit' => ['required', Rule::in(['seconds', 'minutes', 'hours'])],
        ]);

        $seconds = PeopleTimesheetSetting::popupIntervalToSeconds(
            (int) $data['popup_interval_value'],
            (string) $data['popup_interval_unit'],
        );

        $this->workspace->timesheetSettings()->forceFill([
            'popup_enabled' => $request->boolean('popup_enabled'),
            'popup_interval_seconds' => $seconds,
        ])->save();

        Toastr::success('Timesheet popup settings saved.');

        return redirect()->route('admin.settings.timesheet');
    }

    private function requireTimesheetSettingsAccess(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless(
            $user instanceof User && (is_super_admin() || $this->workspace->isHr($user)),
            403
        );
    }
}
