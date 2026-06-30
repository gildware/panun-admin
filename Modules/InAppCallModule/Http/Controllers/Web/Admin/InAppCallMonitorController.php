<?php

namespace Modules\InAppCallModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\InAppCallModule\Entities\InAppCall;
use Modules\InAppCallModule\Services\InAppCallHealthService;
use Modules\InAppCallModule\Services\InAppCallService;

class InAppCallMonitorController extends Controller
{
    public function __construct(
        private readonly InAppCallService $inAppCallService,
        private readonly InAppCallHealthService $healthService,
    ) {
    }

    public function index(Request $request): View
    {
        $request->validate([
            'tab' => 'nullable|in:monitor,status',
            'status' => 'nullable|string|max:32',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $tab = $request->input('tab', 'monitor');
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $activeCalls = $this->inAppCallService->adminActiveCalls();
        $logsQuery = $this->inAppCallService->adminCallLogsQuery($filters);
        $logsPaginator = $logsQuery->paginate(25)->withQueryString();
        $logs = $logsPaginator->through(fn ($call) => $this->inAppCallService->serializeAdminCall($call));

        return view('inappcallmodule::admin.index', [
            'activeCalls' => $activeCalls,
            'logs' => $logs,
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'isEnabled' => $this->inAppCallService->isEnabled(),
            'tab' => $tab,
            'healthReport' => $this->healthService->run(),
        ]);
    }

    public function activeCalls(): JsonResponse
    {
        $activeCalls = $this->inAppCallService->adminActiveCalls();

        return response()->json([
            'count' => count($activeCalls),
            'html' => view('inappcallmodule::admin.partials._active_calls', [
                'activeCalls' => $activeCalls,
            ])->render(),
        ]);
    }

    public function serviceHealth(): JsonResponse
    {
        $report = $this->healthService->run();

        return response()->json([
            'overall' => $report['overall'],
            'checked_at_label' => $report['checked_at_label'],
            'summary' => $report['summary'],
            'recommendations' => $report['recommendations'],
            'html' => view('inappcallmodule::admin.partials._service_status', [
                'healthReport' => $report,
            ])->render(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return [
            '' => translate('All'),
            InAppCall::STATUS_RINGING => translate('Ringing'),
            InAppCall::STATUS_ACCEPTED => translate('Connected'),
            InAppCall::STATUS_DECLINED => translate('Declined'),
            InAppCall::STATUS_MISSED => translate('Missed'),
            InAppCall::STATUS_ENDED => translate('Ended'),
            InAppCall::STATUS_CANCELLED => translate('Cancelled'),
        ];
    }
}
