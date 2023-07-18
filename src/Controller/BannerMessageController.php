<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BannerMessageController extends AbstractController
{
    #[Route('/banner/message', name: 'app_banner_message')]
    public function index(): Response
    {
        return $this->render('banner_message/index.html.twig', [
            'controller_name' => 'BannerMessageController',
        ]);
    }
}
