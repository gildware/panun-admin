<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Support\AdminMarketingRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class MarketingHubController extends Controller
{
    public function index(?string $section = null): View|RedirectResponse
    {
        $sections = AdminMarketingRegistry::visibleSections();

        if ($sections === []) {
            abort(403);
        }

        $sectionKey = $section ?: $sections[0]['key'];
        $activeSection = collect($sections)->firstWhere('key', $sectionKey);

        if (! $activeSection) {
            return redirect()->route('admin.marketing.index', ['section' => $sections[0]['key']]);
        }

        return view('adminmodule::marketing.index', [
            'marketingSections' => $sections,
            'activeMarketingSection' => $activeSection,
        ]);
    }
}
