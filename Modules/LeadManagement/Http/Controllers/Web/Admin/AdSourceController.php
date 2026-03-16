<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadManagement\Entities\AdSource;

class AdSourceController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $queryParams = ['search' => $search, 'status' => $status];

        $adSources = AdSource::withCount('leads')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('is_active', $status === 'active' ? 1 : 0);
            })
            ->latest()
            ->paginate(pagination_limit())
            ->appends($queryParams);

        return view('leadmanagement::admin.adsources.index', compact('adSources', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:' . uploadMaxFileSizeInKB('image') . '|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = (bool) ($request->input('is_active', true));

        if ($request->hasFile('image')) {
            $validated['image'] = file_uploader('ad-source/', APPLICATION_IMAGE_FORMAT, $request->file('image'));
        }

        AdSource::create($validated);
        toastr()->success(translate('Ad Source created successfully'));
        return redirect()->route('admin.lead.adsource.index', array_filter(['search' => $request->search, 'status' => $request->status]));
    }

    public function edit(int $id): View
    {
        $adSource = AdSource::findOrFail($id);
        return view('leadmanagement::admin.adsources.edit', compact('adSource'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $adSource = AdSource::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:' . uploadMaxFileSizeInKB('image') . '|mimes:' . implode(',', array_column(IMAGEEXTENSION, 'key')),
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = (bool) ($request->input('is_active', true));

        if ($request->hasFile('image')) {
            $validated['image'] = file_uploader('ad-source/', APPLICATION_IMAGE_FORMAT, $request->file('image'), $adSource->image);
        }

        $adSource->update($validated);
        toastr()->success(translate('Ad Source updated successfully'));
        return redirect()->route('admin.lead.adsource.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $adSource = AdSource::findOrFail($id);
        $adSource->delete();
        toastr()->success(translate('Ad Source deleted successfully'));
        return redirect()->route('admin.lead.adsource.index');
    }

    public function statusUpdate(Request $request, int $id): RedirectResponse
    {
        $adSource = AdSource::findOrFail($id);
        $adSource->update(['is_active' => !$adSource->is_active]);
        toastr()->success(translate('Status updated successfully'));
        return redirect()->back();
    }
}
