<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminUserController extends AbstractController
{
    private const ROLES = ['admin', 'staff', 'customer'];

    /* =========================================================
       LIST + SEARCH + FILTER
    ========================================================= */

    #[Route('/admin/users', name: 'app_admin_users')]
    public function index(Request $request, UserRepository $users): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim((string) $request->query->get('search', ''));
        $role = trim((string) $request->query->get('role', ''));

        $qb = $users->createQueryBuilder('u')->orderBy('u.id', 'DESC');

        if (in_array($role, self::ROLES, true)) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        } else {
            $role = '';
        }

        if ($search !== '') {
            $qb->andWhere('u.name LIKE :q OR u.username LIKE :q OR u.email LIKE :q')
                ->setParameter('q', '%' . $search . '%');
        }

        return $this->render('admin/users.html.twig', [
            'users' => $qb->getQuery()->getResult(),
            'search' => $search,
            'role_filter' => $role,
            'roles' => self::ROLES,
            'count_all' => $users->count([]),
            'count_admin' => $users->count(['role' => 'admin']),
            'count_staff' => $users->count(['role' => 'staff']),
            'count_customer' => $users->count(['role' => 'customer']),
        ]);
    }

    /* =========================================================
       CREATE
    ========================================================= */

    #[Route('/admin/users/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = ['name' => '', 'username' => '', 'email' => '', 'phone' => '', 'role' => 'staff', 'status' => 'active'];
        $errors = [];

        if ($request->isMethod('POST')) {
            $data = $this->readData($request, $data);
            $password = (string) $request->request->get('password', '');

            if (!$this->isCsrfTokenValid('admin_user_form', (string) $request->request->get('_token'))) {
                $errors[] = 'Invalid security token. Please try again.';
            }

            $errors = array_merge($errors, $this->validate($data, null, $password, true, $users));

            if (!$errors) {
                $user = new User();
                $this->apply($user, $data);
                $user->setPassword($hasher->hashPassword($user, $password));

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Account "' . $user->getUsername() . '" created successfully.');

                return $this->redirectToRoute('app_admin_users');
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'mode' => 'new',
            'user' => null,
            'data' => $data,
            'errors' => $errors,
            'roles' => self::ROLES,
            'is_self' => false,
        ]);
    }

    /* =========================================================
       EDIT + RESET PASSWORD
    ========================================================= */

    #[Route('/admin/users/{id}/edit', name: 'app_admin_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $users->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $isSelf = $user->getId() === $this->getUser()->getId();

        $data = [
            'name' => $user->getName(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'role' => $user->getRole(),
            'status' => $user->getStatus(),
        ];
        $errors = [];

        if ($request->isMethod('POST')) {
            $data = $this->readData($request, $data);
            $password = (string) $request->request->get('password', '');

            // Dili usbon sa admin ang iyang kaugalingong role ug status (aron dili ma-lock out)
            if ($isSelf) {
                $data['role'] = $user->getRole();
                $data['status'] = $user->getStatus();
            }

            if (!$this->isCsrfTokenValid('admin_user_form', (string) $request->request->get('_token'))) {
                $errors[] = 'Invalid security token. Please try again.';
            }

            $errors = array_merge($errors, $this->validate($data, $user, $password, false, $users));

            if (!$errors) {
                $this->apply($user, $data);

                if ($password !== '') {
                    $user->setPassword($hasher->hashPassword($user, $password));
                }

                $em->flush();

                $this->addFlash('success', 'Account "' . $user->getUsername() . '" updated successfully.');

                return $this->redirectToRoute('app_admin_users');
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'mode' => 'edit',
            'user' => $user,
            'data' => $data,
            'errors' => $errors,
            'roles' => self::ROLES,
            'is_self' => $isSelf,
        ]);
    }

    /* =========================================================
       DISABLE / ENABLE
    ========================================================= */

    #[Route('/admin/users/{id}/status', name: 'app_admin_user_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleStatus(int $id, Request $request, UserRepository $users, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_user_status', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_admin_users');
        }

        $user = $users->find($id);

        if (!$user) {
            $this->addFlash('error', 'User not found.');

            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot disable your own account.');

            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->isActive()) {
            $user->setStatus(User::STATUS_DISABLED);
            $message = 'Account "' . $user->getUsername() . '" has been disabled.';
        } else {
            $user->setStatus(User::STATUS_ACTIVE);
            $message = 'Account "' . $user->getUsername() . '" has been enabled.';
        }

        $em->flush();

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_admin_users');
    }

    /* =========================================================
       DELETE
    ========================================================= */

    #[Route('/admin/users/{id}/delete', name: 'app_admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, UserRepository $users, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_user_delete', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('app_admin_users');
        }

        $user = $users->find($id);

        if (!$user) {
            $this->addFlash('error', 'User not found.');

            return $this->redirectToRoute('app_admin_users');
        }

        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot delete your own account.');

            return $this->redirectToRoute('app_admin_users');
        }

        $username = $user->getUsername();

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Account "' . $username . '" deleted successfully.');

        return $this->redirectToRoute('app_admin_users');
    }

    /* =========================================================
       HELPERS
    ========================================================= */

    private function readData(Request $request, array $data): array
    {
        return [
            'name' => trim((string) $request->request->get('name', '')),
            'username' => trim((string) $request->request->get('username', '')),
            'email' => trim((string) $request->request->get('email', '')),
            'phone' => trim((string) $request->request->get('phone', '')),
            'role' => trim((string) $request->request->get('role', $data['role'])),
            'status' => trim((string) $request->request->get('status', $data['status'])),
        ];
    }

    private function validate(array $data, ?User $existing, string $password, bool $passwordRequired, UserRepository $users): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Full name is required.';
        } elseif (strlen($data['name']) > 120) {
            $errors[] = 'Full name is too long.';
        }

        if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $data['username'])) {
            $errors[] = 'Username must be 4 to 30 characters (letters, numbers, and underscore only).';
        }

        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (strlen($data['phone']) > 40) {
            $errors[] = 'Phone number is too long.';
        }

        if (!in_array($data['role'], self::ROLES, true)) {
            $errors[] = 'Please select a valid role.';
        }

        if (!in_array($data['status'], ['active', 'disabled'], true)) {
            $errors[] = 'Please select a valid status.';
        }

        if ($passwordRequired && $password === '') {
            $errors[] = 'Password is required.';
        } elseif ($password !== '' && strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        // Unique username ug email
        $sameUsername = $users->findOneBy(['username' => $data['username']]);
        if ($sameUsername && (!$existing || $sameUsername->getId() !== $existing->getId())) {
            $errors[] = 'Username is already taken.';
        }

        $sameEmail = $users->findOneBy(['email' => $data['email']]);
        if ($sameEmail && (!$existing || $sameEmail->getId() !== $existing->getId())) {
            $errors[] = 'Email address is already registered.';
        }

        return $errors;
    }

    private function apply(User $user, array $data): void
    {
        $user->setName($data['name'])
            ->setUsername($data['username'])
            ->setEmail($data['email'])
            ->setPhone($data['phone'])
            ->setRole($data['role'])
            ->setStatus($data['status']);
    }
}