<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class ProcessGuideController extends Controller
{
    public function index(): View
    {
        return view('adminmodule::admin.process-guide.index', [
            'miroBoardId' => 'uXjVH2L4j28=',
            'miroShareLinkId' => '342998623562',
            'miroTitle' => 'Lead Qualification Flow',
            'boardJsonUrl' => asset('assets/admin-module/process-guide/miro-board.json'),
        ]);
    }
}
