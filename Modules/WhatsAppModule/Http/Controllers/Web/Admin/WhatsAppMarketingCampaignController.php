<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingCampaign;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingMessage;
use Modules\WhatsAppModule\Jobs\ProcessWhatsAppMarketingCampaignJob;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsAppMarketingCampaignController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('whatsapp_marketing_campaign_view');

        $campaigns = WhatsAppMarketingCampaign::query()
            ->with('template')
            ->withCount([
                'messages as total_recipients_count',
                'messages as sent_count' => fn ($q) => $q->whereIn('status', [
                    WhatsAppMarketingMessage::STATUS_SENT,
                    WhatsAppMarketingMessage::STATUS_DELIVERED,
                    WhatsAppMarketingMessage::STATUS_READ,
                    WhatsAppMarketingMessage::STATUS_REPLIED,
                ]),
                'messages as delivered_count' => fn ($q) => $q->whereIn('status', [
                    WhatsAppMarketingMessage::STATUS_DELIVERED,
                    WhatsAppMarketingMessage::STATUS_READ,
                    WhatsAppMarketingMessage::STATUS_REPLIED,
                ]),
                'messages as read_count' => fn ($q) => $q->whereIn('status', [
                    WhatsAppMarketingMessage::STATUS_READ,
                    WhatsAppMarketingMessage::STATUS_REPLIED,
                ]),
                'messages as failed_count' => fn ($q) => $q->where('status', WhatsAppMarketingMessage::STATUS_FAILED),
                'messages as replied_count' => fn ($q) => $q->where('status', WhatsAppMarketingMessage::STATUS_REPLIED),
            ])
            ->latest()
            ->paginate(pagination_limit());

        return view('whatsappmodule::admin.marketing.campaigns-index', compact('campaigns'));
    }

    public function show(Request $request, int $id): View
    {
        $this->authorize('whatsapp_marketing_campaign_view');

        $campaign = WhatsAppMarketingCampaign::query()
            ->with(['template', 'category', 'creator'])
            ->findOrFail($id);

        $tab = $request->query('tab', 'overview');
        if (!in_array($tab, ['overview', 'sent', 'delivered', 'read', 'failed', 'replied'], true)) {
            $tab = 'overview';
        }

        $search = $request->query('search', '');

        $messagesQuery = $campaign->messages()->orderByDesc('id');

        if ($search !== '') {
            $messagesQuery->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', '%' . $search . '%')
                    ->orWhere('phone_e164', 'like', '%' . $search . '%');
            });
        }

        if ($tab === 'sent') {
            $messagesQuery->whereIn('status', [
                WhatsAppMarketingMessage::STATUS_SENT,
                WhatsAppMarketingMessage::STATUS_DELIVERED,
                WhatsAppMarketingMessage::STATUS_READ,
                WhatsAppMarketingMessage::STATUS_REPLIED,
            ]);
        } elseif ($tab === 'delivered') {
            $messagesQuery->whereIn('status', [
                WhatsAppMarketingMessage::STATUS_DELIVERED,
                WhatsAppMarketingMessage::STATUS_READ,
                WhatsAppMarketingMessage::STATUS_REPLIED,
            ]);
        } elseif ($tab === 'read') {
            $messagesQuery->where('status', WhatsAppMarketingMessage::STATUS_READ);
        } elseif ($tab === 'failed') {
            $messagesQuery->where('status', WhatsAppMarketingMessage::STATUS_FAILED);
        } elseif ($tab === 'replied') {
            $messagesQuery->where('status', WhatsAppMarketingMessage::STATUS_REPLIED);
        }

        $messages = $tab === 'overview'
            ? null
            : $messagesQuery->paginate(pagination_limit())->appends(['tab' => $tab, 'search' => $search]);

        $overviewStats = [
            'total' => $campaign->messages()->count(),
            'sent' => $campaign->messages()->whereIn('status', [
                WhatsAppMarketingMessage::STATUS_SENT,
                WhatsAppMarketingMessage::STATUS_DELIVERED,
                WhatsAppMarketingMessage::STATUS_READ,
                WhatsAppMarketingMessage::STATUS_REPLIED,
            ])->count(),
            'delivered' => $campaign->messages()->whereIn('status', [
                WhatsAppMarketingMessage::STATUS_DELIVERED,
                WhatsAppMarketingMessage::STATUS_READ,
                WhatsAppMarketingMessage::STATUS_REPLIED,
            ])->count(),
            'read' => $campaign->messages()->whereIn('status', [
                WhatsAppMarketingMessage::STATUS_READ,
                WhatsAppMarketingMessage::STATUS_REPLIED,
            ])->count(),
            'failed' => $campaign->messages()->where('status', WhatsAppMarketingMessage::STATUS_FAILED)->count(),
            'replied' => $campaign->messages()->where('status', WhatsAppMarketingMessage::STATUS_REPLIED)->count(),
        ];

        return view('whatsappmodule::admin.marketing.campaign-show', compact('campaign', 'tab', 'search', 'messages', 'overviewStats'));
    }

    public function retryFailed(int $id): RedirectResponse
    {
        $this->authorize('whatsapp_marketing_campaign_update');

        $campaign = WhatsAppMarketingCampaign::query()->findOrFail($id);

        DB::transaction(function () use ($campaign) {
            WhatsAppMarketingMessage::query()
                ->where('whatsapp_marketing_campaign_id', $campaign->id)
                ->where('status', WhatsAppMarketingMessage::STATUS_FAILED)
                ->update([
                    'status' => WhatsAppMarketingMessage::STATUS_PENDING,
                    'failure_reason' => null,
                    'wa_message_id' => null,
                    'sent_at' => null,
                    'delivered_at' => null,
                    'read_at' => null,
                    'replied_at' => null,
                ]);

            $campaign->update([
                'status' => WhatsAppMarketingCampaign::STATUS_QUEUED,
                'completed_at' => null,
            ]);
        });

        ProcessWhatsAppMarketingCampaignJob::dispatch($campaign->id);

        Toastr::success(translate('Updated_successfully'));

        return redirect()->route('admin.whatsapp.marketing.campaigns.show', ['channel' => 'whatsapp', 'id' => $campaign->id, 'tab' => 'failed']);
    }

    public function exportCsv(int $id): StreamedResponse
    {
        $this->authorize('whatsapp_marketing_campaign_view');

        $campaign = WhatsAppMarketingCampaign::query()->findOrFail($id);

        $fileName = 'campaign-' . $campaign->id . '-recipients.csv';

        return response()->streamDownload(function () use ($campaign) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'phone', 'status', 'sent_at', 'failure_reason']);
            $campaign->messages()->orderBy('id')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $m) {
                    fputcsv($out, [
                        $m->recipient_name,
                        $m->phone_e164,
                        $m->status,
                        $m->sent_at?->toIso8601String(),
                        $m->failure_reason,
                    ]);
                }
            });
            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function duplicate(int $id): RedirectResponse
    {
        $this->authorize('whatsapp_marketing_bulk_view');

        $campaign = WhatsAppMarketingCampaign::query()->findOrFail($id);
        session(['marketing_duplicate_campaign_id' => $campaign->id]);

        return redirect()->route('admin.whatsapp.marketing.bulk.create', ['channel' => 'whatsapp']);
    }
}
