<?php

namespace App\Support;

use Illuminate\Http\Request;

class AdminBreadcrumb
{
    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    public static function resolve(?Request $request = null): array
    {
        return AdminNavRegistry::breadcrumbs($request);
    }
}
