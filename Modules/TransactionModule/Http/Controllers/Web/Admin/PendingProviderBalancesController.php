<?php

namespace Modules\TransactionModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Modules\ProviderManagement\Entities\Provider;
use Modules\TransactionModule\Services\PendingProviderBalanceListingService;
use Modules\WhatsAppModule\Services\LedgerPaymentWhatsAppService;

class PendingProviderBalancesController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, PendingProviderBalanceListingService $listing): View
    {
        if (!$request->user()?->can('transaction_view') && !$request->user()?->can('ledger_view')) {
            abort(403);
        }

        $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|string',
            'sort' => 'nullable|in:balance_desc,balance_asc,name_asc',
        ]);

        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $sort = $request->input('sort', 'balance_desc');

        $rows = $listing->buildRows(
            is_string($search) ? $search : null,
            is_string($categoryId) ? $categoryId : null,
            is_string($sort) ? $sort : 'balance_desc'
        );

        $perPage = pagination_limit();
        $page = max(1, (int) $request->get('page', 1));
        $paginator = new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('transactionmodule::admin.pending-provider-balances', [
            'rows' => $paginator,
            'categories' => $listing->categoriesForFilter(),
            'search' => is_string($search) ? $search : '',
            'category_id' => is_string($categoryId) ? $categoryId : '',
            'sort' => $sort,
        ]);
    }

    public function sendReminders(Request $request, PendingProviderBalanceListingService $listing): RedirectResponse|JsonResponse
    {
        $this->authorize('provider_update');

        $rules = [
            'mode' => 'required|in:selected,all_filtered',
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|string',
            'sort' => 'nullable|in:balance_desc,balance_asc,name_asc',
        ];
        if ($request->input('mode') === 'selected') {
            $rules['provider_ids'] = 'required|array|min:1';
            $rules['provider_ids.*'] = 'string';
        }
        $request->validate($rules);

        if ($request->input('mode') === 'all_filtered') {
            $ids = array_column(
                $listing->buildRows(
                    $request->input('search'),
                    $request->input('category_id'),
                    $request->input('sort', 'balance_desc')
                ),
                'provider_id'
            );
        } else {
            $ids = $request->input('provider_ids', []);
        }

        $ids = array_values(array_unique(array_filter($ids)));
        $ledgerWa = app(LedgerPaymentWhatsAppService::class);
        $adminUserId = auth()->id() ? (int) auth()->id() : null;

        $sent = 0;
        $failed = 0;
        foreach ($ids as $pid) {
            $provider = Provider::with('owner.account')->find($pid);
            if (!$provider) {
                $failed++;
                continue;
            }
            $result = $ledgerWa->trySendProviderPaymentReminder($provider, $adminUserId);
            if ($result['ok']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $message = __('lang.Pending_provider_balances_reminders_result', [
            'sent' => $sent,
            'failed' => $failed,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $sent > 0 && $failed === 0,
                'sent' => $sent,
                'failed' => $failed,
                'message' => $message,
                'show_chat_link' => false,
                'chat_url' => null,
            ]);
        }

        Toastr::success($message);

        return redirect()->route('admin.transaction.pending_provider_balances.index', $request->only(['search', 'category_id', 'sort', 'page']));
    }
}
