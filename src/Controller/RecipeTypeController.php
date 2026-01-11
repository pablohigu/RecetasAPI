<?php

namespace App\Controller;

use App\Entity\RecipeType;
use App\Model\RecipeTypeDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recipe-types')]
class RecipeTypeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('', name: 'get_recipe_types', methods: ['GET'])]
    public function getRecipeTypes(): JsonResponse
    {
      
        $types = $this->entityManager->getRepository(RecipeType::class)->findAll();
        
    
        $data = [];
        foreach ($types as $type) {
            $data[] = new RecipeTypeDTO($type->getId(), $type->getName(), $type->getDescription());
        }

        return $this->json($data);
    }
}
