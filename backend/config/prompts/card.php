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

    'suffix_card' => 'Photoreal, clean sharp, 4K, no watermarks. Printed on-card words must match the exact text line block above, character for character; no CJK or garbled script substitutes.',

    'models' => [
        'kontext' => [
            'id'                => 'black-forest-labs/flux-kontext-pro',
            'aspect_ratio'      => '3:4',
            'output_format'     => 'jpg',
            'output_quality'    => 95,
            'safety_tolerance'  => 2,
            'prompt_upsampling' => true,
        ],
    ],
];
