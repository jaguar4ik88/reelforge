<?php

/*
 * ─────────────────────────────────────────────────────────────
 * ReelForge — промпти для генерації зображень (v2)
 *
 * Wishes: користувацький текст спочатку проходить через WishesPromptEnrichmentService
 * (Claude → OpenAI → fallback), щоб отримати англомовний промпт для FLUX.
 * ─────────────────────────────────────────────────────────────
 */

return [

    'photo_analysis_system' => <<<'PROMPT'
You are a product photography analyst. Analyze the provided product image and return ONLY a JSON object with these keys:
- dominant_colors: array of 2-3 hex colors most prominent on the product
- background_type: "white" | "gradient" | "lifestyle" | "studio" | "transparent"
- product_angle: "front" | "side" | "three_quarter" | "top_down"
- material_cues: string (e.g. "glossy leather, rubber sole, mesh panels")
- mood: string (e.g. "sporty urban", "luxury minimal", "bold streetwear")
- composition_notes: string (1 sentence about framing)
No markdown, no explanation, only valid JSON.
PROMPT,

    /*
     | ── Wishes preprocessor (Anthropic / OpenAI) ─────────────────
     */
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

        'user_card' => <<<'PROMPT'
Product: {product_name}
Category: {category}
The following text MUST appear on the product card image as typography (preserve exact characters, including Cyrillic):
{wishes}

Output ONLY an English prompt for FLUX describing layout, typography style, and product placement. Do not replace or translate the text that must appear on the card — refer to it as "the exact user text above".
PROMPT,

        'user_video' => <<<'PROMPT'
Product: {product_name}
Category: {category}
User's video direction (camera, mood, effects): {wishes}

Output ONLY an English prompt describing key visual frames, motion, and atmosphere for a short vertical product video concept.
PROMPT,

        'user_card_kontext' => <<<'PROMPT'
Product: {product_name}
Category: {category}
Photo analysis: {photo_analysis}

The following Cyrillic text labels MUST appear verbatim on the card image (do not translate them):
{card_texts_json}

Describe ONLY:
- The layout (where each label is positioned: top-left, bottom-right, etc.)
- Typography style (bold black sans-serif, uppercase)
- Diagonal graphic elements or geometric accents color and style
- Background treatment
- How the product is placed

Do NOT translate the Cyrillic text. Refer to each label as its exact string in quotes.
Output one English prompt for FLUX, max 120 words.
PROMPT,
    ],

    /*
     | ── Сцени (photo-guided flow) ────────────────────────────────
     | from_wishes: порожньо — основний зміст дає enrichment + побажання.
     */
    'styles' => [

        'from_wishes' => '',

        'in_use' => implode(', ', [
            'product lifestyle photography',
            'product naturally held or used in context',
            'authentic real-world setting',
            'soft natural window light',
            'shallow depth of field',
            'warm editorial atmosphere',
            'story-driven composition',
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

    /*
     | ── Типи контенту ────────────────────────────────────────────
     */
    'content_types' => [

        'photo' => implode(', ', [
            'photorealistic commercial product image',
            'suitable for Instagram and paid ads',
            'high-end production value',
        ]),

        'card' => implode(', ', [
            'product marketing card',
            'clean white background',
            'product as visual anchor',
            'exact on-image typography must include this text: {card_text}',
            'bold readable typographic layout',
            'premium retail presentation',
            'generous white space',
        ]),

        'video' => implode(', ', [
            'vertical short-form product video storyboard',
            'dynamic cinematic framing',
            'motion and pacing implied',
        ]),

        'video_short' => implode(', ', [
            '5-second product video keyframe concept',
            'dynamic close-up angle',
            'high energy visual',
        ]),

        'video_long' => implode(', ', [
            '20-second product video keyframe concept',
            'cinematic product reveal',
            'smooth camera movement implied',
            'atmospheric lighting',
        ]),

        'preview' => 'quick product preview, clean background, sharp product detail',
    ],

    /*
     | ── Суфікс ───────────────────────────────────────────────────
     */
    'suffix' => implode(', ', [
        '4K resolution',
        'photorealistic',
        'professional color grading',
        'no watermarks',
        'no logos',
        'single product hero',
    ]),

    /** product marketing card (kontext): typography rules — do not reuse generic suffix (it contradicted on-card text). */
    'suffix_card' => implode(', ', [
        'sharp product photography',
        'bold Cyrillic typography rendered in Cyrillic script exactly as provided',
        'clean geometric graphic design layout',
        'high contrast legible text on light background',
        'diagonal accent lines where appropriate in red and black',
        'professional retail card composition',
        '4K resolution, no watermarks or logos on image',
    ]),

    /*
     | ── Негативний промпт (flux-dev без референсу) ───────────────
     */
    'negative' => implode(', ', [
        'blurry', 'out of focus', 'low quality', 'low resolution',
        'distorted product', 'deformed', 'warped shape',
        'watermark', 'signature',
        'multiple products', 'duplicate items',
        'oversaturated', 'blown out highlights',
        'amateur photography', 'phone camera quality',
        'bad lighting', 'harsh shadows on product',
        'extra limbs', 'bad anatomy',
        'CGI look', 'plastic texture', 'fake looking',
    ]),

    /*
     | ── Моделі Replicate ─────────────────────────────────────────
     */
    'models' => [
        'kontext' => [
            'id'               => 'black-forest-labs/flux-kontext-pro',
            'aspect_ratio'     => '3:4',   // вертикальный формат для карточки маркетплейса
            'output_format'    => 'jpg',
            'output_quality'   => 95,
            'safety_tolerance' => 2,
            'prompt_upsampling'=> true,
            // input_image передаётся отдельно через Replicate API как base64 или URL
        ],

        'default' => [
            'id'                  => 'black-forest-labs/flux-dev',
            'num_inference_steps' => 35,
            'guidance_scale'      => 3.5,
            'width'               => 1024,
            'height'              => 1024,
        ],

        'preview' => [
            'id'                  => 'black-forest-labs/flux-schnell',
            'num_inference_steps' => 4,
            'guidance_scale'      => 3.5,
            'width'               => 1024,
            'height'              => 1024,
        ],
    ],

];
