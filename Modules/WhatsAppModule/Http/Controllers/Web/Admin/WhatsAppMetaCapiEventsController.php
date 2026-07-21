<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsAppModule\Entities\WhatsAppMetaCapiEvent;
use Modules\WhatsAppModule\Services\MetaConversionsApiService;

class WhatsAppMetaCapiEventsController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('whatsapp_chat_view');

        $siInboxCh = (string) ($request->route('channel') ?? 'whatsapp');
        $status = trim((string) $request->query('status', ''));
        $eventName = trim((string) $request->query('event_name', ''));
        $search = trim((string) $request->query('q', ''));

        $tableReady = Schema::hasTable((new WhatsAppMetaCapiEvent)->getTable());
        $events = null;
        $statusCounts = [
            'all' => 0,
            WhatsAppMetaCapiEvent::STATUS_SENT => 0,
            WhatsAppMetaCapiEvent::STATUS_FAILED => 0,
            WhatsAppMetaCapiEvent::STATUS_PENDING => 0,
        ];

        if ($tableReady) {
            $base = WhatsAppMetaCapiEvent::query()->when(
                $siInboxCh !== '',
                fn ($q) => $q->where(function ($inner) use ($siInboxCh) {
                    $inner->where('channel', $siInboxCh)->orWhereNull('channel');
                })
            );

            $statusCounts['all'] = (clone $base)->count();
            foreach ([WhatsAppMetaCapiEvent::STATUS_SENT, WhatsAppMetaCapiEvent::STATUS_FAILED, WhatsAppMetaCapiEvent::STATUS_PENDING] as $st) {
                $statusCounts[$st] = (clone $base)->where('status', $st)->count();
            }

            $query = (clone $base)->orderByDesc('id');
            if ($status !== '' && in_array($status, [
                WhatsAppMetaCapiEvent::STATUS_SENT,
                WhatsAppMetaCapiEvent::STATUS_FAILED,
                WhatsAppMetaCapiEvent::STATUS_PENDING,
                WhatsAppMetaCapiEvent::STATUS_SKIPPED,
            ], true)) {
                $query->where('status', $status);
            }
            if ($eventName !== '' && in_array($eventName, [
                MetaConversionsApiService::EVENT_LEAD_SUBMITTED,
                MetaConversionsApiService::EVENT_SCHEDULE,
                MetaConversionsApiService::EVENT_PURCHASE,
            ], true)) {
                $query->where('event_name', $eventName);
            }
            if ($search !== '') {
                $digits = preg_replace('/\D+/', '', $search) ?? '';
                $query->where(function ($q) use ($search, $digits) {
                    $q->where('phone', 'like', '%'.$search.'%')
                        ->orWhere('event_id', 'like', '%'.$search.'%')
                        ->orWhere('ctwa_clid', 'like', '%'.$search.'%');
                    if ($digits !== '') {
                        $q->orWhere('phone', 'like', '%'.$digits.'%');
                    }
                    if (ctype_digit($search)) {
                        $q->orWhere('lead_id', (int) $search);
                    }
                });
            }

            $events = $query->paginate(pagination_limit())->appends($request->query());
        }

        $datasetId = trim((string) config('services.meta_conversions.dataset_id', ''));
        $capiConfigured = app(MetaConversionsApiService::class)->isConfigured();
        $fbEventsManagerUrl = $datasetId !== ''
            ? 'https://business.facebook.com/events_manager2/list/dataset/'.$datasetId
            : 'https://business.facebook.com/events_manager2';
        $fbTestEventsUrl = $datasetId !== ''
            ? 'https://business.facebook.com/events_manager2/list/dataset/'.$datasetId.'/test_events'
            : 'https://business.facebook.com/events_manager2';

        return view('whatsappmodule::admin.meta-capi-events', [
            'siInboxCh' => $siInboxCh,
            'events' => $events,
            'tableReady' => $tableReady,
            'statusCounts' => $statusCounts,
            'status' => $status,
            'eventName' => $eventName,
            'search' => $search,
            'capiConfigured' => $capiConfigured,
            'datasetId' => $datasetId,
            'fbEventsManagerUrl' => $fbEventsManagerUrl,
            'fbTestEventsUrl' => $fbTestEventsUrl,
        ]);
    }
}
