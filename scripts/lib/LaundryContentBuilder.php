<?php

class LaundryContentBuilder
{
    public static function build(array $service): array
    {
        $slug = (string) ($service['slug'] ?? '');
        $name = (string) ($service['name'] ?? 'Laundry');

        return match ($slug) {
            'clothing-laundry' => self::service($name, 'everyday clothing and workwear laundry', [
                'Daily wear laundry', 'Wash & fold by the kg', 'Wash & iron by the kg', 'Office and college clothes',
            ], [
                'Sorting and fabric-safe wash for booked clothing load',
                'Wash & fold or wash & iron as per selected variation',
                'Fresh finish and neat packing for return',
                'Final quality check before handover',
            ], [
                'Dry cleaning for delicate or structured garments (book separately)',
                'Shoe or bag cleaning (book separately)',
                'Repair, alteration, or stain guarantee on every old mark',
            ]),
            'home-linen-laundry' => self::service($name, 'home linen and soft furnishings', [
                'Bedsheets', 'Blankets & quilts', 'Curtains', 'Towels & pillow covers',
            ], [
                'Laundry cleaning for the booked linen item and size',
                'Careful handling for home textiles within scope',
                'Fresh finish ready for reuse',
                'Final quality check before handover',
            ], [
                'Dry cleaning for specialty linen (book Home Linen Dry Cleaning)',
                'Curtain re-hanging or hardware repair',
                'Guaranteed removal of every set or aged stain',
            ]),
            'shoe-cleaning' => self::service($name, 'sneakers, leather shoes, sports shoes, and boots', [
                'Sneakers', 'Leather shoes', 'Sports shoes', 'Boots',
            ], [
                'Exterior cleaning for the booked shoe type',
                'Visible dirt, mud, and surface stain reduction',
                'Basic finishing for a fresher look',
                'Pair-wise quality check before handover',
            ], [
                'Sole replacement, stitching, or structural repair',
                'Color restoration or re-dye of faded leather',
                'Waterproofing treatments unless confirmed separately',
            ]),
            'bag-cleaning' => self::service($name, 'school bags, backpacks, handbags, and laptop bags', [
                'School bags', 'Backpacks', 'Handbags', 'Laptop bags',
            ], [
                'Exterior and accessible interior cleaning for the booked bag type',
                'Visible dirt and odor reduction within scope',
                'Zipper and strap wipe-down where practical',
                'Final quality check before handover',
            ], [
                'Leather restoration, dyeing, or hardware replacement',
                'Torn fabric or broken zipper repair',
                'Contents packing, valuables handling, or waterproof coating',
            ]),
            'garment-dry-cleaning' => self::service($name, 'shirts, suits, sarees, and occasion garments', [
                'Formal shirts & trousers', 'Suits, blazers & coats', 'Sarees & salwar suits', 'Lehenga & sherwani',
            ], [
                'Fabric and condition review before processing',
                'Dry cleaning process suited to the booked garment type',
                'Basic finishing for a ready-to-wear look',
                'Final quality check before handover',
            ], [
                'Guaranteed removal of every old or set stain',
                'Repair of tears, missing buttons, or damaged lining',
                'Major alteration or tailoring work',
            ]),
            'home-linen-dry-cleaning' => self::service($name, 'curtains, blankets, and comforters needing dry cleaning', [
                'Curtains', 'Blankets', 'Comforters & quilts', 'Delicate home textiles',
            ], [
                'Dry cleaning process for the booked linen item',
                'Careful handling for bulky or delicate textiles',
                'Fresh finish ready for home use',
                'Final quality check before handover',
            ], [
                'Curtain installation or re-hanging',
                'Filling repair or quilt restitching',
                'Guaranteed reversal of every aged stain or color fade',
            ]),
            default => self::service($name, 'laundry and fabric care', [
                'Everyday laundry', 'Fabric-safe care', 'Fresh finish', 'Home pickup-ready items', $name,
            ], [
                'Item review before processing',
                'Suitable laundry or dry-cleaning process within booked scope',
                'Fresh finish and neat handover',
                'Final quality check',
            ], [
                'Guaranteed removal of every old stain',
                'Repair, alteration, or restoration work',
                'Any item type not confirmed in the booking',
            ]),
        };
    }

    public static function variantDescription(string $serviceName, string $variantTitle): string
    {
        return "{$serviceName} — {$variantTitle}. Verified Panun Kaergar fabric-care team processes the booked item carefully and completes a neat handover.";
    }

    public static function variantNote(string $serviceSlug): string
    {
        if (in_array($serviceSlug, ['clothing-laundry'], true)) {
            return 'Price is per kg for the selected wash option. Extra kg beyond the booked estimate needs confirmation before processing.';
        }

        return 'Final price is based on the selected variation. Extra items or special treatments need confirmation before work starts.';
    }

