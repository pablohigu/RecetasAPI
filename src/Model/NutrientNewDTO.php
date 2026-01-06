<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;

class NutrientNewDTO
{
    public function __construct(
        // Usamos SerializedName porque en el JSON viene como "type-id" (kebab-case)
        // pero en PHP usamos camelCase.
        #[Assert\NotBlank]
        #[SerializedName('type-id')]
        public int $typeId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public float $quantity
    ) {}
}