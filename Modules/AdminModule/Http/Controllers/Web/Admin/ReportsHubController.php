<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Support\AdminReportsRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class ReportsHubController extends Controller
{
    public function index(?string $section = null): View|RedirectResponse
    {
        $sections = AdminReportsRegistry::visibleSections();

        if ($sections === []) {
            abort(403);
        }

        $sectionKey = $section ?: AdminReportsRegistry::defaultSectionKey();
        $activeSection = collect($sections)->firstWhere('key', $sectionKey);

        if (! $activeSection) {
            return redirect()->route('admin.reports.index', ['section' => $sections[0]['key']]);
        }

        return view('adminmodule::reports.index', [
            'reportsSections' => $sections,
            'activeReportsSection' => $activeSection,
        ]);
    }
}
