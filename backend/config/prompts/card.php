<?php

/**
 * Product marketing card (kontext / FLUX) — wishes for card, content line, suffix, Replicate kontext model.
 * Used by: WishesPromptEnrichmentService, ProductPromptBuilder, ProcessPhotoGuidedGenerationJob
 */
return [

    'wishes_processor' => [

        'user_card' => <<<'PROMPT'
Product: {product_name}
Category: {category}
On-card typography (UTF-8, preserve every character and line):
{wishes}

Output ONLY English (60–80 words): commercial card — graphic background, product as hero, label positions, short photoreal note. Isolated product, no in-hand pose from a reference unless user asked. Do not translate the on-card lines; call them "the user text above".
PROMPT,

        'user_card_kontext' => <<<'PROMPT'
Product: {product_name}
Category: {category}
Photo analysis (JSON, use "card_output_subject" and product facts): {photo_analysis}

On-card text lines (copy verbatim in the final design — UTF-8, Latin/Cyrillic/mixed, same line breaks and spelling):
{card_texts_json}

Write ONE short English block for the image model (60–80 words, no long bullet lists). Priority order:
1) Commercial ad-style product card: the product (from analysis) is the only physical subject, solo, catalog-style, no hands from the reference.
2) Graphic / flat or soft-gradient background; product placed clearly on it; space for typography.
3) Where labels sit (corners, hierarchy), general type style, optional simple geometric accents.
4) Short lighting/realism note (soft, sharp, photoreal) — one phrase.

Follow card_output_subject: if the reference was in-hand, describe the *final* image as a solo product, not the same hand pose. Do not pad with repeated marketing clichés. Quote any label in double quotes in English only for anchoring, not the whole label block again.
PROMPT,
    ],

    'content_types' => [

        'card' => implode(' ', [
            'Retail graphic designer mindset, clear typographic hierarchy, ad layout with labels in fixed regions.',
            'Commercial product card (marketplace / ad): photoreal, graphic or minimal background, product the clear hero, main offer as on-image typography (not in the prompt body alone).',
            'Exact on-card text as printed here — any script, line breaks preserved: {card_text}.',
            'Solo product, no extra hands; bold legible type, balanced layout, generous breathing room.',
        ]),
    ],

    'suffix_card' => 'Photoreal, clean sharp, 4K. Remove every stock watermark, URL, shop branding, and diagonal overlay from the reference — do not reproduce them. Printed on-card words must match the exact text line block above, character for character; no CJK or garbled script substitutes.',

    /**
     * When card mode uses server-side PHP composition (see platform.card.php_composite), Replicate
     * generates a **background only**; typography is drawn with GD/Imagick.
     */
    'background_composite' => [
        'instruction' => 'Square empty commercial product-card backdrop only (no product in frame). Each render may vary: flat solid color, soft two-color gradient, subtle geometric shapes, or minimal abstract graphic — modern marketplace / catalog look, not always gray. Generous negative space in the lower half for a cut-out product; clear margins in corners for typography. No text, letters, numbers, watermark, or logos. Sharp, photoreal studio feel.',
    ],

    /** FLUX Kontext: full scene with product (hands removed, new backdrop). Used when php_composite.regen_scene_before_text is true. */
    'card_scene_regen_instruction' => <<<'PROMPT'
Commercial product hero for a square marketing card. Using the reference image, recreate ONLY the physical PRODUCT (shape, materials, colors, branding). Remove all hands, arms, people, and busy lifestyle clutter.

CRITICAL — source must NOT appear in the output: erase every watermark, logo stamp, domain name, shop URL, phone number, stock photo mark, photographer credit, semi-transparent overlay text, corner badges, and ANY text or symbols that are not part of the product itself. The output image must have zero overlaid typography except what belongs on the real product packaging. If the reference shows a watermark across the photo, invent a clean background behind the product instead of copying those pixels.

New scene: solo catalog product, studio lighting, sharp photoreal. Backdrop — one fresh direction: flat solid, soft gradient, subtle geometry, minimal pattern, or vignette; leave clearly empty areas top and/or bottom for later marketing copy (generous negative space). Product placement: centered or lower-third; no props unless minimal pedestal shadow.
PROMPT,

    /** Prepended to Kontext card scene prompt — models often weight early tokens heavily. */
    'card_scene_regen_watermark_prefix' => <<<'PROMPT'
EDIT PRIORITY 1 — Remove ALL non-product overlays from the reference: watermarks, stock-site text (e.g. Shutterstock/Getty/iStock patterns), shop URLs, social handles, diagonal repeating logos, semi-transparent bands, corner badges, and photographer credits. Do NOT copy those pixels; inpaint with clean background or plausible product/body continuation.
PROMPT,

    /** Appended after creative direction so removal rules are reinforced at the end of the prompt. */
    'card_scene_regen_watermark_suffix' => <<<'PROMPT'
FINAL CHECK: The rendered image must contain NO watermark, NO stock overlay, NO URL, and NO site branding from the source. If the reference is covered by a watermark, reconstruct the underlying scene and product as if the watermark never existed. Only text physically printed on the product packaging may remain.
PROMPT,

    'models' => [
        'kontext' => [
            'id' => 'black-forest-labs/flux-kontext-pro',
            'aspect_ratio' => '3:4',
            'output_format' => 'jpg',
            'output_quality' => 95,
            'safety_tolerance' => 2,
            'prompt_upsampling' => true,
        ],
    ],
];
