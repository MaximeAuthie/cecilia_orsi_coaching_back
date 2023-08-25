<?php

namespace App\Controller;

use App\Entity\Visit;
use App\Repository\VisitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Utils;
use App\Service\ApiAuthentification;

class VisitController extends AbstractController {
    #[Route('/api/visit/add', name: 'app_add_visit_api', methods: ['POST','OPTIONS'])] 
    public function addVisit(Request $request ,VisitRepository $visitRepository, SerializerInterface $serializerInterface, EntityManagerInterface $entityManagerInterface): Response {
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
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Sérializer le json (on le change de format json -> tableau)
            $data = $serializerInterface->decode($json, 'json');

            //? Nettoyer les données issues du json et les stocker dans des variables
            $ip = Utils::cleanInput($data['ip']);

            //? Vérifier si le format de l'adresse ip est valide
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return $this->json(
                    ['message' => 'Le format de l\'adresse ip n\'est pas valide.'],
                    422,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'PATCH'], 
                    []
                );
            }

            //? Vérifier si une visite de moins de 30 minutes n'existe pas pour cette adresse ip
            $currentTime = new \DateTimeImmutable();
            $filterHour = clone $currentTime;
            $filterHour = $filterHour->modify('-30 minutes');

            $visits = $visitRepository->createQueryBuilder('v')
                ->where('v.time_visit > :filterHour AND v.ip_visit= :ip')
                ->setParameter('ip', $ip)
                ->setParameter('filterHour', $filterHour)
                ->getQuery()
                ->getResult();

            //? Si une visite de moins de 30 minutes pour cette adresse ip existe déjà
            if ($visits) {
                return $this->json(
                    ['message' => 'Une visite a déjà été enregistrée il y a moins de 30 minutes pour cette adresse IP'],
                    206,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'], 
                    []
                );
            }
            

            // ?Instancier un objet visit
            $visit = new Visit();
            $visit->setTimeVisit(new \DateTimeImmutable());
            $visit->setIpVisit($ip);

            //? Persiter et flush des données pour les insérer en BDD
            $entityManagerInterface->persist($visit);
            $entityManagerInterface->flush();
            

            //? Renvoyer un json pour avertir que l'enregistrement à bien été effectué
            return $this->json(
                ['message'=> 'La visite à bien été ajoutée à la BDD'],
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []);

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json pour détailler l'erreur inattendue
            return $this->json(
                ['message'=> 'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []);
        }
    }

    #[Route('/api/visit/stats', name: 'app_stats_visit_api', methods: ['GET','OPTIONS'])] 
    public function getVisitStats(Request $request , VisitRepository $visitRepository, ApiAuthentification $apiAuthentification): Response {
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

            //? Récupérer toutes les visites et calculer leur nombre
            $allVisits = $visitRepository->findAll();
            $allVisits = count($allVisits);

            //? Récupérer toutes les visites des 30 derniers jours et calculer leur nombre
            $currentTime = new \DateTimeImmutable();
            $filterDateMonth = clone $currentTime;
            $filterDateMonth = $filterDateMonth->modify('-30 days');
            
            $monthVisits = $visitRepository->createQueryBuilder('v')
                ->where('v.time_visit > :filterDate')
                ->setParameter('filterDate', $filterDateMonth)
                ->getQuery()
                ->getResult();

            $monthVisits = count($monthVisits);

            //? Récupérer toutes les visites des 24 dernières heures et calculer leur nombre
            $filterDateDay = clone $currentTime;
            $filterDateDay = $filterDateDay->modify('-24 hours');

            $dayVisits = $visitRepository->createQueryBuilder('v')
                ->where('v.time_visit > :filterDate')
                ->setParameter('filterDate', $filterDateDay)
                ->getQuery()
                ->getResult();

            $dayVisits = count($dayVisits);

            //? Déclarer l'objet qui va être retourné au front 
            $stats = [
                "all"   => $allVisits,
                "month" => $monthVisits,
                "day"   => $dayVisits
            ];
           
            // //? Renvoyer un json avec les statistiques demandées
            return $this->json(
                $stats,
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []
            );

        //? En cas d'erreur inattendue, capter l'erreur rencontrée        
        } catch (\Exception $error) {

            //? Retourner un json poour détailler l'erreur inattendue
            return $this->json(
                ['message'=> 'Etat du json : '.$error->getMessage()],
                400, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Method' => 'POST'],
                []
            );
        }
    }
}
