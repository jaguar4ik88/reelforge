<?php

/**
 * ReelForge image/video prompt strings — loaded from `config/prompts/*.php` and merged here.
 * Edit per-domain files: analysis, photo, card, video, common, card/example.
 *
 * Merged config keys match `config('prompts.*')` used in ProductPromptBuilder, WishesPromptEnrichmentService, jobs, etc.
 */
$merge = static function (array $base, array $next): array {
    $mergeable = ['wishes_processor', 'content_types', 'styles', 'models'];
    foreach ($next as $key => $value) {
        if (
            in_array($key, $mergeable, true)
            && isset($base[$key])
            && is_array($base[$key])
            && is_array($value)
        ) {
            $base[$key] = array_merge($base[$key], $value);
        } else {
            $base[$key] = $value;
        }
    }

    return $base;
};

/** @var array<string, mixed> $config */
$config = require __DIR__.'/prompts/analysis.php';

foreach (
    [
        __DIR__.'/prompts/photo.php',
        __DIR__.'/prompts/card.php',
        __DIR__.'/prompts/card/example.php',
        __DIR__.'/prompts/video.php',
        __DIR__.'/prompts/common.php',
    ] as $path
) {
    $config = $merge($config, require $path);
}

return $config;
