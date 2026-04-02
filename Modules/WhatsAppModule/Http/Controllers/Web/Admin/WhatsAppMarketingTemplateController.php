<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\WhatsAppModule\Entities\WhatsAppMarketingTemplate;
use Modules\WhatsAppModule\Services\WhatsAppCloudService;
use Modules\WhatsAppModule\Services\WhatsAppTemplateButtonValidator;

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

    public function create(): View
    {
        $this->authorize('whatsapp_marketing_template_update');

        $languages = [
            'en' => 'English (en)',
            'en_US' => 'English US (en_US)',
            'en_GB' => 'English UK (en_GB)',
            'ur' => 'Urdu (ur)',
            'hi' => 'Hindi (hi)',
            'ar' => 'Arabic (ar)',
            'ar_AE' => 'Arabic UAE (ar_AE)',
        ];

        return view('whatsappmodule::admin.marketing.templates-create', compact('languages'));
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

    public function store(Request $request, WhatsAppCloudService $cloud): RedirectResponse
    {
        $this->authorize('whatsapp_marketing_template_update');

        $data = $request->validate([
            'name' => 'required|string|max:512',
            'language' => 'required|string|max:32',
            'category' => 'required|in:MARKETING,UTILITY',
            'body_text' => 'required|string|max:1024',
            'footer_text' => 'nullable|string|max:60',
            'header_format' => 'required|in:NONE,TEXT,IMAGE,VIDEO',
            'header_text' => 'required_if:header_format,TEXT|nullable|string|max:60',
            'header_media' => [
                'nullable',
                'file',
                'max:16384',
                Rule::requiredIf(fn () => in_array($request->input('header_format'), ['IMAGE', 'VIDEO'], true)),
            ],
            'buttons' => 'nullable|array|max:10',
            'buttons.*.kind' => 'nullable|string|in:QUICK_REPLY,URL,PHONE_NUMBER',
            'buttons.*.text' => 'nullable|string|max:25',
            'buttons.*.url' => 'nullable|string|max:2000',
            'buttons.*.phone' => 'nullable|string|max:24',
        ]);

        $name = WhatsAppCloudService::normalizeTemplateName($data['name']);
        if ($name === '' || !preg_match('/^[a-z0-9_]{1,512}$/', $name)) {
            Toastr::error(translate('Template_name_invalid_hint'));

            return back()->withInput();
        }

        if ($data['header_format'] === 'TEXT' && trim((string) ($data['header_text'] ?? '')) === '') {
            Toastr::error(translate('Template_header_text_empty'));

            return back()->withInput();
        }

        $built = WhatsAppTemplateButtonValidator::buildButtonsComponent($request->input('buttons', []));
        if ($built['error'] !== null) {
            Toastr::error(translate($built['error']));

            return back()->withInput();
        }
        $buttonsComponent = $built['component'];

        $components = [];
        $headerFormat = $data['header_format'];

        if ($headerFormat === 'TEXT') {
            $ht = trim((string) ($data['header_text'] ?? ''));
            if ($ht !== '') {
                $components[] = [
                    'type' => 'HEADER',
                    'format' => 'TEXT',
                    'text' => $ht,
                ];
            }
        } elseif (in_array($headerFormat, ['IMAGE', 'VIDEO'], true)) {
            if ((string) config('services.whatsapp_cloud.app_id') === '') {
                Toastr::error(translate('Template_missing_app_id'));

                return back()->withInput();
            }

            $media = $request->file('header_media');
            if (!$media || !$media->isValid()) {
                Toastr::error(translate('Template_media_type_invalid'));

                return back()->withInput();
            }

            $graphType = WhatsAppCloudService::mapUploadedFileToGraphTemplateFileType($media, $headerFormat);
            if ($graphType === null) {
                Toastr::error(translate('Template_media_type_invalid'));

                return back()->withInput();
            }

            $realPath = $media->getRealPath();
            if ($realPath === false) {
                Toastr::error(translate('Create_failed'));

                return back()->withInput();
            }

            $uploadErr = null;
            $uploadCtx = null;
            $handle = $cloud->resumableUploadFileForTemplateSample($realPath, $graphType, $uploadErr, $uploadCtx);
            if ($handle === null) {
                Toastr::error(translate('Create_failed') . ($uploadErr !== null && $uploadErr !== '' ? ': ' . $uploadErr : ''));

                return back()->withInput();
            }

            $components[] = [
                'type' => 'HEADER',
                'format' => $headerFormat,
                'example' => [
                    'header_handle' => [$handle],
                ],
            ];
        }

        $components[] = [
            'type' => 'BODY',
            'text' => $data['body_text'],
        ];

        $footer = trim((string) ($data['footer_text'] ?? ''));
        if ($footer !== '') {
            $components[] = [
                'type' => 'FOOTER',
                'text' => $footer,
            ];
        }

        if ($buttonsComponent !== null) {
            $components[] = $buttonsComponent;
        }

        $error = null;
        $graphContext = null;
        $payload = $cloud->submitMessageTemplateForWaba(
            $name,
            $data['language'],
            $data['category'],
            $components,
            $error,
            $graphContext
        );

        if ($payload === null) {
            Toastr::error(translate('Create_failed') . ($error ? ': ' . $error : ''));

            return back()->withInput();
        }

        $status = strtoupper((string) ($payload['status'] ?? 'PENDING'));
        $category = isset($payload['category']) ? (string) $payload['category'] : $data['category'];

        WhatsAppMarketingTemplate::query()->updateOrCreate(
            [
                'name' => $name,
                'language' => $data['language'],
            ],
            [
                'meta_template_id' => isset($payload['id']) ? (string) $payload['id'] : null,
                'category' => $category,
                'status' => $status,
                'body_parameter_count' => WhatsAppCloudService::countBodyPlaceholdersFromComponents($components),
                'components' => $components,
                'preview_text' => WhatsAppCloudService::previewTextFromComponents($components),
                'synced_at' => now(),
            ]
        );

        Toastr::success(translate('Template_submitted') . ' — ' . $status . '. ' . translate('Sync_after_approval_hint'));

        return redirect()->route('admin.whatsapp.marketing.templates.index');
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

        return redirect()->route('admin.whatsapp.marketing.templates.index');
    }
}
