<?php

/*
 *  This file is part of Restos software
 * 
 *  Restos is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 * 
 *  Restos is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with Restos.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class RestosLang is a generic class to translate funtionalities
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class RestosLang {

    public static $CurrentLang = 'es';
    public static $DefaultLang = 'es';

    private static  $_strings = array();

    public static function get ($key, $type = 'restos', $params = null){

        if (!$type) {
            $type = 'restos';
        }

        if (!isset(RestosLang::$_strings[$type])) {
            $s = array();

            $file = Restos::$Properties->LocalPath . 'langs/' . RestosLang::$CurrentLang . '/' . $type . '.php';

            if (!file_exists($file) || is_dir($file)) {
                $file = Restos::$Properties->LocalPath . 'langs.' . RestosLang::$DefaultLang . '.' . $type;

                if (!file_exists($file) || is_dir($file)) {
                    return '{{' . $key . ':' . $type . '}}';
                }
            }

            include $file;

            if (count($s) == 0) {
                return '{{{' . $key . ':' . $type . '}}}';
            }

            RestosLang::$_strings[$type] = $s;
        }

        if (!isset(RestosLang::$_strings[$type][$key])) {
            return '{' . $key . ':' . $type . '}';
        }

        if (!empty($params)) {
            $a = $params;
            $str = str_replace('\\', '\\\\', RestosLang::$_strings[$type][$key]);
            $str = str_replace('"', '\"', $str);
            eval("\$str = \"$str\";");
            return $str;
        }
        else {
            return RestosLang::$_strings[$type][$key];
        }
    }

    public static function langExists ($lang) {

        $dir = Restos::$Properties->LocalPath . 'langs/' . $lang;
        return is_dir($dir);
    }

    public static function currentCulture () {
        if(self::$CurrentLang == 'es') {
            return 'CO';
        }
        else {
            return 'US';
        }
    }
}
