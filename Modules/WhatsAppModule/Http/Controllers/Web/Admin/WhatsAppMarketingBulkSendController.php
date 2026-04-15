<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\CategoryManagement\Entities\Category;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingCampaign;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingMessage;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;
use Modules\WhatsAppModule\Jobs\ProcessWhatsAppMarketingCampaignJob;
use Modules\WhatsAppModule\Services\WhatsAppMarketingAudienceService;
use Modules\WhatsAppModule\Services\WhatsAppMarketingBodyParameterBuilder;

class WhatsAppMarketingBulkSendController extends Controller
{
    use AuthorizesRequests;

    public function create(Request $request, WhatsAppMarketingAudienceService $audienceService): View
    {
        $this->authorize('whatsapp_marketing_bulk_view');

        $templates = WhatsAppMarketingTemplate::query()->approved()->orderBy('name')->get();
        $categories = Category::query()->ofType('main')->ofStatus(1)->orderBy('name')->get();

        $duplicate = null;
        if ($request->session()->has('marketing_duplicate_campaign_id')) {
            $dupId = (int) $request->session()->pull('marketing_duplicate_campaign_id');
            $duplicate = WhatsAppMarketingCampaign::query()->with('template')->find($dupId);
        }

        $audienceCounts = [
            'all_customers' => $audienceService->countCustomersWithPhone(),
            'all_providers' => $audienceService->countProvidersWithPhone(),
        ];

        $categoryRecipientCounts = [];
        foreach ($categories as $cat) {
            $categoryRecipientCounts[(string) $cat->id] = $audienceService->countProvidersInCategory((string) $cat->id);
        }

        return view('whatsappmodule::admin.marketing.bulk-create', compact(
            'templates',
            'categories',
            'duplicate',
            'audienceCounts',
            'categoryRecipientCounts'
        ));
    }

