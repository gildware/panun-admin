<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Modules\AdminModule\Entities\PeopleHoliday;
use Modules\AdminModule\Entities\PeopleProfile;
use Modules\AdminModule\Support\ProcessGuideRegistry;
use Modules\AdminModule\Services\PeopleWorkspace;
use Modules\UserManagement\Entities\User;

class WorkspaceDashboardController extends Controller
{
    public function __construct(
        private PeopleWorkspace $workspace,
    ) {
    }

    public function people(): View
    {
        $user = $this->actor();
        $profile = $this->workspace->ensureStaffFile($user);

        return view('adminmodule::dashboards.people', [
            'name' => $this->workspace->displayName($user),
            'profile' => $profile,
            'birthdays' => $this->upcomingBirthdays(),
            'holidays' => $this->holidayWindow(false),
            'previousHolidays' => $this->holidayWindow(true),
        ]);
    }

    public function hr(): RedirectResponse
    {
        return redirect()->route('admin.dashboard.people');
    }

    public function training(): View
    {
        $guides = [];
        foreach (ProcessGuideRegistry::all() as $guide) {
            $class = $guide['training_guide'] ?? null;
            $slides = is_string($class) && method_exists($class, 'slides') ? count($class::slides()) : 0;
            $guides[] = [
                'title' => (string) ($guide['title'] ?? 'Guide'),
                'subtitle' => (string) ($guide['training_subtitle'] ?? ''),
                'slides' => $slides,
                'href' => route('admin.process-guides.index', ['guide' => $guide['key']]),
            ];
        }

        return view('adminmodule::dashboards.training', [
            'guides' => $guides,
            'showBusinessSystem' => ! is_admin_employee(),
        ]);
    }

    /**
     * @return Collection<int, array{name: string, detail: string, when: string, label: string, today: bool}>
     */
    private function upcomingBirthdays(): Collection
    {
        $today = now()->startOfDay();
        $users = $this->workspace->staffUsers()->keyBy('id');
        $profiles = PeopleProfile::query()
            ->whereIn('user_id', $users->keys())
            ->whereNotNull('date_of_birth')
            ->where(function ($query) {
                $query->whereNull('employment_status')->orWhere('employment_status', '!=', 'exited');
            })
            ->get();

        return $profiles
            ->map(function (PeopleProfile $profile) use ($users, $today) {
                $user = $users->get($profile->user_id);
                if (! $user instanceof User || ! $profile->date_of_birth) {
                    return null;
                }

                $next = $this->nextBirthday($profile->date_of_birth, $today);
                $days = (int) $today->diffInDays($next);

                return [
                    'name' => $this->workspace->displayName($user),
                    'detail' => (string) ($profile->department ?: ''),
                    'day' => $next->format('j'),
                    'month' => $next->format('M'),
                    'year' => '',
                    'label' => $this->whenLabel($days),
                    'today' => $days === 0,
                    'days' => $days,
                ];
            })
            ->filter()
            ->sortBy('days')
            ->take(8)
            ->values();
    }

    /**
     * Upcoming holidays run from today forward. Previous holidays are earlier days, newest first.
     *
     * @return Collection<int, array{name: string, detail: string, day: string, month: string, year: string, label: string, today: bool}>
     */
    private function holidayWindow(bool $previous): Collection
    {
        $today = now()->startOfDay();
        $query = PeopleHoliday::query();

        if ($previous) {
            $query->whereDate('holiday_on', '<', $today->toDateString())->orderByDesc('holiday_on');
        } else {
            $query->whereDate('holiday_on', '>=', $today->toDateString())->orderBy('holiday_on');
        }

        return $query
            ->limit(8)
            ->get()
            ->map(function (PeopleHoliday $holiday) use ($today, $previous) {
                $date = $holiday->holiday_on->copy()->startOfDay();
                $days = (int) abs($date->diffInDays($today));

                return [
                    'name' => $holiday->name,
                    'detail' => (string) ($holiday->description ?: ''),
                    'day' => $holiday->holiday_on->format('j'),
                    'month' => $holiday->holiday_on->format('M'),
                    'year' => $holiday->holiday_on->format('Y'),
                    'label' => $previous ? $this->pastLabel($days) : $this->whenLabel($days),
                    'today' => ! $previous && $days === 0,
                ];
            });
    }

    private function nextBirthday(Carbon $born, Carbon $today): Carbon
    {
        $year = (int) $today->year;
        $next = $this->birthdayInYear($born, $year);
        if ($next->lt($today)) {
            $next = $this->birthdayInYear($born, $year + 1);
        }

        return $next;
    }

    private function birthdayInYear(Carbon $born, int $year): Carbon
    {
        $day = (int) $born->day;
        if ((int) $born->month === 2 && $day === 29 && ! Carbon::create($year, 1, 1)->isLeapYear()) {
            $day = 28;
        }

        return Carbon::create($year, (int) $born->month, $day)->startOfDay();
    }

    private function whenLabel(int $days): string
    {
        return match (true) {
            $days === 0 => 'Today',
            $days === 1 => 'Tomorrow',
            default => 'in '.$days.' days',
        };
    }

    private function pastLabel(int $days): string
    {
        return $days === 1 ? 'Yesterday' : $days.' days ago';
    }

    private function actor(): User
    {
        /** @var User|null $user */
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
