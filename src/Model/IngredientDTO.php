<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class IngredientDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "El nombre del ingrediente es obligatorio")]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Positive(message: "La cantidad debe ser mayor a 0")]
        public float $quantity,

        #[Assert\NotBlank]
        public string $unit
    ) {}
}