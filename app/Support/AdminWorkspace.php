<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminWorkspace
{
    public const OPERATIONS = 'operations';

    public const HR = 'hr';

    public const TRAINING = 'training';

    public const MARKETING = 'marketing';

    public const SETTINGS = 'settings';

    public const ACCOUNTS = 'accounts';

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [self::OPERATIONS, self::HR, self::TRAINING, self::MARKETING, self::SETTINGS, self::ACCOUNTS];
    }

    /**
     * Workspaces the signed-in person can open. Operations, HR, and Training
     * stay available. The other three appear only when that person can use them.
     *
     * @return array<int, string>
     */
    public static function visibleKeys(): array
    {
        if (! auth()->check()) {
            return self::keys();
        }

        return array_values(array_filter(self::keys(), [self::class, 'userCanEnter']));
    }

    public static function userCanEnter(string $workspace): bool
    {
        return match ($workspace) {
            self::OPERATIONS => self::userCanEnterOperations(),
            self::MARKETING => AdminMarketingRegistry::visibleSections() !== [],
            self::SETTINGS => AdminSettingsRegistry::visibleSections() !== [],
            self::ACCOUNTS => self::userCanEnterAccounts(),
            default => in_array($workspace, self::keys(), true),
        };
    }

    public static function current(?Request $request = null): string
    {
        $request = $request ?? request();
        $detected = self::detect($request);

        if ($detected !== null) {
            session(['admin_workspace' => $detected]);

            return $detected;
        }

        $stored = session('admin_workspace', self::OPERATIONS);

        return in_array($stored, self::keys(), true) ? $stored : self::OPERATIONS;
    }

    public static function label(string $workspace): string
    {
        return match ($workspace) {
            self::HR => 'HR Management',
            self::TRAINING => 'Training',
            self::MARKETING => 'Marketing',
            self::SETTINGS => 'Settings',
            self::ACCOUNTS => 'Accounts',
            default => 'Operations',
        };
    }

    public static function landing(string $workspace): string
    {
        return match ($workspace) {
            self::HR => route('admin.dashboard.people'),
            self::TRAINING => route('admin.dashboard.training'),
            self::MARKETING => route('admin.marketing.index'),
            self::SETTINGS => route('admin.settings.index'),
            self::ACCOUNTS => self::accountsLanding(),
            default => route('admin.dashboard'),
        };
    }

    /**
     * Workspace this URL belongs to. Null means a More page, or a page that
     * should keep whichever workspace the person last chose.
     */
    public static function detect(Request $request): ?string
    {
        if ($request->is('admin/workspace', 'admin/workspace/*')) {
            return null;
        }

        if ($request->is(
            'admin/hr*',
            'admin/people*',
            'admin/dashboard/hr',
            'admin/dashboard/people',
            'admin/employee*',
            'admin/role*',
        )) {
            return self::HR;
        }

        if ($request->is('admin/process-guides*', 'admin/dashboard/business-system*', 'admin/dashboard/training')) {
            return self::TRAINING;
        }

        if (self::isMarketingPath($request)) {
            return self::MARKETING;
        }

        if (self::isSettingsPath($request)) {
            return self::SETTINGS;
        }

        if (self::isAccountsPath($request)) {
            return self::ACCOUNTS;
        }

        if ($request->is(
            'admin/dashboard',
            'admin/dashboard/operations',
            'admin/dashboard/rank-marks-chart',
            'admin/dashboard/progress-scope',
            'admin/my-progress*',
            'admin/lead*',
            'admin/booking*',
            'admin/task-board*',
            'admin/customer',
            'admin/customer/*',
            'admin/customer-cart*',
            'admin/provider*',
            'admin/chat*',
            'admin/social-inbox*',
            'admin/reports*',
            'admin/report*',
            'admin/analytics*',
        )) {
            return self::OPERATIONS;
        }

        return null;
    }

    private static function isMarketingPath(Request $request): bool
    {
        return $request->is(
            'admin/marketing*',
            'admin/advertisements*',
            'admin/campaign*',
            'admin/social-inbox/*/marketing*',
        );
    }

    private static function isSettingsPath(Request $request): bool
    {
        return $request->is(
            'admin/settings*',
            'admin/business-settings*',
            'admin/subscription*',
            'admin/business-page-setup*',
            'admin/social-media*',
            'admin/lead/configuration*',
            'admin/booking/configuration*',
            'admin/social-inbox/*/booking-message-templates*',
            'admin/social-inbox/*/ai-support*',
            'admin/social-inbox/*/meta-capi-events*',
            'admin/customer/settings*',
            'admin/provider/feedback-tags*',
            'admin/withdraw/method*',
            'admin/service-overview*',
            'admin/configuration*',
            'admin/business-ai*',
            'admin/mobile-app-management*',
            'admin/language*',
            'admin/system-maintenance*',
            'admin/system-logs*',
            'admin/data-transfer*',
            'admin/addon*',
            'admin/add-on-activation*',
        );
    }

    private static function isAccountsPath(Request $request): bool
    {
        return $request->is(
            'admin/dashboard/finance',
            'admin/accounts/payroll*',
            'admin/accounts/salary*',
            'admin/transaction*',
            'admin/ledger*',
            'admin/withdraw/request*',
            'admin/customer/wallet*',
            'admin/customer/loyalty-point*',
        );
    }

    private static function accountsLanding(): string
    {
        if (! is_admin_employee()) {
            return route('admin.dashboard.finance');
        }

        if (Gate::allows('ledger_view')) {
            return route('admin.ledger.index');
        }

        if (Gate::allows('transaction_view')) {
            return route('admin.transaction.list', ['trx_type' => 'all']);
        }

        if (Gate::allows('withdraw_view')) {
            return route('admin.withdraw.request.list', ['status' => 'all']);
        }

        if (Gate::allows('wallet_view')) {
            return route('admin.customer.wallet.report');
        }

        if (Gate::allows('point_view')) {
            return route('admin.customer.loyalty-point.report');
        }

        if (Gate::allows('people_hr')) {
            return route('admin.accounts.salary');
        }

        return route('admin.dashboard.finance');
    }

    private static function userCanEnterOperations(): bool
    {
        if (! is_admin_employee()) {
            return true;
        }

        return Gate::any([
            'lead_view',
            'booking_view',
            'customer_view',
            'provider_view',
            'onboarding_request_view',
        ]);
    }

    private static function userCanEnterAccounts(): bool
    {
        if (! is_admin_employee()) {
            return true;
        }

        return Gate::any([
            'transaction_view',
            'ledger_view',
            'wallet_view',
            'point_view',
            'withdraw_view',
            'people_hr',
        ]);
    }

    public static function isMorePage(?Request $request = null): bool
    {
        $request = $request ?? request();

        return $request->is(
            'admin/dashboard/operating-system*',
            'admin/catalog*',
            'admin/category*',
            'admin/sub-category*',
            'admin/service*',
            'admin/zone*',
        );
    }

    /**
     * Which workspace a search result belongs to. Reports and profile stay
     * shared. Marketing, Settings, and Accounts belong to their own workspace.
     */
    public static function workspaceForPath(string $uri): ?string
    {
        $path = self::normalizePath($uri);
        if ($path === '' || ! str_starts_with($path, 'admin')) {
            return null;
        }

        $request = Request::create('/'.$path, 'GET');

        if ($request->is(
            'admin/hr*',
            'admin/people*',
            'admin/dashboard/hr',
            'admin/dashboard/people',
            'admin/employee*',
            'admin/role*',
        )) {
            return self::HR;
        }

        if ($request->is(
            'admin/reports*',
            'admin/report*',
            'admin/analytics*',
            'admin/profile*',
        )) {
            return 'shared';
        }

        return self::detect($request) ?? self::OPERATIONS;
    }

    public static function uriBelongsTo(string $workspace, string $uri): bool
    {
        $owner = self::workspaceForPath($uri);

        if ($owner === null) {
            return false;
        }

        if ($owner === 'shared') {
            return true;
        }

        return $owner === $workspace;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $grouped
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function filterSearchGroups(array $grouped, ?string $workspace = null): array
    {
        if (! in_array($workspace, self::keys(), true)) {
            $workspace = self::current();
        }

        $filtered = [];

        foreach ($grouped as $type => $items) {
            if (! is_array($items)) {
                continue;
            }

            $kept = array_values(array_filter($items, function ($item) use ($workspace) {
                if (! is_array($item)) {
                    return false;
                }

                $uri = (string) ($item['uri'] ?? $item['full_route'] ?? '');

                return self::uriBelongsTo($workspace, $uri);
            }));

            if ($kept !== []) {
                $filtered[$type] = $kept;
            }
        }

        return $filtered;
    }

    public static function normalizePath(string $uri): string
    {
        $uri = trim($uri);
        if ($uri === '') {
            return '';
        }

        if (str_contains($uri, '://')) {
            $path = parse_url($uri, PHP_URL_PATH) ?: '';
        } else {
            $path = explode('?', $uri, 2)[0];
        }

        return ltrim(rawurldecode($path), '/');
    }

    public static function bodyClass(): string
    {
        if (request()->routeIs('admin.workspace.choose')) {
            return 'workspace-choosing';
        }

        return 'workspace-'.self::current();
    }
}
