<?php

namespace App\Services\Replicate;

class PromptBuilderService
{
    /**
     * Build the full prompt string from style + description + contentType.
     * Reads from config/prompts.php (merged from config/prompts/*.php).
     */
    public function build(string $style, string $description, string $contentType = 'photo'): string
    {
        $styleText   = config("prompts.styles.{$style}", config('prompts.styles.studio'));
        $contentText = config("prompts.content_types.{$contentType}", config('prompts.content_types.photo'));
        if (str_contains((string) $contentText, '{card_text}')) {
            $contentText = str_replace('{card_text}', trim($description), (string) $contentText);
        }
        $suffix      = config('prompts.suffix', '8k resolution, photorealistic');

        $parts = array_filter([
            $styleText,
            trim($description),
            $contentText,
            $suffix,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Return model config (id + inference params) based on contentType.
     */
    public function modelConfig(string $contentType): array
    {
        $key = $contentType === 'preview' ? 'preview' : 'default';

        return config("prompts.models.{$key}", config('prompts.models.default'));
    }

    public function negativePrompt(): string
    {
        return (string) config('prompts.negative', 'blurry, low quality, distorted, text, watermark');
    }
}
