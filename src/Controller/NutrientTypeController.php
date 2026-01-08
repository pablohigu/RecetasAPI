<?php

namespace App\Controller;

use App\Entity\NutrientType;
use App\Model\NutrientTypeDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/nutrient-types')]
class NutrientTypeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('', name: 'get_nutrient_types', methods: ['GET'])]
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
