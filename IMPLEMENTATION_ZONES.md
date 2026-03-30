# Zone hierarchy, multi-zone providers & pricing — implementation checklist

This document tracks work to support:

- **Zone tree**: parent → child → grandchild (`zones.parent_id`).
- **Leaf-only bookings & addresses**: `bookings.zone_id` and resolved address zones are **leaf** zones only; consistent point → zone resolution across the app.
- **Provider coverage**: cascade + exclusions in UI, stored as **allowed leaf zone IDs** via `provider_zone` pivot (replacing reliance on a single `providers.zone_id`).
- **Service pricing**: **default** variation price + **optional per-zone overrides** (schema/strategy TBD below).

Use this as a ticket backlog; check items off as you complete them.

---

## Product rules (locked)

| Decision | Choice |
|----------|--------|
| Provider zone UI | Cascade with exclusions → normalize to **leaf** zone IDs for storage/queries. |
| Booking / reporting | **Leaf-only** `zone_id` on bookings (and customer addresses when geocoded). |
| Nested polygons | Single resolver: **deepest leaf** (or equivalent) among zones containing the point — not `latest()->first()`. |

---

## Phase 0 — Schema & core models

### Migrations

- [ ] Add `parent_id` (nullable UUID FK → `zones.id`) + index on `zones`.
- [ ] Optional: `sort_order`, `is_leaf` (boolean, maintained when children added/removed) — or derive leaves only in code (`whereDoesntHave('children')`).
- [ ] Create `provider_zone` table: `provider_id`, `zone_id`, unique (`provider_id`, `zone_id`), FKs, timestamps.
- [ ] Variations / default pricing: either **nullable `variations.zone_id`** (NULL = default row) with resolution **specific zone → default**, OR default columns on `services` + override rows only — pick one and migrate.
- [ ] Backfill: copy `providers.zone_id` → `provider_zone`; plan deprecation for `providers.zone_id` (keep as legacy “primary” or drop after cutover).

### Models

- [ ] `Modules/ZoneManagement/Entities/Zone.php` — `parent()`, `children()`, optional ancestors/descendants helpers; `scopeLeaves()` or equivalent.
- [ ] `Modules/ProviderManagement/Entities/Provider.php` — `zones(): BelongsToMany` via `provider_zone`; align `zone()` / `zone_id` during transition.
- [ ] `Modules/ServiceManagement/Entities/Variation.php` — nullable `zone_id` if using default rows; fix `booted()` global scope for **multi-zone** providers (do not rely on single `auth()->user()->provider->zone_id` for provider API).

### New shared service

- [ ] Add e.g. `ZoneResolutionService::resolveLeafZoneForPoint(Point): ?Zone` (or under `Modules/ZoneManagement/Services/`) — **one** implementation used everywhere coordinates map to a zone.

---

## Phase 1 — Leaf zone from coordinates (critical)

Replace `Zone::whereContains(...)->latest()->first()` with the shared resolver:

- [ ] `Modules/CustomerModule/Traits/CustomerAddressTrait.php`
- [ ] `Modules/CustomerModule/Http/Controllers/Api/V1/Customer/AddressController.php`
- [ ] `Modules/CustomerModule/Http/Controllers/Api/V1/Customer/ConfigController.php`

---

## Phase 2 — Zone admin UI & API

- [ ] `Modules/ZoneManagement/Http/Controllers/Web/Admin/ZoneController.php` — store/update `parent_id`; validate hierarchy; optional geometry rules (child inside parent).
- [ ] `Modules/ZoneManagement/Resources/views/admin/create.blade.php` — parent selector.
- [ ] `Modules/ZoneManagement/Resources/views/admin/edit.blade.php` — parent selector.
- [ ] `Modules/ZoneManagement/Resources/views/admin/partials/_table.blade.php` — show hierarchy (indent / breadcrumb).
- [ ] `Modules/ZoneManagement/Http/Controllers/Api/V1/Admin/ZoneController.php` — expose `parent_id`, children if mobile/admin API needs it.

---

## Phase 3 — Provider coverage (pivot + UI + registration)

