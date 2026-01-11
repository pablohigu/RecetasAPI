<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\SerializedName;

class RecipeNewDTO
{
    public function __construct(
        #[Assert\NotBlank(message: "El título es obligatorio")]
        public string $title,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[SerializedName('number-diner')]
        public int $numberDiner,

        #[Assert\NotBlank]
        #[SerializedName('type-id')]
        public int $typeId,

       
        #[Assert\Count(min: 1, minMessage: "Debes añadir al menos 1 ingrediente")]
        #[Assert\Valid]
        /** @var IngredientDTO[] */
        public array $ingredients,

        #[Assert\Count(min: 1, minMessage: "Debes añadir al menos 1 paso")]
        #[Assert\Valid]
        /** @var StepDTO[] */
        public array $steps,

        #[Assert\Valid]
        /** @var NutrientNewDTO[] */
        public array $nutrients
    ) {}
}