<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AdminModule\Support\CallSimulationCatalog;
use Modules\UserManagement\Entities\User;

class TrainingCallAssignment extends Model
{
    use HasUuid;

    protected $fillable = [
        'training_call_test_id',
        'user_id',
        'assigned_by',
        'due_at',
        'assigned_scope',
        'department_name',
    ];

    protected $casts = [
        'due_at' => 'date',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(TrainingCallTest::class, 'training_call_test_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingCallSession::class);
    }

    /**
     * Latest scored session for each scenario on this test.
     *
     * @return array{
     *     total: int,
     *     done: int,
     *     overall: ?int,
     *     earned: ?int,
     *     marks: ?int,
     *     passed: bool,
     *     complete: bool,
     *     overdue: bool,
     *     on_call: bool,
     *     label: string,
     *     tone: string,
     *     next: ?string,
     *     sessions: array<string, TrainingCallSession>
     * }
     */
    public function rollup(): array
    {
        $this->loadMissing('test', 'sessions');
        $scenarioIds = array_values(array_filter(
            $this->test?->scenarios ?? [],
            fn ($id) => is_string($id) && CallSimulationCatalog::scenario($id)
        ));
        $latest = [];
        foreach ($this->sessions as $session) {
            if ($session->overall === null || $session->ended_at === null) {
                continue;
            }
            $current = $latest[$session->scenario] ?? null;
            if (! $current || $session->ended_at->greaterThan($current->ended_at)) {
                $latest[$session->scenario] = $session;
            }
        }

        $doneIds = array_values(array_intersect($scenarioIds, array_keys($latest)));
        $overall = $doneIds === []
            ? null
            : (int) round(collect($doneIds)->avg(fn (string $id) => (int) $latest[$id]->overall));
        $markTotal = array_sum(array_map('intval', is_array($this->test->skill_marks) ? $this->test->skill_marks : []));
        $earned = null;
        if ($markTotal > 0 && $doneIds !== []) {
            $earned = (int) round(collect($doneIds)->avg(fn (string $id) => $this->sessionEarned($latest[$id])));
        }
        $complete = $scenarioIds !== [] && count($doneIds) === count($scenarioIds);
        $passed = $markTotal > 0
            ? $complete && $earned !== null && $earned >= (int) $this->test->pass_mark
            : $complete && $overall !== null && $overall >= (int) ($this->test->pass_mark ?? 70);
        $onCall = $this->sessions->contains(fn (TrainingCallSession $session) => $session->ended_at === null);
        $overdue = ! $complete && $this->due_at && $this->due_at->toDateString() < now()->toDateString();
        $next = null;
        foreach ($scenarioIds as $id) {
            if (! isset($latest[$id])) {
                $next = $id;
                break;
            }
        }

        if ($complete && $passed) {
            $label = 'Passed';
            $tone = 'ok';
        } elseif ($complete) {
            $label = 'Below the pass mark';
            $tone = 'bad';
        } elseif ($onCall) {
            $label = 'On a call';
            $tone = 'warn';
        } elseif ($overdue) {
            $label = 'Past the deadline';
            $tone = 'bad';
        } elseif ($doneIds !== []) {
            $label = count($doneIds).' of '.count($scenarioIds).' calls';
            $tone = 'warn';
        } else {
            $label = 'Not started';
            $tone = 'muted';
        }

        return [
            'total' => count($scenarioIds),
            'done' => count($doneIds),
            'overall' => $overall,
            'earned' => $earned,
            'marks' => $markTotal > 0 ? $markTotal : null,
            'passed' => $passed,
            'complete' => $complete,
            'overdue' => $overdue,
            'on_call' => $onCall,
            'label' => $label,
            'tone' => $tone,
            'next' => $next,
            'sessions' => $latest,
        ];
    }

    private function sessionEarned(TrainingCallSession $session): int
    {
        $scores = is_array($session->scores) ? $session->scores : [];
        $earned = 0;
        $found = false;
        foreach ($scores as $row) {
            if (! is_array($row) || ! array_key_exists('earned', $row)) {
                continue;
            }
            $found = true;
            $earned += (int) $row['earned'];
        }
        if ($found) {
            return $earned;
        }

        $marks = is_array($this->test->skill_marks) ? $this->test->skill_marks : [];
        foreach ($scores as $row) {
            if (! is_array($row) || ! is_string($row['id'] ?? null)) {
                continue;
            }
            $weight = max(1, (int) ($marks[$row['id']] ?? 10));
            $earned += (int) round(((int) ($row['score'] ?? 0)) * $weight / 100);
            $found = true;
        }

        return $found ? $earned : 0;
    }
}