- [ ] `Modules/ProviderManagement/Http/Controllers/Web/Admin/ProviderController.php` — save/load `provider_zone`; cascade UI → leaf IDs; `subCategoriesForZoneQuery` / settings that use `$provider->zone_id`.
- [ ] `Modules/ProviderManagement/Http/Controllers/Api/V1/Admin/ProviderController.php` — stop using only `zone_ids[0]`; persist pivot; `owner->zones()->sync(...)`.
- [ ] `Modules/ProviderManagement/Http/Controllers/Api/V1/Provider/ProviderController.php` — replace `where('zone_id', $request->user()->provider->zone_id)`; profile `zone_ids` handling.
- [ ] `Modules/ProviderManagement/Http/Controllers/Web/Provider/ProviderController.php` — notifications, dashboard filters using `zone_id`.
- [ ] `Auth/Http/Controllers/RegisterController.php` — web registration zones.
- [ ] `Auth/Http/Controllers/Api/V1/RegisterController.php` — API registration zones.
- [ ] `Modules/BusinessSettingsModule/Http/Controllers/Web/Provider/BusinessInformationController.php` — business info zone updates.
- [ ] `Modules/ProviderManagement/Resources/views/admin/provider/partials/provider-add-edit-form.blade.php`
- [ ] `Modules/ProviderManagement/Resources/views/admin/provider/edit.blade.php`
- [ ] `Modules/ProviderManagement/Resources/views/admin/provider/create.blade.php`
- [ ] `Modules/ProviderManagement/Http/Requests/ProviderStoreRequest.php`
- [ ] `Modules/ProviderManagement/Resources/views/admin/provider/onboarding.blade.php`

---

## Phase 4 — Customer API & middleware

- [ ] `Modules/ProviderManagement/Http/Controllers/Api/V1/Customer/ProviderController.php` — `where('zone_id', Config::get('zone_id'))` → provider covers leaf zone via pivot; variation helpers.
- [ ] `Modules/ProviderManagement/Http/Controllers/Api/V1/Customer/FavoriteProviderController.php`
- [ ] `app/Http/Middleware/ZoneAdder.php` — document / enforce: `zoneid` header = **leaf** zone (optional validation if `is_leaf` exists).

---

## Phase 5 — Bookings & reassignment

- [ ] `Modules/BookingModule/Http/Traits/BookingTrait.php` — ensure booking `zone_id` is always leaf where applicable.
- [ ] `Modules/BookingModule/Http/Traits/BookingScopes.php` — replace `$provider->zone_id` with pivot coverage for `booking.zone_id`.
- [ ] `Modules/BookingModule/Http/Controllers/Web/Admin/BookingController.php` — provider queries using `where('zone_id', $booking->zone_id)` on `Provider`.
- [ ] `Modules/BookingModule/Http/Controllers/Web/Provider/BookingController.php`
- [ ] `Modules/BookingModule/Http/Controllers/Api/V1/Provider/BookingController.php` — variation loads vs booking zone.
- [ ] `Modules/BidModule/Http/Controllers/APi/V1/Customer/PostController.php` — provider filter by zone.
- [ ] `Modules/CartModule/Http/Controllers/Api/V1/Customer/CartController.php` — header `zoneid` + variations.

---

## Phase 6 — Provider web: services, categories, discounts

- [ ] `Modules/ServiceManagement/Http/Controllers/Web/Provider/ServiceController.php`
- [ ] `Modules/ServiceManagement/Http/Controllers/Api/V1/Provider/ServiceController.php` (if present)
- [ ] `Modules/CategoryManagement/Http/Controllers/Api/V1/Provider/CategoryController.php` — `category_zone` vs multi-zone provider.
- [ ] `Modules/ServiceManagement/Entities/Service.php` — discount `type_wise_id` / variation scopes using `provider->zone_id`.
- [ ] `Modules/ProviderManagement/Resources/views/layouts/partials/_aside.blade.php` — sidebar queries using `provider->zone_id`.

---

## Phase 7 — Variations & admin service CRUD

- [ ] `Modules/ServiceManagement/Http/Controllers/Web/Admin/ServiceController.php` — default + optional overrides (replace full variant × all zones matrix).
- [ ] `Modules/ServiceManagement/Http/Controllers/Api/V1/Admin/ServiceController.php`
- [ ] `Modules/ServiceManagement/Resources/views/admin/create.blade.php`
- [ ] `Modules/ServiceManagement/Resources/views/admin/edit.blade.php`
- [ ] `Modules/ServiceManagement/Resources/views/admin/detail.blade.php`
- [ ] `Modules/ServiceManagement/Resources/views/admin/partials/_variant-data.blade.php`
- [ ] `Modules/ServiceManagement/Resources/views/admin/partials/_update-variant-data.blade.php`
- [ ] `Modules/ServiceManagement/Resources/views/provider/detail.blade.php` — zone tabs from `provider->zones`.
- [ ] `Modules/ServiceManagement/Http/Controllers/Api/V1/Customer/ServiceController.php` — `Config::get('zone_id')` + default variation fallback.
- [ ] `Modules/AI/PromptTemplates/ProductVariationSetup.php` — align with new variation rules.

