<?php

namespace App\Controller;

use App\Entity\NutrientType;
use App\Entity\RecipeType;
use App\Model\NutrientTypeDTO;
use App\Model\RecipeTypeDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class MasterDataController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager) {}
    #[Route('/recipe-types', name: 'get_recipe_types', methods: ['GET'])]
    public function getRecipeTypes(): JsonResponse
    {
        // 1. Recuperar entidades (PDF pág. 52)
        $types = $this->entityManager->getRepository(RecipeType::class)->findAll();
        
        // 2. Convertir a DTOs
        $data = [];
        foreach ($types as $type) {
            $data[] = new RecipeTypeDTO($type->getId(), $type->getName(), $type->getDescription());
        }

        return $this->json($data);
    }

    #[Route('/nutrient-types', name: 'get_nutrient_types', methods: ['GET'])]
    public function getNutrientTypes(): JsonResponse
    {
        $types = $this->entityManager->getRepository(NutrientType::class)->findAll();
        
        $data = [];
        foreach ($types as $type) {
            $data[] = new NutrientTypeDTO($type->getId(), $type->getName(), $type->getUnit());
        }

        return $this->json($data);
    }
    // --- BORRADO LÓGICO (PDF pág. 28 y 52) ---
    // Endpoint: DELETE /recipes/{id}
    #[Route('/{id}', name: 'delete_recipe', methods: ['DELETE'])]
    public function deleteRecipe(int $id): JsonResponse
    {
        // 1. Buscamos la receta
        $recipe = $this->entityManager->getRepository(Recipe::class)->find($id);

        // 2. Validación: Debe existir y NO estar ya borrada
        if (!$recipe || $recipe->isIsDeleted()) {
            return $this->json(['error' => 'La receta no existe o ya ha sido eliminada.'], Response::HTTP_NOT_FOUND);
        }

        // 3. Borrado Lógico (No usamos remove(), solo cambiamos el flag)
        $recipe->setIsDeleted(true);

        // 4. Guardamos cambios
        $this->entityManager->flush();

        // 5. Devolvemos la receta (que ahora estará marcada como borrada internamente)
        // Para devolver el DTO, necesitaríamos mapear de nuevo. 
        // Por simplicidad, devolvemos un mensaje de éxito o el DTO básico.
        return $this->json(['message' => 'Receta eliminada correctamente (Borrado lógico).'], Response::HTTP_OK);
    }

    // --- VALORACIÓN (RATING) ---
    // Endpoint: POST /recipes/{recipeId}/rating/{rate}
    // Usamos path params para ID y Rate según tu YAML
    #[Route('/{recipeId}/rating/{rate}', name: 'rate_recipe', methods: ['POST'])]
    public function rateRecipe(int $recipeId, int $rate, Request $request): JsonResponse
    {
        // 1. Validación de Rango (0-5)
        if ($rate < 0 || $rate > 5) {
            return $this->json(['error' => 'El voto debe estar entre 0 y 5.'], Response::HTTP_BAD_REQUEST);
        }

        // 2. Validar que la receta existe
        $recipe = $this->entityManager->getRepository(Recipe::class)->find($recipeId);
        if (!$recipe || $recipe->isIsDeleted()) {
            return $this->json(['error' => 'La receta no existe.'], Response::HTTP_NOT_FOUND);
        }

        // 3. Validar IP única (Regla de negocio: "no puede haber más de 1 voto con una misma IP")
        $clientIp = $request->getClientIp(); // Obtenemos IP de la request
        
        // Buscamos si ya existe un voto para esta receta y esta IP
        $existingRating = $this->entityManager->getRepository(Rating::class)->findOneBy([
            'recipe' => $recipe,
            'ipAddress' => $clientIp
        ]);

        if ($existingRating) {
            return $this->json(['error' => 'Ya has votado esta receta desde esta IP.'], Response::HTTP_BAD_REQUEST);
        }

        // 4. Crear el voto
        $rating = new Rating();
        $rating->setScore($rate);
        $rating->setIpAddress($clientIp ?? '127.0.0.1'); // Fallback por si acaso es null en local
        $rating->setRecipe($recipe);

        $this->entityManager->persist($rating);
        $this->entityManager->flush();

        return $this->json(['message' => 'Voto registrado correctamente.'], Response::HTTP_OK);
    } 
}