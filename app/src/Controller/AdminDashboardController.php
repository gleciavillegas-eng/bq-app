<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $em->getRepository(User::class);
        $messages = $em->getRepository(ContactMessage::class);

        $recentMessages = (int) $messages->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.createdAt >= :since')
            ->setParameter('since', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $users->count([]),
            'total_staff' => $users->count(['role' => 'staff']),
            'total_customers' => $users->count(['role' => 'customer']),
            'total_records' => $em->getRepository(Product::class)->count([]),
            'total_messages' => $messages->count([]),
            'recent_messages' => $recentMessages,
            'recent_contacts' => $messages->findBy([], ['createdAt' => 'DESC'], 5),
        ]);
    }
}