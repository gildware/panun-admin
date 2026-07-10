<?php

/**
 * Update Door Installation long description + FAQs on live DB.
 *
 * LIVE_DB_PASSWORD='...' php artisan tinker scripts/update-door-installation-content-live.php
 */

use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\ServiceManagement\Entities\Faq;
use Modules\ServiceManagement\Entities\Service;

$liveConnection = 'live_service_content';
config(['database.connections.'.$liveConnection => [
    'driver' => 'mysql',
    'host' => '82.25.121.201',
    'port' => '3306',
    'database' => 'u397782854_live_pk_dec',
    'username' => 'u397782854_live_pk_usr',
    'password' => env('LIVE_DB_PASSWORD', env('DB_PASSWORD', '')),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
]]);

if ((string) config('database.connections.'.$liveConnection.'.password') === '') {
    throw new RuntimeException('Set LIVE_DB_PASSWORD (or DB_PASSWORD) for live database.');
}

$serviceId = '7ae680f7-97ed-464e-87e1-5da2aaae55c5';
$mediaBase = 'https://pub-d94f3aebce9d4036815a281f00dd51b3.r2.dev/prod/';
$coverUrl = $mediaBase.'service/door-installation/2026-07-08-6a4e58e7a1b82.webp';
$thumbUrl = $mediaBase.'service/door-installation/2026-07-08-6a4e58dc3e9c1.webp';

$list = static function (array $items, string $listClass = 'pk-list'): string {
    $html = '<ul class="'.$listClass.'">';
    foreach ($items as $item) {
        $markerClass = str_contains($listClass, 'check')
            ? 'pk-marker pk-marker--check'
            : (str_contains($listClass, 'cross') ? 'pk-marker pk-marker--cross' : 'pk-marker');
        $symbol = str_contains($listClass, 'check')
            ? '&#10003;'
            : (str_contains($listClass, 'cross') ? '&#10007;' : '&#8226;');
        $html .= '<li class="pk-item"><span class="'.$markerClass.'">'.$symbol.'</span>'.$item.'</li>';
    }

    return $html.'</ul>';
};

$includedCard = static function (string $title, string $desc = ''): string {
    $descHtml = $desc !== '' ? '<span class="pk-included-desc">'.$desc.'</span>' : '';

    return '<div class="pk-included-card">'
        .'<span class="pk-included-title">'.$title.'</span>'
        .$descHtml
        .'</div>';
};

$featureCard = static function (string $title, string $desc): string {
    return '<div class="pk-feature-card glass">'
        .'<span class="pk-feature-card-title">'.$title.'</span>'
        .'<span class="pk-feature-card-desc">'.$desc.'</span>'
        .'</div>';
};

$whyItem = static function (string $title, string $desc): string {
    return '<div class="pk-why-item">'
        .'<span class="pk-why-title">'.$title.'</span>'
        .'<span class="pk-why-desc">'.$desc.'</span>'
        .'</div>';
};

$stepCard = static function (int $no, string $title, string $text, ?string $img = null): string {
    $imgHtml = $img ? '<img class="pk-step-card-img" src="'.$img.'" alt="'.$title.'" />' : '';

    return '<div class="pk-step-card">'
        .'<span class="pk-step-card-no">'.$no.'</span>'
        .'<span class="pk-step-card-title">'.$title.'</span>'
        .'<span class="pk-step-card-text">'.$text.'</span>'
        .$imgHtml
        .'</div>';
};

