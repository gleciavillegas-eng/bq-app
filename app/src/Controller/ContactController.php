<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $data = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];

        if ($user = $this->getUser()) {
            $data['name'] = $user->getName();
            $data['email'] = $user->getEmail();
            $data['phone'] = $user->getPhone();
        }

        if ($request->isMethod('POST')) {
            $data['name'] = trim((string) $request->request->get('name'));
            $data['email'] = trim((string) $request->request->get('email'));
            $data['phone'] = trim((string) $request->request->get('phone'));
            $data['message'] = trim((string) $request->request->get('message'));

            $errors = [];

            if (!$this->isCsrfTokenValid('contact', (string) $request->request->get('_token'))) {
                $errors[] = 'Invalid security token. Please try again.';
            }

            if ($data['name'] === '') {
                $errors[] = 'Please enter your name.';
            } elseif (strlen($data['name']) > 120) {
                $errors[] = 'Your name must not exceed 120 characters.';
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            if (strlen($data['phone']) > 40) {
                $errors[] = 'Your phone number must not exceed 40 characters.';
            }

            if ($data['message'] === '') {
                $errors[] = 'Please enter your message.';
            } elseif (strlen($data['message']) > 2000) {
                $errors[] = 'Your message must not exceed 2000 characters.';
            }

            if ($errors) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            } else {
                $contact = new ContactMessage();
                $contact->setUser($this->getUser());
                $contact->setName($data['name']);
                $contact->setEmail($data['email']);
                $contact->setPhone($data['phone'] !== '' ? $data['phone'] : null);
                $contact->setMessage($data['message']);

                $em->persist($contact);
                $em->flush();

                $this->addFlash('success', 'Thank you, ' . $data['name'] . '! Your message has been received.');

                return $this->redirectToRoute('app_contact');
            }
        }

        return $this->render('contact/index.html.twig', ['data' => $data]);
    }
}