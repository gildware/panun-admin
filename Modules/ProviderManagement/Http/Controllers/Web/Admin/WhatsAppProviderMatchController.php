<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
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
            'colors' => $payload['colors'] ?? [],
        ]);
    }

    public function manage(WhatsAppProviderMatchService $service): Renderable
    {
        $this->authorize('provider_view');

        $groups = $service->groups();
        $colors = $service->groupColors();
        $totalPhones = 0;
        foreach ($groups as $contacts) {
            $totalPhones += count($contacts);
        }

        return view('providermanagement::admin.provider.whatsapp-match-groups', [
            'groups' => $groups,
            'colors' => $colors,
            'stats' => [
                'groups' => count($groups),
                'phones' => $totalPhones,
            ],
        ]);
    }

    public function storeGroup(Request $request, WhatsAppProviderMatchService $service): RedirectResponse
    {
        $this->authorize('provider_update');

        $result = $service->upsertGroup(
            (string) $request->input('group_name', ''),
            $this->contactsFromRequest($request),
            null,
            true
        );

        if (! $result['ok']) {
            Toastr::error(translate($result['message']));

            return redirect()
                ->route('admin.provider.whatsapp-match.groups')
                ->withInput()
                ->with('open_group_modal', 'create');
        }

        Toastr::success(translate($result['message']).' ('.$result['count'].')');

        return redirect()->route('admin.provider.whatsapp-match.groups', ['focus' => $result['group']]);
    }

    public function updateGroup(Request $request, WhatsAppProviderMatchService $service): RedirectResponse
    {
        $this->authorize('provider_update');

        $originalName = (string) $request->input('original_name', '');
        $result = $service->upsertGroup(
            (string) $request->input('group_name', ''),
            $this->contactsFromRequest($request),
            $originalName !== '' ? $originalName : null,
            true
        );

        if (! $result['ok']) {
            Toastr::error(translate($result['message']));

            return redirect()
                ->route('admin.provider.whatsapp-match.groups', ['focus' => $originalName])
                ->withInput()
                ->with('open_group_modal', 'edit');
        }

        Toastr::success(translate($result['message']).' ('.$result['count'].')');

        return redirect()->route('admin.provider.whatsapp-match.groups', ['focus' => $result['group']]);
    }

    public function destroyGroup(Request $request, WhatsAppProviderMatchService $service): RedirectResponse
    {
        $this->authorize('provider_update');

        $result = $service->deleteGroup((string) $request->input('group_name', ''));

        if (! $result['ok']) {
            Toastr::error(translate($result['message']));
        } else {
            Toastr::success(translate($result['message']));
        }

        return redirect()->route('admin.provider.whatsapp-match.groups');
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

    /**
     * @return array<int, array{name?: string, phone?: string}>|string
     */
    private function contactsFromRequest(Request $request): array|string
    {
        $contacts = $request->input('contacts');
        if (is_array($contacts)) {
            return $contacts;
        }

        return (string) $request->input('phones', '');
    }
}
