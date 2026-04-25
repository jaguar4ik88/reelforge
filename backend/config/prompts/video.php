<?php

/**
 * Product video (photo-guided, content_type: video) — keyframe / storyboard wording, wishes.
 * Used by: ProductPromptBuilder, WishesPromptEnrichmentService
 */
return [

    'wishes_processor' => [

        'user_video' => <<<'PROMPT'
Product: {product_name}
Category: {category}
User's video direction (camera, mood, effects): {wishes}

Output ONLY an English prompt describing key visual frames, motion, and atmosphere for a short vertical product video concept.
PROMPT,
    ],

    'content_types' => [

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
    ],
];
