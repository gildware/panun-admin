<?php

namespace Modules\AdminModule\Support;

class CallSimulationEngine
{
    /** @var array<string, string> */
    private const INTENT_PATTERNS = [
        'greet' => '/\b(hello|hi|hey|good morning|good afternoon|good evening|assalamu?\s*alaikum|as-salamu|assalam|salaam|namaste|namaskar|adaab|salam)\b/iu',
        'company' => '/\b(panun|kaergar)\b/i',
        'ask_problem' => '/\b(what happened|what is the problem|what\'s the problem|what is wrong|what\'s wrong|which service|tell me (?:a bit )?more|what do you need|describe the|kya hua|kya problem|kya masla|kya dikkat|problem kya)\b/i',
        'ask_area' => '/\b(where (?:are|is|do) you|your address|the address|which area|what area|your location|send the location|kahan|kaunse area|address kya|kahan se|pata kya)\b/i',
        'ask_time' => '/\b(what time|which day|what day|when (?:can|should|do|are|will|would)|which slot|kab aana|kab chahiye|kis time|kab free)\b/i',
        'ask_name' => '/\b(your name|may i (?:know|have) your name|who am i speaking|who is this|naam kya|aapka naam|apka naam)\b/i',
        'empathy' => '/\b(sorry|i am sorry|i\'m sorry|apolog|i understand|that must|frustrating|inconvenience|i can hear|maaf|maafi|samajh|afsos|takleef|tension mat|ghabraiye)\b/i',
        'price' => '/\b(published price|rate card|standard charge|our charge|the charge|the price|the cost|visit charge)\b/i',
        'no_promise' => '/\b(cannot promise|can\'t promise|can not promise|will not promise|won\'t promise|do not promise|don\'t promise|only after|once a provider|after a provider|after the provider|when a provider is)\b/i',
        'promise_soon' => '/\b(within \d+|in \d+ minutes|in 30|half an hour|right now|immediately|on the way|coming now|within the hour|he is coming|she is coming|will be there (?:in|by|within)|today for sure)\b/i',
        'no_discount' => '/\b(no discount|cannot discount|can\'t discount|cannot reduce|can\'t reduce|unable to reduce|not able to reduce|no reduction)\b/i',
        'discount' => '/\b(i (?:can|will) (?:give|offer).{0,24}(?:discount|off|reduc)|special price|\d+\s*%|we can reduce|i can reduce)\b/i',
        'escalate' => '/\b(manager|supervisor|escalat|senior)\b/i',
        'document' => '/\b(note|save|remark|on the (?:lead|booking|panel)|i(?:\'ll| will) (?:write|update|record)|likh|note kar|save kar)\b/i',
        'followup' => '/\b(follow[- ]?up|call you back|whatsapp|i(?:\'ll| will) (?:call|update|confirm)|get back to you|batata|bata dunga|call karunga|call karungi|baad mein call)\b/i',
        'close' => '/\b(anything else|thank you for calling|thanks for calling|have a good|goodbye|take care|that is all|that\'s all|shukriya|allah hafiz|khuda hafiz|kuch aur)\b/i',
        'reschedule' => '/\b(reschedul|another day|another time|move the booking|different day|keep the booking)\b/i',
        'cancel_ok' => '/\b(i(?:\'ll| will) cancel|cancel(?:ling|ed)? (?:it|the|your)|close the booking)\b/i',
        'objection' => '/\b(you can keep|option is|if you prefer|no pressure|without forcing|the reason to)\b/i',
        'reassign' => '/\b(another (?:provider|plumber|electrician|person)|different (?:provider|plumber|electrician|person)|someone else|reassign)\b/i',
        'need_docs' => '/\b(document|aadhaar|aadhar|agreement|id proof|photo id|pan card)\b/i',
        'approve_now' => '/\b(you are approved|you\'re approved|approved today|start (?:taking )?jobs today|add you (?:now|today)|on the panel now)\b/i',
        'decision' => '/\b(i can decide|i cannot decide|i can\'t decide|not (?:my|within my) authority|a manager (?:has to|must|will) confirm|i am not allowed)\b/i',
        'unsafe_stop' => '/\b(not safe|unsafe|out of scope|do not (?:open|touch|send)|don\'t (?:open|touch|send)|cannot send|can\'t send|will not send|won\'t send|must not|too dangerous|safe nahi|nahi bhej|nahin bhej|khatarnak)\b/i',
        'refund_promise' => '/\b(will refund|refund (?:it|the|your)|full refund|money back|visit charge back|no charge|free revisit|will not charge|won\'t charge)\b/i',
        'blame' => '/\b(your fault|you should have|not our problem|these (?:things|delays) happen)\b/i',
        'rude' => '/\b(shut up|stupid|idiot)\b/i',
        'excuse' => '/\b(stuck in traffic|probably (?:late|on the way)|i think he is|maybe he is)\b/i',
        'pressure' => '/\b(you must keep|you should not cancel|do not cancel|don\'t cancel|final chance)\b/i',
        'problem' => '/\b(i will|i\'ll|we will|next step|send someone|arrange|book|revisit)\b/i',
        'satisfaction' => '/\b(does that work|is that (?:okay|ok|fine)|are you (?:okay|happy|clear)|shall i go ahead)\b/i',
    ];

