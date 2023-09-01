<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\Utils;
use App\Service\Messaging;
use App\Service\ApiAuthentification;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserController extends AbstractController {
    //! Demander l'authentification et envoyer un mail de double authentification
    #[ROUTE('api/user/logIn', name:"app_api_user_login", methods: ['PATCH','OPTIONS'])]
    public function logInUser(ApiAuthentification $apiAuthentification, UserPasswordHasherInterface $userPasswordHasherInterface,  Request $request, SerializerInterface $serializerInterface, UserRepository $userRepository, Messaging $messaging, EntityManagerInterface $entityManagerInterface ):Response {
        
        try {

            //? Répondre uniquement aux requêtes OPTIONS avec les en-têtes appropriés
            if ($request->isMethod('OPTIONS')) {
                return new Response('', 204, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'PATCH, OPTION',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, access-control-allow-origin',
                    'Access-Control-Max-Age' => '86400', 
                ]);
            }

            //? Récupérer le contenu de la requête en provenance du front
            $json = $request->getContent();

            //? Vérifier que le json n'est pas vide
            if (!$json) {
                return $this->json(
                    ['message' => 'Je Json es vide ou n\'existe pas.'],
                    400,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Serializer le json
            $data = $serializerInterface->decode($json,'json');

            //? Nettoyer les données du json et les stocker dans des variables
            $email          = Utils::cleanInput($data['email']);
            $password       = Utils::cleanInput($data['password']);
            $currentTime    = new \DateTimeImmutable();

            //? Récupérer la clé de chiffrement
            $secretkey = $this->getParameter('token');

            //? Vérifier si les données du json ne sont pas vides
            if (empty($email) OR empty($password)) {
                return $this->json(
                    ['message' => 'Toutes les données ne sont pas renseignées.'],
                    400,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Vérifier si le format de l'adresse mail est valide
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json(
                    ['message' => 'Le format de l\'adresse email n\'est pas valide.'],
                    400,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }
            
            //? Appeller la méthode d'authentification du service ApiAuthentification pour vérifier si on peut connecter l'utilisateur
            if ($apiAuthentification->authentification($userPasswordHasherInterface ,$userRepository, $email, $password )) {

                //? Récupérer les données de l'utilisateur dans une instance $user
                $user = $userRepository->findOneBy(['email'=>$email]);

                //? Vérifier si l'utilisateur est actif
                if (!$user->isIsActiveUser()) {
                    return $this->json(
                        ['message' => 'L\'accès à votre compte a été suspendu par un administrateur.'],
                        400,
                        ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                        []
                    );
                }

                //? Récupérer la clé secrète pour générer un token avec la méthode genNewToken() du service ApiAuthentification
                $secretkey      = $this->getParameter('token');
                $token          = $apiAuthentification->genNewToken($email, $secretkey, $userRepository, 5);
                
                //? Récupérer les variables d'authentification du webmail pour utiliser la méthode sendEmail() du service Messaging    
                $mailLogin      = $this->getParameter('mailaccount');
                $mailPassword   = $this->getParameter('mailpassword');
            
                //? Définition des variables pour utiliser la méthode sendEmail() de la classe Messenging
                $hour           = date( "H:i:s", time());
                $mailObject     = mb_convert_encoding('Cécilia Orsi Coaching : authentification à double facteur', 'ISO-8859-1', 'UTF-8');
                $mailContent    = mb_convert_encoding("<img src='https://i.postimg.cc/mrR6JHNW/LOGO4.png'/>".
                                                      "<p>Bonjour ".$user->getFirstNameUser()." ! </p>".
                                                      "<p>Tu as essayé de te connecter à l'espace administrateur de Cécilia Orsi Coaching à ".$hour.". Pour confirmer ton identité et accéder à ton espace administrateur, cliques sur le lien suivant : </br>".
                                                      '<a href = "https://127.0.0.1:8000/api/user/logIn/'.$user->getId().'/'.$token.'">Accéder à l\'espace administrateur</a>', 'ISO-8859-1', 'UTF-8');
                
                //? Executer la méthode sendMail() de la classe Messenging
                $mailStatus = $messaging->sendEmail($mailLogin, $mailPassword, $user->getEmail(), $mailObject, $mailContent, $user->getFirstNameUser(), $user->getLastNameUser());
                
                //? Vérifier si l'envoi du mail à échoué
                if ($mailStatus != 'The email has been sent') {
                    return $this->json(
                        ['message' => 'Impossible d\'envoyer le mail de confirmation. Merci de réessayer plus tard.'],
                        500,
                        ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                        []
                    );
                }

                //? Mettre à jour l'heure de dernière authentification de l'utilisateur dans la BDD
                $user->setLastAuthUser($currentTime);
                $entityManagerInterface->persist($user);
                $entityManagerInterface->flush();

                //? Retourner un json pour avertir que la première étape de la connexion a réussie
                return $this->json(
                    ['message'=> 'Un email de double indentification vient d\'être envoyé à '.$user->getEmail()],
                    200, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                    []
                );
                
            } else {
                return $this->json(
                    ['message' => 'Connexion refusée : l\'identifiant et/ou le mot de passe n\'est pas correct'],
                    401,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

        //? En cas d'erreur inattendue, capter l'erreur rencontrée
        } catch (\Exception $error){

            //? Retourner un json pour détailler l'erreur inattendu
            return $this->json(
                ['message' =>$error->getMessage()],
                400,
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                []
            );
        }
    }
    
    //! Vérifier la double authentification et finaliser l'authentification
    #[ROUTE('api/user/logIn/{id}/{token}', name:"app_api_user_login_validation", methods: 'GET')]
    public function logInValidation(string $id, string $token, Request $request, SerializerInterface $serializerInterface, UserRepository $userRepository, ApiAuthentification $apiAuthentification):Response {
         
        //? Nettoyer les données
        $id = Utils::cleanInput($id);
        $token = Utils::cleanInput($token);
 
        //? Vérifier si l'utilisateur existe
        $user = $userRepository->find($id);
 
        if (!$user) {
            return $this->json(
                ['message' => 'This user does not exist in the database.'],
                400,
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                []
            );
        }
 
        //? Récupérer la secret key pour vérifier la validité du token avec la méthode verifyToken() du service ApiAuthentification
        $secretkey = $this->getParameter('token');
 
        //? Appeller la méthode verifyToken() de ApiAuthentification
        $checkToken = $apiAuthentification->verifyToken($token, $secretkey);

        //? Vérifier si les token est valide 
        if ($checkToken === "Signature verification failed") {
            header("Location: http://localhost:3000/managerApp/logIn/invalid-token");
            die();
        }
 
        //? Vérifier si le token est expiré
        if ($checkToken === "Expired token") {
            header("Location: http://localhost:3000/managerApp/logIn/expired-token");
            die();
        }
  
        //? Récupérer la clé secrète pour générer un token avec la méthode genNewToken() du service ApiAuthentification
        $secretkey      = $this->getParameter('token');
        $token          = $apiAuthentification->genNewToken($user->getEmail(), $secretkey, $userRepository, 1);

        //? Rediriger l'utilisateur vers le front avec le token
        header("Location: http://localhost:3000/managerApp/logIn/".$token."!".$id);
        die();
    }

    //! Récupérer le rôle d'un utilisateur
    #[Route('/api/user/role', name: 'app_user_role_api', methods: ['PATCH','OPTIONS'])]
    public function getUserRole(Request $request , UserRepository $userRepository,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $userPasswordHasherInterface, ApiAuthentification $apiAuthentification): Response {
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

            //? Vérifier si l'utilisateur existe
            $user = $userRepository->find($id);
            
            if (!$user) {
                return $this->json(
                    ['message' => 'L\'utilisateur n\'existe pas dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }
        
            //? Récupérer le rôle de l'utilsateur
            $role = $user->getRoles();
            $role = $role[count($role)-1];

            //? Récupérer son prénom
            $firstName = $user->getFirstNameUser();

            //? Créer un tableau à retourner dans le json
            $objectToReturn = [
                "role"        => $role,
                "firstName"   => $firstName
            ];

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                $objectToReturn,
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                []);

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message'=>'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                []);
        }
    }

    //! Vérifier la validité d'un token et d'en fournir un nouveau si ce dernier est expiré (sous certaines conditions)
    #[Route('/api/user/jwt/check', name: 'app_user_jwt_api', methods: ['PATCH','OPTIONS'])]
    public function checkTokenValidity(Request $request , UserRepository $userRepository, ApiAuthentification $apiAuthentification, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface): Response {
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
            
            if ($verifyToken !== true && $verifyToken !== "Expired token") {
                return $this->json(
                    ['message' => "expired-session"],
                    498, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }
            
            if ($verifyToken === "Expired token") {
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
                $id = Utils::cleanInput($data['idApplicant']);
                
                //? Vérifier si l'utilisateur existe
                $user = $userRepository->findOneBy(['id' => $id, 'isActive_user' => 'true']);
             
                //? Si l'utilisateur n'existe pas ou n'est pas actif
                if (!isset($user)) {
                    return $this->json(
                        ['message'=> 'expired-session'],
                        498, 
                        ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                        []
                    );
                }
              
                //? Vérifier si la dernière demande de connexion a moins d'une heure
                $lastAuthTime = $user->getLastAuthUser();
                $lastUpdateTime = $user->getLastUpdateUser();
                $currentTime = new \DateTimeImmutable();
                $expirationTime = clone $lastAuthTime;
                $expirationTime->modify('+60 minutes');
                
                
                if ($lastAuthTime > $currentTime) {
                    return $this->json(
                        ['message'=> 'expired-session'],
                        498, 
                        ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                        []
                    );
                }
                //? Vérifier si l'heure de renouvellement (deniere authentification + 60 min) n'est pas dépassée
                if ($currentTime > $expirationTime ) {
                    return $this->json(
                        ['message'=> 'expired-session'],
                        206, 
                        ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                        []
                    );
                }
      
                //? Verifier si la date de dernière authentification n'est pas antérieure à la date de dernière modification
                if ($lastUpdateTime > $lastAuthTime ) {
                    return $this->json(
                        ['message'=> 'expired-session'],
                        498, 
                        ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                        []
                    );
                }
                
                //? Récupérer la clé secrète pour générer un token avec la méthode genNewToken() du service ApiAuthentification
                $secretkey      = $this->getParameter('token');
                $newToken       = $apiAuthentification->genNewToken($user->getEmail(), $secretkey, $userRepository, 5);
                
                //? Mettre à jour la date de dernière authentification dans la BDD
                $user->setLastAuthUser($currentTime);
                $entityManagerInterface->persist($user);
                $entityManagerInterface->flush();
                
                //? Renvoyer un nouveau token
                return $this->json(
                    $newToken, 
                    200, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH']
                ); 
            }

            return $this->json(
                $jwt, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH']
            ); 

        //? En cas d'erreur inattendue, capter l'erreur rencontrée
        } catch (\Exception $error) {
            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message' => $error->getMessage()],
                400,
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'PATCH'], 
                []
            );
        }
    }

    //! Récupérer les informations de son compte
    #[Route('/api/user/account', name: 'app_users_account_api', methods: ['PATCH','OPTIONS'])]
    public function getUserAccount(Request $request , UserRepository $userRepository, ApiAuthentification $apiAuthentification,SerializerInterface $serializerInterface): Response {
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

            //? Récupérer le contenu de la requête en provenance du front (tout ce qui se trouve dans le body de la requête)
            $json = $request->getContent();

            //? Vérifier si le json n'est pas vide
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
            $idApplicant = Utils::cleanInput($data['idApplicant']);

            //? Rechercher les utilisateurs dans la base de données
            $user = $userRepository->find($idApplicant);

            //? Vérifier si l'utilisateur existe
            if (!isset($user)) {
                return $this->json(
                    ['message'=> 'L\'utilisateur n\'existe pas aps la base de données'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si l'utilisateur existe
            return $this->json(
                $user, 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'], 
                ['groups' => 'user:getAll']
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

    //! Mettre à jour les informations de son compte
    #[Route('/api/user/account/update', name: 'app_user_account_upadate_api', methods: ['PATCH','OPTIONS'])]
    public function updateUserAccount(Request $request , UserRepository $userRepository,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $userPasswordHasherInterface, ApiAuthentification $apiAuthentification): Response {
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

            //? Récupérer le contenu de la requête en provenance du front (tout ce qui se trouve dans le body de la requête)
            $json = $request->getContent();

            //? Vérifier si le json n'est pas vide
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
            $firstName                  = Utils::cleanInput($data['firstName']);
            $lastName                   = Utils::cleanInput($data['lastName']);
            $email                      = Utils::cleanInput($data['email']);     
            $password                   = Utils::cleanInput($data['password']);
            $currentTime                = new \DateTimeImmutable();
                
            //? Vérifier si l'utilisateur existe
            $user = $userRepository->find($id);
            
            if (!$user) {
                return $this->json(
                    ['message' => 'L\'utilisateur n\'existe pas dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Vérifier si le format de l'adresse mail est valide
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            
                return $this->json(
                    ['message' => 'Le format de l\'adresse email n\'est pas valide.'],
                    422,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }
        
            //? Instancier un objet User et setter ses propriétés
            $user->setFirstNameUser($firstName);
            $user->setLastNameUser($lastName);
            $user->setEmail($email);
            $user->setLastUpdateUser($currentTime);

            //? Si un nouveau mot de passe existe dans le json, setter le password
            if($password != "") {
                $user->setPassword($userPasswordHasherInterface->hashPassword($user, $password));
            }

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'Les informations de votre compte ont bien été mises à jour'],
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                []);

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message'=>'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'],
                []);
        }
    }

}
 
