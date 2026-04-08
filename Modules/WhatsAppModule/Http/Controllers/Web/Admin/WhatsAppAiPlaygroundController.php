<?php

namespace Modules\WhatsAppModule\Http\Controllers\Web\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\WhatsAppModule\Services\WhatsAppAiPlaygroundRunner;

class WhatsAppAiPlaygroundController extends Controller
{
    use AuthorizesRequests;

    public function thread(Request $request, WhatsAppAiPlaygroundRunner $runner): JsonResponse
    {
        $this->authorize('whatsapp_chat_assign');

        $phone = $request->query('phone');
        $phone = is_string($phone) ? trim($phone) : null;

        return response()->json($runner->getThread($phone));
    }

    public function run(Request $request, WhatsAppAiPlaygroundRunner $runner): JsonResponse
    {
        $this->authorize('whatsapp_chat_assign');

        $validated = $request->validate([
            'message' => 'required|string|max:4000',
            'phone' => 'nullable|string|max:64',
        ]);

        $result = $runner->runCustomerText($validated['message'], $validated['phone'] ?? null);

        return response()->json($result);
    }

    public function reset(Request $request, WhatsAppAiPlaygroundRunner $runner): JsonResponse
    {
        $this->authorize('whatsapp_chat_assign');

        $validated = $request->validate([
            'phone' => 'nullable|string|max:64',
        ]);

        $result = $runner->resetSandboxThread($validated['phone'] ?? null);

        return response()->json($result);
    }
}
