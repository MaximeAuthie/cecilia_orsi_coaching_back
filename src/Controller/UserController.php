<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\User;
use App\Service\Utils;
use App\Service\Messaging;
use App\Service\ApiAuthentification;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserController extends AbstractController {
    //! API pour vérifier la demande d'authentification et envoyer un mail de double authentification
    #[ROUTE('api/user/logIn', name:"app_api_user_login", methods: ['PATCH','OPTIONS'])]
    public function logInUser(ApiAuthentification $apiAuthentification, UserPasswordHasherInterface $userPasswordHasherInterface,  Request $request, SerializerInterface $serializerInterface, UserRepository $userRepository, Messaging $messaging ):Response {
        
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
            $email      = Utils::cleanInput($data['email']);
            $password   = Utils::cleanInput($data['password']);

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
                                                      '<a href = "https://127.0.0.1:8000/api/user/logIn/'.$user->getId().'/'.$token.'">Lien d\'activation</a>', 'ISO-8859-1', 'UTF-8');
                
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
    
    //! API pour vérifier la double authentification et finaliser l'authentification
    #[ROUTE('api/user/logIn/{id}/{token}', name:"app_api_user_login_validation", methods: 'GET')]
    public function logInValidation(string $id, string $token, Request $request, SerializerInterface $serializerInterface, UserRepository $userRepository, ApiAuthentification $apiAuthentification):Response {
         
        //? Nettoyer les données
        $id = Utils::cleanInput($id);
        $token = Utils::cleanInput($token);
 
        //? Vérifier si l'utilisateur existe
        $user = $userRepository->find($id);
 
        if (!$user) {
            return $this->json(
                ['Error' => 'This user does not exist in the database.'],
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
        $token          = $apiAuthentification->genNewToken($user->getEmail(), $secretkey, $userRepository, 60);

        //? Rediriger l'utilisateur vers le front avec le token
        header("Location: http://localhost:3000/managerApp/logIn/".$token);
        die();
    }
}
 
