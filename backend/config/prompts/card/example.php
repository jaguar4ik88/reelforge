<?php

/**
 * Product card generation “by example” — GPT Image 1.5 (Images Edits, two reference images).
 * Placeholders in `template`: {bg_removal_note}, {canvas_line}, {background_line}, {product_position},
 * {title}, {title_color_hint}, {title_position}, {title_style}, {features_layout}, {features_position},
 * {features_block}, {body_color_hint}, {overall_style}, {composition_notes}, {color_palette}
 */
return [
    'card_by_example' => [
        'gpt_image' => [
            'product_note_needs_bg_removal' => 'If Image 1 has a distracting backdrop, treat the product as a clean cut-out on the new card canvas.',
            'product_note_default' => 'Use Image 1 as-is for product appearance; keep the product faithful to the photo.',
            'template' => <<<'PROMPT'
Create a product card image with the following layout.

Inputs (order matters):
- Image 1: product — use this exact product. {bg_removal_note}
- Image 2: example card — copy its layout structure, hierarchy, spacing, and visual style only. Do not copy any text, SKU, price, watermark, logos, or the old product from Image 2.

CANVAS: {canvas_line}

BACKGROUND: {background_line}

PRODUCT: Place the product from Image 1 in {product_position}.
Preserve its exact appearance, colors, packaging graphics, and silhouette from Image 1.
Apply a subtle drop shadow beneath the product, consistent with Image 2's depth cues.

TYPOGRAPHY:
- Title: "{title}" — bold, {title_color_hint}, {title_position}, large size (style reference: {title_style})
- Features ({features_layout}) at {features_position}:
{features_block}
Font: clean sans-serif, {body_color_hint}, medium size

STYLE: {overall_style}. Spatial composition notes: {composition_notes}

COLOR PALETTE (harmonize type and accents with): {color_palette}

Important: render all text exactly as written, including Cyrillic characters.

Render all text exactly as written, preserve Cyrillic characters accurately.
PROMPT,
        ],
    ],
];
