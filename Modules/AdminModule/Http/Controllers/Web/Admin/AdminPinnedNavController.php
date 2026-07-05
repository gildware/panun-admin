<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use App\Support\AdminPinnedNav;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class AdminPinnedNavController extends Controller
{
    public function save(Request $request): JsonResponse
    {
        $validKeys = AdminPinnedNav::validPinKeys();

        $request->validate([
            'pins' => ['nullable', 'array'],
            'pins.*' => ['string', 'distinct', Rule::in($validKeys)],
        ]);

        $pins = AdminPinnedNav::sanitizePinKeys($request->input('pins', []));

        $request->user()->update([
            'admin_pinned_nav' => $pins,
        ]);

        return response()->json([
            'status' => 1,
            'message' => translate('Saved_successfully'),
            'data' => [
                'pins' => $pins,
            ],
        ]);
    }
}
