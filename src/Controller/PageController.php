<?php

namespace App\Controller;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController {

    #[Route('/api/page/{id}', name: 'app_tiles_api', methods: ['GET','OPTIONS'])]
    public function getPageDataById(int $id, Request $request , PageRepository $pageRepository): Response
    {
        try {

            //? Répondre uniquement aux requêtes OPTIONS avec les en-têtes appropriés
            if ($request->isMethod('OPTIONS')) {
                
                return new Response('', 204, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, access-control-allow-origin',
                    'Access-Control-Max-Age' => '86400', 
                ]);
            }

            //? Rechercher la page dans la BDD avec son id
            $page = $pageRepository->find($id);
            // dd($page);
            //? Si la page demandée n'existe pas dans la BDD
            if (!isset($page)) {
                return $this->json(
                    ['erreur'=> 'La page N°'.$id.' n\'existe pas dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], //renvoie du json, uniquement depuis local host, et uniquelent sous forme de GET
                    []
                );
            }

            //? Si la page existe dans la BDD
            return $this->json(
                $page, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], //renvoie du json, uniquement depuis local host, et uniquelent sous forme de GET
                ['groups' => 'page:getById']
            );  
        
        //? En cas d'erreur inattendue, capter l'erreur rencontrée
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['Error' => $error->getMessage()],
                400,
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'POST, OPTIONS'], 
                []
            );
        }
    }
}
