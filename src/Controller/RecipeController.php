<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\NutrientType;
use App\Entity\Recipe;
use App\Entity\RecipeNutrient;
use App\Entity\RecipeType;
use App\Entity\Step;
use App\Model\RecipeNewDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Rating;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use App\Model\RecipeTypeDTO;
use App\Model\IngredientDTO;
use App\Model\StepDTO;
use App\Model\NutrientTypeDTO;
use App\Model\RecipeDTO;

#[Route('/recipes')]
class RecipeController extends AbstractController
{
    
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    
    #[Route('', name: 'post_recipe', methods: ['POST'], format: 'json')]
    public function createRecipe(
        #[MapRequestPayload] RecipeNewDTO $recipeDto
    ): JsonResponse
    {
        
        if (count($recipeDto->ingredients) < 1) {
            return $this->json(['error' => 'La receta debe tener al menos 1 ingrediente.'], Response::HTTP_BAD_REQUEST);
        }

       
        if (count($recipeDto->steps) < 1) {
            return $this->json(['error' => 'La receta debe tener al menos 1 paso.'], Response::HTTP_BAD_REQUEST);
        }

       
        $recipeType = $this->entityManager->getRepository(RecipeType::class)->find($recipeDto->typeId);
        
        if (!$recipeType) {
            return $this->json(['error' => 'El tipo de receta especificado no existe.'], Response::HTTP_BAD_REQUEST);
        }

        
        $recipe = new Recipe();
        $recipe->setTitle($recipeDto->title);
        $recipe->setNumDiners($recipeDto->numberDiner);
        $recipe->setType($recipeType); 
        $recipe->setIsDeleted(false); 
        foreach ($recipeDto->ingredients as $ingDto) {
            $ingredient = new Ingredient();
            $ingredient->setName($ingDto->name);
            $ingredient->setQuantity($ingDto->quantity);
            $ingredient->setUnit($ingDto->unit);
            $ingredient->setRecipe($recipe);
            $this->entityManager->persist($ingredient);
            $recipe->addIngredient($ingredient);
        }
        foreach ($recipeDto->steps as $stepDto) {
            $step = new Step();
            $step->setDescription($stepDto->description);
            $step->setStepOrder($stepDto->order);
            $step->setRecipe($recipe);
            
            $this->entityManager->persist($step);
            $recipe->addStep($step);
        }

        
        foreach ($recipeDto->nutrients as $nutDto) {
            
            $nutrientType = $this->entityManager->getRepository(NutrientType::class)->find($nutDto->typeId);
            
            if (!$nutrientType) {
                return $this->json(['error' => "El tipo de nutriente ID {$nutDto->typeId} no existe."], Response::HTTP_BAD_REQUEST);
            }

            $recipeNutrient = new RecipeNutrient();
            $recipeNutrient->setQuantity($nutDto->quantity);
            $recipeNutrient->setNutrientType($nutrientType);
            $recipeNutrient->setRecipe($recipe);             
            
            $this->entityManager->persist($recipeNutrient);
            $recipe->addRecipeNutrient($recipeNutrient);
        }
        $this->entityManager->persist($recipe);
        $this->entityManager->flush();
        
        return $this->json($this->mapRecipeToDTO($recipe), Response::HTTP_OK);
    }

    #[Route('', name: 'get_recipes', methods: ['GET'])]
    public function getRecipes(
        #[MapQueryParameter] ?int $type = null 
    ): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Recipe::class);
        if ($type) {
            $recipes = $repo->findBy(['type' => $type, 'isDeleted' => false]);
        } else {
            $recipes = $repo->findBy(['isDeleted' => false]);
        }

        $responseList = [];
        
        foreach ($recipes as $recipe) {
            $responseList[] = $this->mapRecipeToDTO($recipe);
        }

        return $this->json($responseList);
    }

   
    #[Route('/{recipeId}', name: 'delete_recipe', methods: ['DELETE'])]
    public function deleteRecipe(int $recipeId): JsonResponse
    {
        
        $recipe = $this->entityManager->getRepository(Recipe::class)->find($recipeId);

        if (!$recipe || $recipe->isDeleted()) {
            return $this->json(['error' => 'La receta no existe o ya ha sido eliminada.'], Response::HTTP_NOT_FOUND);
        }

        $recipe->setIsDeleted(true);

        $this->entityManager->flush();

        return $this->json($this->mapRecipeToDTO($recipe), Response::HTTP_OK);
    }

    #[Route('/{recipeId}/rating/{rate}', name: 'rate_recipe', methods: ['POST'])]
    public function rateRecipe(int $recipeId, int $rate, Request $request): JsonResponse
    {
        if ($rate < 0 || $rate > 5) {
            return $this->json(['error' => 'El voto debe estar entre 0 y 5.'], Response::HTTP_BAD_REQUEST);
        }
        $recipe = $this->entityManager->getRepository(Recipe::class)->find($recipeId);
        if (!$recipe || $recipe->isDeleted()) {
            return $this->json(['error' => 'La receta no existe.'], Response::HTTP_NOT_FOUND);
        }

        $clientIp = $request->getClientIp();
        
        $existingRating = $this->entityManager->getRepository(Rating::class)->findOneBy([
            'recipe' => $recipe,
            'ipAddress' => $clientIp
        ]);

        if ($existingRating) {
            return $this->json(['error' => 'Ya has votado esta receta desde esta IP.'], Response::HTTP_BAD_REQUEST);
        }
        $rating = new Rating();
        $rating->setScore($rate);
        $rating->setIpAddress($clientIp ?? '127.0.0.1');
        $rating->setRecipe($recipe);

        $this->entityManager->persist($rating);
        $recipe->addRating($rating); // Ensure the relation is updated in memory for the DTO
        $this->entityManager->flush();

        return $this->json($this->mapRecipeToDTO($recipe), Response::HTTP_OK);
    }

    private function mapRecipeToDTO(Recipe $recipe): RecipeDTO
    {
        $typeDto = new RecipeTypeDTO(
            $recipe->getType()->getId(),
            $recipe->getType()->getName(),
            $recipe->getType()->getDescription()
        );

        $ingDtos = [];
        foreach ($recipe->getIngredients() as $ing) {
            $ingDtos[] = new IngredientDTO($ing->getName(), $ing->getQuantity(), $ing->getUnit());
        }

        $stepDtos = [];
        foreach ($recipe->getSteps() as $step) {
            $stepDtos[] = new StepDTO($step->getStepOrder(), $step->getDescription());
        }

        $nutDtos = [];
        foreach ($recipe->getRecipeNutrients() as $rn) {
            $nutDtos[] = [
                'id' => $rn->getId(), 
                'type' => new NutrientTypeDTO(
                    $rn->getNutrientType()->getId(),
                    $rn->getNutrientType()->getName(),
                    $rn->getNutrientType()->getUnit()
                ),
                'quantity' => $rn->getQuantity()
            ];
        }

        $ratings = $recipe->getRatings();
        $count = count($ratings);
        $sum = 0;
        foreach($ratings as $r) $sum += $r->getScore();
        $avg = $count > 0 ? $sum / $count : 0;
        
        $ratingObj = (object)[
            'number-votes' => $count,
            'rating-avg' => round($avg, 1)
        ];

        return new RecipeDTO(
            $recipe->getId(),
            $recipe->getTitle(),
            $recipe->getNumDiners(),
            $typeDto,
            $ingDtos,
            $stepDtos,
            $nutDtos,
            $ratingObj
        );
    }
}