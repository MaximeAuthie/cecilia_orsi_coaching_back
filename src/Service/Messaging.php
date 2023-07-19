<?php
    namespace App\Service;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    class Messaging {

        public function sendEmail(string $login, string $password, string $emailAdress, string $subject, string $body, string $contactFirstName, string $contactLastName) {
    
            //Load Composer's autoloader
            require '../vendor/autoload.php';
    
            //Create an instance; passing `true` enables exceptions
            $mail = new PHPMailer(true);
    
            try {
                //Server settings
                $mail->SMTPDebug   = 0;                                      // Enable verbose debug output : permet de gérer le debogage -> mettre à 0 pour désactiver
                $mail->isSMTP();                                             // Send using SMTP : pour dire qu'on utilise un server SMTP
                $mail->Host        = 'ssl0.ovh.net';                         // Set the SMTP server to send through
                $mail->SMTPAuth    = true;                                   // Enable SMTP authentication
                $mail->Username    = $login;                                 // SMTP username
                $mail->Password    = $password;                              // SMTP password
                $mail->SMTPSecure  = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
                $mail->Port        = 465;                                    // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
    
                //Recipients
                $mail->setFrom($login, mb_convert_encoding('Admin Cécilia Orsi Coaching', 'ISO-8859-1', 'UTF-8'));                                // Adresse de l'expéditeur + alias qui apparait dans la boite du destinataire
                $mail->addAddress($emailAdress, $contactFirstName.' '.$contactLastName);        // Adresse mail du destinataire
                
    
                //Content
                $mail->isHTML(true);                                        //Set email format to HTML : voir https://www.alsacreations.com/tuto/lire/1533-Un-e-mail-en-HTML-responsive-multi-clients.html pour la mise en forme
                $mail->Subject = $subject;                                  //Objet du mail
                $mail->Body    = $body;               
                
                //Send the email
                $mail->send();

                //Return to controller
                return 'The email has been sent';
    
            } catch (Exception $error) {
    
                //Catch error
                return "The email could not be sent : {$mail->ErrorInfo}";
            }
        }
    
    }
?>