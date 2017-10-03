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
 * Class Util
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Util extends Entity {

    static function mergeObjects($object1, $object2) {

        $arr1 = (array) $object1;
        $arr2 = (array) $object2;

        $keys = array_keys($arr2);

        foreach( $keys as $key ) {
            if (isset($arr1[$key])) {
                if (is_array($arr1[$key]) && is_array($arr2[$key])) {
                    $arr1[$key] = self::mergeArrays($arr1[$key], $arr2[$key]);
                }
                else if (is_object($arr1[$key]) && is_object($arr2[$key])) {
                    $arr1[$key] = self::mergeObjects($arr1[$key], $arr2[$key]);
                }
                else {
                    $arr1[$key] = $arr2[$key];
                }
            }
            else {
                $arr1[$key] = $arr2[$key];
            }
        }

        return (object) $arr1;

    }

    static function mergeArrays($arr1, $arr2) {

        $keys = array_keys($arr2);

        foreach( $keys as $key ) {
            if (isset($arr1[$key])) {
                if (is_array($arr1[$key]) && is_array($arr2[$key])) {
                    $arr1[$key] = self::mergeArrays($arr1[$key], $arr2[$key]);
                }
                else if (is_object($arr1[$key]) && is_object($arr2[$key])) {
                    $arr1[$key] = self::mergeObjects($arr1[$key], $arr2[$key]);
                }
                else {
                    $arr1[$key] = $arr2[$key];
                }
            }
            else {
                $arr1[$key] = $arr2[$key];
            }
        }

        return $arr1;
    }
}

