<?php

namespace App\Controller;

use App\Entity\BannerText;
use App\Repository\BannerTextRepository;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\Utils;
use App\Service\ApiAuthentification;

class PageController extends AbstractController {

    //! Récupérer les données de toutes les pages de la BDD
    #[Route('/api/page/all', name: 'app_page_api', methods: ['GET','OPTIONS'])]
    public function getAllPages(Request $request , PageRepository $pageRepository): Response {
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
            $pages = $pageRepository->findAll();
            
            //? Si aucune page n'est présente dans la BDD
            if (!isset($pages)) {
                return $this->json(
                    ['message'=> 'Aucune page présente dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si des catégories sont présentes dans la BDD
            return $this->json(
                $pages, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                ['groups' => 'page:getAll']
            );
        
        //? En cas d'erreur inattendue, capter l'erreur rencontrée
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message' => $error->getMessage()],
                400,
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'POST, OPTIONS'], 
                []
            );
        }
    }

    //! Récupérer les données d'une page grace à son titre
    #[Route('/api/page/title/{title}', name: 'app_page_title_api', methods: ['GET','OPTIONS'])]
    public function getPageByTitle(string $title, Request $request , PageRepository $pageRepository): Response {
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
            $page = $pageRepository->findOneBy(['title_page'=>$title]);
            
            //? Vérifier si la page est présente dans la BDD
            if (!isset($page)) {
                return $this->json(
                    ['message'=> 'La page n\'est pas présente dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si la page est présente dans la BDD
            return $this->json(
                $page, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                ['groups' => 'page:getAll']
            );
        
        //? En cas d'erreur inattendue, capter l'erreur rencontrée
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message' => $error->getMessage()],
                400,
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'POST, OPTIONS'], 
                []
            );
        }
    }

    //! Mettre à jour une page dans la BDD
    #[Route('/api/page/update', name: 'app_page_update_api', methods: ['PATCH','OPTIONS'])]
    public function updatePage(Request $request , PageRepository $pageRepository, BannerTextRepository $bannerTextRepository, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ApiAuthentification $apiAuthentification): Response {
                
        try {

            //? Répondre uniquement aux requêtes OPTIONS avec les en-têtes appropriés
            if ($request->isMethod('OPTIONS')) {
                return new Response('', 204, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'PATCH, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, access-control-allow-origin',
                    'Access-Control-Max-Age' => '86400', 
                ]);
            }

            //? Récupérer les données nécessaires à la vérification du token
            $key = $this->getParameter('token');
            $jwt = $request->server->get('HTTP_AUTHORIZATION');
            $jwt = str_replace('Bearer ', '', $jwt);
            
            //? Vérifier si le token existe bien dans la requête
            if ($jwt == '') {
                return $this->json(
                    ['message' => 'Le token n\'existe pas.'],
                    401, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Executer la méthode verifyToken() du service ApiAthentification
            $verifyToken = $apiAuthentification->verifyToken($jwt,$key);

            if ($verifyToken !== true) {
                return $this->json(
                    ['message' => "Token invalide"],
                    498, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }
            
            //?Récupérer le contenu de la requête en provenance du front (tout ce qui se trouve dans le body de la requête)
            $json = $request->getContent();

            //?On vérifie si le json n'est pas vide
            if (!$json) {
                return $this->json(
                    ['message' => 'Le json est vide ou n\'existe pas.'],
                    400,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Sérializer le json (on le change de format json -> tableau)
            $data = $serializerInterface->decode($json, 'json');

            //? Nettoyer les données issues du json
            $id                         = Utils::cleanInput($data['id']);
            $bannerUrlPage              = Utils::cleanInput($data['banner_url_page']);
            $img1UrlPage                = Utils::cleanInput($data['img1_url_page']);
            $img2UrlPage                = Utils::cleanInput($data['img2_url_page']);
            $newBannerText              = $data['BannerTextsList'];

            //? Vérifier si la page existe déjà
            $page = $pageRepository->find($id);
            
            if (!$page) {
                return $this->json(
                    ['message' => 'La page n\'existe pas dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Stocker la liste des objets BannerText remontés de la BDD dans une variable
            $databaseBannerText = $page->getBannerTextsList()->toArray();
           
            //? Vérifier s'il faut supprimer des BannerText de la BDD
            $dbList = [];
            $jsonList= [];
            // Récupérer tous les id de BannerText en BDD
            foreach ($databaseBannerText as $item) {  
                $dbList[] = $item->getId();
            }
            // Récupérer tous les id de BannerText dans le json
            foreach($newBannerText as $item) { 
                $jsonList[] =  $item['id'];
            }

            // Stocker les id présents dans $dbList qui ne sont pas dans $jsonList
            $result = array_diff($dbList, $jsonList);

            //Supprimer les bannerList en trop de la BDD
            foreach($result as $item) { 
                $bannerTextToDelete = $bannerTextRepository->find($item);
                $entityManagerInterface->remove($bannerTextToDelete);
                $entityManagerInterface->flush();
            }

            //? Vérifier si les BannerText du json existent existent déjà dans la BDD et les créer si besoin
            if (isset($data['BannerTextsList'])) {
                foreach($data['BannerTextsList'] as $value) {
                    $bannerText = $bannerTextRepository->find($value['id']);
                    if ($bannerText) {
                        if ($bannerText->getContentBannerText() != $value['content_banner_text']) {
                            $bannerText->setContentBannerText($value['content_banner_text']);
                        } else {
                            continue;
                        }
                    } else {
                        $bannerText = new BannerText();
                        $bannerText->setContentBannerText(Utils::cleanInput($value['content_banner_text']));
                        $bannerText->setPage($page);
                    }

                    $entityManagerInterface->persist($bannerText);
                    $entityManagerInterface->flush();
                }
            }
            
            //? Instancier un objet Category
            $page->setBannerUrlPage($bannerUrlPage);
            $page->setImg1UrlPage($img1UrlPage);
            $page->setImg2UrlPage($img2UrlPage);
 

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($page);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'La page à bien été modifiée dans la BDD'],
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                []);

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message'=> 'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                []);
        }
    }
  
}
