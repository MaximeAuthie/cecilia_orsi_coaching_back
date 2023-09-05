<?php
    namespace App\Service;

    class Utils {

        //! Nettoyer une chaine de caractères
        /**
         * @param string $value
         * @return null|string
         */
        public static function cleanInput(string|null $value):?string{
            return htmlspecialchars(strip_tags(trim($value)), ENT_NOQUOTES);
        }

        //! Nettoyer une chaine de caractères issue de l'éditeur de texte du frontend
         /**
         * @param string $value
         * @return null|string
         */
        public static function cleanInputArticleContent(string|null $value):?string{
            return strip_tags(trim($value), '<strong><em><ul><li><h1><h2><h3><u><br><p>');
        }

        //! Vérifier la validité d'une date au format 'Y-m-d'
        /**
         * @param string  $date
         * @param string  $format
         * @return bool
        */
        public static function isValidDate($date, $format = 'Y-m-d'):bool{
            $dt = \DateTime::createFromFormat($format, $date);
            return $dt && $dt->format($format) === $date;
        }

        //! Vérifier la validité d'une date au format 'Y-m-d H:i:s'
         /**
         * @param string  $date
         * @param string  $format
         * @return bool
        */
        public static function isValidDatetime($date, $format = 'Y-m-d H:i:s'):bool{
            $dt = \DateTime::createFromFormat($format, $date);
            return $dt && $dt->format($format) === $date;
        }
    }
?>