<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\Utils;
use App\Service\Messaging;

class ContactController extends AbstractController
{   
    //! Envoyer un mail de contact
    #[Route('/api/contact', name: 'app_contact_api', methods: ['POST','OPTIONS'])]
    public function sendContactEmail(Request $request, SerializerInterface $serializerInterface, Messaging $messaging): Response {
        
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
        
            
            //?Récupérer le contenu de la requête en provenance du front
            $json = $request->getContent();

            //?Vérifier que le json n'est pas vide
            if (!$json) {
                return $this->json(
                    ['message' => 'Le json est vide ou n\'existe pas.'],
                    400,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'POST, OPTIONS'], 
                    []
                );
            }

            //?Sérializer le json (le transformer en tableau)
            $data = $serializerInterface->decode($json,'json');

            //?Nettoyer les données du tableau $data et les stocker dans des variables
            $firstName  = Utils::cleanInput($data['firstName']);
            $lastName   = Utils::cleanInput($data['lastName']);
            $email      = Utils::cleanInput($data['email']);
            $subject    = Utils::cleanInput($data['subject']);
            $content    = Utils::cleanInput($data['content']);

            //?Vérifier si le format de l'adresse email est valide
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json(
                    ['message' => 'The email adress '.$data['email'].' is not a valid email adress.'],
                    422,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'POST, OPTIONS'], 
                    []
                );
            }

            //?Récupérer les variables d'authentification du webmail pour utiliser la méthode sendEmail() du service Messaging
            $emailLogin     = $this->getParameter('mailaccount');
            $emailPassword  = $this->getParameter('mailpassword');
            $emailContact   = $this->getParameter('mailcontact');

            //? Définition des variables pour utiliser la méthode sendEmail() de la classe Messenging
            $date           = date('d-m-y', time());
            $hour           = date('h:i:s', time());
            $emailObject    = mb_convert_encoding('Cécilia Orsi Coaching : nouveau message de '.$firstName.' '.$lastName, 'ISO-8859-1', 'UTF-8');
            $emailContent   = mb_convert_encoding("<img src='https://i.postimg.cc/mrR6JHNW/LOGO4.png'/>".
                                                    "<p>Bonjour !</p>".
                                                    '<p>Vous avez reçu un nouveau message de <strong>'.$firstName.' '.$lastName.'</strong>, envoyé le <strong>'.$date.' à '.$hour.'</strong></br>'.
                                                    '<hr><p><strong><u>Sujet du message :</u></strong> '.$subject.' </p><hr>'.
                                                    '<p><strong><u>Contenu du message :</u></strong> </p>'.
                                                    $content.'<hr><br>'.
                                                    '<a href="mailto:'.$email.'?subject=Cécilia Orsi Coaching : réponse à votre message du '.$date.' à '.$hour.'">Répondre à '.$firstName.' '.$lastName.'</a>'
                                                    , 'ISO-8859-1', 'UTF-8');

            //? Executer la méthode sendMail() du service Messenging
            $mailStatus = $messaging->sendEmail($emailLogin, $emailPassword, $emailContact, $emailObject, $emailContent, mb_convert_encoding('Cécilia', 'ISO-8859-1', 'UTF-8'), 'Orsi');
            
            //?Vérfier si l'envoie de l'email à échoué
            if ($mailStatus != 'The email has been sent') {
                return $this->json(
                    ['message' => 'Unable to send mail'],
                    500,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'POST, OPTIONS'], 
                    []
                );
            }
            
            //? Retourner un json pour avertir que l'envoi du mail a fonctionné
            return $this->json(
                ['message'=> 'Le message a été envoyé avec succès'], 
                200, 
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin' =>'*', 'Access-Control-Allow-Methods' => 'POST, OPTIONS'],
                []
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
}