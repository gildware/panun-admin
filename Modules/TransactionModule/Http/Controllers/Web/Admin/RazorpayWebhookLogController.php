<?php

namespace Modules\TransactionModule\Http\Controllers\Web\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\PaymentModule\Entities\RazorpayWebhookLog;

class RazorpayWebhookLogController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Renderable
    {
        $this->authorize('transaction_view');

        $request->validate([
            'result' => 'nullable|string|max:64',
            'search' => 'nullable|string|max:191',
        ]);

        $search = trim((string) $request->get('search', ''));
        $result = trim((string) $request->get('result', ''));

        $query = RazorpayWebhookLog::query()->latest();

        if ($result !== '') {
            $query->where('result', $result);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('razorpay_payment_id', 'like', '%' . $search . '%')
                    ->orWhere('razorpay_order_id', 'like', '%' . $search . '%')
                    ->orWhere('payment_request_id', 'like', '%' . $search . '%')
                    ->orWhere('booking_readable_id', 'like', '%' . $search . '%')
                    ->orWhere('event', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->paginate(pagination_limit())->appends($request->query());

        $summary = [
            'total' => RazorpayWebhookLog::query()->count(),
            'successful' => RazorpayWebhookLog::query()->whereIn('result', ['completed', 'already_completed'])->count(),
            'failed' => RazorpayWebhookLog::query()->whereIn('result', [
                'failed',
                'fulfillment_failed',
                'invalid_signature',
                'not_found',
                'amount_mismatch',
            ])->count(),
            'last_received_at' => RazorpayWebhookLog::query()->latest()->value('created_at'),
        ];

        return view('transactionmodule::admin.razorpay-webhook-logs', [
            'logs' => $logs,
            'summary' => $summary,
            'search' => $search,
            'result' => $result,
        ]);
    }

    public function show(string $id): Renderable
    {
        $this->authorize('transaction_view');

        $log = RazorpayWebhookLog::query()->findOrFail($id);

        return view('transactionmodule::admin.razorpay-webhook-log-details', [
            'log' => $log,
        ]);
    }
}
