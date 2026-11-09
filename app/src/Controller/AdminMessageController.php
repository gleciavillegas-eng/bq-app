<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminMessageController extends AbstractController
{
    #[Route('/admin/messages', name: 'app_admin_messages')]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/messages.html.twig', [
            'messages' => $em->getRepository(ContactMessage::class)->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/messages/{id}/delete', name: 'app_admin_message_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_message_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_admin_messages');
        }

        $message = $em->find(ContactMessage::class, $id);

        if (!$message) {
            $this->addFlash('error', 'Unable to delete message.');

            return $this->redirectToRoute('app_admin_messages');
        }

        $em->remove($message);
        $em->flush();

        $this->addFlash('success', 'Message deleted successfully.');

        return $this->redirectToRoute('app_admin_messages');
    }
}