<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        return $this->render('profile/index.html.twig');
    }

    #[Route('/profile/password', name: 'app_profile_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        /** @var User $user */
        $user = $this->getUser();

        $current = (string) $request->request->get('current_password', '');
        $new = (string) $request->request->get('new_password', '');
        $confirm = (string) $request->request->get('confirm_password', '');

        if (!$this->isCsrfTokenValid('change_password', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
        } elseif (!$hasher->isPasswordValid($user, $current)) {
            $this->addFlash('error', 'Your current password is incorrect.');
        } elseif (strlen($new) < 8) {
            $this->addFlash('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            $this->addFlash('error', 'New password and confirmation do not match.');
        } elseif ($new === $current) {
            $this->addFlash('error', 'New password must be different from your current password.');
        } else {
            $user->setPassword($hasher->hashPassword($user, $new));
            $em->flush();

            $this->addFlash('success', 'Your password was changed successfully.');
        }

        return $this->redirectToRoute('app_profile');
    }
}