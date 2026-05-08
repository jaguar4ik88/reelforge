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
            'catalog / studio shot — isolated product on clean white seamless background, soft even lighting, sharp detail',
            'strip from the reference all watermarks, shop URLs, stock marks, subtitles, captions, stickers, badges, price tags as overlay, screenshots UI text, promo banners, QR codes as graphic overlays, and any typography not physically printed on the product',
            'output must contain no watermark, no added text, no extra logos beyond what is molded or printed on the product itself',
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

    /**
     * Optional short “Scene:” lines for client-side prompt preview tooling.
     * Source of truth for photo scene wording alongside `styles` (server prompt uses `styles`).
     *
     * @var array<string, string>
     */
    'ui_scene_lines' => [
        'from_wishes' =>
            'Scene: follow the user wishes as the primary art direction — composition, lighting, setting, and mood come from their description.',
        'no_watermark' =>
            'Scene: remove watermarks, shop URLs, stickers, screenshots UI text, and overlaid typography from the reference; place the product on a clean neutral studio background; final image — no captions or added graphics beyond physical print on the product; sharp, listing-ready.',
        'studio' =>
            'Scene: strip watermarks and all non-physical overlaid text from the reference; isolated catalog hero — clean white seamless background, soft even lighting, sharp product detail — no captions, stamps, or added graphics except molded or printed marks on the product itself.',
        'environment' =>
            'Scene: product placed in a believable real-world environment; natural light, depth, context.',
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
