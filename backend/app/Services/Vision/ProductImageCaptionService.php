<?php

namespace App\Services\Vision;

use App\Models\ProjectImage;
use App\Support\ReelForgeStorage;

/**
 * Describes a product reference image for prompt enrichment.
 * Replace the body of describe() with a call to your vision API when ready.
 */
class ProductImageCaptionService
{
    public function describe(?ProjectImage $image): string
    {
        if ($image === null) {
            return '';
        }

        $image->loadMissing('project');
        $project = $image->project;
        $meta    = $project?->product_meta_json;

        if (is_array($meta) && ($meta['name'] ?? '') !== '') {
            $parts = [(string) $meta['name']];
            if (! empty($meta['category'])) {
                $parts[] = 'Category: '.(string) $meta['category'];
            }
            if (! empty($meta['qualities']) && is_array($meta['qualities'])) {
                $parts[] = 'Selling points: '.implode('; ', array_slice($meta['qualities'], 0, 6));
            }

            return implode('. ', $parts);
        }

        \Log::debug('ProductImageCaptionService: no product_meta_json, empty caption', [
            'project_image_id' => $image->id,
        ]);

        return '';
    }
}
