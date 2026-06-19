<?php

namespace Database\Seeders;

use App\Support\CatalogAssetImageLoader;
use Illuminate\Database\Seeder;
use Modules\BusinessSettingsModule\Entities\Translation;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Faq;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Tag;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Seeds production-grade catalog data for Home Appliances → Air Conditioner.
 *
 * Run: php artisan catalog:seed-home-appliances-ac
 */
class HomeAppliancesAirConditionerCatalogSeeder extends Seeder
{
    private const MAIN_CATEGORY_ID = '028602bc-174a-41f9-b583-ae8f4850e646';

    private const SUB_CATEGORY_ID = '716233b9-7954-4262-a79e-8df58a6a3090';

    private CatalogAssetImageLoader $images;

    /** @var array<string, string> Published step/detail image URLs keyed by asset filename. */
    private array $publishedDetailImages = [];

    public function run(): void
    {
        $this->images = new CatalogAssetImageLoader;
        $zoneIds = Zone::query()->ofStatus(1)->orderBy('name')->pluck('id')->all();

        if ($zoneIds === []) {
            $this->command?->error('No active zones found. Create zones before seeding catalog data.');

            return;
        }

        $mainCategory = $this->seedMainCategory();
        $subCategory = $this->seedSubCategory($mainCategory);
        $this->seedServices($mainCategory, $subCategory, $zoneIds);

        $this->command?->info('Home Appliances → Air Conditioner catalog seeded with realistic images.');
    }

    private function seedMainCategory(): Category
    {
        $category = Category::withoutGlobalScopes()->findOrFail(self::MAIN_CATEGORY_ID);

        $category->name = 'Home Appliances';
        $category->slug = 'home-appliances';
        $category->description = 'Professional repair, installation, and maintenance for all major home appliances across Kashmir. Panun Kaergar connects you with verified technicians for air conditioners, refrigerators, washing machines, geysers, TVs, and more.';
        $category->is_active = 1;
        $category->is_featured = 1;
        $category->image = $this->images->publish('home-appliances-category.png', 'category/');
        $category->save();

        $this->upsertTranslation($category, 'name', 'Home Appliances');
        $this->upsertTranslation($category, 'description', $category->description);

        return $category;
    }

    private function seedSubCategory(Category $mainCategory): Category
    {
        $subCategory = Category::withoutGlobalScopes()->findOrFail(self::SUB_CATEGORY_ID);

        $subCategory->name = 'Air Conditioner';
        $subCategory->slug = 'air-conditioner';
        $subCategory->parent_id = $mainCategory->id;
        $subCategory->position = 2;
        $subCategory->description = 'Complete air conditioner services — installation, repair, gas refilling, deep cleaning, and uninstallation. Certified technicians for split, window, and inverter AC units with genuine parts and transparent pricing.';
        $subCategory->is_active = 1;
        $subCategory->image = $this->images->publish('air-conditioner-subcategory.png', 'category/');
        $subCategory->save();

        $this->upsertTranslation($subCategory, 'name', 'Air Conditioner');
        $this->upsertTranslation($subCategory, 'description', $subCategory->description);

        return $subCategory;
    }

