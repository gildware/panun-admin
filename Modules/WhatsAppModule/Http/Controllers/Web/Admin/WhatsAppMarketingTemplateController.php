<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;

class WhatsAppMarketingTemplateController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('whatsapp_marketing_template_view');

        $templates = WhatsAppMarketingTemplate::query()
            ->orderByRaw("CASE UPPER(COALESCE(status,'')) WHEN 'APPROVED' THEN 0 WHEN 'PENDING' THEN 1 WHEN 'REJECTED' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->orderBy('language')
            ->paginate(pagination_limit());

        return view('whatsappmodule::admin.marketing.templates-index', compact('templates'));
    }

    public function preview(WhatsAppMarketingTemplate $template): JsonResponse
    {
        $this->authorize('whatsapp_marketing_template_view');

        $state = WhatsAppCloudService::extractTemplatePreviewState(
            is_array($template->components) ? $template->components : []
        );
        $html = view('whatsappmodule::admin.marketing._template_phone_preview', ['preview' => $state])->render();

        return response()->json(['html' => $html]);
    }

    public function sync(WhatsAppCloudService $cloud): RedirectResponse
    {
        $this->authorize('whatsapp_marketing_template_update');

        $error = null;
        [$rows, $err] = $cloud->fetchMessageTemplates($error);
        if ($err !== null) {
            Toastr::error(translate('Sync_failed') . ': ' . $err);

            return back();
        }

        $synced = 0;
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            $lang = is_array($row['language'] ?? null)
                ? (string) ($row['language']['code'] ?? '')
                : (string) ($row['language'] ?? '');
            if ($name === '' || $lang === '') {
                continue;
            }

            $components = $row['components'] ?? [];
            $components = is_array($components) ? $components : [];
            $bodyCount = WhatsAppCloudService::countBodyPlaceholdersFromComponents($components);
            $preview = WhatsAppCloudService::previewTextFromComponents($components);

            WhatsAppMarketingTemplate::query()->updateOrCreate(
                [
                    'name' => $name,
                    'language' => $lang,
                ],
                [
                    'meta_template_id' => isset($row['id']) ? (string) $row['id'] : null,
                    'category' => isset($row['category']) ? (string) $row['category'] : null,
                    'status' => strtoupper((string) ($row['status'] ?? 'UNKNOWN')),
                    'body_parameter_count' => $bodyCount,
                    'components' => $components,
                    'preview_text' => $preview,
                    'synced_at' => now(),
                ]
            );
            $synced++;
        }

        Toastr::success(translate('Synced') . ': ' . $synced . ' ' . translate('templates'));

        return redirect()->route('admin.whatsapp.marketing.templates.index', ['channel' => 'whatsapp']);
    }
}
