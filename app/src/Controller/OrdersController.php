<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrdersController extends AbstractController
{
    #[Route('/orders', name: 'app_orders')]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $orders = $em->getRepository(Order::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        $active = 0;
        $completed = 0;
        $spent = 0.0;

        foreach ($orders as $order) {
            $status = strtolower(trim($order->getStatus()));

            if (in_array($status, ['pending', 'processing', 'preparing', 'out for delivery'], true)) {
                $active++;
            }

            if (in_array($status, ['completed', 'delivered'], true)) {
                $completed++;
            }

            $spent += (float) $order->getTotal();
        }

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
            'active_orders' => $active,
            'completed_orders' => $completed,
            'total_spent' => $spent,
        ]);
    }
}