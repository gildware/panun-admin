<?php

namespace Modules\AdminModule\Support;

/**
 * Turns the lead qualification training into lessons. Questions are written
 * as an exam in LeadQualificationExam — a situation, then one correct answer.
 */
class LeadQualificationCourseBuilder
{
    public const NAME = 'Lead management';

    /** @var array<int, array{title: string, lessons: array<int, array<string, mixed>>}> */
    private array $sections = [];

    /** @var array<int, array<string, mixed>> */
    private array $lessons = [];

    /** @var array<string, true> */
    private array $renderedFlows = [];

    private string $sectionTitle = '';

    private string $lead = '';

    private bool $leadPlaced = false;

    /**
     * @return array<int, array{title: string, lessons: array<int, array<string, mixed>>}>
     */
    public function build(): array
    {
        $this->sections = [];
        $this->lessons = [];
        $this->renderedFlows = [];

        foreach (LeadQualificationTrainingGuide::slides() as $slide) {
            $this->begin((string) $slide['title'], trim(((string) ($slide['subtitle'] ?? ''))."\n".((string) ($slide['overview'] ?? ''))));
            $this->renderSlide($slide);
            $this->end();
        }

        return $this->sections;
    }

    /**
     * @param  array<string, mixed>  $slide
     */
    private function renderSlide(array $slide): void
    {
        if (($slide['type'] ?? '') === 'quiz') {
            $this->unit('Before the scenarios', '<p>These scenarios use every rule from the lessons above. Every answer must be correct. Booking follow-up is a separate course.</p>');
            $this->renderQuiz($slide['questions'] ?? []);

            return;
        }

        if (! empty($slide['shift_checklist']) && is_array($slide['shift_checklist'])) {
            $html = '<div class="lq-block"><div class="lq-kicker">'.$this->icon('checklist').'<span>Shift checklist</span></div><ol>';
            foreach ($slide['shift_checklist'] as $item) {
                $html .= '<li>'.$this->h($item).'</li>';
                $this->ask(false, 'Shift checklist', (string) $item);
            }
            $html .= '</ol></div>';
            $this->unit('Shift checklist', $html);
        }

        if (! empty($slide['important'])) {
            $this->ask(false, $this->sectionTitle, (string) $slide['important']);
            $this->unit('How this checklist is used', '<p>'.$this->h($slide['important']).'</p>');
        }

        if (! empty($slide['terms']) && is_array($slide['terms'])) {
            $html = '<div class="lq-block"><div class="lq-kicker">'.$this->icon('menu_book').'<span>'.$this->h($slide['terms_title'] ?? 'Terms').'</span></div>';
            $html .= '<p>Read each term. The questions describe a situation and ask which term, or which action, is correct.</p>';
            foreach ($slide['terms'] as $term) {
                $html .= '<h3>'.$this->h($term['term'] ?? '').'</h3><p>'.$this->h($term['definition'] ?? '').'</p>';
            }
            $html .= '</div>';
            $this->askTerms($slide['terms']);
            $this->unit('Terms we use', $html);
        }

        foreach ($slide['point_cards'] ?? [] as $card) {
            $this->unit((string) $card['title'], $this->missionCard($card));
        }

        $columns = $slide['source_guide_cols'] ?? [
            'where' => 'Where',
            'dont_miss' => 'Common mistakes',
            'manage' => 'What to do',
        ];
        foreach ($slide['source_guides'] ?? [] as $guide) {
            $this->unit((string) $guide['title'], $this->sourceGuide($guide, $columns));
        }

        if (! empty($slide['cards'])) {
            $html = '';
            foreach ($slide['cards'] as $card) {
                $html .= $this->infoCard($card);
            }
            $this->unit($this->sectionTitle.' — the types', $html);
        }

        foreach ($slide['card_groups'] ?? [] as $group) {
            $html = '<p>'.$this->h($group['hint'] ?? '').'</p>';
            $this->ask(false, (string) ($group['title'] ?? ''), (string) ($group['hint'] ?? ''));
            foreach ($group['cards'] ?? [] as $card) {
                $html .= $this->infoCard($card);
            }
            $this->unit((string) ($group['title'] ?? 'Cards'), $html);
        }

        if (! empty($slide['qualifier'])) {
            $this->unit((string) ($slide['qualifier']['title'] ?? 'Qualifier'), $this->qualifier($slide['qualifier']));
        }

        foreach ($slide['ui_maps'] ?? [] as $map) {
            $this->unit((string) $map['title'], $this->uiMap($map));
        }

        foreach ($slide['path_steps'] ?? [] as $path) {
            $this->unit((string) ($path['label'] ?? 'Steps'), $this->pathSteps($path));
        }

        if (! empty($slide['scenarios'])) {
            $html = '';
            foreach ($slide['scenarios'] as $scenario) {
                $html .= $this->scenario($scenario);
            }
            $this->unit($this->sectionTitle.' — examples', $html);
        }

        foreach ($slide['flowcharts'] ?? [] as $chart) {
            $this->renderFlow((string) $chart['key'], (string) ($chart['title'] ?? $chart['key']));
        }

        if (! empty($slide['message'])) {
            $this->unit((string) ($slide['message']['label'] ?? 'Message'), $this->message($slide['message']));
        }
        foreach ($slide['messages'] ?? [] as $message) {
            $this->unit((string) ($message['label'] ?? 'Message'), $this->message($message));
        }

        if (! empty($slide['panel_links'])) {
            $html = '<div class="lq-block"><div class="lq-kicker">'.$this->icon('open_in_new').'<span>Open in the panel</span></div><ul>';
            foreach ($slide['panel_links'] as $link) {
                $html .= '<li><a href="'.$this->h($link['url'] ?? '#').'">'.$this->h($link['label'] ?? '').'</a></li>';
                $this->ask(false, 'Panel page', (string) ($link['label'] ?? ''));
            }
            $html .= '</ul></div>';
            $this->unit($this->sectionTitle.' — panel pages', $html);
        }

        if (! empty($slide['remember']) || ! empty($slide['avoid'])) {
            $html = '<div class="lq-pair">';
            $html .= '<div class="lq-do"><div class="lq-kicker">'.$this->icon('check_circle').'<span>Remember</span></div><ul>';
            foreach ($slide['remember'] ?? [] as $line) {
                $html .= '<li>'.$this->h($line).'</li>';
                $this->ask(false, $this->sectionTitle.' — remember', (string) $line);
            }
            $html .= '</ul></div><div class="lq-dont"><div class="lq-kicker">'.$this->icon('block').'<span>Do not</span></div><ul>';
            foreach ($slide['avoid'] ?? [] as $line) {
                $html .= '<li>'.$this->h($line).'</li>';
                $this->ask(true, $this->sectionTitle.' — do not', (string) $line);
            }
            $html .= '</ul></div></div>';
            $this->unit($this->sectionTitle.' — remember and avoid', $html);
        }

        $this->renderLooseFlows($slide['id'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $card
     */
    private function missionCard(array $card): string
    {
        $html = '<div class="lq-block">';
        $html .= '<div class="lq-kicker">'.$this->icon((string) ($card['icon'] ?? 'flag')).'<span>'.$this->h($card['title'] ?? '').'</span></div>';
        $html .= '<p><strong>'.$this->h($card['description'] ?? '').'</strong></p>';
        $html .= '<p>'.$this->h($card['detail'] ?? '').'</p>';
        $this->ask(false, (string) ($card['title'] ?? ''), (string) ($card['description'] ?? ''));
        $this->ask(false, (string) ($card['title'] ?? ''), (string) ($card['detail'] ?? ''));
        $html .= $this->figure((string) ($card['image'] ?? ''), (string) ($card['title'] ?? ''));
        if (! empty($card['detail_points'])) {
            $html .= '<div class="lq-label">Do this</div><ol>';
            foreach ($card['detail_points'] as $point) {
                $html .= '<li>'.$this->h($point).'</li>';
                $this->ask(false, (string) ($card['title'] ?? ''), (string) $point);
            }
            $html .= '</ol>';
        }
        foreach ($card['examples'] ?? [] as $example) {
            $good = ($example['type'] ?? '') === 'good';
            $html .= '<div class="lq-block">';
            $html .= '<span class="lq-pill '.($good ? 'is-good' : 'is-bad').'">'.($good ? 'Do this' : 'Do not do this').'</span>';
            $html .= '<strong>'.$this->h($example['label'] ?? '').'</strong>';
            $html .= '<p>'.$this->h($example['text'] ?? '').'</p>';
            $html .= $this->figure((string) ($example['image'] ?? ''), (string) ($example['label'] ?? ''));
            $html .= '</div>';
            $this->ask(! $good, (string) (($card['title'] ?? '').' — '.($example['label'] ?? '')), (string) ($example['text'] ?? ''));
        }
        $html .= '<div class="lq-pair"><div class="lq-do"><div class="lq-label">Remember</div><ul>';
        foreach ($card['best_practices'] ?? [] as $line) {
            $html .= '<li>'.$this->h($line).'</li>';
            $this->ask(false, (string) ($card['title'] ?? '').' — remember', (string) $line);
        }
        $html .= '</ul></div><div class="lq-dont"><div class="lq-label">Do not</div><ul>';
        foreach ($card['avoid'] ?? [] as $line) {
            $html .= '<li>'.$this->h($line).'</li>';
            $this->ask(true, (string) ($card['title'] ?? '').' — do not', (string) $line);
        }
        $html .= '</ul></div></div></div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $guide
     * @param  array<string, string>  $columns
     */
    private function sourceGuide(array $guide, array $columns): string
    {
        $html = '<div class="lq-block">';
        $html .= '<div class="lq-kicker">'.$this->icon((string) ($guide['icon'] ?? 'article')).'<span>'.$this->h($guide['title'] ?? '').'</span></div>';
        if (! empty($guide['badge'])) {
            $html .= '<p><span class="lq-pill is-good">'.$this->h($guide['badge']).'</span></p>';
        }
        $html .= '<p>'.$this->h($guide['summary'] ?? '').'</p>';
        $this->ask(false, (string) ($guide['title'] ?? ''), (string) ($guide['summary'] ?? ''));
        $html .= $this->bulletBlock((string) ($columns['where'] ?? 'Where'), $guide['where'] ?? [], false, (string) ($guide['title'] ?? ''));
        $html .= $this->bulletBlock((string) ($columns['dont_miss'] ?? 'Common mistakes'), $guide['dont_miss'] ?? [], true, (string) ($guide['title'] ?? ''));
        $html .= $this->bulletBlock((string) ($columns['manage'] ?? 'What to do'), $guide['manage'] ?? [], false, (string) ($guide['title'] ?? ''));
        if (! empty($guide['links'])) {
            $html .= '<div class="lq-label">Open in the panel</div><ul>';
            foreach ($guide['links'] as $link) {
                $html .= '<li><a href="'.$this->h($link['url'] ?? '#').'">'.$this->h($link['label'] ?? '').'</a></li>';
                $this->ask(false, (string) ($guide['title'] ?? '').' — panel', (string) ($link['label'] ?? ''));
            }
            $html .= '</ul>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function bulletBlock(string $label, array $lines, bool $mistake, string $context): string
    {
        if ($lines === []) {
            return '';
        }
        $html = '<div class="lq-label">'.$this->h($label).'</div><ul>';
        foreach ($lines as $line) {
            $html .= '<li>'.$this->h($line).'</li>';
            $this->ask($mistake, $context.' — '.$label, (string) $line);
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $card
     */
    private function infoCard(array $card): string
    {
        $html = '<div class="lq-block">';
        $html .= '<div class="lq-kicker">'.$this->icon((string) ($card['icon'] ?? 'label')).'<span>'.$this->h($card['title'] ?? '').'</span></div>';
        $html .= '<p>'.$this->h($card['text'] ?? '').'</p>';
        $this->ask(false, (string) ($card['title'] ?? ''), (string) ($card['text'] ?? ''));
        if (! empty($card['points'])) {
            $html .= '<ul>';
            foreach ($card['points'] as $point) {
                $html .= '<li>'.$this->h($point).'</li>';
                $this->ask(false, (string) ($card['title'] ?? ''), (string) $point);
            }
            $html .= '</ul>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $qualifier
     */
    private function qualifier(array $qualifier): string
    {
        $html = '<div class="lq-block"><div class="lq-kicker">'.$this->icon('quiz').'<span>'.$this->h($qualifier['title'] ?? '').'</span></div>';
        $this->ask(false, $this->sectionTitle, (string) ($qualifier['title'] ?? ''));
        foreach ($qualifier['items'] ?? [] as $item) {
            $html .= '<div class="lq-row">'.$this->icon('help').'<div>';
            $html .= '<strong>'.$this->h($item['question'] ?? '').'</strong>';
            $html .= '<div><span class="lq-pill is-good">'.$this->h($item['type'] ?? '').'</span></div>';
            $html .= '<p>'.$this->h($item['note'] ?? '').'</p></div></div>';
            $line = trim((string) ($item['type'] ?? '').' — '.(string) ($item['note'] ?? ''));
            $this->ask(false, (string) ($item['question'] ?? $qualifier['title'] ?? ''), $line);
            $this->ask(false, (string) ($qualifier['title'] ?? ''), (string) ($item['question'] ?? ''));
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private function uiMap(array $map): string
    {
        $html = '<div class="lq-block"><div class="lq-kicker">'.$this->icon('map').'<span>'.$this->h($map['title'] ?? '').'</span></div>';
        $html .= '<p>'.$this->h($map['summary'] ?? '').'</p>';
        $this->ask(false, (string) ($map['title'] ?? ''), (string) ($map['summary'] ?? ''));
        $html .= '<ol>';
        foreach ($map['steps'] ?? [] as $step) {
            $html .= '<li><strong>'.$this->h($step['label'] ?? '').'</strong> — '.$this->h($step['text'] ?? '').'</li>';
            $this->ask(false, (string) ($map['title'] ?? '').' — '.($step['label'] ?? ''), (string) ($step['text'] ?? ''));
        }
        $html .= '</ol></div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $path
     */
    private function pathSteps(array $path): string
    {
        $html = '';
        $index = 1;
        foreach ($path['steps'] ?? [] as $step) {
            $html .= '<div class="lq-block"><div class="lq-kicker">'.$this->icon('format_list_numbered').'<span>'.$index.'. '.$this->h($step['text'] ?? '').'</span></div>';
            $this->ask(false, (string) ($path['label'] ?? 'Steps'), (string) ($step['text'] ?? ''));
            foreach (['detail' => 'What this step requires', 'collect' => 'What you record', 'example' => 'Example', 'next' => 'Immediately after'] as $key => $label) {
                if (! empty($step[$key])) {
                    $html .= '<div class="lq-label">'.$this->h($label).'</div><p>'.$this->h($step[$key]).'</p>';
                    $this->ask(false, (string) ($step['text'] ?? $path['label'] ?? '').' — '.$label, (string) $step[$key]);
                }
            }
            $html .= '</div>';
            $index++;
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function scenario(array $scenario): string
    {
        $html = '<div class="lq-block">';
        $html .= '<div class="lq-kicker">'.$this->icon('theater_comedy').'<span>'.$this->h($scenario['title'] ?? '').'</span></div>';
        foreach (['trigger' => 'When this happens', 'action' => 'What you do', 'panel' => 'What the panel shows'] as $key => $label) {
            if (! empty($scenario[$key])) {
                $html .= '<div class="lq-label">'.$this->h($label).'</div><p>'.$this->h($scenario[$key]).'</p>';
                $this->ask(false, (string) ($scenario['title'] ?? '').' — '.$label, (string) $scenario[$key]);
            }
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function message(array $message): string
    {
        $html = '<div class="lq-block lq-wa">';
        $html .= '<div class="lq-kicker">'.$this->icon('chat').'<span>'.$this->h($message['label'] ?? 'WhatsApp').'</span></div>';
        if (! empty($message['mandatory'])) {
            $html .= '<p><span class="lq-pill is-good">Send this</span></p>';
        }
        $html .= '<div class="lq-label">Template</div><pre>'.$this->h($message['template'] ?? '').'</pre>';
        $this->ask(false, (string) ($message['label'] ?? 'Message').' — template', (string) ($message['template'] ?? ''));
        if (! empty($message['example']) && trim((string) $message['example']) !== trim((string) ($message['template'] ?? ''))) {
            $html .= '<div class="lq-label">Filled example</div><pre>'.$this->h($message['example']).'</pre>';
            $this->ask(false, (string) ($message['label'] ?? 'Message').' — example', (string) $message['example']);
        }
        $html .= '</div>';

        return $html;
    }

    private function renderFlow(string $key, string $title): void
    {
        if (isset($this->renderedFlows[$key])) {
            return;
        }
        $this->renderedFlows[$key] = true;
        $html = '<div class="lq-block"><div class="lq-kicker">'.$this->icon('account_tree').'<span>'.$this->h($title).'</span></div><ol>';
        foreach (LeadQualificationTrainingFlowcharts::richSteps($key) as $step) {
            $html .= '<li><strong>'.$this->h($step['text'] ?? '').'</strong>';
            $this->ask(false, $title, (string) ($step['text'] ?? ''));
            if (! empty($step['detail'])) {
                $html .= '<div>'.$this->h($step['detail']).'</div>';
                $this->ask(false, $title.' — '.($step['text'] ?? ''), (string) $step['detail']);
            }
            $html .= '</li>';
        }
        $html .= '</ol>';
        $nodes = LeadQualificationTrainingFlowcharts::get($key) ?? [];
        $extra = '';
        foreach ($nodes as $node) {
            if (($node['kind'] ?? '') === 'fork') {
                foreach ($node['branches'] ?? [] as $branch) {
                    $line = trim((string) ($branch['label'] ?? '').' → '.(string) ($branch['to'] ?? ''));
                    $extra .= '<li>'.$this->h($line).'</li>';
                    $this->ask(false, $title.' — branch', $line);
                    $this->ask(false, $title.' — branch', (string) ($branch['label'] ?? ''));
                    $this->ask(false, $title.' — branch', (string) ($branch['to'] ?? ''));
                }
            } else {
                $this->ask(false, $title, (string) ($node['label'] ?? ''));
            }
        }
        if ($extra !== '') {
            $html .= '<div class="lq-label">Branches</div><ul>'.$extra.'</ul>';
        }
        $html .= '</div>';
        $this->unit($title, $html);
    }

    private function renderLooseFlows(string $slideId): void
    {
        $map = [
            'lead-classifications' => ['master-journey' => 'Whole lead journey', 'classify' => 'Classify the enquiry'],
            'shift-routine' => ['followup-daily' => 'Daily follow-up queue'],
        ];
        foreach ($map[$slideId] ?? [] as $key => $title) {
            $this->renderFlow($key, $title);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function renderQuiz(array $questions): void
    {
        $ready = [];
        foreach ($questions as $question) {
            $options = array_values($question['options'] ?? []);
            if (count($options) < 2 || ! isset($options[$question['correct'] ?? -1])) {
                continue;
            }
            $ready[] = [
                'prompt' => $this->clip((string) $question['question'], 1000),
                'options' => array_map(fn ($option) => $this->clip((string) $option, 500), $options),
                'correct' => (int) $question['correct'],
                'explanation' => $this->clip((string) ($question['explanation'] ?? $question['explain'] ?? ''), 500),
            ];
        }
        $chunks = array_chunk($ready, 12);
        foreach ($chunks as $index => $chunk) {
            $title = $index === 0 ? 'Certification scenarios' : 'Certification scenarios — part '.($index + 1);
            $html = '<p class="lq-lead">Answer from the lead process you just learned. Every question in this part must be correct. Booking follow-up is a separate course.</p>';
            $this->lessons[] = [
                'title' => $title,
                'body' => $html,
                'questions' => $chunk,
            ];
        }
    }

    private function begin(string $title, string $lead): void
    {
        $this->sectionTitle = $this->clip($title, 160);
        $this->lessons = [];
        $this->lead = trim($lead);
        $this->leadPlaced = $this->lead === '';
    }

    private function end(): void
    {
        if (! $this->leadPlaced && $this->lead !== '') {
            $this->unit($this->sectionTitle, '');
        }
        if ($this->lessons !== []) {
            $this->sections[] = [
                'title' => $this->sectionTitle,
                'lessons' => $this->lessons,
            ];
        }
    }

    private function unit(string $title, string $html): void
    {
        if (! $this->leadPlaced && $this->lead !== '') {
            $html = '<p class="lq-lead">'.$this->h($this->lead).'</p>'.$html;
            $this->leadPlaced = true;
        }
        $questions = $this->examQuestions($title);
        if (trim(strip_tags($html)) === '' && $questions === []) {
            return;
        }
        if ($questions === []) {
            $this->lessons[] = [
                'title' => $this->clip($title, 160),
                'body' => $html !== '' ? $html : '<p>'.$this->h($title).'</p>',
                'questions' => [],
            ];

            return;
        }
        $chunks = array_chunk($questions, 12);
        foreach ($chunks as $index => $chunk) {
            $lessonTitle = $title;
            if ($index > 0) {
                $lessonTitle .= ' — questions '.($index + 1);
            }
            $body = $index === 0 ? $html : $this->recap($title, $chunk);
            $this->lessons[] = [
                'title' => $this->clip($lessonTitle, 160),
                'body' => $body !== '' ? $body : '<p>'.$this->h($lessonTitle).'</p>',
                'questions' => $chunk,
            ];
        }
    }

    /**
     * @return array<int, array{prompt: string, options: array<int, string>, correct: int, explanation: string}>
     */
    private function examQuestions(string $title): array
    {
        $ready = [];
        foreach (LeadQualificationExam::forLesson($this->sectionTitle, $title) as $question) {
            $options = array_values($question['options'] ?? []);
            $correct = (int) ($question['correct'] ?? -1);
            if (count($options) < 2 || count($options) > 4 || ! isset($options[$correct])) {
                continue;
            }
            $ready[] = [
                'prompt' => $this->clip((string) ($question['prompt'] ?? ''), 1000),
                'options' => array_map(fn ($option) => $this->clip((string) $option, 500), $options),
                'correct' => $correct,
                'explanation' => $this->clip((string) ($question['explanation'] ?? ''), 500),
            ];
        }

        return $ready;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function recap(string $title, array $questions): string
    {
        $html = '<p class="lq-lead">These questions continue “'.$this->h($title).'”. Read each rule, then answer.</p><ul>';
        foreach ($questions as $question) {
            $html .= '<li>'.$this->h($question['options'][$question['correct']] ?? '').'</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function ask(bool $mistake, string $context, string $line): void
    {
    }

    /**
     * @param  array<int, array<string, mixed>>  $terms
     */
    private function askTerms(array $terms): void
    {
    }

    private function figure(string $file, string $caption): string
    {
        $file = basename($file);
        if ($file === '' || ! str_ends_with($file, '.png')) {
            return '';
        }
        if (! is_file(public_path('assets/admin-module/process-guide/training/examples/'.$file))) {
            return '';
        }
        $src = asset('assets/admin-module/process-guide/training/examples/'.$file);

        return '<figure class="lq-figure"><img src="'.$this->h($src).'" alt="'.$this->h($caption).'"><figcaption>'.$this->h($caption).'</figcaption></figure>';
    }

    private function icon(string $name): string
    {
        $name = preg_replace('/[^a-z0-9_]/', '', $name) ?: 'article';

        return '<span class="material-icons">'.$name.'</span>';
    }

    private function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function clean(string $value): string
    {
        $value = html_entity_decode(strip_tags($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function clip(string $value, int $limit): string
    {
        $value = $this->clean($value);
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit - 1)).'…';
    }
}
