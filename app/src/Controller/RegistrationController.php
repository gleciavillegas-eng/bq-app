<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $errors = [];
        $data = ['username' => '', 'fullname' => '', 'email' => '', 'phone' => ''];

        if ($request->isMethod('POST')) {
            $data['username'] = trim((string) $request->request->get('username'));
            $data['fullname'] = trim((string) $request->request->get('fullname'));
            $data['email'] = trim((string) $request->request->get('email'));
            $data['phone'] = trim((string) $request->request->get('phone'));
            $password = (string) $request->request->get('password');
            $confirm = (string) $request->request->get('confirmPassword');
            $terms = $request->request->has('terms');

            if (!$this->isCsrfTokenValid('register', (string) $request->request->get('_token'))) {
                $errors[] = 'Invalid security token. Please try again.';
            }

            if (strlen($data['username']) < 4 || strlen($data['username']) > 30) {
                $errors[] = 'Username must be 4 to 30 characters.';
            }

            if ($data['fullname'] === '') {
                $errors[] = 'Full name is required.';
            } elseif (strlen($data['fullname']) > 120) {
                $errors[] = 'Full name is too long.';
            }

            if ($data['email'] === '') {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            if ($data['phone'] === '') {
                $errors[] = 'Phone number is required.';
            }

            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (!$terms) {
                $errors[] = 'You must agree to the Terms & Conditions.';
            }

            if (!$errors) {
                if ($users->findOneBy(['username' => $data['username']])) {
                    $errors[] = 'Username is already taken.';
                }
                if ($users->findOneBy(['email' => $data['email']])) {
                    $errors[] = 'Email address is already registered.';
                }
            }

            if (!$errors) {
                $user = new User();
                $user->setUsername($data['username']);
                $user->setName($data['fullname']);
                $user->setEmail($data['email']);
                $user->setPhone($data['phone']);
                $user->setRole('customer');
                $user->setPassword($hasher->hashPassword($user, $password));

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Your Burger Queen account has been created successfully. Please log in.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'errors' => $errors,
            'data' => $data,
        ]);
    }
}