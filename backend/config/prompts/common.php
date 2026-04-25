<?php

/**
 * Shared: negative prompt, default/preview Replicate image models (text-to-image fallbacks).
 * Used by: ProcessPhotoGuidedGenerationJob, PromptBuilderService
 */
return [

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

    'models' => [
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
