<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReceiptController extends AbstractController
{
    #[Route('/receipt/{id}', name: 'app_receipt', requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $order = $em->getRepository(Order::class)->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        // Dili makita ang order sa ubang tawo
        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $this->render('receipt/show.html.twig', [
            'order' => $order,
        ]);
    }
}