    /**
     * @param  list<array{role: string, text: string}>  $transcript
     * @param  list<string>  $skillIds
     * @return array{line: string, done: bool}
     */
    public function reply(string $scenarioId, array $transcript, array $skillIds = [], string $extraLine = ''): array
    {
        $scenario = CallSimulationCatalog::scenario($scenarioId);
        if (! $scenario) {
            return ['line' => 'Sorry, I cannot continue this call.', 'done' => true];
        }

        $turns = self::employeeTurns($transcript);
        $count = count($turns);
        $last = $count ? (string) $turns[$count - 1]['text'] : '';
        $lastIntents = self::intentsIn($last);
        $seen = self::unionIntents($transcript);

        if ($count >= (int) ($scenario['max_turns'] ?? 9)) {
            return ['line' => (string) $scenario['close'], 'done' => true];
        }

        if (in_array('close', $lastIntents, true) && $count >= (int) ($scenario['min_turns'] ?? 4)) {
            return ['line' => (string) $scenario['close'], 'done' => true];
        }

        if (in_array('close', $lastIntents, true)) {
            return ['line' => 'Please stay on the line. This is not finished yet.', 'done' => false];
        }

        if (mb_strlen(trim($last)) < 8 && ! in_array('greet', $lastIntents, true)) {
            return ['line' => 'Sorry, I could not hear that. Please say it again.', 'done' => false];
        }

        if ($count === 1 && ! in_array('greet', $lastIntents, true) && ! in_array('company', $seen, true)) {
            return ['line' => 'Hello? Are you from Panun Kaergar?', 'done' => false];
        }

        $reaction = $this->firstUnsaid($scenario['reactions'] ?? [], $lastIntents, $transcript);
        if ($reaction !== null) {
            return ['line' => $reaction, 'done' => false];
        }

        $facts = $this->unsaidMatching($scenario['facts'] ?? [], $lastIntents, $transcript, 2);
        if ($facts === [] && $count >= 2) {
            $previous = (string) ($turns[$count - 2]['text'] ?? '');
            $facts = $this->unsaidMatching($scenario['facts'] ?? [], self::intentsIn($previous), $transcript, 1);
        }
        if ($facts !== []) {
            return ['line' => implode(' ', $facts), 'done' => false];
        }

        $probe = $this->dueProbe($scenario['probes'] ?? [], $count, $seen, $transcript);
        if ($probe !== null) {
            return ['line' => $probe, 'done' => false];
        }

        $extraLine = trim($extraLine);
        if ($extraLine !== '' && $count >= 3 && ! $this->said($transcript, $extraLine)) {
            return ['line' => $extraLine, 'done' => false];
        }

        $curveball = trim((string) ($scenario['curveball'] ?? ''));
        if ($curveball !== '' && $count >= 3 && ! $this->said($transcript, $curveball)) {
            return ['line' => $curveball, 'done' => false];
        }

        if ($count >= 4) {
            $skillLine = $this->skillProbe($scenario, $skillIds, $transcript);
            if ($skillLine !== null) {
                return ['line' => $skillLine, 'done' => false];
            }
        }

        $nudges = $scenario['nudges'] ?? ['What will you do next?'];
        $nudge = (string) $nudges[$count % count($nudges)];

        return ['line' => $nudge, 'done' => false];
    }

