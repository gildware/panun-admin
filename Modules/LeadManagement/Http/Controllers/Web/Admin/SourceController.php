<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadManagement\Entities\Source;

class SourceController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $queryParams = ['search' => $search, 'status' => $status];

        $sources = Source::withCount('leads')
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

        return view('leadmanagement::admin.sources.index', compact('sources', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = (bool) ($request->input('is_active', true));

        Source::create($validated);
        toastr()->success(translate('Source created successfully'));
        return redirect()->route('admin.lead.source.index', array_filter(['search' => $request->search, 'status' => $request->status]));
    }

    public function edit(int $id): View
    {
        $source = Source::findOrFail($id);
        return view('leadmanagement::admin.sources.edit', compact('source'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $source = Source::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = (bool) ($request->input('is_active', true));
        $source->update($validated);
        toastr()->success(translate('Source updated successfully'));
        return redirect()->route('admin.lead.source.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $source = Source::findOrFail($id);
        $source->delete();
        toastr()->success(translate('Source deleted successfully'));
        return redirect()->route('admin.lead.source.index');
    }

    public function statusUpdate(Request $request, int $id): RedirectResponse
    {
        $source = Source::findOrFail($id);
        $source->update(['is_active' => !$source->is_active]);
        toastr()->success(translate('Status updated successfully'));
        return redirect()->back();
    }
}
