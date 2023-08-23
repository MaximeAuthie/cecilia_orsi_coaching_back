<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Keyword;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\KeywordRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Utils;
use App\Service\ApiAuthentification;

class ArticleController extends AbstractController
{
    #[Route('/api/article/validated/all', name: 'app_articles_published_api', methods: ['GET','OPTIONS'])]
    public function getAllPublishedArticles(Request $request , ArticleRepository $articleRepository): Response {
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

            //? Rechercher les articles dans la base de données
            $articles = $articleRepository->findBy(['isPublished_article' => 'true', 'isActive_article' => 'true']);
            
            //? Si aucun article n'est présent dans la BDD
            if (!isset($articles)) {
                return $this->json(
                    ['erreur'=> 'Aucun article présent dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si des articles sont présents dans la BDD
            return $this->json(
                $articles, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                ['groups' => 'article:getAll']
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

    #[Route('/api/article/all', name: 'app_articles_api', methods: ['GET','OPTIONS'])]
    public function getAllArticles(Request $request , ArticleRepository $articleRepository, ApiAuthentification $apiAuthentification): Response {
        try {

            // //? Répondre uniquement aux requêtes OPTIONS avec les en-têtes appropriés
            if ($request->isMethod('OPTIONS')) {
                
                return new Response('', 204, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'POST, OPTIONS',
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

            //? Rechercher les articles dans la base de données
            $articles = $articleRepository->findBy(['isActive_article' => 'true']);
            
            //? Si aucun article n'est présent dans la BDD
            if (!isset($articles)) {
                return $this->json(
                    ['erreur'=> 'Aucun article présent dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si des articles sont présents dans la BDD
            return $this->json(
                $articles, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                ['groups' => 'article:getAll']
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

    #[Route('/api/article/update', name: 'app_article_update_api', methods: ['PATCH','OPTIONS'])]
    public function updateArticle(Request $request , ArticleRepository $articleRepository, KeywordRepository $keywordRepository, CategoryRepository $categoryRepository,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ApiAuthentification $apiAuthentification): Response {
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

            //? Nettoyer les données issues du json et les stocker dans des variables
            $id                         = Utils::cleanInput($data['id']);
            $title                      = Utils::cleanInput($data['title_article']);
            $date                       = new \DateTimeImmutable();
            $bannerUrl                  = Utils::cleanInput($data['banner_url_article']);
            $description                = Utils::cleanInput($data['description_article']);
            $content                    = Utils::cleanInputArticleContent($data['content_article']);
            $summary                    = substr($content,0, 380).'...';
            $newCategories              = $data['categories_list'];
            $newKeywords                = $data['kewords_list'];
           
            //? Vérifier si l'article existe
            $article = $articleRepository->find($id);
            
            if (!$article) {
                return $this->json(
                    ['message' => 'La l\'article n\'existe pas dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Stocker la liste des objets BannerText remontés de la BDD dans une variable
            $databaseCategories = $article->getCategoriesList()->toArray();
           
            //? Vérifier s'il faut supprimer des BannerText de la BDD
            $dbList = [];
            $jsonList= [];

            // Récupérer tous les id de BannerText en BDD
            foreach ($databaseCategories as $item) {  
                $dbList[] = $item->getId();
            }
            // Récupérer tous les id de BannerText dans le json
            foreach($newCategories as $item) { 
                $jsonList[] =  Utils::cleanInput($item['id']);
            }

            // Stocker les id présents dans $dbList qui ne sont pas dans $jsonList
            $result = array_diff($dbList, $jsonList);

            //Supprimer les bannerList en trop de la BDD
            foreach($result as $item) { 
                $categoryToDelete = $categoryRepository->find($item);
                $article->removeCategoriesList($categoryToDelete);
            }
            
            //? Vérifier si les catégories existent
            if (isset($newCategories)) {
                foreach ($newCategories as $item) {
                    $categorie = $categoryRepository->find(Utils::cleanInput($item['id']));

                    if (!$categorie) {
                        return $this->json(
                            ['erreur'=> 'La catégorie '.$item['name_category'].' n\'existe pas dans la BDD'],
                            400, 
                            ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'localhost', 'Access-Control-Allow-Method' => 'GET'], //renvoie du json, uniquement depuis local host, et uniquelent sous forme de GET
                            []
                        );
                    } else {
                        $article->addCategoriesList($categorie);
                    }
                }
            }

            //? Stocker la liste des objets BannerText remontés de la BDD dans une variable
            $databaseKeywords = $article->getKewordsList()->toArray();
           
            //? Vérifier s'il faut supprimer des BannerText de la BDD
            $dbList = [];
            $jsonList= [];

            // Récupérer tous les id de BannerText en BDD
            foreach ($databaseKeywords as $item) {  
                $dbList[] = $item->getId();
            }
            // Récupérer tous les id de BannerText dans le json
            foreach($newKeywords as $item) { 
                $jsonList[] =  $item['id'];
            }

            // Stocker les id présents dans $dbList qui ne sont pas dans $jsonList
            $result = array_diff($dbList, $jsonList);

            //Supprimer les bannerList en trop de la BDD
            foreach($result as $item) { 
                $keywordToDelete = $keywordRepository->find($item);
                $entityManagerInterface->remove($keywordToDelete);
                $entityManagerInterface->flush();
            }

            //? Vérifier si les BannerText du json existent existent déjà dans la BDD et les créer si besoin
            if (isset($newKeywords)) {
                foreach($newKeywords as $item) {
                    $keywordToAdd = $keywordRepository->find($item['id']);
                    if ($keywordToAdd) {
                        if ($keywordToAdd->getContentKeywork() != $item['content_keywork']) {
                            $keywordToAdd->setContentKeywork($item['content_keywork']);
                        } else {
                            continue;
                        }
                    } else {
                        $keywordToAdd = new Keyword();
                        $keywordToAdd->setContentKeywork(Utils::cleanInput($item['content_keywork']));
                        $keywordToAdd->setArticle($article);
                    }

                    $entityManagerInterface->persist($keywordToAdd);
                    $entityManagerInterface->flush();
                }
            }
            
            //? Instancier un objet Article et setter ses propriétés
            $article->setTitleArticle($title);
            $article->setDateArticle($date);
            $article->setBannerUrlArticle($bannerUrl);
            $article->setDescriptionArticle($description);
            $article->setSummaryArticle($summary);
            $article->setContentArticle($content);
 

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($article);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'L\'article à bien été modifiée dans la BDD'],
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

    #[Route('/api/article/publish', name: 'app_article_publish_api', methods: ['PATCH','OPTIONS'])]
    public function publishArticle(Request $request , ArticleRepository $articleRepository, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ApiAuthentification $apiAuthentification): Response {
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

            //? Nettoyer les données issues du json et les stocker dans des variables
            $id = Utils::cleanInput($data['id']);
    

            //? Vérifier si l'article existe
            $article = $articleRepository->find($id);
            
            if (!$article) {
                return $this->json(
                    ['message' => 'La l\'article n\'existe pas dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Instancier une variable $message qui sera implémenté en fonction du cas de figue et renvoyé via json
            $message = '';

            //? Setter la propriété isPublishedArticle en fonction du cas de figure
            if ($article->isIsPublishedArticle()) {
                $article->setIsPublishedArticle(false);
                $message = "L'article n'est plus publié sur le site.";
            } else {
                $article->setIsPublishedArticle(true);
                $message = "L'article est publié sur le site.";
            }
            

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($article);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> $message],
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

    #[Route('/api/article/add', name: 'app_article_add_api', methods: ['POST','OPTIONS'])]
    public function addArticle(Request $request , UserRepository $userRepository,  CategoryRepository $categoryRepository,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ApiAuthentification $apiAuthentification): Response {
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
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                    []
                );
            }

            //? Sérializer le json (on le change de format json -> tableau)
            $data = $serializerInterface->decode($json, 'json');

            //? Nettoyer les données issues du json et les stocker dans des variables
            $title              = Utils::cleanInput($data['title_article']);
            $bannerUrl          = Utils::cleanInput($data['banner_url_article']);
            $date               = new \DateTimeImmutable();
            $description        = Utils::cleanInput($data['description_article']);
            $content            = Utils::cleanInputArticleContent($data['content_article']);
            $summary            = substr($content,0, 380).'...';
            $userId             = Utils::cleanInput($data['user_id']);
            $keywordsList       = $data['kewords_list'];
            $categoriesList     = $data['categories_list'];
            
            //? Vérifier si le user existe
            $user = $userRepository->find($userId);

            if (!$user) {
                return $this->json(
                    ['message' => 'La l\'utilisateur n\'existe pas dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                    []
                );
            }

            

            //? Instancier un nouvel article et setter ses propriétés
            $article = new Article();
            $article->setTitleArticle($title);
            $article->setBannerUrlArticle($bannerUrl);
            $article->setDateArticle($date);
            $article->setDescriptionArticle($description);
            $article->setContentArticle($content);
            $article->setSummaryArticle($summary);
            $article->setIsPublishedArticle(false);
            $article->setIsActiveArticle(true);
            $article->setUser($user);

            foreach ($categoriesList as $item) {
                $categorie = $categoryRepository->find($item['id']);
                $article->addCategoriesList($categorie);
            }
            

            //? Persiter et flush des données pour les insérerb l'article en BDD
            $entityManagerInterface->persist($article);
            $entityManagerInterface->flush();

            //? Ajouter les keywords à la BDD
            if ($keywordsList) {
                foreach($keywordsList as $item) { 
                    $keywordToAdd = new Keyword();
                    $keywordToAdd->setContentKeywork(Utils::cleanInput($item['content_keywork']));
                    $keywordToAdd->setArticle($article);
                    $entityManagerInterface->persist($keywordToAdd);
                    $entityManagerInterface->flush();
                }
            }
            
            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message' => 'Article ajouté avec succès.'], 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                ['groups' => 'article:getAll']
            );

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['erreumessager'=> 'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []
            );
        }
    }

    #[Route('/api/article/disable', name: 'app_article_disable_api', methods: ['PATCH','OPTIONS'])]
    public function disableArticle(Request $request , ArticleRepository $articleRepository,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ApiAuthentification $apiAuthentification): Response {
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

            //? Nettoyer les données issues du json et les stocker dans des variables
            $id     = Utils::cleanInput($data['id']);
            $title   = Utils::cleanInput($data['title']);

            //? Vérifier si l'article existe
            $article = $articleRepository->find($id);
            
            if (!$article) {
                return $this->json(
                    ['message' => 'La l\'article n\'existe pas dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Instancier une variable $message qui sera implémenté en fonction du cas de figue et renvoyé via json
            $message = '';

            //? Setter la propriété isPublishedArticle en fonction du cas de figure
            $article->setIsActiveArticle(false);
            

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($article);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'L\'article '.$title.' a été désactivé.'],
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