    /**
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @return list<array{role: string, text: string}>
     */
    public static function employeeTurns(array $transcript): array
    {
        $turns = [];
        foreach ($transcript as $row) {
            if (($row['role'] ?? '') === 'employee' && trim((string) ($row['text'] ?? '')) !== '') {
                $turns[] = ['role' => 'employee', 'text' => trim((string) $row['text'])];
            }
        }

        return $turns;
    }

    /**
     * @return list<string>
     */
    public static function intentsIn(string $text): array
    {
        $found = [];
        foreach (self::INTENT_PATTERNS as $intent => $pattern) {
            if (preg_match($pattern, $text)) {
                $found[] = $intent;
            }
        }

        if (in_array('no_promise', $found, true)) {
            $found = array_values(array_diff($found, ['promise_soon']));
        }
        if (in_array('no_discount', $found, true)) {
            $found = array_values(array_diff($found, ['discount']));
        }

        return $found;
    }

    /**
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @return list<string>
     */
    public static function unionIntents(array $transcript): array
    {
        $seen = [];
        foreach (self::employeeTurns($transcript) as $turn) {
            foreach (self::intentsIn($turn['text']) as $intent) {
                $seen[$intent] = true;
            }
        }

        return array_keys($seen);
    }

    /**
     * @param  list<array{role?: string, text?: string}>  $transcript
     */
    public static function employeeText(array $transcript): string
    {
        return implode("\n", array_map(
            fn (array $turn) => $turn['text'],
            self::employeeTurns($transcript)
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $intents
     * @param  list<array{role?: string, text?: string}>  $transcript
     */
    private function firstUnsaid(array $rows, array $intents, array $transcript): ?string
    {
        $matched = $this->unsaidMatching($rows, $intents, $transcript, 1);

        return $matched[0] ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $intents
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @return list<string>
     */
    private function unsaidMatching(array $rows, array $intents, array $transcript, int $limit): array
    {
        $lines = [];
        foreach ($rows as $row) {
            $when = $row['when'] ?? [];
            if (! is_array($when) || $when === [] || array_intersect($when, $intents) === []) {
                continue;
            }
            $line = trim((string) ($row['line'] ?? ''));
            if ($line === '' || $this->said($transcript, $line)) {
                continue;
            }
            $lines[] = $line;
            if (count($lines) >= $limit) {
                break;
            }
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $probes
     * @param  list<string>  $seen
     * @param  list<array{role?: string, text?: string}>  $transcript
     */
    private function dueProbe(array $probes, int $count, array $seen, array $transcript): ?string
    {
        foreach ($probes as $probe) {
            if ($count < (int) ($probe['after_turn'] ?? 1)) {
                continue;
            }
            $unless = $probe['unless_intent'] ?? [];
            if (is_array($unless) && array_intersect($unless, $seen) !== []) {
                continue;
            }
            $line = trim((string) ($probe['line'] ?? ''));
            if ($line === '' || $this->said($transcript, $line)) {
                continue;
            }

            return $line;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @param  list<string>  $skillIds
     * @param  list<array{role?: string, text?: string}>  $transcript
     */
    private function skillProbe(array $scenario, array $skillIds, array $transcript): ?string
    {
        $asked = 0;
        $overrides = is_array($scenario['skill_probes'] ?? null) ? $scenario['skill_probes'] : [];
        foreach ($skillIds as $skillId) {
            $line = trim((string) ($overrides[$skillId] ?? CallSimulationCatalog::skill($skillId)['probe'] ?? ''));
            if ($line === '') {
                continue;
            }
            if ($this->said($transcript, $line)) {
                $asked++;

                continue;
            }
            if ($asked >= 3) {
                return null;
            }

            return $line;
        }

        return null;
    }

    /**
     * @param  list<array{role?: string, text?: string}>  $transcript
     */
    private function said(array $transcript, string $line): bool
    {
        $needle = mb_strtolower(trim($line));
        foreach ($transcript as $row) {
            if (($row['role'] ?? '') === 'customer' && mb_strtolower(trim((string) ($row['text'] ?? ''))) === $needle) {
                return true;
            }
        }

        return false;
    }
}
