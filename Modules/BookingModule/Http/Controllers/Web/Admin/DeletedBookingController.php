<?php

namespace Modules\BookingModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\BookingModule\Entities\Booking;
use Modules\BookingModule\Services\AdminBookingDeletionService;

class DeletedBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(is_super_admin(), 403);

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $bookings = Booking::onlyTrashed()
            ->with([
                'customer',
                'deletedByUser',
                'provider' => function ($query) {
                    $query->withTrashed();
                },
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('readable_id', 'like', '%'.$search.'%')
                        ->orWhere('id', 'like', '%'.$search.'%')
                        ->orWhere('deletion_reason', 'like', '%'.$search.'%')
                        ->orWhereHas('provider', function ($provider) use ($search) {
                            $provider->withTrashed()->where(function ($name) use ($search) {
                                $name->where('company_name', 'like', '%'.$search.'%')
                                    ->orWhere('company_phone', 'like', '%'.$search.'%');
                            });
                        })
                        ->orWhereHas('customer', function ($customer) use ($search) {
                            $customer->where(function ($name) use ($search) {
                                $name->where('first_name', 'like', '%'.$search.'%')
                                    ->orWhere('last_name', 'like', '%'.$search.'%')
                                    ->orWhere('phone', 'like', '%'.$search.'%');
                            });
                        });
                });
            })
            ->latest('deleted_at')
            ->paginate(30)
            ->withQueryString();

        return view('bookingmodule::admin.booking.deleted', [
            'bookings' => $bookings,
            'search' => $search,
        ]);
    }

    public function restore(string $id): RedirectResponse
    {
        $booking = Booking::onlyTrashed()->findOrFail($id);
        $booking->deleted_by = null;
        $booking->deletion_reason = null;
        $booking->restore();

        Toastr::success(translate('Booking_restored_successfully'));

        return redirect()->route('admin.booking.deleted');
    }

    public function forceDestroy(string $id): RedirectResponse
    {
        $booking = Booking::onlyTrashed()
            ->with([
                'detail',
                'details_amounts',
                'schedule_histories',
                'status_histories',
                'booking_offline_payments',
                'ignores',
                'reviews',
                'booking_partial_payments',
                'extra_services',
                'repeat.detail',
                'repeat.details_amounts',
                'repeat.statusHistories',
                'repeat.scheduleHistories',
                'repeat.repeatHistories',
            ])
            ->findOrFail($id);

        DB::transaction(function () use ($booking) {
            app(AdminBookingDeletionService::class)->deleteBookingAndRelations($booking);
        });

        Toastr::success(translate('Booking_deleted_permanently'));

        return redirect()->route('admin.booking.deleted');
    }
}
