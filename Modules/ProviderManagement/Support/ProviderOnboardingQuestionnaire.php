<?php

namespace Modules\ProviderManagement\Support;

class ProviderOnboardingQuestionnaire
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'expertise_availability',
                'title' => 'Expertise & Availability',
                'questions' => [
                    [
                        'id' => 'expertise',
                        'label' => 'What is your main area of expertise, and what other related services or types of work can you also provide?',
                        'type' => 'text_pair',
                        'fields' => [
                            ['id' => 'main_expertise', 'label' => 'Main Expertise', 'max' => 255],
                            ['id' => 'other_services', 'label' => 'Other Services/Work', 'max' => 500],
                        ],
                    ],
                    [
                        'id' => 'working_area',
                        'label' => 'What is your main working area and what other areas can you cover?',
                        'type' => 'textarea',
                        'max' => 1000,
                    ],
                    [
                        'id' => 'days_available',
                        'label' => 'How many days a week are you normally available for work?',
                        'type' => 'radio',
                        'other_key' => 'days_available_other',
                        'options' => [
                            ['value' => '5_days', 'label' => '5 Days'],
                            ['value' => '6_days', 'label' => '6 days'],
                            ['value' => '7_days', 'label' => '7 Days'],
                            ['value' => 'other', 'label' => 'Other', 'other' => true],
                        ],
                    ],
                    [
                        'id' => 'max_travel',
                        'label' => 'What is the maximum total distance you are willing to travel to provide a service?',
                        'type' => 'radio',
                        'other_key' => 'max_travel_other',
                        'options' => [
                            ['value' => '1_4_kms', 'label' => '1-4 Kms'],
                            ['value' => '5_10_kms', 'label' => '5-10 Kms'],
                            ['value' => '10_15_kms', 'label' => '10-15 Kms'],
                            ['value' => 'other', 'label' => 'Other', 'other' => true],
                        ],
                    ],
                    [
                        'id' => 'working_hours',
                        'label' => 'What are your usual working hours during the day?',
                        'type' => 'time_range',
                        'from_key' => 'working_hours_from',
                        'from_period_key' => 'working_hours_from_period',
                        'to_key' => 'working_hours_to',
                        'to_period_key' => 'working_hours_to_period',
                    ],
                    [
                        'id' => 'emergency_off_day',
                        'label' => 'If you receive an emergency service request on your off day, are you willing to take the job?',
                        'type' => 'radio',
                        'options' => [
                            ['value' => 'yes', 'label' => 'Yes'],
                            ['value' => 'no', 'label' => 'No'],
                        ],
                        'followup' => [
                            'id' => 'emergency_conditions',
                            'label' => 'If Yes, under what conditions?',
                            'type' => 'textarea',
                            'show_when' => 'yes',
                            'max' => 1000,
                        ],
                    ],
                    [
                        'id' => 'years_experience',
                        'label' => 'How many Years of Experience do you have?',
                        'type' => 'radio',
                        'options' => [
                            ['value' => '0_1', 'label' => '0-1 Years'],
                            ['value' => '2_4', 'label' => '2-4 years'],
                            ['value' => '5_7', 'label' => '5-7 years'],
                            ['value' => '10_plus', 'label' => '10+ years'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'jobs_earnings',
                'title' => 'Jobs, Earnings & Expectations',
                'questions' => [
                    [
                        'id' => 'job_types',
                        'label' => 'What type of jobs do you prefer to take? (Select all that apply)',
                        'type' => 'checkbox',
                        'options' => [
                            ['value' => 'small', 'label' => 'Small Jobs — Up to half a day'],
                            ['value' => 'medium', 'label' => 'Medium Jobs — 1-2 days'],
                            ['value' => 'large', 'label' => 'Large Jobs — 3-4 days'],
                            ['value' => 'long_term', 'label' => 'Long-Term Projects — 1 week or more'],
                        ],
                    ],
                    [
                        'id' => 'travel_mode',
                        'label' => 'How do you normally travel to customers?',
                        'type' => 'radio',
                        'other_key' => 'travel_mode_other',
                        'options' => [
                            ['value' => 'by_foot', 'label' => 'By Foot'],
                            ['value' => 'bike_scooter', 'label' => 'Bike/scooter'],
                            ['value' => 'public_transport', 'label' => 'Public Transport'],
                            ['value' => 'other', 'label' => 'Other', 'other' => true],
                        ],
                    ],
                    [
                        'id' => 'current_earnings',
                        'label' => 'What is your average current earning from your work?',
                        'type' => 'money_pair',
                        'daily_key' => 'earning_daily',
                        'monthly_key' => 'earning_monthly',
                    ],
                    [
                        'id' => 'min_job_value',
                        'label' => 'What is the minimum job value you are willing to accept?',
                        'type' => 'radio',
                        'other_key' => 'min_job_value_other',
                        'options' => [
                            ['value' => '200_300', 'label' => '₹ 200-300'],
                            ['value' => '400_500', 'label' => '₹ 400-500'],
                            ['value' => 'daily_basis', 'label' => 'Daily Basis'],
                            ['value' => 'other', 'label' => 'Other', 'other' => true],
                        ],
                    ],
                    [
                        'id' => 'problems',
                        'label' => 'What problems do you currently face as a service provider?',
                        'type' => 'checkbox',
                        'other_key' => 'problems_other',
                        'options' => [
                            ['value' => 'finding_customers', 'label' => 'Difficulty finding new customers'],
                            ['value' => 'no_regular_work', 'label' => 'Not getting regular work'],
                            ['value' => 'payment_delays', 'label' => 'Payment delays'],
                            ['value' => 'other', 'label' => 'Other', 'other' => true],
                        ],
                    ],
                    [
                        'id' => 'expectations',
                        'label' => 'What do you expect from Panun Kaergar as a service provider?',
                        'type' => 'checkbox',
                        'other_key' => 'expectations_other',
                        'options' => [
                            ['value' => 'complaint_support', 'label' => 'Support in handling customer complaints/issues'],
                            ['value' => 'regular_work', 'label' => 'Regular work opportunities'],
                            ['value' => 'timely_payment', 'label' => 'Timely payment'],
                            ['value' => 'more_customers', 'label' => 'More customers'],
                            ['value' => 'other', 'label' => 'Other', 'other' => true],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function allQuestions(): array
    {
        $questions = [];
        foreach (self::sections() as $section) {
            foreach ($section['questions'] as $question) {
                $questions[] = $question;
            }
        }

        return $questions;
    }

    public static function questionCount(): int
    {
        return count(self::allQuestions());
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function sanitize(array $input): array
    {
        $out = [];

        foreach (self::allQuestions() as $question) {
            $type = $question['type'];

            if ($type === 'text' || $type === 'textarea') {
                $out[$question['id']] = self::cleanString($input[$question['id']] ?? '', (int) ($question['max'] ?? 1000));
                continue;
            }

            if ($type === 'text_pair') {
                foreach ($question['fields'] as $field) {
                    $out[$field['id']] = self::cleanString($input[$field['id']] ?? '', (int) ($field['max'] ?? 255));
                }
                continue;
            }

            if ($type === 'radio') {
                self::sanitizeChoice($out, $input, $question, false);
                continue;
            }

            if ($type === 'checkbox') {
                self::sanitizeChoice($out, $input, $question, true);
                continue;
            }

            if ($type === 'time_range') {
                $out[$question['from_key']] = self::cleanString($input[$question['from_key']] ?? '', 20);
                $out[$question['to_key']] = self::cleanString($input[$question['to_key']] ?? '', 20);
                $out[$question['from_period_key']] = self::period($input[$question['from_period_key']] ?? '');
                $out[$question['to_period_key']] = self::period($input[$question['to_period_key']] ?? '');
                continue;
            }

            if ($type === 'money_pair') {
                $out[$question['daily_key']] = self::cleanString($input[$question['daily_key']] ?? '', 40);
                $out[$question['monthly_key']] = self::cleanString($input[$question['monthly_key']] ?? '', 40);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    public static function answeredCount(array $answers): int
    {
        $count = 0;
        foreach (self::allQuestions() as $question) {
            if (self::formatAnswer($question, $answers) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $question
     * @param  array<string, mixed>  $answers
     */
    public static function formatAnswer(array $question, array $answers): ?string
    {
        $type = $question['type'];

        if ($type === 'text' || $type === 'textarea') {
            $value = trim((string) ($answers[$question['id']] ?? ''));

            return $value !== '' ? $value : null;
        }

        if ($type === 'text_pair') {
            $parts = [];
            foreach ($question['fields'] as $field) {
                $value = trim((string) ($answers[$field['id']] ?? ''));
                if ($value !== '') {
                    $parts[] = $field['label'].': '.$value;
                }
            }

            return $parts !== [] ? implode(' · ', $parts) : null;
        }

        if ($type === 'radio' || $type === 'checkbox') {
            $selected = $type === 'checkbox'
                ? (is_array($answers[$question['id']] ?? null) ? $answers[$question['id']] : [])
                : array_filter([(string) ($answers[$question['id']] ?? '')], fn ($v) => $v !== '');

            $labels = [];
            foreach ($question['options'] as $option) {
                if (! in_array($option['value'], $selected, true)) {
                    continue;
                }
                if (! empty($option['other'])) {
                    $other = trim((string) ($answers[$question['other_key'] ?? ''] ?? ''));
                    $labels[] = $other !== '' ? 'Other: '.$other : 'Other';
                    continue;
                }
                $labels[] = $option['label'];
            }

            $followup = $question['followup'] ?? null;
            if (is_array($followup) && in_array($followup['show_when'] ?? '', $selected, true)) {
                $extra = trim((string) ($answers[$followup['id']] ?? ''));
                if ($extra !== '') {
                    $labels[] = $followup['label'].' '.$extra;
                }
            }

            return $labels !== [] ? implode(', ', $labels) : null;
        }

        if ($type === 'time_range') {
            $from = trim((string) ($answers[$question['from_key']] ?? ''));
            $to = trim((string) ($answers[$question['to_key']] ?? ''));
            if ($from === '' && $to === '') {
                return null;
            }
            $fromPeriod = self::period($answers[$question['from_period_key']] ?? 'AM') ?: 'AM';
            $toPeriod = self::period($answers[$question['to_period_key']] ?? 'PM') ?: 'PM';

            return trim($from.' '.$fromPeriod).' to '.trim($to.' '.$toPeriod);
        }

        if ($type === 'money_pair') {
            $daily = trim((string) ($answers[$question['daily_key']] ?? ''));
            $monthly = trim((string) ($answers[$question['monthly_key']] ?? ''));
            $parts = [];
            if ($daily !== '') {
                $parts[] = 'Daily ₹ '.$daily;
            }
            if ($monthly !== '') {
                $parts[] = 'Monthly ₹ '.$monthly;
            }

            return $parts !== [] ? implode(' · ', $parts) : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return list<array{id: string, question: string, answer: ?string}>
     */
    public static function displayAll(array $answers): array
    {
        $rows = [];
        foreach (self::allQuestions() as $question) {
            $rows[] = [
                'id' => $question['id'],
                'question' => $question['label'],
                'answer' => self::formatAnswer($question, $answers),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $out
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $question
     */
    private static function sanitizeChoice(array &$out, array $input, array $question, bool $multiple): void
    {
        $allowed = array_column($question['options'], 'value');
        if ($multiple) {
            $raw = $input[$question['id']] ?? [];
            if (! is_array($raw)) {
                $raw = [];
            }
            $selected = array_values(array_intersect(array_map('strval', $raw), $allowed));
            $out[$question['id']] = $selected;
        } else {
            $value = (string) ($input[$question['id']] ?? '');
            $out[$question['id']] = in_array($value, $allowed, true) ? $value : '';
            $selected = $out[$question['id']] !== '' ? [$out[$question['id']]] : [];
        }

        if (! empty($question['other_key'])) {
            $out[$question['other_key']] = in_array('other', $selected, true)
                ? self::cleanString($input[$question['other_key']] ?? '', 255)
                : '';
        }

        $followup = $question['followup'] ?? null;
        if (is_array($followup)) {
            $out[$followup['id']] = in_array($followup['show_when'] ?? '', $selected, true)
                ? self::cleanString($input[$followup['id']] ?? '', (int) ($followup['max'] ?? 1000))
                : '';
        }
    }

    private static function cleanString(mixed $value, int $max): string
    {
        $text = trim(strip_tags((string) $value));
        if ($text === '') {
            return '';
        }

        return mb_substr($text, 0, $max);
    }

    private static function period(mixed $value): string
    {
        $period = strtoupper(trim((string) $value));

        return in_array($period, ['AM', 'PM'], true) ? $period : '';
    }
}
