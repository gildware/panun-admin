<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
}
