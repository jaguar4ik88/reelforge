<?php

namespace App\DTO;

class CreateProjectDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $price,
        public readonly string $description,
        public readonly int    $templateId,
        public readonly int    $userId,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            title:      $data['title'],
            price:      $data['price'],
            description: $data['description'],
            templateId: $data['template_id'],
            userId:     $userId,
        );
    }
}
