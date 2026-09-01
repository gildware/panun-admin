<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Support\ProcessGuideRegistry;
use Modules\AdminModule\Support\ProcessGuideSearch;
use Modules\AdminModule\Support\ProcessGuideStorage;

class ProcessGuideController extends Controller
{
    public function index(Request $request): View
    {
        $guideKey = (string) $request->query('guide', 'panun-kaergar');
        $guide = ProcessGuideRegistry::get($guideKey) ?? ProcessGuideRegistry::default();

        return view('adminmodule::admin.process-guide.index', [
            'guide' => $guide,
            'guides' => ProcessGuideRegistry::all(),
            'initialSlideId' => (string) $request->query('slide', ''),
            'trainingSearchIndex' => ProcessGuideSearch::index(),
            'miroBoardId' => $guide['miro_board_id'] ?? null,
            'miroShareLinkId' => $guide['miro_share_link_id'] ?? null,
            'miroTitle' => $guide['title'],
            'boardJsonUrl' => route('admin.process-guides.board'),
            'boardSaveUrl' => route('admin.process-guides.board.save'),
            'groupsSaveUrl' => route('admin.process-guides.groups.save'),
            'processGuideGroups' => ProcessGuideStorage::groups(),
        ]);
    }

    public function board(): JsonResponse
    {
        return response()->json(ProcessGuideStorage::loadBoard());
    }

    public function saveBoard(Request $request): JsonResponse
    {
        $request->validate([
            'shapes' => ['required', 'array'],
            'shapes.*.id' => ['required', 'string'],
            'shapes.*.x' => ['required', 'numeric'],
            'shapes.*.y' => ['required', 'numeric'],
            'shapes.*.w' => ['required', 'numeric'],
            'shapes.*.h' => ['required', 'numeric'],
            'shapes.*.shape' => ['required', 'string'],
            'connectors' => ['nullable', 'array'],
            'labels' => ['nullable', 'array'],
        ]);

        ProcessGuideStorage::saveBoard($request->all());

        return response()->json([
            'status' => 1,
            'message' => translate('Saved_successfully'),
        ]);
    }

    public function saveGroups(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'groups' => ['required', 'array'],
            'groups.*.id' => ['required', 'string'],
            'groups.*.step' => ['required', 'integer', 'min:1'],
            'groups.*.title' => ['required', 'string', 'max:255'],
            'groups.*.subtitle' => ['nullable', 'string', 'max:255'],
            'groups.*.intro' => ['nullable', 'string'],
            'groups.*.nodeIds' => ['nullable', 'array'],
            'groups.*.nodeIds.*' => ['string'],
            'groups.*.matchKinds' => ['nullable', 'array'],
            'groups.*.matchTextContains' => ['nullable', 'array'],
            'groups.*.sections' => ['nullable', 'array'],
            'groups.*.notes' => ['nullable', 'array'],
        ]);

        ProcessGuideStorage::saveGroups($validated['groups']);

        return response()->json([
            'status' => 1,
            'message' => translate('Saved_successfully'),
        ]);
    }
}
