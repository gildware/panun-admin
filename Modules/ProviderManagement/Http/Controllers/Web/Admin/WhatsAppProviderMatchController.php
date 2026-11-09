<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProviderManagement\Services\WhatsAppProviderMatchService;

class WhatsAppProviderMatchController extends Controller
{
    use AuthorizesRequests;

    public function index(WhatsAppProviderMatchService $service): Renderable
    {
        $this->authorize('provider_view');

        $payload = $service->build();

        return view('providermanagement::admin.provider.whatsapp-match', [
            'rows' => $payload['rows'],
            'stats' => $payload['stats'],
            'categories' => $payload['categories'],
        ]);
    }

    public function lookup(Request $request, WhatsAppProviderMatchService $service): Renderable
    {
        $this->authorize('provider_view');

        $raw = (string) $request->input('phones', '');
        $phones = $service->parsePhones($raw);
        $payload = $phones === []
            ? ['results' => [], 'stats' => ['searched' => 0, 'matched' => 0, 'none' => 0], 'phones' => []]
            : $service->lookup($phones);

        return view('providermanagement::admin.provider.whatsapp-match-lookup', [
            'rawPhones' => $raw,
            'results' => $payload['results'],
            'stats' => $payload['stats'],
            'searched' => $phones !== [],
        ]);
    }
}
