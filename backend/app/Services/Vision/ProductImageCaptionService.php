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

        // Stub: no external API. Log path for debugging pipelines.
        \Log::debug('ProductImageCaptionService: stub caption', [
            'project_image_id' => $image->id,
            'path'             => $image->path,
            'disk'             => ReelForgeStorage::contentDisk(),
        ]);

        return '';
    }
}
