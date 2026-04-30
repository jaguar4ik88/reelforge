<?php

namespace App\Services\Infographic;

/**
 * Maps UI aspect ratio labels to OpenAI Images API `size` values (gpt-image-1.5).
 */
final class InfographicByExampleOpenAiSize
{
    /**
     * @return array{0: string, 1: string} [api_size, human_canvas_label]
     */
    public static function fromAspectUi(string $aspectUi): array
    {
        $ui = trim($aspectUi);
        if ($ui === '16:9') {
            return ['1536x1024', '1536×1024'];
        }
        if ($ui === '9:16' || $ui === '4:5' || $ui === '3:4') {
            return ['1024x1536', '1024×1536'];
        }

        return ['1024x1024', '1024×1024'];
    }
}
