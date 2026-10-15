<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgress extends Model
{
    use HasUuid;

    protected $table = 'training_progress';

    protected $fillable = [
        'training_assignment_id',
        'started_at',
        'content_done_at',
        'quiz_correct',
        'quiz_answers',
        'ai_answer',
        'ai_hits',
        'ai_score',
        'certified_at',
        'restart_count',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'content_done_at' => 'datetime',
        'certified_at' => 'datetime',
        'quiz_answers' => 'array',
        'ai_hits' => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TrainingAssignment::class, 'training_assignment_id');
    }

    public function canRestart(): bool
    {
        return $this->started_at !== null
            || $this->content_done_at !== null
            || $this->certified_at !== null
            || $this->quiz_correct !== null
            || $this->ai_score !== null;
    }

    public function markStarted(): void
    {
        if ($this->started_at) {
            return;
        }

        $this->started_at = now();
        $this->save();
    }

    public function refreshCertificate(): void
    {
        $snap = $this->snapshot();
        if ($snap['ready'] && ! $this->certified_at) {
            $this->certified_at = now();
            $this->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $program = $this->assignment->program;
        $questionCount = (int) ($program->questions_count ?? $program->questions->count());
        $pointCount = (int) ($program->marking_points_count ?? $program->markingPoints->count());
        $quizAnswered = $this->quiz_correct !== null;
        $aiAnswered = $this->ai_score !== null;
        $quizPassed = $questionCount === 0 || ($quizAnswered && (int) $this->quiz_correct >= (int) $program->question_pass_mark);
        $aiPassed = $pointCount === 0 || ($aiAnswered && (int) $this->ai_score >= (int) $program->ai_pass_mark);
        $contentDone = $this->content_done_at !== null;
        $certified = $this->certified_at !== null;
        $ready = $contentDone && $quizPassed && $aiPassed;

        $where = 'Not started';
        $message = 'Not opened yet';
        $whereTone = 'muted';
        $live = false;
        if ($certified || $ready) {
            $where = 'Finished';
            $message = 'Course finished';
            $whereTone = 'ok';
        } elseif ($aiAnswered && ! $aiPassed) {
            $where = 'AI test';
            $message = 'Practice not passed';
            $whereTone = 'bad';
            $live = true;
        } elseif ($quizAnswered && ! $quizPassed) {
            $where = 'Questions';
            $message = 'Quiz not passed';
            $whereTone = 'bad';
            $live = true;
        } elseif ($contentDone && $questionCount > 0 && ! $quizPassed) {
            $where = 'Questions';
            $message = 'Quiz is next';
            $whereTone = 'warn';
            $live = true;
        } elseif ($contentDone && $pointCount > 0 && ! $aiPassed) {
            $where = 'AI test';
            $message = 'Practice is next';
            $whereTone = 'warn';
            $live = true;
        } elseif ($this->started_at) {
            $where = 'Content';
            $message = 'Lessons in progress';
            $whereTone = 'warn';
            $live = true;
        }

        $steps = 0;
        if ($contentDone) {
            $steps++;
        }
        if ($contentDone && $quizPassed) {
            $steps++;
        }
        if ($contentDone && $quizPassed && $aiPassed) {
            $steps++;
        }
        if ($certified || $ready) {
            $steps = 4;
        }

        return [
            'steps' => $steps,
            'where' => $where,
            'message' => $message,
            'where_tone' => $whereTone,
            'live' => $live && ! $certified,
            'ready' => $ready,
            'quiz' => $questionCount === 0 ? 'Not required' : ($quizAnswered ? $this->quiz_correct.' of '.$questionCount.($quizPassed ? ' · passed' : ' · below the mark') : 'Not submitted'),
            'quiz_tone' => $questionCount === 0 ? 'muted' : ($quizAnswered ? ($quizPassed ? 'ok' : 'bad') : 'muted'),
            'ai' => $pointCount === 0 ? 'Not required' : ($aiAnswered ? $this->ai_score.' of '.$pointCount.($aiPassed ? ' · passed' : ' · below the mark') : 'Not submitted'),
            'ai_tone' => $pointCount === 0 ? 'muted' : ($aiAnswered ? ($aiPassed ? 'ok' : 'bad') : 'muted'),
            'cert' => $certified || $ready ? 'Issued' : 'Not issued',
            'cert_tone' => $certified || $ready ? 'ok' : 'muted',
            'quiz_blocking' => $questionCount > 0 && ! $quizPassed,
            'ai_blocking' => $pointCount > 0 && ! $aiPassed,
        ];
    }
}