$longDescription =
    '<div class="pk-desc">'

    .'<div class="pk-trust-float">'
    .'<span class="pk-trust-badge">Verified Carpenters</span>'
    .'<span class="pk-trust-badge">Secure Booking</span>'
    .'<span class="pk-trust-badge">Quality Assured</span>'
    .'</div>'

    .'<div class="pk-hero-wrap">'
    .'<img class="pk-hero" src="'.$coverUrl.'" alt="Door installation service" />'
    .'</div>'

    .'<div class="pk-lead">'
    .'<span class="pk-lead-kicker">Panun Kaergar · Carpentry Installation</span>'
    .'<p class="pk-lead-title">Professional door fitting with precise alignment and smooth operation</p>'
    .'</div>'

    .'<div class="pk-scroll-h pk-stats-scroll">'
    .'<div class="pk-stat-card glass"><span class="pk-stat-label">Starts at</span><span class="pk-stat-value">₹100</span></div>'
    .'<div class="pk-stat-card glass"><span class="pk-stat-label">Duration</span><span class="pk-stat-value">1–3 hrs</span></div>'
    .'<div class="pk-stat-card glass"><span class="pk-stat-label">Service type</span><span class="pk-stat-value">At home</span></div>'
    .'</div>'

    .'<div class="pk-features-row">'
    .$featureCard('Verified carpenters', 'Background-checked professionals for residential and commercial door fitting.')
    .$featureCard('Level-aligned fitting', 'Spirit-level accuracy for flush close, even gaps and quiet operation.')
    .$featureCard('Tested handover', 'Open/close, latch and hardware checks before the technician leaves.')
    .'</div>'

    .'<div class="pk-card glass pk-overview">'
    .'<h3 class="pk-h">Overview</h3>'
    .'<p class="pk-sub">Door Installation by <strong>Panun Kaergar</strong> connects you with verified carpenters for precise fitting of new interior and exterior wooden doors.</p>'
    .'<p>From hinge placement and frame alignment to smooth swing and secure latching, every job is completed with professional tools and quality workmanship — so your door operates quietly, closes flush, and lasts for years.</p>'
    .'<p>Book an at-home consultation or a full installation visit. Your technician will assess the opening, confirm measurements, and complete the installation as per the agreed scope.</p>'
    .'</div>'

    .'<div class="pk-scroll-h pk-gallery">'
    .'<img class="pk-gallery-img" src="'.$coverUrl.'" alt="Door installation cover" />'
    .'<img class="pk-gallery-img" src="'.$thumbUrl.'" alt="Door alignment" />'
    .'<img class="pk-gallery-img" src="'.$coverUrl.'" alt="Professional fitting" />'
    .'</div>'

    .'<div class="pk-card glass">'
    .'<h3 class="pk-h">Ideal For</h3>'
    .'<div class="pk-chips">'
    .'<span class="pk-chip">New homes</span>'
    .'<span class="pk-chip">Renovation</span>'
    .'<span class="pk-chip">Door replacement</span>'
    .'<span class="pk-chip">Bedroom doors</span>'
    .'<span class="pk-chip">Main entrance</span>'
    .'<span class="pk-chip">Office cabins</span>'
    .'<span class="pk-chip">Pre-purchased doors</span>'
    .'</div>'
    .'</div>'

    .'<div class="pk-card glass">'
    .'<h3 class="pk-h">What\'s Included</h3>'
    .'<div class="pk-included-grid">'
    .$includedCard('On-site inspection', 'Door opening, frame and hinge positions assessed before work begins.')
    .$includedCard('Door mounting &amp; alignment', 'Customer-supplied door fitted in existing or prepared frame.')
    .$includedCard('Hinge installation', 'Standard butt hinges mounted or re-fixed securely.')
    .$includedCard('Level-aligned fitting', 'Vertical and horizontal accuracy using spirit level.')
    .$includedCard('Latch &amp; lock alignment', 'Striker plate and basic lock hardware where applicable.')
    .$includedCard('Gap adjustment', 'Shimming for even margins around the door.')
    .$includedCard('Testing &amp; handover', 'Smooth open/close check and basic care guidance.')
    .$includedCard('Work-area cleanup', 'Site tidied after installation is complete.')
    .'</div>'
    .'</div>'

    .'<div class="pk-card glass">'
    .'<h3 class="pk-h">How It Works</h3>'
    .'<p class="pk-sub">Your door installation journey in six simple steps</p>'
    .'<div class="pk-scroll-h pk-steps-h">'
    .$stepCard(1, 'Book your slot', 'Choose consultation or full installation, then share your address, door size and photos of the opening.')
    .$stepCard(2, 'Carpenter assigned', 'A verified Panun Kaergar carpenter confirms your visit and arrives with professional fitting tools.')
    .$stepCard(3, 'On-site visit', 'Technician reaches your home or office on schedule and inspects the door opening and frame condition.', $thumbUrl)
    .$stepCard(4, 'Frame &amp; door check', 'Opening measurements, frame squareness and hinge positions are assessed before mounting begins.')
    .$stepCard(5, 'Precision fitting', 'Door is level-aligned, hinges secured, gaps shimmed and lock hardware adjusted for smooth operation.', $coverUrl)
    .$stepCard(6, 'Test &amp; handover', 'Open/close and latch checks completed, work area cleaned, and basic door care tips shared with you.')
    .'</div>'
    .'</div>'

    .'<div class="pk-callout pk-callout--warn glass">'
    .'<h3 class="pk-h">Things to Know</h3>'
    .$list([
        'Please ensure the <strong>door unit and compatible hinges/lock</strong> are available before the visit, unless confirmed otherwise at booking.',
        'Clear access to the work area helps the technician complete the job in one visit.',
        'Final time and cost may vary if the frame is out of square or needs extra shimming — explained <strong>before</strong> proceeding.',
        'Wooden doors may need minor seasonal adjustment in changing humidity.',
        'Share door type, size and photos of the opening when booking for best results.',
        'Notify at least 2 hours before the slot for cancellation or rescheduling where possible.',
    ])
    .'</div>'

    .'<div class="pk-card glass pk-exclusions">'
    .'<h3 class="pk-h">Exclusions</h3>'
    .$list([
        'Cost of the door, frame, lockset, or hardware (unless supplied separately)',
        'Major frame rebuilding, wall breaking, or civil/concrete work',
        'Custom door fabrication or carpentry from raw timber',
        'Repair of severely damaged, rotted, or termite-affected frames',
        'Electrical work for automatic doors or access-control systems',
        'Painting, polishing, veneer work, or post-install finishing',
        'Old door removal and disposal unless agreed as an add-on on site',
        'Glass cutting, panel replacement, or fire-rated door certification',
    ], 'pk-list pk-list--cross')
    .'</div>'

    .'<div class="pk-card glass pk-why">'
    .'<h3 class="pk-h">Why Choose Panun Kaergar</h3>'
    .$whyItem('Verified carpenters', 'Experienced in residential and light commercial door fitting.')
    .$whyItem('Precise alignment', 'Professional levels and proper installation practices.')
    .$whyItem('Transparent scope', 'Clear inclusions and exclusions before work starts.')
    .$whyItem('Secure fittings', 'Safer, quieter and longer-lasting door operation.')
    .'<p class="pk-footer">Book, track and pay with confidence through <strong>Panun Kaergar</strong>.</p>'
    .'</div>'

    .'<div class="pk-card glass">'
    .'<h3 class="pk-h">Related Services</h3>'
    .'<p class="pk-sub">More from Carpentry Installation</p>'
    .'<div class="pk-scroll-h pk-related">'
    .'<span class="pk-related-chip">Furniture Installation</span>'
    .'<span class="pk-related-chip">Kitchen Cabinet</span>'
    .'<span class="pk-related-chip">Wardrobe Installation</span>'
    .'<span class="pk-related-chip">Wooden Panel</span>'
    .'<span class="pk-related-chip">Roof Installation</span>'
    .'</div>'
    .'</div>'

    .'<div class="pk-cta-card glass">'
    .'<p class="pk-cta-title">Ready to install your door?</p>'
    .'<p class="pk-cta-sub">Book a verified carpenter for precise fitting at your home or office.</p>'
    .'</div>'

    .'</div>';

