<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserAdminController extends AbstractController
{
    #[Route('/user/admin', name: 'app_user_admin')]
    public function index(): Response
    {
        return $this->render('user_admin/index.html.twig', [
            'controller_name' => 'UserAdminController',
        ]);
    }
}
