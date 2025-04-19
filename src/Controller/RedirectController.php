<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RedirectController extends AbstractController
{
    #[Route('/', name: 'api_redirect_doc', methods: ['GET'])]
    public function index(): Response
    {
        return new RedirectResponse('/api/doc');
    }
}
