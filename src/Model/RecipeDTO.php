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
        
        public array $ingredients, 
        public array $steps,      
        public array $nutrients,   
        public ?object $rating     
    ) {}
}