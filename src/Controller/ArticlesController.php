<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticlesController extends AbstractController
{
    #[Route('/articles', name: 'app_articles')]
    public function index(): Response
    {
        return $this->render('Pages/Articles/index.html.twig');
    }


    #[Route('/articles/{slug}', name: 'app_articles_slug')]
    public function pageWithSlug(string $slug): Response
    {
        return $this->render("Pages/Articles/$slug.html.twig");
    }
}