    /**
     * @param  array<int, string>  $zoneIds
     */
    private function seedServices(Category $mainCategory, Category $subCategory, array $zoneIds): void
    {
        foreach ($this->serviceDefinitions() as $definition) {
            $service = $this->findOrCreateService($definition, $mainCategory, $subCategory);
            $this->syncVariations($service, $zoneIds, $definition['consultation_price']);
            $this->syncTags($service, $definition['tags']);
            $this->syncFaqs($service, $definition['faqs']);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serviceDefinitions(): array
    {
        return [
            [
                'slug' => 'ac-repair',
                'name' => 'AC Repair',
                'thumbnail_asset' => 'ac-repair-thumbnail.png',
                'cover_asset' => 'ac-repair-cover.png',
                'min_bidding_price' => 299,
                'consultation_price' => 149,
                'tags' => ['AC Repair', 'Cooling Problem', 'Compressor', 'Emergency'],
                'short_description' => 'Expert AC repair for poor cooling, water leakage, unusual noise, compressor faults, and electrical issues. Same-day diagnosis available across Kashmir.',
                'bullets' => [
                    'Diagnosis of cooling performance, refrigerant levels, and airflow',
                    'Repair of compressor, capacitor, PCB, fan motor, and thermostat faults',
                    'Fixing water leakage, ice formation, and drainage blockages',
                    'Electrical safety checks and wiring corrections',
                    'Genuine spare parts with service warranty',
                ],
                'footer' => 'Book a verified AC technician for fast, reliable repairs. Transparent pricing with no hidden charges. Available for split, window, cassette, and inverter AC units from all major brands including Daikin, Voltas, LG, Samsung, Blue Star, and Hitachi.',
                'steps' => [
                    ['title' => 'Step 1 — Inspection & Diagnosis', 'asset' => 'ac-repair-step-inspection.png', 'text' => 'Our technician inspects the indoor and outdoor units, checks airflow, listens for unusual sounds, and uses diagnostic tools to identify the root cause of the problem.'],
                    ['title' => 'Step 2 — Fault Repair', 'asset' => 'ac-repair-step-repair.png', 'text' => 'Faulty components such as capacitors, PCBs, fan motors, or blocked drains are repaired or replaced using genuine parts to restore proper AC function.'],
                    ['title' => 'Step 3 — Performance Testing', 'asset' => 'ac-repair-step-testing.png', 'text' => 'After repair, cooling output, thermostat response, and electrical safety are tested to ensure your AC is running efficiently before we leave.'],
                ],
                'faqs' => [
                    ['q' => 'How do I know if my AC needs repair?', 'a' => 'Signs include weak cooling, water dripping, strange noises, bad smell, or the AC not turning on. Our technician will diagnose the exact issue during the home visit.'],
                    ['q' => 'Do you repair all AC brands?', 'a' => 'Yes. We service split, window, cassette, and inverter AC units from Daikin, Voltas, LG, Samsung, Blue Star, Hitachi, and other major brands.'],
                    ['q' => 'Is same-day AC repair available?', 'a' => 'Same-day slots are available in most Kashmir zones subject to technician availability. Book through the app for the earliest visit.'],
                ],
            ],
            [
                'slug' => 'ac-installation',
                'name' => 'AC Installation',
                'thumbnail_asset' => 'ac-installation-thumbnail.png',
                'cover_asset' => 'ac-installation-cover.png',
                'min_bidding_price' => 499,
                'consultation_price' => 199,
                'tags' => ['AC Installation', 'Split AC', 'Window AC', 'Mounting'],
                'short_description' => 'Professional AC installation with proper wall mounting, copper piping, vacuuming, electrical wiring, and performance testing for optimal cooling efficiency.',
                'bullets' => [
                    'Site assessment and optimal indoor/outdoor unit placement',
                    'Secure wall bracket mounting and vibration-free installation',
                    'Copper piping, insulation, and drainage setup',
                    'Vacuuming and gas pressure testing',
                    'Electrical connection with MCB and earthing check',
                    'Full cooling performance test before handover',
                ],
                'footer' => 'Whether it is a new purchase or relocation, our technicians ensure your AC is installed correctly the first time — improving efficiency, reducing breakdowns, and protecting your warranty.',
                'steps' => [
                    ['title' => 'Step 1 — Site Assessment & Mounting', 'asset' => 'ac-installation-step-mounting.png', 'text' => 'We assess the room layout, mark the ideal height and position, and securely mount the indoor unit bracket for safe, vibration-free installation.'],
                    ['title' => 'Step 2 — Piping & Connections', 'asset' => 'ac-installation-step-piping.png', 'text' => 'Copper refrigerant pipes, drainage hose, and electrical wiring are connected between indoor and outdoor units with proper insulation and slope.'],
                    ['title' => 'Step 3 — Vacuum & Testing', 'asset' => 'ac-installation-cover.png', 'text' => 'The system is vacuumed, gas pressure is checked, and a full cooling test is run before handover so your AC performs at its best from day one.'],
                ],
                'faqs' => [
                    ['q' => 'What is included in AC installation?', 'a' => 'Standard installation includes bracket mounting, copper piping up to a defined length, drainage setup, electrical connection, vacuuming, and a cooling performance test.'],
                    ['q' => 'Can you install both split and window AC?', 'a' => 'Yes. We install split, window, and inverter AC units. The technician will confirm any additional material costs before starting work.'],
                    ['q' => 'How long does AC installation take?', 'a' => 'A standard split AC installation typically takes 2–4 hours depending on wall type, pipe length, and outdoor unit placement.'],
                ],
            ],
            [
                'slug' => 'ac-uninstallation',
                'name' => 'AC Uninstallation',
                'thumbnail_asset' => 'ac-uninstallation-thumbnail.png',
                'cover_asset' => 'ac-uninstallation-cover.png',
                'min_bidding_price' => 349,
                'consultation_price' => 149,
                'tags' => ['AC Removal', 'Relocation', 'Uninstallation'],
                'short_description' => 'Safe AC uninstallation with proper gas recovery, careful unit removal, and secure packing — ideal for relocation, renovation, or upgrading your air conditioner.',
                'bullets' => [
                    'Safe recovery and sealing of refrigerant gas',
                    'Careful disconnection of electrical and drainage lines',
                    'Damage-free removal of indoor and outdoor units',
                    'Copper pipe and accessory removal or preservation',
                    'Wall patch guidance and unit packing for transport',
                ],
                'footer' => 'Moving homes or replacing your old AC? Our technicians handle uninstallation professionally so your unit stays in good condition for reinstallation or resale.',
                'steps' => [
                    ['title' => 'Step 1 — Safe Disconnection', 'asset' => 'ac-uninstallation-step-disconnect.png', 'text' => 'Power is switched off, refrigerant lines are sealed, and electrical and drainage connections are safely disconnected before removal begins.'],
                    ['title' => 'Step 2 — Unit Removal', 'asset' => 'ac-uninstallation-cover.png', 'text' => 'Indoor and outdoor units are carefully detached from brackets and removed without damaging walls, pipes, or the AC itself.'],
                    ['title' => 'Step 3 — Packing for Relocation', 'asset' => 'ac-uninstallation-thumbnail.png', 'text' => 'Units are prepared for transport or storage. Copper pipes and accessories can be preserved if you plan to reinstall the same AC elsewhere.'],
                ],
                'faqs' => [
                    ['q' => 'Will uninstallation damage my wall?', 'a' => 'Our technicians use proper tools and techniques to minimise wall damage. Minor bracket holes may remain and can be patched during renovation.'],
                    ['q' => 'Can you uninstall AC for relocation?', 'a' => 'Yes. We safely remove and prepare the unit for transport. You can book AC Installation at your new address separately.'],
                    ['q' => 'Is gas recovered during uninstallation?', 'a' => 'Yes. Refrigerant is recovered and lines are sealed to protect the compressor and environment during removal.'],
                ],
            ],
            [
                'slug' => 'ac-servicing',
                'name' => 'AC Servicing',
                'thumbnail_asset' => 'ac-servicing-thumbnail.png',
                'cover_asset' => 'ac-servicing-cover.png',
                'min_bidding_price' => 399,
                'consultation_price' => 199,
                'tags' => ['AC Service', 'Deep Cleaning', 'Maintenance', 'Filter Cleaning'],
                'short_description' => 'Complete AC servicing including filter cleaning, coil wash, drain pipe flush, gas pressure check, and full performance inspection to keep your AC running efficiently.',
                'bullets' => [
                    'Indoor unit filter, blower, and drain tray cleaning',
                    'Outdoor condenser coil wash and fan cleaning',
                    'Drain pipe flushing to prevent water leakage',
                    'Gas pressure and cooling efficiency check',
                    'Electrical connection and thermostat inspection',
                    'Anti-rust and hygiene treatment (on request)',
                ],
                'footer' => 'Regular servicing improves cooling, lowers electricity bills, and extends AC lifespan. Recommended every 3–6 months, especially before summer season in Kashmir.',
                'steps' => [
                    ['title' => 'Step 1 — Filter & Blower Cleaning', 'asset' => 'ac-servicing-step-filter-clean.png', 'text' => 'Dust-clogged filters and blowers are removed, washed, and dried to restore healthy airflow and improve cooling efficiency.'],
                    ['title' => 'Step 2 — Coil Deep Cleaning', 'asset' => 'ac-servicing-step-coil-clean.png', 'text' => 'Evaporator and condenser coils are cleaned to remove dirt buildup that reduces cooling and increases power consumption.'],
                    ['title' => 'Step 3 — Outdoor Unit Service', 'asset' => 'ac-servicing-cover.png', 'text' => 'The outdoor unit is inspected, cleaned, and tested. Drain pipes are flushed and gas pressure is checked for optimal performance.'],
                ],
                'faqs' => [
                    ['q' => 'How often should I service my AC?', 'a' => 'We recommend servicing every 3–6 months, or before peak summer. Homes with heavy usage or dusty environments may need more frequent cleaning.'],
                    ['q' => 'What is the difference between basic and deep service?', 'a' => 'Basic service covers filter and general cleaning. Deep service includes coil wash, drain flush, outdoor unit cleaning, and a full performance check.'],
                    ['q' => 'Will servicing reduce my electricity bill?', 'a' => 'Yes. A clean AC runs more efficiently, cools faster, and uses less power compared to a unit clogged with dust and dirt.'],
                ],
            ],
            [
                'slug' => 'ac-gas-refill',
                'name' => 'AC Gas Refill',
                'thumbnail_asset' => 'ac-gas-refill-thumbnail.png',
                'cover_asset' => 'ac-gas-refill-cover.png',
                'min_bidding_price' => 599,
                'consultation_price' => 249,
                'tags' => ['Gas Refill', 'Refrigerant', 'R22', 'R32', 'R410A'],
                'short_description' => 'AC gas refilling with leak detection, vacuuming, and pressure testing. Supports R22, R32, R410A, and other refrigerants for split and window AC units.',
                'bullets' => [
                    'Leak detection using pressure test and soap solution method',
                    'Vacuuming of refrigerant lines before refill',
                    'Correct refrigerant type and quantity as per manufacturer specs',
                    'Post-refill cooling performance verification',
                    'Leak repair recommendations if gas loss is due to puncture',
                ],
                'footer' => 'Low gas is a common cause of weak cooling. Our technicians identify the root cause, refill with the correct refrigerant, and restore your AC to full cooling capacity.',
                'steps' => [
                    ['title' => 'Step 1 — Leak Detection', 'asset' => 'ac-gas-refill-step-leak-check.png', 'text' => 'Joints and copper lines are pressure-tested and checked with leak detection solution to find why gas was lost before refilling.'],
                    ['title' => 'Step 2 — Vacuum & Preparation', 'asset' => 'ac-gas-refill-step-vacuum.png', 'text' => 'The system is vacuumed to remove moisture and air from refrigerant lines, preparing the AC for a safe and effective gas refill.'],
                    ['title' => 'Step 3 — Gas Refill & Testing', 'asset' => 'ac-gas-refill-cover.png', 'text' => 'The correct refrigerant (R32, R410A, R22, etc.) is filled to manufacturer specifications and cooling performance is verified after refill.'],
                ],
                'faqs' => [
                    ['q' => 'How do I know my AC needs gas refill?', 'a' => 'Weak cooling despite clean filters, ice on pipes, or hissing sounds may indicate low gas. Our technician will confirm with pressure gauges during the visit.'],
                    ['q' => 'Which refrigerants do you support?', 'a' => 'We refill R32, R410A, R22, and other standard refrigerants as per your AC model and manufacturer requirements.'],
                    ['q' => 'Will gas refill fix cooling completely?', 'a' => 'If low gas is the only issue, yes. If there is a leak or compressor fault, the technician will explain and recommend the appropriate repair.'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function findOrCreateService(array $definition, Category $mainCategory, Category $subCategory): Service
    {
        $service = Service::withoutGlobalScopes()
            ->where('sub_category_id', $subCategory->id)
            ->where(function ($query) use ($definition) {
                $query->where('slug', $definition['slug'])
                    ->orWhere('name', $definition['name']);
            })
            ->first();

        if (! $service) {
            $service = new Service;
            $service->category_id = $mainCategory->id;
            $service->sub_category_id = $subCategory->id;
        }

        $description = $this->buildRichDescription($definition);
        $service->name = $definition['name'];
        $service->short_description = $definition['short_description'];
        $service->description = $description;
        $service->min_bidding_price = $definition['min_bidding_price'];
        $service->is_active = 1;
        $service->thumbnail = $this->images->publish($definition['thumbnail_asset'], 'service/');
        $service->cover_image = $this->images->publish($definition['cover_asset'], 'service/');
        $service->save();

        $this->upsertTranslation($service, 'name', $definition['name']);
        $this->upsertTranslation($service, 'short_description', $definition['short_description']);
        $this->upsertTranslation($service, 'description', $description);

        return $service;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function buildRichDescription(array $definition): string
    {
        $items = '';
        foreach ($definition['bullets'] as $bullet) {
            $items .= '<li>' . e($bullet) . '</li>';
        }

        $stepsHtml = '';
        foreach ($definition['steps'] as $step) {
            $imageUrl = $this->detailImageUrl($step['asset']);
            $stepsHtml .= '<div style="margin:20px 0;">'
                . '<h4 style="margin-bottom:8px;">' . e($step['title']) . '</h4>'
                . '<img src="' . e($imageUrl) . '" alt="' . e($step['title']) . '" style="width:100%;max-width:640px;border-radius:12px;margin:8px 0;" />'
                . '<p>' . e($step['text']) . '</p>'
                . '</div>';
        }

        return '<h3>' . e('Panun Kaergar ' . $definition['name'] . ' Service') . '</h3>'
            . '<p><strong>What\'s included:</strong></p>'
            . '<ul>' . $items . '</ul>'
            . '<p>' . e($definition['footer']) . '</p>'
            . '<hr />'
            . '<h3>How our technicians work</h3>'
            . $stepsHtml;
    }

    private function detailImageUrl(string $assetFilename): string
    {
        if (! isset($this->publishedDetailImages[$assetFilename])) {
            $filename = $this->images->publish($assetFilename, 'service/');
            $this->publishedDetailImages[$assetFilename] = $this->images->publicUrl('service/', $filename);
        }

        return $this->publishedDetailImages[$assetFilename];
    }

    /**
     * @param  array<int, string>  $zoneIds
     */
    private function syncVariations(Service $service, array $zoneIds, float $consultationPrice): void
    {
        $variantKey = 'Book-at-Home-Consultation';
        $variantLabel = 'Book at Home Consultation';

        $service->variation_pricing = [
            $variantKey => [
                'use_zone_pricing' => false,
                'default_price' => $consultationPrice,
            ],
        ];
        $service->save();

        Variation::query()->where('service_id', $service->id)->delete();

        $rows = [];
        $now = now();
        foreach ($zoneIds as $zoneId) {
            $rows[] = [
                'service_id' => $service->id,
                'zone_id' => $zoneId,
                'variant' => $variantLabel,
                'variant_key' => $variantKey,
                'price' => $consultationPrice,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Variation::query()->insert($rows);
    }

    /** @param  array<int, string>  $tagNames */
    private function syncTags(Service $service, array $tagNames): void
    {
        $tagIds = [];
        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate(['tag' => $tagName]);
            $tagIds[] = $tag->id;
        }

        $service->tags()->sync($tagIds);
    }

    /** @param  array<int, array{q: string, a: string}>  $faqs */
    private function syncFaqs(Service $service, array $faqs): void
    {
        Faq::query()->where('service_id', $service->id)->delete();

        foreach ($faqs as $faqData) {
            $faq = new Faq;
            $faq->question = $faqData['q'];
            $faq->answer = $faqData['a'];
            $faq->service_id = $service->id;
            $faq->is_active = 1;
            $faq->save();
        }
    }

    private function upsertTranslation(object $model, string $key, string $value): void
    {
        Translation::updateOrInsert(
            [
                'translationable_type' => get_class($model),
                'translationable_id' => $model->id,
                'locale' => 'en',
                'key' => $key,
            ],
            ['value' => $value],
        );
    }
}
