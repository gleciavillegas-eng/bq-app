<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderViewController extends AbstractController
{
    #[Route('/order-details/{id}', name: 'app_order_details', requirements: ['id' => '\d+'])]
    public function details(int $id, EntityManagerInterface $em): Response
    {
        $order = $this->findOwnOrder($id, $em);

        return $this->render('orders/details.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/track-order/{id}', name: 'app_track_order', requirements: ['id' => '\d+'])]
    public function track(int $id, EntityManagerInterface $em): Response
    {
        $order = $this->findOwnOrder($id, $em);

        // Delivery orders lang ang pwede i-track
        if ($order->getOrderType() !== 'delivery') {
            return $this->redirectToRoute('app_order_details', ['id' => $id]);
        }

        return $this->render('orders/track.html.twig', [
            'order' => $order,
        ]);
    }

    private function findOwnOrder(int $id, EntityManagerInterface $em): Order
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $order = $em->getRepository(Order::class)->findOneBy([
            'id' => $id,
            'user' => $this->getUser(),
        ]);

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $order;
    }
}