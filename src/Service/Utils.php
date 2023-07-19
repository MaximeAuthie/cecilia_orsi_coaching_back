<?php
    namespace App\Service;

    class Utils {
        /**
         * @param string $value
         * @return null|string
         */
        public static function cleanInput(string $value):?string{
            return htmlspecialchars(strip_tags(trim($value)));
        }

        /**
         * @param string  $date
         * @param string  $format
         * @return bool
        */
        public static function isValidDate($date, $format = 'Y-m-d'):bool{
            $dt = \DateTime::createFromFormat($format, $date);
            return $dt && $dt->format($format) === $date;
        }

    }
?>