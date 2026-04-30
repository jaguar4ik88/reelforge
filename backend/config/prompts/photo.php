<?php

/**
 * Still product generation (photo-guided, content_type: photo) — scene styles, photo content line, main suffix, wishes blocks.
 * Used by: ProductPromptBuilder, WishesPromptEnrichmentService, PromptBuilderService
 */
return [

    'wishes_processor' => [

        'system' => <<<'PROMPT'
You are a professional prompt engineer for AI image generation (FLUX / product photography).
Your task: receive a product name, category, and a user's scene wishes (possibly in Russian, Ukrainian, or any language), then output a single English prompt optimized for photorealistic product photography.

Rules:
- Output ONLY the English prompt — no explanations, no JSON, no markdown fences.
- Translate and expand the user's wishes into vivid, specific visual language.
- Keep the product as the clear hero of the shot.
- Use photography and lighting terminology where helpful (soft light, depth of field, color temperature, mood).
- Max 120 words.
PROMPT,

        'user' => <<<'PROMPT'
Product: {product_name}
Category: {category}
User's scene wishes: {wishes}

Write the image generation prompt.
PROMPT,

        'user_no_wishes' => <<<'PROMPT'
Product: {product_name}
Category: {category}
No specific scene requested — choose the most flattering commercial photography setup for this product type.

Write the image generation prompt.
PROMPT,
    ],

    /*
     | Photo-guided scene styles (image generation).
     */
    'styles' => [

        'from_wishes' => '',

        'no_watermark' => implode(', ', [
            'e-commerce product cleanup and relight',
            'remove all watermarks, shop URLs, stock marks, and overlaid text from the source reference',
            'reproduce the product on a clean neutral light-grey to off-white seamless studio background',
            'soft even three-point professional lighting, tack-sharp product detail',
            'no people, no hands, no busy environment',
            'generous copy-safe negative space, marketplace listing hero',
        ]),

        'studio' => implode(', ', [
            'professional studio product photography',
            'clean white seamless background',
            'three-point softbox lighting',
            'tack-sharp focus on product',
            'zero harsh shadows on background',
            'commercial catalog e-commerce hero shot',
        ]),

        'environment' => implode(', ', [
            'product in believable real-world environment',
            'natural depth and context',
            'realistic ambient light',
        ]),

        'lifestyle' => 'lifestyle product photography, natural light, bokeh background, editorial style',

        'minimal' => 'minimalist product photo, marble surface, elegant composition, luxury brand aesthetic',

        'outdoor' => 'outdoor product photography, natural environment, golden hour lighting',
    ],

    'content_types' => [

        'photo' => implode(', ', [
            'photorealistic commercial product image',
            'suitable for Instagram and paid ads',
            'high-end production value',
        ]),

        'preview' => 'quick product preview, clean background, sharp product detail',
    ],

    'suffix' => implode(', ', [
        '4K resolution',
        'photorealistic',
        'professional color grading',
        'no watermarks',
        'no logos',
        'single product hero',
    ]),
];
