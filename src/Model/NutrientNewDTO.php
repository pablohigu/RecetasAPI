<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;

class NutrientNewDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[SerializedName('type-id')]
        public int $typeId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public float $quantity
    ) {}
}