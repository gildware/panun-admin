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
        $syncedNameLangKeys = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $lang = WhatsAppCloudService::languageCodeFromGraphTemplateRow($row);
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
            $syncedNameLangKeys[$name."\0".$lang] = true;
            $synced++;
        }

        // Rows left in the DB that Meta no longer returns stay "APPROVED" forever and cause #132001 at send.
        $stale = 0;
        WhatsAppMarketingTemplate::query()->orderBy('id')->chunkById(100, function ($chunk) use ($syncedNameLangKeys, &$stale) {
            foreach ($chunk as $tpl) {
                $key = trim((string) $tpl->name)."\0".trim((string) $tpl->language);
                if (isset($syncedNameLangKeys[$key])) {
                    continue;
                }
                if (strtoupper((string) $tpl->status) === 'NOT_ON_META') {
                    continue;
                }
                $tpl->forceFill([
                    'status' => 'NOT_ON_META',
                    'synced_at' => now(),
                ])->save();
                $stale++;
            }
        });

        $msg = translate('Synced').': '.$synced.' '.translate('templates');
        if ($stale > 0) {
            $msg .= ' — '.__('lang.WhatsApp_templates_sync_stale_suffix', ['count' => $stale]);
        }
        Toastr::success($msg);

        return redirect()->route('admin.whatsapp.marketing.templates.index', ['channel' => 'whatsapp']);
    }
}
