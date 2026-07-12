<?php

class PetGroomingContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Pet grooming');
        $subSlug = (string) ($service['sub_category_slug'] ?? '');

        $pet = $subSlug === 'cat-grooming' ? 'cat' : 'dog';
        $focus = self::serviceFocus($slug, $pet);

        return [
            'short_description' => "Professional {$name} at your home by trained Panun Kaergar pet groomers — gentle handling, hygienic tools, and a clean, comfortable finish.",
            'intro' => self::introLine($name, $pet, $focus),
            'description' => "{$name} by Panun Kaergar is delivered at your doorstep by trained pet groomers who prioritise your {$pet}'s comfort and safety. Your groomer reviews coat condition, prepares the session with clean equipment, completes the booked service with care, and shares simple aftercare tips before handover.",
            'card_highlights' => [
                self::highlight('verified', 'Trained Groomers', 'purple', 0),
                self::highlight('shield', 'Gentle Handling', 'green', 1),
                self::highlight('sparkle', 'Hygienic Finish', 'blue', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share your {$pet}'s breed, size, and any sensitivities when booking {$name}."),
                self::step('verified', 'Groomer arrival', 'A verified groomer arrives with portable kit and reviews your pet before starting.', 'thumb'),
                self::step('tools', 'Grooming session', self::sessionLine($focus), 'cover'),
                self::step('quality', 'Comfort check', 'Your pet is dried, brushed, and checked for comfort throughout the session.'),
                self::step('sparkle', 'Aftercare tips', 'Simple coat and hygiene guidance shared before the groomer leaves.'),
            ],
            'perfect_for' => self::chips(self::idealFor($slug, $pet)),
            'whats_included' => self::included(self::includedItems($slug, $pet)),
            'good_to_know' => self::notes([
                'Keep a calm, well-lit area ready — bathroom or balcony works well.',
                'Ensure your pet has relieved itself before the session.',
                'Inform the groomer about skin issues, ticks, or recent vet treatments.',
                'Aggressive or highly anxious pets may need extra time — mention this when booking.',
                'Notify at least 2 hours before for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included([
                'Veterinary diagnosis or medical treatment',
                'Sedation or anaesthesia',
                'Grooming products supplied by customer unless agreed',
                'Pickup and drop for pets',
                'Treatment of serious skin infections or wounds',
            ]),
            'faqs' => self::faqsFor($slug, $pet),
        ];
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        return "{$serviceName} — {$variantTitle}. At-home session with trained groomers, clean tools, and gentle handling.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        return 'Pricing is for one pet per booking. Mention multiple pets when booking if you need grooming for more than one.';
    }

    private static function serviceFocus(string $slug, string $pet): string
    {
        return match (true) {
            str_contains($slug, 'full-') => 'full grooming including bath, brush, coat trim, nails, and ears',
            str_contains($slug, 'bath-and-brush') => 'bath, blow-dry, and thorough brushing',
            str_contains($slug, 'haircut') => 'coat trimming and styling',
            str_contains($slug, 'nail') => 'nail clipping and filing',
            str_contains($slug, 'ear') => 'gentle ear cleaning',
            str_contains($slug, 'teeth') => 'teeth brushing and oral hygiene',
            str_contains($slug, 'deshedding') => 'deshedding treatment for loose undercoat',
            str_contains($slug, 'flea') => 'medicated flea and tick bath',
            str_contains($slug, 'paw') => 'paw pad trimming and fur tidy-up',
            str_contains($slug, 'puppy') || str_contains($slug, 'kitten') => 'gentle first grooming introduction',
            str_contains($slug, 'senior') => 'slow, gentle grooming suited for senior pets',
            str_contains($slug, 'spa') => 'spa bath, massage, and paw balm',
            str_contains($slug, 'mat') => 'mat removal and coat detangling',
            str_contains($slug, 'lion-cut') => 'lion cut or sanitary trim',
            str_contains($slug, 'monthly') => 'scheduled monthly grooming visits',
            default => "professional {$pet} grooming",
        };
    }

    private static function introLine(string $name, string $pet, string $focus): string
    {
        return "At-home {$name} with gentle handling and a hygienic finish for your {$pet}.";
    }

    private static function sessionLine(string $focus): string
    {
        return "The groomer completes {$focus} using clean, pet-safe products and equipment.";
    }

    private static function idealFor(string $slug, string $pet): array
    {
        $base = $pet === 'cat'
            ? ['Indoor cats', 'Long-haired cats', 'Cats that avoid travel', 'Routine coat care']
            : ['All dog breeds', 'Puppies and adults', 'Dogs anxious about salons', 'Routine coat care'];

        return match (true) {
            str_contains($slug, 'puppy') || str_contains($slug, 'kitten') => ['First-time grooming', 'Young pets', 'Gentle introduction', 'New pet parents'],
            str_contains($slug, 'senior') => ['Senior pets', 'Low-stress grooming', 'Arthritis-friendly handling', 'Regular maintenance'],
            str_contains($slug, 'monthly') => ['Regular grooming routine', 'Busy pet parents', 'Seasonal shedding', 'Coat maintenance plans'],
            str_contains($slug, 'flea') => ['Flea or tick concerns', 'Outdoor pets', 'Post-walk hygiene', 'Preventive care'],
            str_contains($slug, 'mat') => ['Tangled coats', 'Long-haired cats', 'Seasonal matting', 'Before full shave'],
            default => $base,
        };
    }

    private static function includedItems(string $slug, string $pet): array
    {
        return match (true) {
            str_contains($slug, 'full-') => [
                'Coat bath with pet-safe shampoo',
                'Blow-dry and brush-out',
                'Coat trim or tidy as per breed',
                'Nail clipping',
                'Ear cleaning',
            ],
            str_contains($slug, 'bath-and-brush') => [
                'Pet-safe shampoo bath',
                'Blow-dry',
                'Thorough brushing',
                'Basic coat check',
            ],
            str_contains($slug, 'haircut') => [
                'Coat trimming and styling',
                'Scissor or clipper work as suited',
                'Brush-out before and after',
                'Basic finishing',
            ],
            str_contains($slug, 'nail') => [
                'Nail clipping',
                'Gentle filing if needed',
                'Paw check',
            ],
            str_contains($slug, 'ear') => [
                'Outer ear cleaning',
                'Gentle wipe with pet-safe solution',
                'Basic ear health check',
            ],
            str_contains($slug, 'teeth') => [
                'Teeth brushing with pet-safe paste',
                'Basic oral hygiene session',
            ],
            str_contains($slug, 'deshedding') => [
                'Deshedding shampoo bath',
                'Undercoat removal brushing',
                'Blow-dry and finish',
            ],
            str_contains($slug, 'flea') => [
                'Medicated flea and tick bath',
                'Thorough rinse',
                'Coat dry and brush',
            ],
            str_contains($slug, 'paw') => [
                'Paw pad fur trim',
                'Pad tidy-up',
                'Basic paw check',
            ],
            str_contains($slug, 'spa') => [
                'Spa shampoo bath',
                'Relaxing massage',
                'Paw balm application',
                'Blow-dry and brush',
            ],
            str_contains($slug, 'mat') => [
                'Mat assessment',
                'Careful dematting or shave as needed',
                'Brush-out finish',
            ],
            str_contains($slug, 'lion-cut') => [
                'Lion cut or sanitary trim',
                'Coat shaping as agreed',
                'Bath if included in scope',
            ],
            str_contains($slug, 'monthly') => [
                'Scheduled visits as per plan',
                'Bath and brush each visit',
                'Nail and ear check each visit',
                'Consistent groomer when possible',
            ],
            str_contains($slug, 'puppy') || str_contains($slug, 'kitten') => [
                'Gentle introduction to grooming',
                'Light bath and brush',
                'Nail and ear familiarisation',
                'Positive handling techniques',
            ],
            str_contains($slug, 'senior') => [
                'Slow, gentle grooming pace',
                'Comfort breaks as needed',
                'Bath, brush, and basic tidy',
                'Joint-aware handling',
            ],
            default => [
                'At-home grooming session',
                'Clean equipment',
                'Pet-safe products',
                'Basic aftercare guidance',
            ],
        };
    }

    private static function faqsFor(string $slug, string $pet): array
    {
        $petLabel = $pet === 'cat' ? 'cat' : 'dog';

        return [
            self::faq("Do groomers come to my home?", "Yes. All Panun Kaergar pet grooming services are delivered at your home for a calmer experience for your {$petLabel}."),
            self::faq('What should I prepare before the visit?', 'Keep a bathroom or balcony ready with water access, and ensure your pet has relieved itself. Mention any skin issues or anxieties when booking.'),
            self::faq('Are the products pet-safe?', 'Yes. Groomers use pet-safe shampoos and tools. Share any known allergies when booking.'),
            self::faq('How long does a session take?', 'Duration depends on coat type, size, and behaviour. Most standard sessions are completed within the booked slot.'),
        ];
    }

    private static function highlight(string $icon, string $text, string $color, int $sort): array
    {
        return ['icon' => $icon, 'text' => $text, 'color' => $color, 'sort_order' => $sort];
    }

    private static function step(string $icon, string $title, string $description, ?string $image = null): array
    {
        $step = ['icon' => $icon, 'title' => $title, 'description' => $description];
        if ($image) {
            $step['image'] = $image;
        }

        return $step;
    }

    private static function chips(array $items): array
    {
        $chips = [];
        foreach ($items as $i => $text) {
            $chips[] = ['icon' => 'home', 'text' => $text, 'sort_order' => $i];
        }

        return $chips;
    }

    private static function included(array $items): array
    {
        $rows = [];
        foreach ($items as $i => $title) {
            $rows[] = ['icon' => 'check', 'title' => $title, 'sort_order' => $i];
        }

        return $rows;
    }

    private static function notes(array $items): array
    {
        $rows = [];
        foreach ($items as $i => $title) {
            $rows[] = ['icon' => 'check', 'title' => $title, 'sort_order' => $i];
        }

        return $rows;
    }

    private static function faq(string $question, string $answer): array
    {
        return [$question, $answer];
    }
}