    /**
     * Sample contacts CSV (header row + example rows) for bulk import.
     */
    public function sampleCsv(): StreamedResponse
    {
        $this->authorize('whatsapp_marketing_bulk_view');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['name', 'phone']);
            fputcsv($out, ['Example Customer', '923001234567']);
            fputcsv($out, ['Another Contact', '03001234567']);
            fclose($out);
        }, 'whatsapp-marketing-contacts-sample.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function previewRecipients(
        Request $request,
        WhatsAppMarketingAudienceService $audienceService
    ): JsonResponse {
        $this->authorize('whatsapp_marketing_bulk_view');

        $data = $request->validate([
            'audience_type' => 'required|in:all_customers,all_providers,providers_by_category',
            'category_id' => 'nullable|string|max:64|exists:categories,id',
            'recipient_adjustments' => 'nullable|string|max:131072',
        ]);

        $adj = $this->parseRecipientAdjustmentsJson($request->input('recipient_adjustments'));

        $preview = $audienceService->previewRecipientsMerged(
            $data['audience_type'],
            $data['category_id'] ?? null,
            null,
            $adj['exclude'],
            $adj['extra'],
        );

        return response()->json($preview);
    }

    public function previewCsv(
        Request $request,
        WhatsAppMarketingAudienceService $audienceService
    ): JsonResponse {
        $this->authorize('whatsapp_marketing_bulk_view');

        $request->validate([
            'contacts_csv' => 'required|file|mimes:csv,txt|max:5120',
            'preview_adjustments' => 'nullable|string|max:131072',
        ]);

        $adj = $this->parseRecipientAdjustmentsJson($request->input('preview_adjustments'));

        $path = $request->file('contacts_csv')->store('whatsapp_marketing/csv_preview', 'local');

        try {
            $preview = $audienceService->previewRecipientsMerged(
                WhatsAppMarketingCampaign::AUDIENCE_CSV_IMPORT,
                null,
                $path,
                $adj['exclude'],
                $adj['extra'],
            );
        } finally {
            Storage::disk('local')->delete($path);
        }

        return response()->json($preview);
    }

    /**
     * @return array{exclude: array<int, string>, extra: array<int, array{name: string, phone: string, client_id: ?string}>}
     */
    private function parseRecipientAdjustmentsJson(?string $raw): array
    {
        $empty = ['exclude' => [], 'extra' => []];
        if ($raw === null || $raw === '') {
            return $empty;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $empty;
        }

        $exclude = $decoded['exclude'] ?? [];
        if (!is_array($exclude)) {
            $exclude = [];
        }
        $exclude = array_slice(array_values(array_filter(array_map(
            static fn ($v) => (string) $v,
            $exclude
        ))), 0, 500);

        $extraIn = $decoded['extra'] ?? [];
        if (!is_array($extraIn)) {
            $extraIn = [];
        }

        $extra = [];
        foreach (array_slice($extraIn, 0, 200) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $phone = trim((string) ($item['phone'] ?? ''));
            if ($phone === '') {
                continue;
            }
            $cid = $item['client_id'] ?? null;
            $extra[] = [
                'name' => trim((string) ($item['name'] ?? '')),
                'phone' => $phone,
                'client_id' => $cid !== null && $cid !== '' ? substr((string) $cid, 0, 64) : null,
            ];
        }

        return ['exclude' => $exclude, 'extra' => $extra];
    }

    public function store(
        Request $request,
        WhatsAppMarketingAudienceService $audienceService,
        WhatsAppMarketingBodyParameterBuilder $paramBuilder
    ): RedirectResponse {
        $this->authorize('whatsapp_marketing_bulk_add');

        $scheduledAt = null;

        $data = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'template_id' => 'required|exists:whatsapp_marketing_templates,id',
            'audience_type' => 'required|in:all_customers,all_providers,providers_by_category,csv_import',
            'category_id' => 'required_if:audience_type,providers_by_category|nullable|string|max:64|exists:categories,id',
            'contacts_csv' => 'required_if:audience_type,csv_import|nullable|file|mimes:csv,txt|max:5120',
            'send_option' => 'required|in:now,schedule',
            'scheduled_at' => 'nullable|date|after:now',
            'recipient_adjustments' => 'nullable|string|max:262144',
            'variable_map' => 'nullable|array',
            'variable_map.*' => 'nullable|string|max:500',
            'variable_static' => 'nullable|array',
            'variable_static.*' => 'nullable|string|max:1000',
        ]);

        $template = WhatsAppMarketingTemplate::query()
            ->whereKey($data['template_id'])
            ->approved()
            ->first();

        if (!$template) {
            Toastr::error(translate('Select_an_approved_template'));

            return back()->withInput();
        }

        $csvPath = null;
        if ($data['audience_type'] === WhatsAppMarketingCampaign::AUDIENCE_CSV_IMPORT && $request->hasFile('contacts_csv')) {
            $dir = 'whatsapp_marketing/csv';
            Storage::disk('local')->makeDirectory($dir);
            $csvPath = $request->file('contacts_csv')->storeAs(
                $dir,
                Str::uuid()->toString() . '.csv',
                'local'
            );
        }

        $recipients = $audienceService->resolve(
            $data['audience_type'],
            $data['category_id'] ?? null,
            $csvPath
        );

        $adj = $this->parseRecipientAdjustmentsJson($request->input('recipient_adjustments'));
        $recipients = $audienceService->applyRecipientAdjustments(
            $recipients,
            $adj['exclude'],
            $adj['extra']
        );

        if ($recipients === []) {
            Toastr::error(translate('no_data_found'));

            return back()->withInput();
        }

        $variableMapping = [];
        $statics = $data['variable_static'] ?? [];
        foreach ($data['variable_map'] ?? [] as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            if ($v === 'static_text') {
                $text = trim((string) ($statics[$k] ?? ''));
                $variableMapping[(string) $k] = 'static:' . $text;
            } else {
                $variableMapping[(string) $k] = (string) $v;
            }
        }

        $status = $data['send_option'] === 'schedule'
            ? WhatsAppMarketingCampaign::STATUS_SCHEDULED
            : WhatsAppMarketingCampaign::STATUS_QUEUED;

        if ($data['send_option'] === 'schedule') {
            if (empty($data['scheduled_at'])) {
                Toastr::error(translate('Schedule') . ' ' . translate('date') . ' ' . translate('is_required'));

                return back()->withInput();
            }
            $scheduledAt = \Carbon\Carbon::parse($data['scheduled_at']);
        }

        $campaignId = null;

        DB::transaction(function () use (
            $data,
            $template,
            $variableMapping,
            $recipients,
            $csvPath,
            $status,
            $scheduledAt,
            $paramBuilder,
            &$campaignId
        ) {
            $campaign = WhatsAppMarketingCampaign::query()->create([
                'name' => $data['campaign_name'],
                'whatsapp_marketing_template_id' => $template->id,
                'audience_type' => $data['audience_type'],
                'category_id' => $data['category_id'] ?? null,
                'csv_path' => $csvPath,
                'variable_mapping' => $variableMapping,
                'status' => $status,
                'scheduled_at' => $scheduledAt,
                'created_by' => auth()->id(),
            ]);

            $campaignId = $campaign->id;

            $rows = [];
            foreach ($recipients as $r) {
                $bodyParams = $paramBuilder->build(
                    $template,
                    $variableMapping,
                    $r['name'],
                    $r['category_name'] ?? ''
                );
                $rows[] = [
                    'whatsapp_marketing_campaign_id' => $campaign->id,
                    'recipient_name' => $r['name'],
                    'phone_e164' => $r['phone'],
                    'status' => WhatsAppMarketingMessage::STATUS_PENDING,
                    'body_parameters' => json_encode($bodyParams),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                WhatsAppMarketingMessage::query()->insert($chunk);
            }
        });

        if ($campaignId === null) {
            Toastr::error(translate('Something_went_wrong'));

            return back()->withInput();
        }

        if ($scheduledAt) {
            ProcessWhatsAppMarketingCampaignJob::dispatch($campaignId)->delay($scheduledAt);
        } else {
            ProcessWhatsAppMarketingCampaignJob::dispatch($campaignId);
        }

        Toastr::success(translate('Campaign_created_successfully'));

        return redirect()->route('admin.whatsapp.marketing.campaigns.index', ['channel' => 'whatsapp']);
    }
}
