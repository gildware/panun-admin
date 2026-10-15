<?php

namespace Modules\AdminModule\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallSimulationCaller
{
    private const BLOCK_KEY = 'training_call_gemini_blocked';
    public function __construct(private readonly CallSimulationEngine $engine) {}

    /**
     * @return array{line: string, done: bool, lang: string}
     */
    public function opening(string $scenarioId, string $listen, string $prompt = '', string $difficulty = 'standard', string $tone = 'warm'): array
    {
        $spoken = $this->ask($scenarioId, [], [], '', $listen, true, $prompt, $difficulty, $tone);
        if ($spoken !== null) {
            $spoken['source'] = 'ai';

            return $spoken;
        }

        $scenario = CallSimulationCatalog::scenario($scenarioId);

        return [
            'line' => (string) ($scenario['opening'] ?? 'Hello?'),
            'done' => false,
            'lang' => 'en',
            'source' => 'script',
            'reason' => $this->blockedReason(),
        ];
    }

    /**
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @param  list<string>  $skillIds
     * @return array{line: string, done: bool, lang: string}
     */
    public function reply(string $scenarioId, array $transcript, array $skillIds, string $brief, string $listen, string $prompt = '', string $difficulty = 'standard', string $tone = 'warm'): array
    {
        $spoken = $this->ask($scenarioId, $transcript, $skillIds, $brief, $listen, false, $prompt, $difficulty, $tone);
        if ($spoken !== null) {
            $spoken['source'] = 'ai';

            return $spoken;
        }

        $scripted = $this->engine->reply($scenarioId, $transcript, $skillIds, $brief);
        $scripted['lang'] = $this->langOf($scripted['line']);
        $scripted['source'] = 'script';
        $scripted['reason'] = $this->blockedReason();

        return $scripted;
    }

