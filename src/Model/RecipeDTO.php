<?php
namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class RecipeDTO
{
    public function __construct(
        public int $id,
        public string $title,
        
        #[SerializedName('number-diner')]
        public int $numberDiner,
        
        public RecipeTypeDTO $type,
        
        public array $ingredients, // Array de IngredientDTO
        public array $steps,       // Array de StepDTO
        public array $nutrients,   // Array de objetos NutrientResponseDTO (ver abajo)
        public ?object $rating     // Objeto simple con votos y media
    ) {}
}