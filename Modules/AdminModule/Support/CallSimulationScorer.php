<?php

namespace Modules\AdminModule\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallSimulationScorer
{
    /**
     * @param  list<string>  $skillIds
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @return array{summary: string, overall: int, passed: bool, scores: list<array{id: string, label: string, score: int, note: string}>, scored_by: string}
     */
    public function score(string $scenarioId, array $skillIds, array $transcript, int $passMark, string $brief = '', string $prompt = '', string $difficulty = 'standard', array $marks = []): array
    {
        $rubric = $this->rubric($scenarioId, $skillIds, $transcript, $passMark, $marks);
        $ai = $this->geminiScores($scenarioId, $skillIds, $transcript, $passMark, $brief, $prompt, $difficulty);
        if ($ai === null) {
            return $rubric;
        }

        $byId = [];
        foreach ($ai['scores'] as $row) {
            $byId[$row['id']] = $row;
        }

        $scores = [];
        foreach ($rubric['scores'] as $row) {
            $replacement = $byId[$row['id']] ?? null;
            $scores[] = $replacement ?? $row;
        }
        $scores = $this->withMarks($scores, $marks);
        $overall = $this->overall($scores, $marks);

        return [
            'summary' => $ai['summary'] !== '' ? $ai['summary'] : $this->summary($scenarioId, $scores, $overall, $passMark, $marks),
            'overall' => $overall,
            'passed' => $this->passed($scores, $marks, $overall, $passMark),
            'scores' => $scores,
            'scored_by' => 'ai',
        ];
    }

    /**
     * @param  list<string>  $skillIds
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @return array{summary: string, overall: int, passed: bool, scores: list<array{id: string, label: string, score: int, note: string}>, scored_by: string}
     */
    public function rubric(string $scenarioId, array $skillIds, array $transcript, int $passMark, array $marks = []): array
    {
        $scenario = CallSimulationCatalog::scenario($scenarioId) ?? [];
        $text = CallSimulationEngine::employeeText($transcript);
        $intents = CallSimulationEngine::unionIntents($transcript);
        $turns = count(CallSimulationEngine::employeeTurns($transcript));
        $scores = [];

        foreach ($skillIds as $skillId) {
            $skill = CallSimulationCatalog::skill($skillId);
            if (! $skill) {
                continue;
            }
            [$score, $note] = $this->skillScore($skillId, $scenario, $text, $intents, $turns, $transcript);
            $scores[] = [
                'id' => $skillId,
                'label' => $skill['name'],
                'score' => $score,
                'note' => $note,
            ];
        }

        $scores = $this->withMarks($scores, $marks);
        $overall = $this->overall($scores, $marks);

        return [
            'summary' => $this->summary($scenarioId, $scores, $overall, $passMark, $marks),
            'overall' => $overall,
            'passed' => $this->passed($scores, $marks, $overall, $passMark),
            'scores' => $scores,
            'scored_by' => 'guide',
        ];
    }

    /**
     * @param  list<array{id: string, score: int}>  $scores
     * @param  array<string, int>  $marks
     * @return list<array{id: string, score: int, marks?: int, earned?: int}>
     */
    private function withMarks(array $scores, array $marks): array
    {
        if ($marks === []) {
            return $scores;
        }

        foreach ($scores as &$row) {
            $weight = max(1, (int) ($marks[$row['id']] ?? 10));
            $row['marks'] = $weight;
            $row['earned'] = (int) round(((int) $row['score']) * $weight / 100);
        }

        return $scores;
    }

    /**
     * @param  list<array{id: string, score: int}>  $scores
     * @param  array<string, int>  $marks
     */
    private function overall(array $scores, array $marks): int
    {
        if ($scores === []) {
            return 0;
        }
        if ($marks === []) {
            return (int) round(collect($scores)->avg('score'));
        }

        $sum = 0;
        $weight = 0;
        foreach ($scores as $row) {
            $mark = max(1, (int) ($marks[$row['id']] ?? 10));
            $sum += ((int) $row['score']) * $mark;
            $weight += $mark;
        }

        return $weight === 0 ? 0 : (int) round($sum / $weight);
    }