---

## Phase 8 — Serviceman, bids, leads

- [ ] `Modules/ServicemanModule/Http/Controllers/Api/V1/Serviceman/ServicemanController.php` — push / `zone_ids`.
- [ ] `Modules/ServiceManagement/Http/Controllers/Api/V1/Serviceman/ServiceController.php`
- [ ] `Modules/BidModule/Http/Controllers/Web/Provider/PostController.php`
- [ ] `Modules/BidModule/Http/Controllers/APi/V1/Provider/PostController.php`
- [ ] `Modules/LeadManagement/Http/Controllers/Web/Admin/LeadController.php` — verify zone filters.

---

## Phase 9 — Push notifications & FCM topic strings

- [ ] `Modules/ProviderManagement/Resources/views/layouts/master.blade.php` — `demandium_provider_{{ zone_id }}_...` multi-topic or new scheme.
- [ ] `Modules/ProviderManagement/Resources/views/layouts/new-master.blade.php`
- [ ] `Modules/ProviderManagement/Http/Controllers/Web/Provider/ProviderController.php` — `whereJsonContains('zone_ids', provider->zone_id)`.

---

## Phase 10 — Reports

- [ ] `Modules/AdminModule/Http/Controllers/Web/Admin/Report/ProviderReportController.php` — filter providers via `provider_zone` / `whereIn` on pivot.
- [ ] `Modules/AdminModule/Http/Controllers/Web/Admin/Report/BookingReportController.php` — `whereIn('zone_id', …)`; if filters allow **parent** zones, add “zone or descendants” or restrict filter to leaves for v1.
- [ ] `Modules/AdminModule/Http/Controllers/Web/Admin/Report/Business/EarningReportController.php`
- [ ] `Modules/AdminModule/Http/Controllers/Web/Admin/Report/Business/ExpenseReportController.php`
- [ ] `Modules/AdminModule/Http/Controllers/Web/Admin/Report/Business/OverviewReportController.php`
- [ ] `Modules/ProviderManagement/Http/Controllers/Web/Provider/Report/*` — any `zone_id` filters.

---

## Phase 11 — Misc & data transfer

- [ ] `Modules/AdminModule/Services/DataTransfer/ServiceCatalogTransfer.php`
- [ ] `Modules/AdminModule/Services/DataTransfer/DomainSnapshotTransfer.php` — include `provider_zone` if snapshotting.
- [ ] `Modules/PromotionManagement/Entities/Banner.php`
- [ ] `Modules/CategoryManagement/Entities/Category.php` — `Config::get('zone_id')` with parent vs leaf browsing.
- [ ] `Modules/CustomerModule/Traits/CustomerSearchTrait.php`

---

## Phase 12 — QA

- [ ] Automated tests: address → one leaf; provider cascade → stored leaves; booking leaf Z → provider must have Z in pivot; variation default vs override.
- [ ] Manual: favorites, cart, admin booking provider swap, web + API registration.

---

## Implementation order (suggested)

1. Migrations + `Zone` model + **`resolveLeafZoneForPoint`** + Phase 1 files.
2. **`provider_zone`** + `Provider::zones()` + replace all **Provider** `zone_id` filters / assignments used for eligibility.
3. Admin zone **parent** UI (Phase 2).
4. Provider **cascade** selection UI + APIs (Phase 3).
5. **Variation** default + override + `Variation` scope + admin service forms (Phase 7).
6. FCM / notification strategy (Phase 9).
7. Reports + parent-zone filter rollup rules (Phase 10).

---

## Quick reference: files that currently assume a single `provider->zone_id`

Search the codebase for `provider->zone_id`, `providers.zone_id`, and `where('zone_id',` on `Provider` queries as implementations proceed; new code should use the pivot and booking/request **leaf** `zone_id` where appropriate.
