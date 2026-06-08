<?php

namespace Modules\LeadManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\LeadManagement\Services\OmniDimensionService;
use Modules\LeadManagement\Services\OutboundCallContextService;
use Modules\LeadManagement\Services\WhatsAppFollowupCandidateQueryService;
use Modules\LeadManagement\Services\WhatsAppFollowupContextBuilder;
use Modules\LeadManagement\Services\WhatsAppVoiceFollowupDispatchService;

class WhatsAppVoiceFollowupController extends Controller
{
    public function __construct(
        private readonly WhatsAppFollowupCandidateQueryService $candidateQuery,
        private readonly WhatsAppFollowupContextBuilder $contextBuilder,
        private readonly OmniDimensionService $omniDimension,
        private readonly WhatsAppVoiceFollowupDispatchService $dispatchService
    ) {}

    public function list(Request $request): View
    {
        $filters = $this->parseFilters($request);
        $page = max(1, (int) $request->get('page', 1));

        try {
            $paginator = $this->candidateQuery->search($filters, $page, pagination_limit());
        } catch (\Throwable $e) {
            report($e);

            return view('leadmanagement::admin.voice-calls._whatsapp_followup_list', [
                'candidates' => collect(),
                'paginator' => null,
                'listError' => 'whatsapp_followup_load_failed',
                'filters' => $filters,
                'callReasonLabels' => OutboundCallContextService::callReasonLabels(),
                'contextKeys' => OutboundCallContextService::CONTEXT_KEYS,
            ]);
        }

        return view('leadmanagement::admin.voice-calls._whatsapp_followup_list', [
            'candidates' => $paginator->getCollection(),
            'paginator' => $paginator,
            'listError' => null,
            'filters' => $filters,
            'callReasonLabels' => OutboundCallContextService::callReasonLabels(),
            'contextKeys' => OutboundCallContextService::CONTEXT_KEYS,
        ]);
    }

    public function conversation(Request $request): JsonResponse
    {
        $phone = trim((string) $request->get('phone', ''));
        if ($phone === '') {
            return response()->json(['ok' => false, 'messages' => []], 422);
        }

        return response()->json([
            'ok' => true,
            'messages' => $this->contextBuilder->conversationPreview($phone, 40),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $phone = trim((string) $request->get('phone', ''));
        if ($phone === '') {
            return response()->json(['ok' => false, 'message' => translate('Invalid_phone_number')], 422);
        }

        $candidate = $this->findCandidateByPhone($phone);
        if (!$candidate) {
            return response()->json(['ok' => false, 'message' => translate('no_data_found')], 404);
        }

        $result = $this->contextBuilder->cachedSummaryOnly($candidate);
        if ($result === null) {
            return response()->json([
                'ok' => true,
                'has_summary' => false,
                'summary' => null,
            ]);
        }

        return response()->json([
            'ok' => true,
            'has_summary' => true,
            'summary' => $result['summary'],
            'ai_generated' => (bool) ($result['ai_generated'] ?? false),
            'from_cache' => true,
            'needs_refresh' => (bool) ($result['needs_refresh'] ?? false),
            'is_current' => (bool) ($result['is_current'] ?? false),
        ]);
    }

    public function generateSummary(Request $request): JsonResponse
    {
        $phone = trim((string) $request->input('phone', ''));
        if ($phone === '') {
            return response()->json(['ok' => false, 'message' => translate('Invalid_phone_number')], 422);
        }

        $candidate = $this->findCandidateByPhone($phone);
        if (!$candidate) {
            return response()->json(['ok' => false, 'message' => translate('no_data_found')], 404);
        }

        $result = $this->contextBuilder->generateSummaryForCandidate($candidate);
        if ($result === null) {
            return response()->json(['ok' => false, 'message' => translate('WhatsApp_followup_summary_failed')], 422);
        }

        $candidate['cached_summary'] = $result['summary'];
        $built = $this->contextBuilder->buildForCandidate($candidate);

        return response()->json([
            'ok' => true,
            'summary' => $result['summary'],
            'ai_generated' => (bool) ($result['ai_generated'] ?? false),
            'from_cache' => (bool) ($result['from_cache'] ?? false),
            'ai_called' => (bool) ($result['ai_called'] ?? false),
            'call_context' => $built['context'],
        ]);
    }

    public function dispatch(Request $request): RedirectResponse|JsonResponse
    {
        if (!$this->omniDimension->isConfigured()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => translate('OmniDimension_is_not_configured')], 422);
            }
            toastr()->error(translate('OmniDimension_is_not_configured'));

            return back();
        }

        $validated = $request->validate([
            'phones' => 'required|array|min:1|max:500',
            'phones.*' => 'required|string|max:32',
            'campaign_name' => 'required|string|max:255',
            'phone_number_id_customer' => 'nullable|integer|min:1',
            'phone_number_id_provider' => 'nullable|integer|min:1',
            'phone_number_id_unknown' => 'nullable|integer|min:1',
            'send_option' => 'required|in:now,schedule',
            'scheduled_at' => 'nullable|required_if:send_option,schedule|date|after:now',
            'timezone' => 'nullable|string|max:64',
            'concurrent_call_limit' => 'nullable|integer|min:1|max:20',
            'enabled_reschedule_call' => 'nullable|boolean',
            'auto_retry' => 'nullable|boolean',
            'auto_retry_schedule' => 'nullable|string|in:immediately,next_day,scheduled_time',
            'retry_limit' => 'nullable|integer|min:1|max:5',
        ]);

        $selectedPhones = array_values(array_unique(array_filter($validated['phones'])));
        $filters = array_merge($this->parseFilters($request), [
            'phones' => $selectedPhones,
            'silent_min_hours' => 0,
        ]);

        $allCandidates = $this->candidateQuery->search($filters, 1, 500);
        $byPhone = $allCandidates->getCollection()->keyBy('phone');
        $candidateList = collect($selectedPhones)
            ->map(fn (string $phone) => $byPhone->get($phone))
            ->filter()
            ->values();

        $result = $this->dispatchService->dispatchCandidates($candidateList, array_merge($validated, [
            'source' => 'manual',
            'dispatched_by' => Auth::id(),
        ]));

        if (!$result['ok']) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $result['message']], 422);
            }
            toastr()->error($result['message']);

            return back()->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $result['message'],
                'campaign_ids' => $result['campaign_ids'],
            ]);
        }

        toastr()->success($result['message']);

        return redirect()->route('admin.voice-call.index', ['tab' => 'whatsapp_followup']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findCandidateByPhone(string $phone): ?array
    {
        $paginator = $this->candidateQuery->search([
            'phones' => [$phone],
            'silent_min_hours' => 0,
            'human_support' => '',
            'exclude_called_within_hours' => 0,
        ], 1, 1);

        return $paginator->getCollection()->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFilters(Request $request): array
    {
        return [
            'silent_min_hours' => $request->input('silent_min_hours', 2),
            'silent_max_hours' => $request->input('silent_max_hours'),
            'lead_types' => array_filter((array) $request->input('lead_types', [])),
            'lead_open' => (string) $request->input('lead_open', ''),
            'wa_chat_bucket' => (string) $request->input('wa_chat_bucket', ''),
            'wa_chat_tag_ids' => array_filter((array) $request->input('wa_chat_tag_ids', [])),
            'customer_lead_tag_ids' => array_filter((array) $request->input('customer_lead_tag_ids', [])),
            'handled_by' => (string) $request->input('handled_by', ''),
            'human_support' => (string) $request->input('human_support', 'exclude'),
            'exclude_called_within_hours' => $request->input('exclude_called_within_hours', 24),
        ];
    }
}
