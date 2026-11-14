<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\OpenWorkService;
use Modules\UserManagement\Entities\User;

class OpenWorkController extends Controller
{
    public function __construct(
        private readonly OpenWorkService $openWork,
    ) {}

    public function index(): Renderable
    {
        /** @var User $user */
        $user = auth()->user();
        $payload = $this->openWork->build($user);

        return view('adminmodule::admin.open-work.index', [
            'items' => $payload['items'],
            'employees' => $payload['employees'],
            'stats' => $payload['stats'],
            'employeeScope' => $payload['employee_scope'],
        ]);
    }
}
