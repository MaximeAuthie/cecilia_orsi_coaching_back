<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\Utils;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserAdminController extends AbstractController
{
    #[Route('/api/user/all', name: 'app_users_all_api', methods: ['GET','OPTIONS'])]
    public function getAllUsers(Request $request , UserRepository $userRepository): Response {
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
            $users = $userRepository->findAll();

            //? Si aucune catégorie n'est présente dans la BDD
            if (!isset($users)) {
                return $this->json(
                    ['message'=> 'Aucun utilisateur présent dans la BDD.'],
                    206, 
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'GET'],
                    []
                );
            }

            //? Si des users sont présents dans la BDD
            return $this->json(
                $users, 
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

    #[Route('/api/user/update', name: 'app_user_update_api', methods: ['PATCH','OPTIONS'])]
    public function updateUser(Request $request , UserRepository $userRepository,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $userPasswordHasherInterface): Response {
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
            $firstName                  = Utils::cleanInput($data['firstName']);
            $lastName                   = Utils::cleanInput($data['lastName']);
            $email                      = Utils::cleanInput($data['email']);     
            $password                   = Utils::cleanInput($data['password']);

            //? Nettoyer les rôles issus du json
            $roles                      = [];

            foreach ($data['roles'] as $item) {
                $roles[] = Utils::cleanInput($item);   
            }


            //? Vérifier si l'utilisateur existe
            $user = $userRepository->find($id);
            
            if (!$user) {
                return $this->json(
                    ['message' => 'La l\'utilisateur n\'existe pas dans la BDD.'],
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
            $user->setRoles($roles);

            //? Si un nouveau mot de passe existe dans le json, setter le password
            if(isset($password)) {
                $user->setPassword($userPasswordHasherInterface->hashPassword($user, $password));
            }

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($user);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'L\'utilisateur à bien été mis à jour dans la BDD'],
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

    #[Route('/api/user/add', name: 'app_user_add_api', methods: ['POST','OPTIONS'])]
    public function addUser(Request $request , UserRepository $userRepository,SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface, UserPasswordHasherInterface $userPasswordHasherInterface): Response {
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
                    []
                );
            }

            //? Sérializer le json (on le change de format json -> tableau)
            $data = $serializerInterface->decode($json, 'json');

            //? Nettoyer les données issues du json et les stocker dans des variables
            $firstName                  = Utils::cleanInput($data['firstName']);
            $lastName                   = Utils::cleanInput($data['lastName']);
            $email                      = Utils::cleanInput($data['email']);     
            $password                   = Utils::cleanInput($data['password']);

            //? Nettoyer les rôles issus du json
            $roles                      = [];

            foreach ($data['roles'] as $item) {
                $roles[] = Utils::cleanInput($item);   
            }


            //? Vérifier si l'utilisateur existe
            $user = $userRepository->findOneBy(['email'=>$email]);
            
            if ($user) {
                return $this->json(
                    ['message' => 'La l\'utilisateur '.$email.' existe déjà dans la BDD.'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                    []
                );
            }

            //? Vérifier si le format de l'adresse mail est valide
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            
                return $this->json(
                    ['message' => 'Le format de l\'adresse email n\'est pas valide.'],
                    422,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                    []
                );
            }
        
            //? Instancier un objet User et setter ses propriétés
            $newUser = new User();
            $newUser->setFirstNameUser($firstName);
            $newUser->setLastNameUser($lastName);
            $newUser->setEmail($email);
            $newUser->setPassword($userPasswordHasherInterface->hashPassword($newUser, $password));
            $newUser->setRoles($roles);

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($newUser);
            $entityManagerInterface->flush();

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'L\'utilisateur à bien été ajouté à la BDD'],
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []);

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message'=>'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []);
        }
    }
}
