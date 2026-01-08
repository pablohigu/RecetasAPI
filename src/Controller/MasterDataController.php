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
}