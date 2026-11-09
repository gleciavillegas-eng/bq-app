<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminOrderController extends AbstractController
{
    private const STATUSES = ['Pending', 'Processing', 'Out for Delivery', 'Completed', 'Cancelled'];

    #[Route('/admin/orders', name: 'app_admin_orders')]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $orders = $em->getRepository(Order::class)->findBy([], ['id' => 'DESC']);

        $stats = [
            'pending' => 0,
            'processing' => 0,
            'delivery' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        $revenue = 0.0;

        foreach ($orders as $order) {
            $status = strtolower(trim($order->getStatus()));

            switch ($status) {
                case 'processing':
                    $stats['processing']++;
                    break;
                case 'out for delivery':
                    $stats['delivery']++;
                    break;
                case 'completed':
                    $stats['completed']++;
                    break;
                case 'cancelled':
                    $stats['cancelled']++;
                    break;
                default:
                    $stats['pending']++;
            }

            if ($status !== 'cancelled') {
                $revenue += (float) $order->getTotal();
            }
        }

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders,
            'stats' => $stats,
            'revenue' => $revenue,
            'statuses' => self::STATUSES,
        ]);
    }

    #[Route('/admin/orders/{id}/status', name: 'app_admin_order_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(int $id, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $status = trim((string) $request->request->get('status'));

        if (!$this->isCsrfTokenValid('admin_order_status', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_admin_orders');
        }

        $order = $em->find(Order::class, $id);

        if (!$order || !in_array($status, self::STATUSES, true)) {
            $this->addFlash('error', 'Invalid order status.');

            return $this->redirectToRoute('app_admin_orders');
        }

        $oldStatus = $order->getStatus();

        if ($oldStatus !== $status) {
            // ₱10 = 1 crown, i-award sa pag-Completed ug i-bawi kung dili na Completed
            $user = $order->getUser();
            $crowns = (int) floor((float) $order->getTotal() / 10);

            if ($user) {
                if ($status === 'Completed' && $oldStatus !== 'Completed') {
                    $user->setCrowns($user->getCrowns() + $crowns);
                } elseif ($oldStatus === 'Completed' && $status !== 'Completed') {
                    $user->setCrowns(max(0, $user->getCrowns() - $crowns));
                }
            }

            $order->setStatus($status);
            $em->flush();
        }

        $this->addFlash('success', 'Order #' . $id . ' status updated successfully.');

        return $this->redirectToRoute('app_admin_orders');
    }
}