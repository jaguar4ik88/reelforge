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
];
