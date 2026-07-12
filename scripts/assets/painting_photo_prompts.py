#!/usr/bin/env python3
"""Build service-specific photorealistic prompts for painting catalog."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[1] / "data" / "painting-catalog.php"
ASSETS = Path("/Users/kamran/.cursor/projects/Users-kamran-Desktop-panun-kaergar/assets")
OUT = Path(__file__).resolve().parent / "data" / "painting-photo-prompts.json"

INTERIOR = "a clean modern Indian home interior in Srinagar Kashmir"
EXTERIOR = "a residential building exterior in a Srinagar Kashmir neighbourhood"
PAINTER = "Kashmiri origin Indian professional painter in clean work clothes and cap"
SUFFIX_THUMB = "natural soft lighting, shallow depth of field, photorealistic stock photo style. No text, no logos, no watermarks."
SUFFIX_COVER = "natural daylight, photorealistic home service photography. No text, no logos, no watermarks."

SPECS: dict[str, tuple[str, str, str]] = {
    "full-house-interior-painting": (
        "interior-painting",
        f"Professional close-up photograph of full house interior painting, {INTERIOR}, {PAINTER} rolling fresh paint on living room walls, paint tray and roller visible, furniture covered with drop cloths, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of full house interior painting, {INTERIOR}, {PAINTER} painting walls across an open living and dining area, drop cloths on floor, roller and brush in use, {SUFFIX_COVER}",
    ),
    "full-room-painting": (
        "interior-painting",
        f"Professional close-up photograph of full room painting, {INTERIOR}, {PAINTER} rolling white paint on bedroom walls, masked edges along ceiling, paint tray nearby, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of full room painting, {INTERIOR}, {PAINTER} painting all walls of a single bedroom, masked trim and covered floor, {SUFFIX_COVER}",
    ),
    "door-painting": (
        "interior-painting",
        f"Professional close-up photograph of interior door painting, {INTERIOR}, {PAINTER} carefully brushing enamel paint on a wooden panel door, fine brush strokes visible, door partially painted, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of interior door painting, {INTERIOR}, {PAINTER} painting a wooden interior door with brush and small roller, door frame masked with tape, {SUFFIX_COVER}",
    ),
    "window-painting": (
        "interior-painting",
        f"Professional close-up photograph of window painting, {INTERIOR}, {PAINTER} brushing paint on wooden window frame and sill, glass masked with tape, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of window painting, {INTERIOR}, {PAINTER} painting interior wooden window frames and sill with brush, neat masking tape on glass, {SUFFIX_COVER}",
    ),
    "primer-application": (
        "interior-painting",
        f"Professional close-up photograph of primer application, {INTERIOR}, {PAINTER} rolling white primer coat onto a bare plaster wall, primer bucket and roller visible, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of primer application, {INTERIOR}, {PAINTER} applying primer across interior walls before topcoat, roller and tray in use, {SUFFIX_COVER}",
    ),
    "texture-wall-painting": (
        "interior-painting",
        f"Professional close-up photograph of texture wall painting, {INTERIOR}, {PAINTER} applying decorative wall texture with trowel and special roller, textured pattern forming on wall, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of texture wall painting, {INTERIOR}, {PAINTER} creating decorative textured finish on accent wall, tools and texture paste visible, {SUFFIX_COVER}",
    ),
    "wall-putty-application": (
        "interior-painting",
        f"Professional close-up photograph of wall putty application, {INTERIOR}, {PAINTER} smoothing white wall putty with broad trowel on uneven plaster wall, putty bucket nearby, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of wall putty application, {INTERIOR}, {PAINTER} applying and leveling wall putty before painting, trowel and sanding block visible, {SUFFIX_COVER}",
    ),
    "single-wall-accent-painting": (
        "interior-painting",
        f"Professional close-up photograph of single accent wall painting, {INTERIOR}, {PAINTER} rolling deep teal paint on one feature wall while adjacent walls remain white, sharp color contrast, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of single accent wall painting, {INTERIOR}, {PAINTER} painting one bold accent wall in a living room, other walls white, neat masked edges, {SUFFIX_COVER}",
    ),
    "ceiling-painting": (
        "interior-painting",
        f"Professional close-up photograph of ceiling painting, {INTERIOR}, {PAINTER} on stepladder rolling white paint on flat ceiling, extension roller handle visible, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of ceiling painting, {INTERIOR}, {PAINTER} on ladder painting white ceiling in a room, walls protected with masking, {SUFFIX_COVER}",
    ),
    "ceiling-trim-painting": (
        "interior-painting",
        f"Professional close-up photograph of ceiling and trim painting, {INTERIOR}, {PAINTER} brushing paint on white skirting board and cornice molding with angled brush, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of ceiling and trim painting, {INTERIOR}, {PAINTER} painting ceiling edges, cornice and skirting boards with detail brush, {SUFFIX_COVER}",
    ),
    "interior-touch-up-patch-painting": (
        "interior-painting",
        f"Professional close-up photograph of interior touch-up painting, {INTERIOR}, {PAINTER} using small brush to patch and blend paint on a small wall blemish, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of interior touch-up and patch painting, {INTERIOR}, {PAINTER} fixing small peeled paint patches on interior wall with brush, {SUFFIX_COVER}",
    ),
    "interior-painting-consultation": (
        "interior-painting",
        f"Professional close-up photograph of interior painting consultation, {INTERIOR}, {PAINTER} holding color shade cards and clipboard while discussing wall condition with homeowner, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of interior painting consultation visit, {INTERIOR}, {PAINTER} inspecting walls and showing paint shade options to customer on site, {SUFFIX_COVER}",
    ),
    "old-paint-removal-scraping": (
        "interior-painting",
        f"Professional close-up photograph of old paint removal, {INTERIOR}, {PAINTER} scraping peeling old paint from interior wall with paint scraper tool, dust and flakes visible, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of old paint removal and surface scraping, {INTERIOR}, {PAINTER} preparing wall by scraping loose old paint before repainting, {SUFFIX_COVER}",
    ),
    "bathroom-kitchen-painting": (
        "interior-painting",
        f"Professional close-up photograph of bathroom wall painting, modern Indian bathroom with tiles, {PAINTER} rolling moisture-resistant paint on upper wall above tiles, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of bathroom and kitchen painting, modern Indian kitchen or bathroom, {PAINTER} painting moisture-resistant emulsion on walls, {SUFFIX_COVER}",
    ),
    "wardrobe-almirah-painting": (
        "interior-painting",
        f"Professional close-up photograph of wardrobe painting, {INTERIOR}, {PAINTER} brushing enamel on built-in wooden almirah wardrobe doors, doors partially painted, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of wardrobe and almirah painting, {INTERIOR}, {PAINTER} painting built-in wooden wardrobe panels with brush and small roller, {SUFFIX_COVER}",
    ),
    "pop-false-ceiling-painting": (
        "interior-painting",
        f"Professional close-up photograph of POP false ceiling painting, {INTERIOR}, {PAINTER} on ladder rolling white paint on gypsum POP false ceiling panels, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of POP false ceiling painting, {INTERIOR}, {PAINTER} painting white gypsum false ceiling in a furnished room, {SUFFIX_COVER}",
    ),
    "interior-crack-filling-repair": (
        "interior-painting",
        f"Professional close-up photograph of interior wall crack repair, {INTERIOR}, {PAINTER} filling hairline wall crack with putty using flexible knife before painting, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of interior crack filling and repair, {INTERIOR}, {PAINTER} repairing wall cracks and holes with putty and sandpaper, {SUFFIX_COVER}",
    ),
    "stain-damp-spot-treatment": (
        "interior-painting",
        f"Professional close-up photograph of damp stain treatment on interior wall, {INTERIOR}, {PAINTER} treating brown water stain and damp patch with sealer before repainting, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of stain and damp spot treatment, {INTERIOR}, {PAINTER} applying anti-damp primer on stained interior wall area, {SUFFIX_COVER}",
    ),
    "building-painting": (
        "exterior-painting",
        f"Professional close-up photograph of building exterior painting, {EXTERIOR}, {PAINTER} on metal scaffolding rolling paint on multi-storey building facade, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of building painting, {EXTERIOR}, {PAINTER} painting full building exterior from scaffolding, fresh paint on facade, {SUFFIX_COVER}",
    ),
    "exterior-texture-painting": (
        "exterior-painting",
        f"Professional close-up photograph of exterior texture painting, {EXTERIOR}, {PAINTER} applying rough textured exterior finish on building wall with trowel, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of exterior texture painting, {EXTERIOR}, {PAINTER} creating decorative textured coating on exterior wall, {SUFFIX_COVER}",
    ),
    "exterior-wall-painting": (
        "exterior-painting",
        f"Professional close-up photograph of exterior wall painting, {EXTERIOR}, {PAINTER} using long extension roller to paint exterior plaster wall, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of exterior wall painting, {EXTERIOR}, {PAINTER} rolling fresh paint on home exterior wall in daylight, {SUFFIX_COVER}",
    ),
    "full-house-exterior-painting": (
        "exterior-painting",
        f"Professional close-up photograph of full house exterior painting, {EXTERIOR}, {PAINTER} painting entire home exterior walls with roller and brush, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of full house exterior painting, {EXTERIOR}, {PAINTER} repainting complete house exterior including walls and trim, {SUFFIX_COVER}",
    ),
    "boundary-wall-painting": (
        "exterior-painting",
        f"Professional close-up photograph of boundary wall painting, {EXTERIOR}, {PAINTER} rolling paint on compound boundary wall around residential property, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of boundary wall painting, {EXTERIOR}, {PAINTER} painting long compound wall with roller on sunny day, {SUFFIX_COVER}",
    ),
    "exterior-door-gate-painting": (
        "exterior-painting",
        f"Professional close-up photograph of exterior gate painting, {EXTERIOR}, {PAINTER} brushing dark enamel on wooden main entrance gate and door, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of exterior door and gate painting, {EXTERIOR}, {PAINTER} painting main entrance gate and exterior door with brush, {SUFFIX_COVER}",
    ),
    "exterior-window-grille-painting": (
        "exterior-painting",
        f"Professional close-up photograph of exterior window grille painting, {EXTERIOR}, {PAINTER} brushing paint on exterior wooden window frame and metal grille, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of exterior window and grille painting, {EXTERIOR}, {PAINTER} painting exterior window frames and security grille, {SUFFIX_COVER}",
    ),
    "exterior-primer-application": (
        "exterior-painting",
        f"Professional close-up photograph of exterior primer application, {EXTERIOR}, {PAINTER} rolling exterior primer on weathered outside wall before topcoat, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of exterior primer application, {EXTERIOR}, {PAINTER} applying primer coat on exterior facade wall, {SUFFIX_COVER}",
    ),
    "exterior-wall-putty-crack-repair": (
        "exterior-painting",
        f"Professional close-up photograph of exterior wall putty and crack repair, {EXTERIOR}, {PAINTER} filling exterior wall cracks with putty using trowel on facade, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of exterior wall putty and crack repair, {EXTERIOR}, {PAINTER} repairing cracks and leveling exterior wall surface before paint, {SUFFIX_COVER}",
    ),
    "exterior-touch-up-patch-painting": (
        "exterior-painting",
        f"Professional close-up photograph of exterior touch-up painting, {EXTERIOR}, {PAINTER} patching peeled exterior paint on small wall section with brush, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of exterior touch-up and patch painting, {EXTERIOR}, {PAINTER} fixing faded and chipped exterior paint patches, {SUFFIX_COVER}",
    ),
    "waterproof-weather-shield-coating": (
        "exterior-painting",
        f"Professional close-up photograph of waterproof weather shield coating, {EXTERIOR}, {PAINTER} rolling waterproof exterior coating on damp-prone wall, cloudy Kashmir sky, {SUFFIX_THUMB}",
        f"Wide landscape professional photograph of waterproof weather shield coating application, {EXTERIOR}, {PAINTER} applying weather-resistant protective coating on building exterior, {SUFFIX_COVER}",
    ),
}


def main() -> None:
    catalog = json.loads(
        subprocess.check_output(
            ["php", "-r", f"echo json_encode(require '{CATALOG}');"],
            text=True,
        )
    )
    rows = []
    for svc in catalog["services"]:
        slug = svc["slug"]
        if slug not in SPECS:
            raise SystemExit(f"Missing prompt spec for {slug}")
        sub, thumb, cover = SPECS[slug]
        rows.append(
            {
                "slug": slug,
                "name": svc["name"],
                "sub_category_slug": sub,
                "thumbnail_prompt": thumb,
                "cover_prompt": cover,
                "thumbnail_path": str(ASSETS / f"{slug}-thumbnail.png"),
                "cover_path": str(ASSETS / f"{slug}-cover.png"),
            }
        )
    OUT.write_text(json.dumps(rows, indent=2))
    print(f"Wrote {len(rows)} service-specific prompts to {OUT}")


if __name__ == "__main__":
    main()
