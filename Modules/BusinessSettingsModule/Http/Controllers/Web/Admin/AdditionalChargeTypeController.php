<?php

namespace Modules\BusinessSettingsModule\Http\Controllers\Web\Admin;

use App\Lib\CommissionTierPayload;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\BusinessSettingsModule\Entities\AdditionalChargeType;

class AdditionalChargeTypeController extends Controller
{
    use AuthorizesRequests;

    public function create(): View
    {
        $this->authorize('business_update');

        $tier = normalize_commission_tier_group_for_ui(null, 0.0);

        return view('businesssettingsmodule::admin.additional-charges.form', [
            'charge' => null,
            'tier' => $tier,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('business_update');

        $request->validate([
            'name' => 'required|string|max:191',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Validator::make($request->all(), [
            'ac_mode' => 'required|in:fixed,tiered',
            'ac_fixed' => 'nullable|numeric|min:0',
            'ac_tiers' => 'nullable|array',
        ])
            ->after(fn (\Illuminate\Validation\Validator $v) => CommissionTierPayload::validateGroups($v, $request, [
                ['ac_mode', 'ac_fixed', 'ac_tiers'],
            ]))
            ->validate();

        $group = CommissionTierPayload::normalizeGroupFromRequest($request, 'ac_mode', 'ac_fixed', 'ac_tiers');

        $row = new AdditionalChargeType;
        $row->name = $request->input('name');
        $row->sort_order = (int) ($request->input('sort_order', 0));
        $row->is_active = $request->has('is_active');
        $row->customizable_at_booking = $request->boolean('customizable_at_booking');
        $row->is_commissionable = $request->boolean('is_commissionable', true);
        $row->charge_setup = $group;
        $row->save();

        Toastr::success(translate(DEFAULT_STORE_200['message']));

        return redirect()->route('admin.business-settings.get-business-information', ['web_page' => 'additional_charges']);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $this->authorize('business_update');

        $charge = AdditionalChargeType::query()->find($id);
        if (! $charge) {
            Toastr::error(translate(DEFAULT_204['message']));

            return redirect()->route('admin.business-settings.get-business-information', ['web_page' => 'additional_charges']);
        }

        $tier = normalize_commission_tier_group_for_ui(
            is_array($charge->charge_setup) ? $charge->charge_setup : null,
            0.0
        );

        return view('businesssettingsmodule::admin.additional-charges.form', [
            'charge' => $charge,
            'tier' => $tier,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize('business_update');

        $row = AdditionalChargeType::query()->find($id);
        if (! $row) {
            Toastr::error(translate(DEFAULT_204['message']));

            return redirect()->route('admin.business-settings.get-business-information', ['web_page' => 'additional_charges']);
        }

        $request->validate([
            'name' => 'required|string|max:191',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Validator::make($request->all(), [
            'ac_mode' => 'required|in:fixed,tiered',
            'ac_fixed' => 'nullable|numeric|min:0',
            'ac_tiers' => 'nullable|array',
        ])
            ->after(fn (\Illuminate\Validation\Validator $v) => CommissionTierPayload::validateGroups($v, $request, [
                ['ac_mode', 'ac_fixed', 'ac_tiers'],
            ]))
            ->validate();

        $group = CommissionTierPayload::normalizeGroupFromRequest($request, 'ac_mode', 'ac_fixed', 'ac_tiers');

        $row->name = $request->input('name');
        $row->sort_order = (int) ($request->input('sort_order', 0));
        $row->is_active = $request->has('is_active');
        $row->customizable_at_booking = $request->boolean('customizable_at_booking');
        $row->is_commissionable = $request->boolean('is_commissionable', true);
        $row->charge_setup = $group;
        $row->save();

        Toastr::success(translate(DEFAULT_UPDATE_200['message']));

        return redirect()->route('admin.business-settings.get-business-information', ['web_page' => 'additional_charges']);
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('business_update');

        $row = AdditionalChargeType::query()->find($id);
        if ($row) {
            $row->delete();
            Toastr::success(translate(DEFAULT_DELETE_200['message']));
        } else {
            Toastr::error(translate(DEFAULT_204['message']));
        }

        return redirect()->route('admin.business-settings.get-business-information', ['web_page' => 'additional_charges']);
    }
}