$faqs = [
    [
        'question' => 'Do I need to buy the door before booking installation?',
        'answer' => 'In most cases, yes. This service covers professional fitting of a customer-supplied door. If you need help choosing the right size or type, book an at-home consultation first and our carpenter can guide you before installation.',
    ],
    [
        'question' => 'How long does a standard door installation take?',
        'answer' => 'A typical single interior door installation takes about 1–3 hours, depending on frame condition, hinge/lock fitting, and alignment work. Multiple doors or difficult openings may take longer.',
    ],
    [
        'question' => 'Can you install both interior and exterior wooden doors?',
        'answer' => 'Yes. Our carpenters install common wooden interior doors and many standard exterior wooden doors. Share the door type and photos of the opening when booking so the right technician and scope can be assigned.',
    ],
    [
        'question' => 'Is old door removal included?',
        'answer' => 'Old door removal is not included in the standard package unless mentioned as an add-on. The technician can advise on removal and disposal on site if extra labour is required.',
    ],
    [
        'question' => 'What if my door frame is damaged or uneven?',
        'answer' => 'Minor shimming and alignment are included. If the frame is severely damaged, warped, or needs rebuilding, the technician will explain the extra work and cost before proceeding.',
    ],
    [
        'question' => 'Will the door be aligned and tested before the technician leaves?',
        'answer' => 'Yes. The carpenter checks vertical/horizontal alignment, hinge operation, latch engagement, and smooth open/close movement before handover.',
    ],
];

$service = Service::on($liveConnection)->withoutGlobalScopes()->find($serviceId);
if (! $service) {
    throw new RuntimeException("Service not found: {$serviceId}");
}

Service::on($liveConnection)
    ->withoutGlobalScopes()
    ->where('id', $serviceId)
    ->update(['description' => $longDescription]);

Translation::on($liveConnection)->updateOrCreate(
    [
        'translationable_type' => Service::class,
        'translationable_id' => $serviceId,
        'locale' => 'en',
        'key' => 'description',
    ],
    ['value' => $longDescription]
);

Faq::on($liveConnection)->where('service_id', $serviceId)->delete();

$sort = 0;
foreach ($faqs as $faqData) {
    Faq::on($liveConnection)->create([
        'question' => $faqData['question'],
        'answer' => $faqData['answer'],
        'service_id' => $serviceId,
        'is_active' => 1,
        'sort_order' => $sort++,
    ]);
}

echo "Updated Door Installation rich description and ".count($faqs)." FAQs on live.\n";
