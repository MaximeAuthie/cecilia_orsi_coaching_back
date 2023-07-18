<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TileController extends AbstractController
{
    #[Route('/tile', name: 'app_tile')]
    public function index(): Response
    {
        return $this->render('tile/index.html.twig', [
            'controller_name' => 'TileController',
        ]);
    }
}
