<?php

namespace Modules\TaskBoardModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TaskBoardModule\Entities\TaskColumn;
use Modules\TaskBoardModule\Services\TaskBoardService;

class TaskColumnController extends Controller
{
    public function __construct(
        private readonly TaskBoardService $boardService,
    ) {
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'color' => 'nullable|string|max:32',
        ]);

        $column = $this->boardService->createColumn($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'column' => $column]);
        }

        Toastr::success(translate('Column_created_successfully'));

        return back();
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $column = TaskColumn::query()->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'color' => 'nullable|string|max:32',
        ]);

        $column = $this->boardService->updateColumn($column, $data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'column' => $column]);
        }

        Toastr::success(translate('Column_updated_successfully'));

        return back();
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $column = TaskColumn::query()->findOrFail($id);
        $this->boardService->deleteColumn($column);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        Toastr::success(translate('Column_deleted_successfully'));

        return back();
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'uuid',
        ]);

        $this->boardService->reorderColumns($data['order']);

        return response()->json(['success' => true]);
    }
}
