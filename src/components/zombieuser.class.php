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
 * Zombie User is a generic class to provide access to user in cron/client mode
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class User extends DefaultComponent {

    /**
     *
     * Public properties
     *
     * @var array
     */
    private static $_properties = array();
        
    /**
     *
     * Id
     *
     * @var int
     */
    private static $_id = null;

    /**
     *
     * If exist an authenticated user
     *
     * @var bool 
     */
    public static $IsUserAuth = false;

    public static function init ($rest) {
        User::$_rest = $rest;
        User::$IsUserAuth = true;
    }

    public static function authenticate ($params) {
    
        return true;

    }

    public static function close () {
        User::$IsUserAuth = false;
    }

    public static function id ($id = null) {
        
        if ($id === null) {
            return self::$_id;
        }
        else {
            self::$_id = $id;
        }
    }
        
    public static function get ($property) {
        
        if (isset(self::$_properties[$property])) {
            return self::$_properties[$property];
        }
        else {
            return null;
        }
    }
        
    public static function set ($property, $value) {
        
        self::$_properties[$property] = $value;
    }
        
    public static function can () {
        return User::$IsUserAuth;
    }

    public static function available() {
        //Only is available in client mode
        if (!defined('RESTOS_CLIENT_MODE') || !RESTOS_CLIENT_MODE) {
            return false;
        }
        
        return true;
    }
}
