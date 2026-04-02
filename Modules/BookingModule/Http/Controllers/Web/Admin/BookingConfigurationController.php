<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BookingModule\Entities\BookingCancellationReason;
use Modules\BookingModule\Entities\BookingHoldReopenReason;

class BookingConfigurationController extends Controller
{
    public function index(): View
    {
        $bookingCancellationReasons = BookingCancellationReason::orderBy('name')->get();
        $bookingHoldReasons = BookingHoldReopenReason::where('kind', BookingHoldReopenReason::KIND_HOLD)->orderBy('name')->get();
        $bookingReopenReasons = BookingHoldReopenReason::where('kind', BookingHoldReopenReason::KIND_REOPEN)->orderBy('name')->get();

        return view('bookingmodule::admin.configuration.index', compact(
            'bookingCancellationReasons',
            'bookingHoldReasons',
            'bookingReopenReasons'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type');
        [$modelClass, $nameField, $extra] = $this->resolveType($type);

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'responsible' => 'required|in:' . implode(',', BookingCancellationReason::responsibleOptions()),
            'is_active' => 'nullable|boolean',
        ];
        $data = $request->validate($rules);

        $payload = [
            $nameField => $data['title'],
            'description' => $data['description'] ?? null,
            'responsible' => $data['responsible'],
            'is_active' => $request->boolean('is_active', true),
        ];
        if (($extra['kind'] ?? null) !== null) {
            $payload['kind'] = $extra['kind'];
        }
        $modelClass::create($payload);

        return back()->with('success', translate('Configuration_saved_successfully'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $type = $request->input('type');
        $mode = $request->input('mode', 'edit');

        [$modelClass, $nameField, $extra] = $this->resolveType($type);

        $item = $modelClass::findOrFail($id);
        if (($extra['kind'] ?? null) !== null && (string) $item->kind !== (string) $extra['kind']) {
            abort(400);
        }

        if ($mode === 'toggle') {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $item->is_active = (bool) $request->input('is_active');
            $item->save();

            return back()->with('success', translate('Configuration_status_updated_successfully'));
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'responsible' => 'required|in:' . implode(',', BookingCancellationReason::responsibleOptions()),
            'is_active' => 'nullable|boolean',
        ];
        $data = $request->validate($rules);

        $item->{$nameField} = $data['title'];
        $item->description = $data['description'] ?? null;
        $item->responsible = $data['responsible'];
        $item->is_active = $request->boolean('is_active', true);
        $item->save();

        return back()->with('success', translate('Configuration_updated_successfully'));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $type = $request->input('type');

        [$modelClass, , $extra] = $this->resolveType($type);

        $item = $modelClass::findOrFail($id);
        if (($extra['kind'] ?? null) !== null && (string) $item->kind !== (string) $extra['kind']) {
            abort(400);
        }
        $item->delete();

        return back()->with('success', translate('Configuration_deleted_successfully'));
    }

    /**
     * @return array{0: class-string, 1: string, 2: array{kind?: string}}
     */
    protected function resolveType(string $type): array
    {
        return match ($type) {
            'booking_cancellation_reason' => [BookingCancellationReason::class, 'name', []],
            'booking_hold_reason' => [BookingHoldReopenReason::class, 'name', ['kind' => BookingHoldReopenReason::KIND_HOLD]],
            'booking_reopen_reason' => [BookingHoldReopenReason::class, 'name', ['kind' => BookingHoldReopenReason::KIND_REOPEN]],
            default => abort(400, 'Unknown configuration type'),
        };
    }
}
