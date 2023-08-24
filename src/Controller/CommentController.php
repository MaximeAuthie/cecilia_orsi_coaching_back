<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Entity\Comment;
use App\Service\Messaging;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Service\Utils;
use App\Service\ApiAuthentification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class CommentController extends AbstractController {
    #[Route('/api/comment/validated', name: 'app_validated_comments_api', methods: ['GET','OPTIONS'])]
    public function getValidatedComments(Request $request , CommentRepository $commentRepository, EntityManagerInterface $entityManagerInterface): Response {
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

            //? Rechercher les commentaires dans la base de données
            
            $comments = $commentRepository->createQueryBuilder('c')
            ->where('c.user IS NOT NULL')
            ->getQuery()
            ->getResult();

            //? Si aucun commentaire n'est présent dans la BDD
            if (!isset($comments)) {
                return $this->json(
                    ['message'=> 'Aucun commentaire présent dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si des commentraires sont présents dans la BDD
            return $this->json(
                $comments, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                ['groups' => 'comment:getValidated']
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

    #[Route('/api/comment/add', name: 'app_validated_add_comment_api', methods: ['POST','OPTIONS'])] 
    public function addComment(Request $request , ArticleRepository $articleRepository, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, Messaging $messaging): Response {
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

            //?Récupérer le contenu de la requête en provenance du front (tout ce qui se trouve dans le body de la requête)
            $json = $request->getContent();

            //?On vérifie si le json n'est pas vide
            if (!$json) {
                return $this->json(
                    ['message' => 'Le json est vide ou n\'existe pas.'],
                    400,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                    [] );
            }

            //?On sérialise le json (on le change de format json -> tableau)
            $data = $serializerInterface->decode($json, 'json');

            //? On nettoie les donnée issues du json
            $authorName     = Utils::cleanInput($data['author_name_comment']);
            $authorEmail    = Utils::cleanInput($data['author_email_comment']);
            $date           = Utils::cleanInput($data['date_comment']);
            $content        = Utils::cleanInput($data['content_comment']);
            $articleId      = Utils::cleanInput($data['articleId']);
            
            //? Vérifier si la date est valide
            if (!Utils::isValidDatetime($date)) {
                return $this->json(
                    ['Error' => 'Le format de la date '.$date.' n\'est pas valide.'],
                    400,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                    [] );
            }

            //? Vérifier si le format de l'adresse mail est valide
            if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {

                return $this->json(
                    ['message' => 'L\adresse email '.$authorEmail.' n\'est pas valide.'],
                    422,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                    []
                );
            }

            // ?Instancier un objet Comment
            $comment = new Comment();
            $comment->setAuthorNameComment($authorName);
            $comment->setAuthorEmailComment($authorEmail);
            $comment->setDateComment(new \DateTimeImmutable($date));
            $comment->setContentComment($content);
            $comment->setIsValidatedComment(0);
            
            //? Créer une instance Article avec les données remontées de la BDD
            $article = $articleRepository->findOneBy(['id'=>$articleId]);
            
            //? Vérifier si l'article existe dans la BDD 
            if (!$article) {
                return $this->json(
                    ['message'=> 'L\'article N°'.$articleId.' n\'existe pas dans la BDD'],
                    400, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                    []);
            } else {
                $comment->setArticle($article); 
            }

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($comment);
            $entityManagerInterface->flush();

            //?Récupérer les variables d'authentification du webmail pour utiliser la méthode sendEmail() du service Messaging
            $emailLogin     = $this->getParameter('mailaccount');
            $emailPassword  = $this->getParameter('mailpassword');
            $emailContact   = $this->getParameter('mailcontact');

            //? Définition des variables pour utiliser la méthode sendEmail() de la classe Messenging
            $date           = date('d-m-y', time());
            $hour           = date('h:i:s', time());
            $emailObject    = mb_convert_encoding('Cécilia Orsi Coaching : nouveau commentaire de '.$authorName, 'ISO-8859-1', 'UTF-8');
            $emailContent   = mb_convert_encoding("<img src='https://i.postimg.cc/mrR6JHNW/LOGO4.png'/>".
                                                    "<p>Bonjour !</p>".
                                                    '<p>Un nouveau commentaire <strong>'.$authorName.'</strong>, envoyé le <strong>'.$date.' à '.$hour.'</strong>, a été posté pour l\'article <strong>'.$article->getTitleArticle().'</strong></br>'.
                                                    '<hr><p><strong><u>Contenu du message :</u></strong> </p><hr>'.
                                                    $content.'<hr><br>'.
                                                    '<a href="http://localhost:3000/manager-interface">Rendez-vous dans votre espace administrateur pour le valider.</a>'
                                                    , 'ISO-8859-1', 'UTF-8');

            //? Executer la méthode sendMail() du service Messenging
            $mailStatus = $messaging->sendEmail($emailLogin, $emailPassword, $emailContact, $emailObject, $emailContent, mb_convert_encoding('Cécilia', 'ISO-8859-1', 'UTF-8'), 'Orsi');
            
            //?Vérfier si l'envoie de l'email à échoué
            if ($mailStatus != 'The email has been sent') {
                return $this->json(
                    ['message' => 'Impossible d\'envoyer la notification par e-mail'],
                    500,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'POST'], 
                    []
                );
            }

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'Le commentaire à bien été ajouté à la BDD'],
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []);

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message'=> 'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []);
        }
    }

    #[Route('/api/comment/toValidate', name: 'app_not_validated_comment_api', methods: ['GET','OPTIONS'])] 
    public function getCommentsToValidate(Request $request , CommentRepository $commentRepository, ApiAuthentification $apiAuthentification): Response {
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

            //? Rechercher les commentaires dans la base de données
            $comments = $commentRepository->findBy(['user'=> null]);

            //? Si aucun commentaire n'est présent dans la BDD
            if (!isset($comments)) {
                return $this->json(
                    ['message'=> 'Aucun commentaire présent dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si des commentraires sont présents dans la BDD
            return $this->json(
                $comments, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                ['groups' => 'comment:getToValidate']
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

    #[Route('/api/comment/validate', name: 'app_validate_comment_api', methods: ['PATCH','OPTIONS'])]
    public function validateComment(Request $request , CommentRepository $commentRepository, UserRepository $userRepository ,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, ApiAuthentification $apiAuthentification): Response {
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
                    [] );
            }

            //?On sérialise le json (on le change de format json -> tableau)
            $data = $serializerInterface->decode($json, 'json');

            //? On nettoie les donnée issues du json
            $commentId     = Utils::cleanInput($data['commentId']);
            $userId       = Utils::cleanInput($data['userId']);

            //? Instancier un objet Comment
            $comment = $commentRepository->find($commentId);
            
            //? Vérifier si le commentaire existe dans la BDD 
            if (!$comment) {
                return $this->json(
                    ['erreur'=> 'Le commentaire N°'.$commentId.' n\'existe pas dans la BDD'],
                    400, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                    []);
            }

            //? Instancier un objet User
            $user = $userRepository->find($userId);

            //? Vérifier si le user existe dans la BDD 
            if (!$user) {
                return $this->json(
                    ['message'=> 'L\'utilisateur N°'.$userId.' n\'existe pas dans la BDD'],
                    400, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                    []);
            }

            //? Modifier la valeur de la propriété isValidated_comment et de user
            $comment->setIsValidatedComment(true);
            $comment->setUser($user);

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($comment);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'Le commentaire à bien été validé avec succès;'],
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

    #[Route('/api/comment/reject', name: 'app_reject_comment_api', methods: ['PATCH','OPTIONS'])]
    public function rejectComment(Request $request , CommentRepository $commentRepository, UserRepository $userRepository ,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, Messaging $messaging, apiAuthentification $apiAuthentification): Response {
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
                    [] );
            }

            //?On sérialise le json (on le change de format json -> tableau)
            $data = $serializerInterface->decode($json, 'json');

            //? On nettoie les donnée issues du json
            $commentId     = Utils::cleanInput($data['commentId']);
            $userId       = Utils::cleanInput($data['userId']);

            //? Instancier un objet Comment
            $comment = $commentRepository->find($commentId);
            
            //? Vérifier si le commentaire existe dans la BDD 
            if (!$comment) {
                return $this->json(
                    ['message'=> 'Le commentaire N°'.$commentId.' n\'existe pas dans la BDD'],
                    400, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                    []);
            }

            //? Instancier un objet User
            $user = $userRepository->find($userId);

            //? Vérifier si le user existe dans la BDD 
            if (!$user) {
                return $this->json(
                    ['message'=> 'L\'utilisateur N°'.$commentId.' n\'existe pas dans la BDD'],
                    400, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                    []);
            }

            //? Modifier la valeur de la propriété isValidated_comment et de user
            $comment->setIsValidatedComment(false);
            $comment->setUser($user);

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($comment);
            $entityManagerInterface->flush();
            
            //? Récupérer les variables d'authentification du webmail pour utiliser la méthode sendEmail() du service Messaging    
            $mailLogin      = $this->getParameter('mailaccount');
            $mailPassword   = $this->getParameter('mailpassword');

            //? Définition des variables pour utiliser la méthode sendEmail() de la classe Messenging
            $dateTimeComment    =$comment->getDateComment();
            $hour               = $dateTimeComment->format('H:i:s');
            $date               = $dateTimeComment->format('d-m-Y');
            $recipientName      = mb_convert_encoding($comment->getAuthorNameComment(), 'ISO-8859-1', 'UTF-8');
            $mailObject         = mb_convert_encoding('Cécilia Orsi Coaching : équipe de modération', 'ISO-8859-1', 'UTF-8');
            $mailContent        = mb_convert_encoding("<img src='https://i.postimg.cc/mrR6JHNW/LOGO4.png'/>".
                                                  "<p>Bonjour ".$comment->getAuthorNameComment()." ! </p>".
                                                  "<p>Nous t'informons que le commentaire que tu as laissé sur le site Cécilia Orsi Coaching le ".$date." à ".$hour." n'a pas été validé par notre équipe de modération car il ne respecte pas les règles éditées dans les mentions légales.</br>".
                                                  "Ton commentaire n'apparaitra donc pas sur le site. <br><br>".
                                                  "Merci de ta compréhension. <br><br>".
                                                  "Cécilia Orsi Coaching", 'ISO-8859-1', 'UTF-8');
            
            //? Executer la méthode sendMail() de la classe Messenging
            $mailStatus = $messaging->sendEmail($mailLogin, $mailPassword, $comment->getAuthorEmailComment(), $mailObject, $mailContent, $recipientName, '');

            //? Vérifier si l'envoi du mail à échoué
            if ($mailStatus != 'The email has been sent') {
                return $this->json(
                    ['message' => 'Impossible d\'envoyer le mail de confirmation. Merci de réessayer plus tard.'],
                    500,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'Le commentaire à bien été rejeté avec succès.'],
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