    private static function service(
        string $name,
        string $focus,
        array $idealFor,
        array $included,
        array $excluded
    ): array {
        return [
            'short_description' => "Careful {$name} by trusted Panun Kaergar professionals for cleaner fabrics, fresher finish, and dependable fabric-safe handling.",
            'intro' => "Detailed {$name} with fabric-safe handling and a fresher ready-to-use handover for {$focus}.",
            'description' => "{$name} by Panun Kaergar is planned for customers who want dependable fabric care with clear scope. The team reviews the booked items first, follows a suitable laundry or dry-cleaning process for {$focus}, then completes finishing and a final quality check. For booking help, call or WhatsApp Panun Kaergar support from the app or website — contact options are also available in the app profile and contact sections.",
            'card_highlights' => [
                self::highlight('quality', 'Fabric Safe', 'blue', 0),
                self::highlight('sparkle', 'Fresh Finish', 'green', 1),
                self::highlight('verified', 'Trusted Team', 'purple', 2),
            ],
            'process_steps' => [
                self::step('calendar', 'Book your slot', "Share item details and any stain or fabric notes for {$name}."),
                self::step('verified', 'Team assigned', 'A Panun Kaergar professional confirms the visit and care requirements.'),
                self::step('check', 'Item review', 'Fabric type, condition, visible stains, and handling needs are reviewed before processing.', 'thumb'),
                self::step('sparkle', 'Care in progress', 'The suitable laundry or dry-cleaning process is completed carefully for the booked variation.'),
                self::step('quality', 'Final check & handover', 'Items are finished, checked, and handed over neatly.', 'cover'),
            ],
            'perfect_for' => self::chips($idealFor),
            'whats_included' => self::included($included),
            'good_to_know' => self::notes([
                'Please mention existing stains, embellishments, fabric sensitivity, or damage before processing.',
                'Some old stains, color bleeding, or wear marks may not fully reverse even with careful treatment.',
                'Turnaround can vary depending on fabric delicacy, item size, and finishing requirements.',
                'For booking help, use call, WhatsApp, website, or the Panun Kaergar app profile/contact options.',
                'Notify at least 2 hours before the slot for cancellation or rescheduling.',
            ]),
            'whats_not_included' => self::included($excluded),
            'faqs' => [
                self::faq("What is covered in {$name}?", 'The team completes the booked laundry or dry-cleaning variation after a quick item review and focuses on fabric-safe cleaning plus a neat finish.'),
                self::faq('Will every stain come out completely?', 'Not always. Results depend on fabric type, stain age, and prior treatment history, but items are handled with fabric safety in mind.'),
                self::faq('How do I contact Panun Kaergar?', 'Use call, WhatsApp, the website booking form, or the Panun Kaergar app — support details are available in the app profile and contact sections.'),
                self::faq('Is a final quality check included?', 'Yes. Items are reviewed before handover.'),
            ],
        ];
    }

    private static function highlight(string $icon, string $text, string $color, int $sortOrder): array
    {
        return [
            'icon' => $icon,
            'text' => $text,
            'color' => $color,
            'sort_order' => $sortOrder,
        ];
    }

    private static function step(string $icon, string $title, string $description, ?string $image = null): array
    {
        $step = [
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
        ];
        if ($image !== null) {
            $step['image'] = $image;
        }

        return $step;
    }

    private static function chips(array $labels): array
    {
        $items = [];
        $icons = ['home', 'building', 'sparkle', 'tools', 'quality', 'check', 'calendar', 'location'];
        foreach (array_values($labels) as $index => $label) {
            $items[] = [
                'icon' => $icons[$index % count($icons)],
                'text' => $label,
                'sort_order' => $index,
            ];
        }

        return $items;
    }

    private static function included(array $titles): array
    {
        $items = [];
        $icons = ['tools', 'check', 'quality', 'sparkle', 'verified', 'location', 'home', 'calendar'];
        foreach (array_values($titles) as $index => $title) {
            $items[] = [
                'icon' => $icons[$index % count($icons)],
                'title' => $title,
                'sort_order' => $index,
            ];
        }

        return $items;
    }

    private static function notes(array $titles): array
    {
        $items = [];
        $icons = ['check', 'home', 'tools', 'quality', 'calendar', 'sparkle', 'verified', 'location'];
        foreach (array_values($titles) as $index => $title) {
            $items[] = [
                'icon' => $icons[$index % count($icons)],
                'title' => $title,
                'sort_order' => $index,
            ];
        }

        return $items;
    }

    private static function faq(string $question, string $answer): array
    {
        return [$question, $answer];
    }
}
