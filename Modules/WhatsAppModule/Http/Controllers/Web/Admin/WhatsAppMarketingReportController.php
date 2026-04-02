<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingCampaign;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingMessage;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;

class WhatsAppMarketingReportController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('whatsapp_marketing_report_view');

        $totalCampaigns = WhatsAppMarketingCampaign::query()->count();
        $totalMessages = WhatsAppMarketingMessage::query()->count();

        $accepted = WhatsAppMarketingMessage::query()->whereIn('status', [
            WhatsAppMarketingMessage::STATUS_SENT,
            WhatsAppMarketingMessage::STATUS_DELIVERED,
            WhatsAppMarketingMessage::STATUS_READ,
            WhatsAppMarketingMessage::STATUS_REPLIED,
        ])->count();

        $delivered = WhatsAppMarketingMessage::query()->whereIn('status', [
            WhatsAppMarketingMessage::STATUS_DELIVERED,
            WhatsAppMarketingMessage::STATUS_READ,
            WhatsAppMarketingMessage::STATUS_REPLIED,
        ])->count();

        $read = WhatsAppMarketingMessage::query()->whereIn('status', [
            WhatsAppMarketingMessage::STATUS_READ,
            WhatsAppMarketingMessage::STATUS_REPLIED,
        ])->count();

        $failed = WhatsAppMarketingMessage::query()
            ->where('status', WhatsAppMarketingMessage::STATUS_FAILED)
            ->count();

        $deliveryPct = $accepted > 0 ? round(100 * $delivered / $accepted, 1) : 0;
        $readPct = $delivered > 0 ? round(100 * $read / $delivered, 1) : 0;
        $failurePct = $totalMessages > 0 ? round(100 * $failed / $totalMessages, 1) : 0;

        $messagesPerDay = WhatsAppMarketingMessage::query()
            ->whereNotNull('sent_at')
            ->selectRaw('DATE(sent_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderByDesc('d')
            ->limit(30)
            ->get()
            ->sortBy('d')
            ->values();

        $topCampaigns = WhatsAppMarketingCampaign::query()
            ->withCount([
                'messages as delivered_total' => fn ($q) => $q->whereIn('status', [
                    WhatsAppMarketingMessage::STATUS_DELIVERED,
                    WhatsAppMarketingMessage::STATUS_READ,
                    WhatsAppMarketingMessage::STATUS_REPLIED,
                ]),
            ])
            ->orderByDesc('delivered_total')
            ->limit(10)
            ->get(['id', 'name']);

        $topTemplates = WhatsAppMarketingTemplate::query()
            ->withCount('campaigns')
            ->orderByDesc('campaigns_count')
            ->limit(10)
            ->get(['id', 'name', 'language']);

        return view('whatsappmodule::admin.marketing.reports-index', compact(
            'totalCampaigns',
            'totalMessages',
            'accepted',
            'delivered',
            'read',
            'failed',
            'deliveryPct',
            'readPct',
            'failurePct',
            'messagesPerDay',
            'topCampaigns',
            'topTemplates'
        ));
    }
}
