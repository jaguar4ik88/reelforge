<?php

namespace App\DTO;

class VideoGenerationDTO
{
    public function __construct(
        public readonly int    $projectId,
        public readonly int    $userId,
        public readonly string $title,
        public readonly string $price,
        public readonly string $description,
        public readonly array  $imagePaths,
        public readonly array  $templateConfig,
    ) {}
}
