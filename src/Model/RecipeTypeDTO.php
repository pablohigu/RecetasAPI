<?php
namespace App\Model;

class RecipeTypeDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description
    ) {}
}