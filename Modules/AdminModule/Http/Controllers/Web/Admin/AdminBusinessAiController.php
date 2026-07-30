<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\AdminBusinessAiGeminiRunner;
use Modules\AdminModule\Services\AdminBusinessAiSessionService;

class AdminBusinessAiController extends Controller
{
    public function __construct(
        protected AdminBusinessAiSessionService $session,
        protected AdminBusinessAiGeminiRunner $runner,
    ) {}

    public function index(): View
    {
        $geminiReady = (string) config('services.gemini.api_key') !== '';
        $enabled = (bool) config('admin_business_ai.enabled', true);

        return view('adminmodule::admin.business-ai.index', compact('geminiReady', 'enabled'));
    }

    public function messages(): JsonResponse
    {
        $messages = $this->session->messages((int) auth()->id());

        return response()->json([
            'ok' => true,
            'messages' => array_map(static fn (array $m) => [
                'role' => $m['role'] === 'model' ? 'assistant' : 'user',
                'text' => $m['text'] ?? '',
                'charts' => is_array($m['charts'] ?? null) ? $m['charts'] : [],
                'tables' => is_array($m['tables'] ?? null) ? $m['tables'] : [],
                'note' => is_string($m['note'] ?? null) ? $m['note'] : null,
                'at' => $m['at'] ?? null,
            ], $messages),
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:8000',
        ]);

        $result = $this->runner->chat((int) auth()->id(), (string) $request->input('message'));

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'error' => $result['error'] ?? __('admin_business_ai.gemini_error'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'reply' => $result['reply'] ?? '',
            'note' => is_string($result['note'] ?? null) ? $result['note'] : null,
            'charts' => is_array($result['charts'] ?? null) ? $result['charts'] : [],
            'tables' => is_array($result['tables'] ?? null) ? $result['tables'] : [],
        ]);
    }

    public function reset(): JsonResponse
    {
        $this->session->reset((int) auth()->id());

        return response()->json(['ok' => true]);
    }
}
