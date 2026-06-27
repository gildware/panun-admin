<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BookingModule\Entities\BookingCancellationReason;
use Modules\BookingModule\Entities\BookingDisputeReason;
use Modules\BookingModule\Entities\BookingHoldReopenReason;
use Modules\BookingModule\Entities\BookingCustomerCancellationReason;
use Modules\BookingModule\Entities\BookingProviderCancellationReason;

class BookingConfigurationController extends Controller
{
    public function index(): View
    {
        $bookingCancellationReasons = BookingCancellationReason::orderBy('name')->get();
        $bookingProviderCancellationReasons = BookingProviderCancellationReason::orderBy('name')->get();
        $bookingCustomerCancellationReasons = BookingCustomerCancellationReason::orderBy('name')->get();
        $bookingHoldReasons = BookingHoldReopenReason::where('kind', BookingHoldReopenReason::KIND_HOLD)->orderBy('name')->get();
        $bookingReopenReasons = BookingHoldReopenReason::where('kind', BookingHoldReopenReason::KIND_REOPEN)->orderBy('name')->get();
        $bookingDisputeReasons = BookingDisputeReason::orderBy('name')->get();

        return view('bookingmodule::admin.configuration.index', compact(
            'bookingCancellationReasons',
            'bookingProviderCancellationReasons',
            'bookingCustomerCancellationReasons',
            'bookingHoldReasons',
            'bookingReopenReasons',
            'bookingDisputeReasons'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type');
        [$modelClass, $nameField, $extra] = $this->resolveType($type);

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];
        if ($extra['show_responsible'] ?? false) {
            $rules['responsible'] = 'required|in:' . implode(',', BookingCancellationReason::responsibleOptions());
        }
        $data = $request->validate($rules);

        $payload = [
            $nameField => $data['title'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];
        if ($extra['show_responsible'] ?? false) {
            $payload['responsible'] = $data['responsible'];
        }
        if (($extra['kind'] ?? null) !== null) {
            $payload['kind'] = $extra['kind'];
        }
        $modelClass::create($payload);

        Toastr::success(translate('Configuration_saved_successfully'));

        return back();
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

            Toastr::success(translate('Configuration_status_updated_successfully'));

            return back();
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];
        if ($extra['show_responsible'] ?? false) {
            $rules['responsible'] = 'required|in:' . implode(',', BookingCancellationReason::responsibleOptions());
        }
        $data = $request->validate($rules);

        $item->{$nameField} = $data['title'];
        $item->description = $data['description'] ?? null;
        $item->is_active = $request->boolean('is_active', true);
        if ($extra['show_responsible'] ?? false) {
            $item->responsible = $data['responsible'];
        }
        $item->save();

        Toastr::success(translate('Configuration_updated_successfully'));

        return back();
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

        Toastr::success(translate('Configuration_deleted_successfully'));

        return back();
    }

    /**
     * @return array{0: class-string, 1: string, 2: array{kind?: string, show_responsible?: bool}}
     */
    protected function resolveType(string $type): array
    {
        return match ($type) {
            'booking_cancellation_reason' => [BookingCancellationReason::class, 'name', ['show_responsible' => true]],
            'booking_provider_cancellation_reason' => [BookingProviderCancellationReason::class, 'name', ['show_responsible' => false]],
            'booking_customer_cancellation_reason' => [BookingCustomerCancellationReason::class, 'name', ['show_responsible' => false]],
            'booking_hold_reason' => [BookingHoldReopenReason::class, 'name', ['kind' => BookingHoldReopenReason::KIND_HOLD, 'show_responsible' => true]],
            'booking_reopen_reason' => [BookingHoldReopenReason::class, 'name', ['kind' => BookingHoldReopenReason::KIND_REOPEN, 'show_responsible' => true]],
            'booking_dispute_reason' => [BookingDisputeReason::class, 'name', ['show_responsible' => true]],
            default => abort(400, 'Unknown configuration type'),
        };
    }
}
