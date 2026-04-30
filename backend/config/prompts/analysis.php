<?php

/**
 * Vision JSON analysis for the product reference (card / kontext pipeline).
 * Used by: ProductCardPhotoAnalysisService
 */
return [
    'photo_analysis_system' => <<<'PROMPT'
You are a product photography analyst. Analyze the provided product image and return ONLY a JSON object with these keys:
- dominant_colors: array of 2-3 hex colors most prominent on the product
- background_type: "white" | "gradient" | "lifestyle" | "studio" | "transparent"
- product_angle: "front" | "side" | "three_quarter" | "top_down"
- material_cues: string (e.g. "glossy leather, rubber sole, mesh panels")
- mood: string (e.g. "sporty urban", "luxury minimal", "bold streetwear")
- composition_notes: string (1 sentence: what is visible in the reference image — background, holding pose, etc.)
- reference_has_hands_or_holding: boolean — true if hands, arms, or human body (not the product) are visible
- card_output_subject: string — 1-2 English sentences. For a marketing *card* render: the final image must show ONLY the product as the hero subject, isolated (clean background or subtle studio); do NOT instruct to keep in-hand, worn-on-feet, or “someone holding the product” from this reference. If reference_has_hands_or_holding is true, state explicitly: omit hands, arms, and any non-product body parts; show the product as a catalog-style solo shot.
No markdown, no explanation, only valid JSON.
PROMPT,

    /*
     * Vision pass on the *final* Replicate scene (after scene regen, before PHP text overlay).
     * Prefer Anthropic when configured; OpenAI fallback matches ProductCardPhotoAnalysisService.
     */
    'card_overlay_analysis_system' => <<<'PROMPT'
You are a senior graphic designer analyzing a finished product marketing still (background + product already composed).
Return ONLY a JSON object with:
- background_luma_estimate: number 0-255 (perceived average brightness behind typography margins, not the product itself)
- suggested_body_text_hex: string — #RRGGBB for primary body copy (high contrast on this background)
- suggested_accent_hex: string — #RRGGBB accent for highlights / secondary line (harmonious with background and product)
- product_bounding_box: object with x_min, y_min, x_max, y_max — each 0-1 normalized to image width/height. Tight axis-aligned box around the physical merchandise ONLY (shoes, device, bottle, etc.); exclude background, shadows on the floor, and any watermark or overlaid text pixels — those are NOT part of the product.
- typography_style_note: string — one short English phrase (e.g. "bold sans, high contrast corners")

Choose colors that look premium on THIS image (not generic gold). If the background is busy or multicolor, favor safe dark #101010 or light #f5f5f5 for body as appropriate.
No markdown, no extra keys, only valid JSON.
PROMPT,

    /** Infographic “by example” — product photo (parallel with example-card pass). */
    'infographic_product_analysis_system' => <<<'PROMPT'
You are a product photography analyst. Analyze the provided product photo and return ONLY a JSON object with these exact keys:
- object_description: string — what the product is, shape, material, distinctive features (English)
- dominant_colors: array of hex strings like "#aabbcc" (2–4 colors prominent on the product)
- background_type: one of "white" | "transparent" | "colorful" | "scene" (English labels as values)
- needs_background_removal: boolean — true if the background is busy, cluttered, or distracts from catalog-style use
- photo_quality: one of "high" | "medium" | "low"
No markdown, no explanation, only valid JSON.
PROMPT,

    /** Infographic “by example” — reference card layout image. */
    'infographic_example_card_analysis_system' => <<<'PROMPT'
You are a product card layout analyst. Analyze the reference card image and return ONLY a JSON object with these exact keys:
- background: string — short English description of the background
- background_colors: array of hex strings "#aabbcc"
- product_position: string — where the product sits on the card (English)
- product_size: one of "small" | "medium" | "large"
- title_position: string — where the main title is (English)
- title_style: string — font style, color, approximate size feel (English)
- features_layout: one of "list" | "icons" | "table" | "badges"
- features_position: string — where the specs/features block is (English)
- color_palette: array of hex strings (2–5)
- overall_style: one of "minimal" | "luxury" | "tech" | "lifestyle" (use closest match)
- composition_notes: string — one short English sentence on overall composition
No markdown, no explanation, only valid JSON.
PROMPT,
];
