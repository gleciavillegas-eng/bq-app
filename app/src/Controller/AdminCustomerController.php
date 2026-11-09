<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminCustomerController extends AbstractController
{
    #[Route('/admin/customers', name: 'app_admin_customers')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim((string) $request->query->get('search', ''));

        $qb = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', 'customer')
            ->orderBy('u.id', 'DESC');

        if ($search !== '') {
            $qb->andWhere('u.name LIKE :q OR u.username LIKE :q OR u.email LIKE :q OR u.phone LIKE :q')
                ->setParameter('q', '%' . $search . '%');
        }

        $customers = $qb->getQuery()->getResult();

        $totalCrowns = 0;
        foreach ($customers as $customer) {
            $totalCrowns += $customer->getCrowns();
        }

        return $this->render('admin/customers.html.twig', [
            'customers' => $customers,
            'search' => $search,
            'total_crowns' => $totalCrowns,
        ]);
    }

    #[Route('/admin/customers/{id}/delete', name: 'app_admin_customer_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_customer_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_admin_customers');
        }

        $customer = $em->find(User::class, $id);

        // Customer lang ang pwede i-delete, dili ang admin
        if (!$customer || $customer->getRole() !== 'customer') {
            $this->addFlash('error', 'Customer could not be found.');

            return $this->redirectToRoute('app_admin_customers');
        }

        // Ang orders ug messages magpabilin (user_id mahimong NULL)
        $em->remove($customer);
        $em->flush();

        $this->addFlash('success', 'Customer removed successfully.');

        return $this->redirectToRoute('app_admin_customers');
    }
}