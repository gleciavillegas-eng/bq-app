<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/our-story', name: 'app_our_story')]
    public function ourStory(): Response
    {
        return $this->render('page/our_story.html.twig');
    }

    #[Route('/royal-club', name: 'app_royal_club')]
    public function royalClub(): Response
    {
        return $this->render('page/royal_club.html.twig');
    }
}