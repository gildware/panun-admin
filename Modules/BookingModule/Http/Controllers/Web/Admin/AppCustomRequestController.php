<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminMenuCounts;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\BookingModule\Entities\AppCustomRequest;
use Modules\BookingModule\Entities\AppCustomRequestMessage;

class AppCustomRequestController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $requests = AppCustomRequest::query()
            ->with(['lead.source'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('reference_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('category_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(pagination_limit())
            ->withQueryString();

        return view('bookingmodule::admin.app-custom-request.index', compact('requests', 'search', 'status'));
    }

    public function show(int $id): View
    {
        $customRequest = AppCustomRequest::query()
            ->with(['lead.source', 'customer', 'messages.sender'])
            ->findOrFail($id);

        return view('bookingmodule::admin.app-custom-request.show', compact('customRequest'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', [
                AppCustomRequest::STATUS_PENDING,
                AppCustomRequest::STATUS_ACCEPTED,
                AppCustomRequest::STATUS_REJECTED,
            ]),
            'admin_message' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            Toastr::error(translate('Invalid_data'));

            return back()->withErrors($validator)->withInput();
        }

        $customRequest = AppCustomRequest::query()->findOrFail($id);
        $previousStatus = $customRequest->status;
        $customRequest->status = (string) $request->input('status');
        $customRequest->save();

        if ($previousStatus !== $customRequest->status) {
            try {
                send_app_custom_request_status_change_notification($customRequest, $previousStatus);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $adminMessage = trim((string) $request->input('admin_message', ''));
        if ($adminMessage !== '') {
            AppCustomRequestMessage::create([
                'app_custom_request_id' => $customRequest->id,
                'sender_type' => AppCustomRequestMessage::SENDER_ADMIN,
                'sender_id' => auth()->id(),
                'message' => $adminMessage,
            ]);

            try {
                send_app_custom_request_admin_reply_notification($customRequest);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));

        AdminMenuCounts::forget();

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->authorize('booking_delete');

        $customRequest = AppCustomRequest::query()->findOrFail($id);
        $customRequest->delete();

        Toastr::success(translate(DEFAULT_DELETE_200['message']));

        AdminMenuCounts::forget();

        return redirect()->route('admin.booking.app-custom-requests.index');
    }
}
