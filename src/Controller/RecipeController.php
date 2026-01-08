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

// Definimos la ruta base para este controlador (opcional, pero organizado)
#[Route('/recipes')]
class RecipeController extends AbstractController
{
    // Inyectamos el EntityManager en el constructor (PDF pág. 52)
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    // POST: Crear nueva receta (PDF pág. 30 y 33)
    #[Route('', name: 'post_recipe', methods: ['POST'], format: 'json')]
    public function createRecipe(
        #[MapRequestPayload] RecipeNewDTO $recipeDto
    ): JsonResponse
    {
        // 1. Buscar el Tipo de Receta en BBDD (Validación de negocio: debe existir)
        $recipeType = $this->entityManager->getRepository(RecipeType::class)->find($recipeDto->typeId);
        
        if (!$recipeType) {
            return $this->json(['error' => 'El tipo de receta especificado no existe.'], Response::HTTP_BAD_REQUEST);
        }

        // 2. Crear la Entidad Receta (Mapping manual DTO -> Entidad)
        $recipe = new Recipe();
        $recipe->setTitle($recipeDto->title);
        $recipe->setNumDiners($recipeDto->numberDiner);
        $recipe->setType($recipeType); // Relación ManyToOne
        $recipe->setIsDeleted(false);  // Valor por defecto

        // 3. Procesar Ingredientes (Relación OneToMany)
        foreach ($recipeDto->ingredients as $ingDto) {
            $ingredient = new Ingredient();
            $ingredient->setName($ingDto->name);
            $ingredient->setQuantity($ingDto->quantity);
            $ingredient->setUnit($ingDto->unit);
            
            // Vincular con la receta (el método addIngredient lo hace Doctrine si usaste orphanRemoval, 
            // pero explícitamente es setRecipe)
            $ingredient->setRecipe($recipe);
            
            // Persistimos el ingrediente
            $this->entityManager->persist($ingredient);
        }

        // 4. Procesar Pasos (Relación OneToMany)
        foreach ($recipeDto->steps as $stepDto) {
            $step = new Step();
            $step->setDescription($stepDto->description);
            $step->setStepOrder($stepDto->order);
            $step->setRecipe($recipe);
            
            $this->entityManager->persist($step);
        }

        // 5. Procesar Nutrientes (Relación N-M con atributos)
        foreach ($recipeDto->nutrients as $nutDto) {
            // Buscar el Tipo de Nutriente en BBDD
            $nutrientType = $this->entityManager->getRepository(NutrientType::class)->find($nutDto->typeId);
            
            if (!$nutrientType) {
                // Si falla un nutriente, podrías devolver error o ignorarlo. Aquí devolvemos error.
                return $this->json(['error' => "El tipo de nutriente ID {$nutDto->typeId} no existe."], Response::HTTP_BAD_REQUEST);
            }

            $recipeNutrient = new RecipeNutrient();
            $recipeNutrient->setQuantity($nutDto->quantity);
            $recipeNutrient->setNutrientType($nutrientType); // Vinculamos el tipo
            $recipeNutrient->setRecipe($recipe);             // Vinculamos la receta
            
            $this->entityManager->persist($recipeNutrient);
        }

        // 6. Guardar la Receta Principal
        $this->entityManager->persist($recipe);

        // 7. Ejecutar cambios en BBDD (Transacción)
        $this->entityManager->flush();

        // 8. Responder
        return $this->json([
            'message' => 'Receta creada con éxito',
            'id' => $recipe->getId(),
            'title' => $recipe->getTitle()
        ], Response::HTTP_OK);
    }
    public function getRecipes(
        #[MapQueryParameter] ?int $type = null // Parámetro opcional de URL (PDF pág. 23)
    ): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Recipe::class);

        // Filtrado básico
        if ($type) {
            // Buscamos recetas por el ID del tipo y que NO estén borradas
            $recipes = $repo->findBy(['type' => $type, 'isDeleted' => false]);
        } else {
            $recipes = $repo->findBy(['isDeleted' => false]);
        }

        // Mapeo manual de Entidad -> DTO de Respuesta
        $responseList = [];
        
        foreach ($recipes as $recipe) {
            // 1. Mapear Tipo
            $typeDto = new RecipeTypeDTO(
                $recipe->getType()->getId(),
                $recipe->getType()->getName(),
                $recipe->getType()->getDescription()
            );

            // 2. Mapear Ingredientes
            $ingDtos = [];
            foreach ($recipe->getIngredients() as $ing) {
                $ingDtos[] = new IngredientDTO($ing->getName(), $ing->getQuantity(), $ing->getUnit());
            }

            // 3. Mapear Pasos
            $stepDtos = [];
            foreach ($recipe->getSteps() as $step) {
                $stepDtos[] = new StepDTO($step->getStepOrder(), $step->getDescription());
            }

            // 4. Mapear Nutrientes (Especial cuidado aquí, accedemos a la tabla intermedia)
            $nutDtos = [];
            foreach ($recipe->getRecipeNutrients() as $rn) {
                // Estructura según YAML: { id, type: {NutrientTypeDTO}, quantity }
                // Ojo: simplificamos según lo que pide tu DTO de salida
                $nutDtos[] = [
                    'id' => $rn->getId(), // ID de la relación o del nutriente? YAML dice ID del Nutriente, pero suele ser el ID de la fila.
                    'type' => new NutrientTypeDTO(
                        $rn->getNutrientType()->getId(),
                        $rn->getNutrientType()->getName(),
                        $rn->getNutrientType()->getUnit()
                    ),
                    'quantity' => $rn->getQuantity()
                ];
            }
            
            // 5. Calcular Rating (Media y Total)
            $ratings = $recipe->getRatings();
            $count = count($ratings);
            $sum = 0;
            foreach($ratings as $r) $sum += $r->getScore();
            $avg = $count > 0 ? $sum / $count : 0;
            
            $ratingObj = (object)[
                'number-votes' => $count,
                'rating-avg' => round($avg, 1)
            ];

            // 6. Construir DTO final
            $responseList[] = new RecipeDTO(
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

        return $this->json($responseList);
    }
}