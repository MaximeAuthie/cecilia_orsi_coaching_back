<?php
namespace App\Service;
    use App\Repository\UserRepository;
    use App\Service\Utils;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

    class ApiAuthentification {

        //! Vérifier si les données d'authentification de l'utilisateur sont valides
        /**
         * @param string  $userEmail
         * @param string  $userPassword
         * @return bool
        */
        public function authentification(UserPasswordHasherInterface $passwordHasherInteface, UserRepository $userRepository, string $userEmail, string $userPassword):bool {
            
            //? Nettoyer les données issues de l'api
            $userEmail = Utils::cleanInput($userEmail);
            $userPassword = Utils::cleanInput($userPassword);

            //? Récupérer le compte utilisateur avec la méthode findOneBy de la classe UserRepository
            $user = $userRepository->findOneBy(['email' => $userEmail]);

            //? Tester si le compte existe
            if ($user) {

                //? Tester si le mot de passe est correct
                if ($passwordHasherInteface->isPasswordValid($user, $userPassword)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        //! Générer un nouveau token
        /**
         * @param string  $email
         * @param string  $secretkey
         * @param string  $duration
         * @return bool
        */
        public function genNewToken(string $email, string $secretkey, UserRepository $userRepository, string $duration):string {

            //? autolaod composer
            require_once('../vendor/autoload.php'); 

            //? Définir les variables pour le token
            $user       = $userRepository->findOneBy(['email'=>$email]);
            $issuedAt   = new \DateTimeImmutable();                                         //Date de génération du token
            $expire     = $issuedAt->modify('+'.$duration.' minutes')->getTimestamp();      //Date d'expiration du token
            $serverName = "your.domain.name";                                               //Domaine du site
            $username   = $user->getFirstNameUser().' '.$user->getLastNameUser();           //Récupérer le nom entier
            
            //? Définir le contenu du token
            $data = [
                'iat'       => $issuedAt->getTimestamp(),         // Timestamp génération du tokenz
                'iss'       => $serverName,                       // Serveur
                'nbf'       => $issuedAt->getTimestamp(),         // Timestamp empécher date  (sécurité si quelqu'un récupère la clé de chiffrement)
                'exp'       => $expire,                           // Timestamp expiration du token
                'userName'  => $username,                         // Nom utilisateur
            ];

            //? Implémenter la méthode statique encode de la classe JWT
                $token = JWT::encode($data, $secretkey, 'HS512');

            //? Retourner le token au contrôleur
                return $token;
        }

        //! Vérifier la validité d'un token
        /**
         * @param string  $token
         * @param string  $key
         * @return bool || string
        */
        public function verifyToken(string $token, string $key) {

            //? autoload composer
            require_once('../vendor/autoload.php'); //Obligatoire

            try {
                //? Décoder le token (on vérifie s'il est valide et la méthode retourne une exception avec un message si quelque chose ne va pas dans son contenu )
                $decodeToken = JWT::decode($token, new Key($key, 'HS512'));
                
                //? Retourner true si il a pu décoder le token (s'il n'y arrive pas, il renvoie une exception sans passer par cette étape)
                return true;

            } catch (\Throwable $error) {
                return $error->getMessage();
            }
        }
    }
?>