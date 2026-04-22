<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\WhatsAppModule\Services\BookingWhatsAppAdminPromptService;

class BookingWhatsAppAdminPromptController extends Controller
{
    use AuthorizesRequests;

    public function send(Request $request, BookingWhatsAppAdminPromptService $prompts): JsonResponse
    {
        $this->authorize('booking_view');

        $validated = $request->validate([
            'token' => 'required|uuid',
        ]);

        $result = $prompts->execute($validated['token']);

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['message'],
        ], $result['ok'] ? 200 : 422);
    }

    public function sendRow(Request $request, BookingWhatsAppAdminPromptService $prompts): JsonResponse
    {
        $this->authorize('booking_view');

        $validated = $request->validate([
            'token' => 'required|uuid',
            'index' => 'required|integer|min:0',
        ]);

        $result = $prompts->removePromptRow($validated['token'], $validated['index'], true);

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['message'],
            'remaining' => $result['remaining'] ?? 0,
        ], $result['ok'] ? 200 : 422);
    }

    public function skipRow(Request $request, BookingWhatsAppAdminPromptService $prompts): JsonResponse
    {
        $this->authorize('booking_view');

        $validated = $request->validate([
            'token' => 'required|uuid',
            'index' => 'required|integer|min:0',
        ]);

        $result = $prompts->removePromptRow($validated['token'], $validated['index'], false);

        return response()->json([
            'success' => $result['ok'],
            'message' => $result['message'],
            'remaining' => $result['remaining'] ?? 0,
        ], $result['ok'] ? 200 : 422);
    }
}