    /**
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @param  list<string>  $skillIds
     * @return array{line: string, done: bool, lang: string}|null
     */
    private function ask(string $scenarioId, array $transcript, array $skillIds, string $brief, string $listen, bool $opening, string $prompt = '', string $difficulty = 'standard', string $tone = 'warm'): ?array
    {
        $key = trim((string) config('services.gemini.api_key'));
        $scenario = CallSimulationCatalog::scenario($scenarioId);
        if ($key === '' || ! $scenario || Cache::get(self::BLOCK_KEY) === 'credits') {
            return null;
        }

        $listen = in_array($listen, ['en', 'hi', 'ur'], true) ? $listen : 'en';
        $difficulty = in_array($difficulty, ['foundation', 'standard', 'advanced'], true) ? $difficulty : 'standard';
        [$system, $turn] = $this->prompt($scenario, $transcript, $skillIds, $brief, $listen, $opening, $prompt, $difficulty, $tone);

        try {
            $model = trim((string) config('services.gemini.model', 'gemini-2.5-flash'));
            if ($model === '') {
                $model = 'gemini-2.5-flash';
            }
            $response = Http::timeout(14)
                ->withQueryParameters(['key' => $key])
                ->acceptJson()
                ->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent', [
                    'systemInstruction' => ['parts' => [['text' => $system]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $turn]]]],
                    'generationConfig' => [
                        'temperature' => 0.85,
                        'maxOutputTokens' => 160,
                        'responseMimeType' => 'application/json',
                        // Flash counts hidden thinking against this limit and was truncating the spoken line.
                        'thinkingConfig' => ['thinkingBudget' => 0],
                    ],
                ]);
            if ($response->status() === 402) {
                Cache::put(self::BLOCK_KEY, 'credits', now()->addMinutes(2));
            }
            if ($response->failed()) {
                Log::warning('Call simulation caller skipped the model', ['status' => $response->status()]);

                return null;
            }
            $raw = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

            return $this->parse($raw);
        } catch (\Throwable $exception) {
            Log::warning('Call simulation caller skipped the model', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * Short character card plus only this turn, so the model answers the employee
     * instead of reciting the scenario, and each request stays small.
     *
     * @param  array<string, mixed>  $scenario
     * @param  list<array{role?: string, text?: string}>  $transcript
     * @param  list<string>  $skillIds
     * @return array{0: string, 1: string}
     */
    private function prompt(array $scenario, array $transcript, array $skillIds, string $brief, string $listen, bool $opening, string $prompt = '', string $difficulty = 'standard', string $tone = 'warm'): array
    {
        $listenName = ['en' => 'English', 'hi' => 'Hindi', 'ur' => 'Urdu'][$listen];
        $turns = count(CallSimulationEngine::employeeTurns($transcript));
        $memory = $this->memory($scenario['facts'] ?? []);
        $system = "You are {$scenario['caller_name']}, a real {$scenario['caller_role']} on a phone call to Panun Kaergar. "
            ."You are not an assistant. Never mention a test, a script, or AI.\n"
            ."Situation: {$scenario['summary']}\n"
            .$this->guide($scenario, $prompt)
            .$this->difficultyLine($difficulty)
            .$this->toneLine($tone)
            .($memory !== '' ? "Secret notes, only if they ask for that exact detail, and never word for word or one note per turn: {$memory}\n" : '')
            .$this->cue($scenario, $transcript, $difficulty, $opening)
            .$this->focus($scenario, $skillIds)
            ."Talk like a person in Kashmir on the phone. One or two short sentences, a bit uneven, with the mood of this situation. Not a notice and not a help desk.\n"
            .($opening ? "Until they speak, use {$listenName}.\n" : "Match their last language and script. English, Hindi, Urdu, Roman Urdu, and Hinglish stay as they are.\n")
            ."Answer only their last sentence. If they did not ask for a detail, do not add the next one.\n"
            ."If they say you sound like a robot, or that they cannot hear you, react to that and stop. Do not continue the story.\n"
            ."If they promise a time, a discount, a refund, or an approval they should not, hold them to it.\n"
            .'done is false until they are ending the call'
            .($turns >= (int) ($scenario['max_turns'] ?? 9) ? ', or now because the call has gone on long enough' : '')
            .".\n"
            .'JSON only: {"line":"what you say out loud","done":false,"lang":"en"}. lang is en, hi, or ur.';

        if ($opening) {
            return [$system, 'The call just connected. Greet them and say why you are calling, in your own words. Do not read a written line.'];
        }

        $history = [];
        foreach ($transcript as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $who = ($row['role'] ?? '') === 'employee' ? 'Employee' : $scenario['caller_name'];
            $history[] = $who.': '.$text;
        }
        $employees = CallSimulationEngine::employeeTurns($transcript);
        $last = $employees === [] ? '' : (string) $employees[array_key_last($employees)]['text'];

        return [
            $system,
            "Call so far:\n".implode("\n", $history)."\n\nThe employee just said: \"{$last}\"\nAnswer only that. Do not add the next detail.",
        ];
    }

    /**
     * One upcoming nudge, not the whole scenario script.
     *
     * @param  array<string, mixed>  $scenario
     * @param  list<array{role?: string, text?: string}>  $transcript
     */
    private function cue(array $scenario, array $transcript, string $difficulty, bool $opening): string
    {
        if ($opening) {
            return '';
        }

        $probeAfter = $difficulty === 'foundation' ? 4 : ($difficulty === 'advanced' ? 1 : 2);
        $curveAfter = $difficulty === 'foundation' ? 5 : ($difficulty === 'advanced' ? 2 : 3);
        $cue = '';
        $turns = count(CallSimulationEngine::employeeTurns($transcript));
        $seen = CallSimulationEngine::unionIntents($transcript);
        if ($turns >= $probeAfter) {
            foreach ($scenario['probes'] ?? [] as $probe) {
                if ($turns < (int) ($probe['after_turn'] ?? 1)) {
                    continue;
                }
                $unless = $probe['unless_intent'] ?? [];
                if (is_array($unless) && array_intersect($unless, $seen) !== []) {
                    continue;
                }
                $line = trim((string) ($probe['line'] ?? ''));
                if ($line !== '') {
                    $cue .= "If they still skip it, ask in your own words, not this sentence: {$line}\n";
                    break;
                }
            }
        }
        $curveball = trim((string) ($scenario['curveball'] ?? ''));
        if ($curveball !== '' && $turns >= $curveAfter) {
            $cue .= "Once, ask in your own words: {$curveball}\n";
        }

        return $cue;
    }

    private function difficultyLine(string $difficulty): string
    {
        return match ($difficulty) {
            'foundation' => "Difficulty is foundation. Stay patient and plain. Ask one simple question, then wait. Give a detail as soon as they ask. Do not stack pressure.\n",
            'advanced' => "Difficulty is advanced. Be sharper and less patient. If an answer is vague, ask again. Add a harder follow-up. Do not accept the first soft answer.\n",
            default => "Difficulty is standard. Sound like a normal caller for this situation: clear, and you still press the point.\n",
        };
    }

    private function toneLine(string $tone): string
    {
        return match ($tone) {
            'calm' => "Tone is calm. Speak gently. Do not snap, even if the situation is awkward.\n",
            'firm' => "Tone is firm. Be direct and short. Do not soften a point you care about.\n",
            'upset' => "Tone is upset. Sound bothered. Keep it to a person on a phone, not a speech.\n",
            default => "Tone is warm. Sound friendly, and still say what you called about.\n",
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function memory(array $rows): string
    {
        $bits = [];
        foreach ($rows as $row) {
            $line = trim((string) ($row['line'] ?? ''));
            if ($line !== '') {
                $bits[] = rtrim($line, '.');
            }
        }

        return implode('; ', $bits);
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function guide(array $scenario, string $prompt = ''): string
    {
        $guide = trim($prompt) !== '' ? trim($prompt) : trim((string) ($scenario['guide'] ?? ''));

        return $guide === '' ? '' : "On this call, follow this in character and do not read it aloud: {$guide}\n";
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @param  list<string>  $skillIds
     */
    private function focus(array $scenario, array $skillIds): string
    {
        $planned = is_array($scenario['test_skills'] ?? null) ? $scenario['test_skills'] : [];
        $ids = array_values(array_intersect($skillIds, $planned));
        if ($ids === []) {
            $ids = $planned !== [] ? $planned : $skillIds;
        }
        $names = [];
        foreach ($ids as $skillId) {
            $skill = CallSimulationCatalog::skill((string) $skillId);
            if ($skill) {
                $names[] = $skill['name'];
            }
        }

        return $names === [] ? '' : 'Draw these out without naming them: '.implode(', ', $names).".\n";
    }

    /**
     * @return array{line: string, done: bool, lang: string}|null
     */
    private function parse(string $raw): ?array
    {
        $json = trim($raw);
        $json = preg_replace('/^```(?:json)?|```$/m', '', $json) ?? $json;
        if (! preg_match('/\{.*\}/s', $json, $match)) {
            return null;
        }
        $decoded = json_decode($match[0], true);
        if (! is_array($decoded)) {
            return null;
        }
        $line = trim(preg_replace('/\s+/', ' ', (string) ($decoded['line'] ?? '')) ?? '');
        $line = trim(str_replace('[[DONE]]', '', $line));
        if ($line === '' || mb_strlen($line) > 420) {
            return null;
        }
        $lang = (string) ($decoded['lang'] ?? '');
        if (! in_array($lang, ['en', 'hi', 'ur'], true)) {
            $lang = $this->langOf($line);
        }

        return [
            'line' => $line,
            'done' => (bool) ($decoded['done'] ?? false),
            'lang' => $lang,
        ];
    }

    private function blockedReason(): ?string
    {
        $reason = Cache::get(self::BLOCK_KEY);

        return is_string($reason) ? $reason : null;
    }

    private function langOf(string $line): string
    {
        if (preg_match('/\p{Arabic}/u', $line)) {
            return 'ur';
        }
        if (preg_match('/\p{Devanagari}/u', $line)) {
            return 'hi';
        }

        return 'en';
    }
}
