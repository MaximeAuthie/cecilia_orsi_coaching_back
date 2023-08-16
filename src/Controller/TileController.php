<?php

namespace App\Controller;

use App\Repository\TileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\Utils;
use App\Service\ApiAuthentification;
use Doctrine\ORM\EntityManagerInterface;

class TileController extends AbstractController {
    #[Route('/api/tile/all', name: 'app_tiles_api', methods: ['GET','OPTIONS'])]
    public function getAllTiles(Request $request , TileRepository $tilesRepository): Response {
        try {

            //? Répondre uniquement aux requêtes OPTIONS avec les en-têtes appropriés
            if ($request->isMethod('OPTIONS')) {
                
                return new Response('', 204, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, access-control-allow-origin',
                    'Access-Control-Max-Age' => '86400', 
                ]);
            }

            //? Rechercher les catégories dans la base de données
            $tiles = $tilesRepository->findAll();

            //? Si aucune catégorie n'est présente dans la BDD
            if (!isset($tiles)) {
                return $this->json(
                    ['message'=> 'Aucune catégorie présente dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si des catégories sont présentes dans la BDD
            return $this->json(
                $tiles, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                ['groups' => 'tile:getAll']
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

    #[Route('/api/tile/update', name: 'app_tile_update_api', methods: ['PATCH','OPTIONS'])]
    public function updateTile(Request $request , TileRepository $tileRepository, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ApiAuthentification $apiAuthentification): Response {
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
                    400, 
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
            $id                 = Utils::cleanInput($data['id']);
            $title              = Utils::cleanInput($data['title_tile']);
            $url                = Utils::cleanInput($data['img_url_tile']);
            
            //? Vérifier si la catégorie existe déjà
            $tile = $tileRepository->find($id);
            
            if (!$tile) {
                return $this->json(
                    ['message' => 'La tuile n\'existe pas dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Instancier un objet Category
            $tile->setTitleTile($title);
            $tile->setImgUrlTile($url);

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($tile);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'La tuile "'.$title.'" à bien été modifiée dans la BDD'],
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                []);

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['erreumessager'=> 'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                []);
        }
    }
    
}
