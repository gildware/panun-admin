<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadManagement\Entities\SuperfoneApiLog;
use Modules\LeadManagement\Entities\SuperfoneCall;

class SuperfoneApiLogController extends Controller
{
    public function index(Request $request): View
    {
        $kind = $request->query('kind');
        $kind = in_array($kind, ['webhook', 'api'], true) ? $kind : '';

        $query = SuperfoneApiLog::query()->latest('id');

        if ($kind === 'webhook') {
            $query->where('kind', 'webhook');
        } elseif ($kind === 'api') {
            $query->where('kind', 'recording');
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($inner) use ($term) {
                $inner->where('event', 'like', '%'.$term.'%')
                    ->orWhere('cdr_uuid', 'like', '%'.$term.'%')
                    ->orWhere('message', 'like', '%'.$term.'%')
                    ->orWhere('url', 'like', '%'.$term.'%');
            });
        }

        if (in_array($request->query('result'), ['received', 'ignored', 'unauthorized', 'empty', 'success', 'failed'], true)) {
            $query->where('result', $request->query('result'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('leadmanagement::admin.calls.api-logs', [
            'logs' => $logs,
            'kind' => $kind,
        ]);
    }

    public function show(SuperfoneApiLog $log): View
    {
        $call = $log->cdr_uuid
            ? SuperfoneCall::query()->where('cdr_uuid', $log->cdr_uuid)->first()
            : null;

        return view('leadmanagement::admin.calls.api-log-show', [
            'log' => $log,
            'call' => $call,
        ]);
    }
}
