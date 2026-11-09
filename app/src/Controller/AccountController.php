<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Pinakabag-ong 10 ka order
        $orders = $em->getRepository(Order::class)->findBy(
            ['user' => $this->getUser()],
            ['id' => 'DESC'],
            10
        );

        return $this->render('account/index.html.twig', [
            'orders' => $orders,
        ]);
    }
}