<?php

namespace Modules\AddonModule\Http\Controllers\Web\Admin;

use App\Traits\ActivationClass;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use function response;


class AddonActivationController extends Controller
{
    use AuthorizesRequests, ActivationClass;

    public function __construct(private BusinessSettings $businessSetting)
    {}

    public function index()
    {
        $data = $this->businessSetting->where('settings_type', 'addon_activation')->get();
        return view('addonmodule::add-on-activation.index', compact('data'));

    }
    public function update(Request $request, $type)
    {
        $email = preg_replace('/\s+/', '', $request['email']);
        $username = preg_replace('/\s+/', '', $request['username']);
        $purchaseKey = preg_replace('/\s+/', '', $request['purchase_key']);

        $response = $this->getRequestConfig(
            username: $username,
            purchaseKey: $purchaseKey,
            softwareId: $request['software_id'] ?? '',
            softwareType: $request['software_type'] ?? '',
            name: $request['name'],
            identifier: $email,
        );

        $this->updateActivationConfig(app: $request['addon_name'], response: $response);

        $status = $response['active'] ?? 0;
        $message = $response['message'] ?? translate('Activation_failed');

        if ((int)$status) {
            $data = [
                'status' => (int)$status,
                'activation_status' => 1,
                "name" => $request['name'],
                "identifier" => $email,
                'username' => $username,
                'purchase_code' => $purchaseKey,
            ];
        }else{
            $data = [
                'status' => (int)$status,
                'message' => $message
            ];
        }

        $keyName = null;
        if ($type == 'provider'){
            $keyName = 'addon_activation_provider_app';
        }elseif ($type == 'serviceman'){
            $keyName = 'addon_activation_serviceman_app';
        }

        if ($data['status'] && $keyName != null) {
            $this->businessSetting->updateOrCreate(
                ['key_name' => $keyName, 'settings_type' => 'addon_activation'],
                [
                    'live_values' => [
                        'activation_status' => $request['status'] ?? 0,
                        'name' => $request['name'],
                        'email' => $email,
                        'username' => $request['username'],
                        'purchase_key' => $request['purchase_key'],
                    ],
                ]
            );
            Toastr::success(translate('activated_successfully'));
        } else {
            Toastr::error($data['message']);
        }
        return back();

    }



}