    /**
     * @param  list<array{id: string, score: int, earned?: int}>  $scores
     * @param  array<string, int>  $marks
     */
    private function passed(array $scores, array $marks, int $overall, int $passMark): bool
    {
        if ($marks === []) {
            return $overall >= $passMark;
        }

        return $this->earnedTotal($scores) >= $passMark;
    }

    /**
     * @param  list<array{earned?: int}>  $scores
     */
    private function earnedTotal(array $scores): int
    {
        $earned = 0;
        foreach ($scores as $row) {
            $earned += (int) ($row['earned'] ?? 0);
        }

        return $earned;
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @param  list<string>  $intents
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @return array{0: int, 1: string}
     */
    private function skillScore(string $skillId, array $scenario, string $text, array $intents, int $turns, array $transcript): array
    {
        $has = fn (string $intent) => in_array($intent, $intents, true);
        $listenHits = 0;
        foreach ($scenario['listen_for'] ?? [] as $word) {
            if ($word !== '' && preg_match('/\b'.preg_quote((string) $word, '/').'\b/i', $text)) {
                $listenHits++;
            }
        }

        return match ($skillId) {
            'greeting' => $has('greet') && $has('company')
                ? [92, 'You greeted the caller and named Panun Kaergar.']
                : ($has('greet')
                    ? [70, 'You greeted the caller. Panun Kaergar was not named.']
                    : [32, 'The call had no clear greeting.']),
            'listening' => $listenHits >= 2
                ? [90, 'You used the caller’s own details, so it was clear you heard them.']
                : ($listenHits === 1 || $has('empathy')
                    ? [68, 'You picked up part of the situation. Repeat the actual problem before you act.']
                    : [36, 'The caller’s problem was not reflected back.']),
            'questioning' => $this->questionScore($text, $intents),
            'language' => $this->languageScore($text),
            'empathy' => $has('empathy')
                ? [90, 'You acknowledged how the situation felt.']
                : (in_array($scenario['name'] ?? '', ['Angry customer or complaint', 'Failed repair or unresolved service', 'Provider unavailable, late or no-show'], true)
                    ? [34, 'This caller needed an apology or acknowledgement before the fix.']
                    : [58, 'A short acknowledgement would have made the call easier to follow.']),
            'problem' => ($has('problem') || $has('reassign') || $has('reschedule') || $has('unsafe_stop'))
                ? [86, 'You gave a next step the caller could follow.']
                : [40, 'The call did not land on a concrete next step.'],
            'sop' => $has('document') && $has('followup') && ! $has('promise_soon')
                ? [92, 'You covered notes and a follow-up, without promising a time that is not confirmed.']
                : (($has('document') || $has('followup'))
                    ? [64, 'Part of the after-call flow was there. Notes and a follow-up both need to be said.']
                    : [36, 'The call did not say what would be saved or who would follow up.']),
            'policy' => $this->policyScore($has),
            'objection' => ($has('reschedule') || $has('objection') || $has('no_discount') || $has('decision'))
                ? [84, 'You answered the pushback with an option, not only a repeat of the script.']
                : [48, 'The objection was not clearly handled.'],
            'decision' => $has('decision') || $has('no_discount') || $has('unsafe_stop')
                ? [88, 'You were clear about what you could and could not decide.']
                : ($has('approve_now') || $has('refund_promise') || $has('discount')
                    ? [30, 'You decided something that needed a limit or a manager.']
                    : [52, 'It was not clear what you are allowed to decide on this call.']),
            'escalation' => $has('escalate')
                ? [90, 'You brought a manager or senior in where the call needed it.']
                : [46, 'Nothing was escalated. Do that when the decision is not yours.'],
            'followup' => $has('followup')
                ? [90, 'You named a follow-up instead of leaving the caller without a next contact.']
                : [38, 'Nobody was named to call back or update the caller.'],
            'documentation' => $has('document')
                ? [90, 'You said what would be saved on the lead or booking.']
                : [36, 'The call ended without saying what would be written down.'],
            'closure' => $has('close')
                ? [88, 'You closed the call instead of dropping it.']
                : [44, 'The call had no clear close.'],
            'adaptability' => $this->adaptabilityScore($scenario, $transcript),
            'conflict' => $has('rude') || $has('blame')
                ? [24, 'The tone argued with the caller or blamed them.']
                : ($has('empathy') && ($has('reassign') || $has('escalate') || $has('problem'))
                    ? [90, 'You stayed calm and moved the complaint to a next step.']
                    : [50, 'The conflict was not settled. Acknowledge it, then give the next step.']),
            'time' => $this->timeScore($turns),
            'accuracy' => ($has('promise_soon') || $has('discount') || $has('approve_now') || $has('refund_promise') || $has('excuse'))
                ? [28, 'A time, price, excuse, or approval was stated before it was confirmed.']
                : ($has('no_promise') || $has('no_discount') || $has('decision') || $has('unsafe_stop') || $has('price')
                    ? [90, 'You avoided inventing a time, a discount, or an approval.']
                    : [60, 'Nothing false was obvious. Be explicit when a time or price is not confirmed yet.']),
            'satisfaction' => $has('satisfaction') || $has('close')
                ? [84, 'You checked the caller was clear before leaving the call.']
                : [48, 'You did not check whether the caller was clear or willing to go ahead.'],
            'safety' => $this->safetyScore($scenario, $has),
            default => [50, 'This skill was not shown clearly.'],
        };
    }

    /**
     * @param  list<string>  $intents
     * @return array{0: int, 1: string}
     */
    private function questionScore(string $text, array $intents): array
    {
        $asks = count(array_intersect($intents, ['ask_problem', 'ask_area', 'ask_time', 'ask_name', 'need_docs']));
        $marks = substr_count($text, '?');
        $total = $asks + ($marks > 0 ? 1 : 0);
        if ($total >= 3) {
            return [92, 'You asked for the details the booking needs.'];
        }
        if ($total >= 1) {
            return [66, 'You asked something. The service, place, and time still need to be covered when they are missing.'];
        }

        return [34, 'Too little was asked. The caller should not have to guess what you need.'];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function languageScore(string $text): array
    {
        if (preg_match('/\b(stupid|idiot|shut up)\b/i', $text)) {
            return [20, 'The language was not acceptable on a Panun Kaergar call.'];
        }
        $words = str_word_count($text);
        if ($words >= 30) {
            return [84, 'The caller could follow what you said.'];
        }
        if ($words >= 12) {
            return [68, 'Understandable, but some replies were too short to be clear.'];
        }

        return [40, 'Too little was said to show clear communication.'];
    }

    /**
     * @param  callable(string): bool  $has
     * @return array{0: int, 1: string}
     */
    private function policyScore(callable $has): array
    {
        if ($has('discount') || $has('refund_promise')) {
            return [30, 'A discount or a refund was offered without the authority to do that.'];
        }
        if ($has('promise_soon')) {
            return [34, 'A visit time was promised before a provider was confirmed.'];
        }
        if (($has('price') || $has('no_discount')) && ($has('no_promise') || $has('decision'))) {
            return [92, 'You stayed with policy: published price, and no time or refund that is not confirmed.'];
        }
        if ($has('price') || $has('no_discount') || $has('no_promise')) {
            return [74, 'Part of the price and time rule was said. Say both the published price and that the time comes after a provider is set.'];
        }

        return [46, 'Price, discount, or refund policy was not stated.'];
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @return array{0: int, 1: string}
     */
    private function adaptabilityScore(array $scenario, array $transcript): array
    {
        $curveball = trim((string) ($scenario['curveball'] ?? ''));
        if ($curveball === '') {
            return [60, 'This call had no unexpected question.'];
        }

        $waitingForReply = false;
        foreach ($transcript as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if (($row['role'] ?? '') === 'customer' && $text === $curveball) {
                $waitingForReply = true;

                continue;
            }
            if ($waitingForReply && ($row['role'] ?? '') === 'employee') {
                if (mb_strlen($text) >= 20 && ! preg_match('/^(ok|okay|hmm|yes|haan|theek)[.!]?$/i', $text)) {
                    return [88, 'You answered the unexpected question instead of ignoring it.'];
                }

                return [42, 'The unexpected question was brushed off.'];
            }
        }

        return [55, 'The call ended before the unexpected question.'];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function timeScore(int $turns): array
    {
        if ($turns >= 4 && $turns <= 8) {
            return [86, 'The call moved and did not end before the situation was handled.'];
        }
        if ($turns < 3) {
            return [36, 'The call ended before the situation was handled.'];
        }

        return [62, 'The call ran long. Close it once the next step is clear.'];
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @param  callable(string): bool  $has
     * @return array{0: int, 1: string}
     */
    private function safetyScore(array $scenario, callable $has): array
    {
        $unsafe = ($scenario['name'] ?? '') === 'Unsafe or out-of-scope job';
        if (! $unsafe) {
            return $has('unsafe_stop')
                ? [86, 'You noticed a safety limit and said so.']
                : [72, 'This call did not require a safety stop.'];
        }
        if ($has('unsafe_stop') && ! $has('promise_soon')) {
            return [94, 'You refused to send someone into an unsafe or out-of-scope job.'];
        }
        if ($has('unsafe_stop')) {
            return [60, 'You mentioned safety, then still promised a visit. Stop the unsafe job clearly.'];
        }

        return [18, 'A sparking main board was treated like a normal job. That visit should have been stopped.'];
    }

    /**
     * @param  list<array{id: string, label: string, score: int, note: string}>  $scores
     */
    private function summary(string $scenarioId, array $scores, int $overall, int $passMark, array $marks = []): string
    {
        $name = CallSimulationCatalog::scenario($scenarioId)['name'] ?? 'This call';
        if ($scores === []) {
            return $name.' could not be scored because no skills were selected.';
        }

        $ranked = $scores;
        usort($ranked, fn (array $a, array $b) => $b['score'] <=> $a['score']);
        $strong = array_slice($ranked, 0, 2);
        $weak = array_values(array_filter($ranked, fn (array $row) => $row['score'] < 70));
        $weak = array_slice(array_reverse($weak), 0, 2);
        $result = $this->passed($scores, $marks, $overall, $passMark) ? 'Passed' : 'Below the pass mark';
        if ($marks !== []) {
            $possible = 0;
            foreach ($scores as $row) {
                $possible += (int) ($row['marks'] ?? 0);
            }
            $sentence = $name.' scored '.$this->earnedTotal($scores).' of '.$possible.'. '.$result.' against '.$passMark.'.';
        } else {
            $sentence = $name.' scored '.$overall.'. '.$result.' against '.$passMark.'.';
        }
        if ($strong !== []) {
            $sentence .= ' Stronger: '.implode(' and ', array_map(fn (array $row) => $row['label'], $strong)).'.';
        }
        if ($weak !== []) {
            $sentence .= ' To improve: '.implode(' and ', array_map(fn (array $row) => $row['label'], $weak)).'.';
        }

        return $sentence;
    }

    /**
     * @param  list<string>  $skillIds
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @return array{summary: string, scores: list<array{id: string, label: string, score: int, note: string}>}|null
     */
    private function geminiScores(string $scenarioId, array $skillIds, array $transcript, int $passMark, string $brief, string $prompt = '', string $difficulty = 'standard'): ?array
    {
        $key = trim((string) config('services.gemini.api_key'));
        if ($key === '' || $skillIds === [] || $transcript === []) {
            return null;
        }

        $scenario = CallSimulationCatalog::scenario($scenarioId);
        if (! $scenario) {
            return null;
        }

        $skillLines = [];
        foreach ($skillIds as $skillId) {
            $skill = CallSimulationCatalog::skill($skillId);
            if ($skill) {
                $skillLines[] = $skillId.': '.$skill['name'].' — '.$skill['blurb'];
            }
        }

        $lines = [];
        foreach ($transcript as $row) {
            $role = ($row['role'] ?? '') === 'employee' ? 'Employee' : 'Caller';
            $lines[] = $role.': '.trim((string) ($row['text'] ?? ''));
        }

        $prompt = "Score this Panun Kaergar training call. The employee is practising a customer or provider call.\n"
            ."Scenario: {$scenario['name']}. {$scenario['summary']}\n"
            .'Caller: '.$scenario['caller_name'].' ('.$scenario['caller_role'].").\n"
            .$this->behaviorLine($scenario, $prompt)
            .$this->difficultyScoreLine($difficulty)
            ."Score each skill from 0 to 100. The pass mark is applied afterwards from the skill marks.\n"
            .($brief !== '' ? "Manager note, already spoken by the caller if it came up: {$brief}\n" : '')
            ."Rules the employee should follow: greet as Panun Kaergar; ask for missing details; do not promise a visit time before a provider is confirmed; do not invent a discount or a refund; do not approve a provider before documents; do not send anyone to a sparking main board or other unsafe job; say what will be saved and who will follow up.\n"
            ."The employee may speak English, Hindi, Urdu, Roman Urdu, or Hinglish. Count those the same. A greeting or an apology in Urdu or Hindi is still that skill. Do not mark them down because the language is not English.\n"
            ."Score only these skills, from 0 to 100, using the actual words in the transcript:\n"
            .implode("\n", $skillLines)."\n\n"
            ."Transcript:\n".implode("\n", $lines)."\n\n"
            .'Return only JSON: {"summary":"two sentences","scores":[{"id":"greeting","score":80,"note":"one sentence citing what they said"}]}'
            .'Use the skill ids exactly. Every listed skill must appear once.';

        try {
            $model = trim((string) config('services.gemini.model', 'gemini-2.5-flash'));
            if ($model === '') {
                $model = 'gemini-2.5-flash';
            }
            $response = Http::timeout(20)
                ->withQueryParameters(['key' => $key])
                ->acceptJson()
                ->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent', [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => min(900, 140 + (70 * count($skillIds))),
                        'responseMimeType' => 'application/json',
                        'thinkingConfig' => ['thinkingBudget' => 0],
                    ],
                ]);
            if ($response->failed()) {
                return null;
            }
            $raw = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

            return $this->parseGemini($raw, $skillIds);
        } catch (\Throwable $exception) {
            Log::warning('Call simulation scoring skipped the model', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function behaviorLine(array $scenario, string $prompt): string
    {
        $behavior = trim($prompt) !== '' ? trim($prompt) : trim((string) ($scenario['guide'] ?? ''));

        return $behavior === '' ? '' : "The caller was behaving this way:\n{$behavior}\n";
    }

    private function difficultyScoreLine(string $difficulty): string
    {
        return match ($difficulty) {
            'foundation' => "Difficulty was foundation. The caller was patient and asked one simple thing at a time. Do not mark the employee down for pressure that was never applied.\n",
            'advanced' => "Difficulty was advanced. The caller was sharper and asked harder follow-ups. Vague answers and early promises should score lower.\n",
            default => "Difficulty was standard. Score a normal customer or provider call.\n",
        };
    }

    /**
     * @param  list<string>  $skillIds
     * @return array{summary: string, scores: list<array{id: string, label: string, score: int, note: string}>}|null
     */
    private function parseGemini(string $raw, array $skillIds): ?array
    {
        $json = trim($raw);
        $json = preg_replace('/^```(?:json)?|```$/m', '', $json) ?? $json;
        if (! preg_match('/\{.*\}/s', $json, $match)) {
            return null;
        }
        $decoded = json_decode($match[0], true);
        if (! is_array($decoded) || ! is_array($decoded['scores'] ?? null)) {
            return null;
        }

        $scores = [];
        foreach ($decoded['scores'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = (string) ($row['id'] ?? '');
            if (! in_array($id, $skillIds, true) || isset($scores[$id])) {
                continue;
            }
            $skill = CallSimulationCatalog::skill($id);
            if (! $skill) {
                continue;
            }
            $scores[$id] = [
                'id' => $id,
                'label' => $skill['name'],
                'score' => min(100, max(0, (int) ($row['score'] ?? 0))),
                'note' => mb_substr(trim((string) ($row['note'] ?? '')), 0, 280),
            ];
        }

        if (count($scores) < count($skillIds)) {
            return null;
        }

        return [
            'summary' => mb_substr(trim((string) ($decoded['summary'] ?? '')), 0, 500),
            'scores' => array_values($scores),
        ];
    }
}
