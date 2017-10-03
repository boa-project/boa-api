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
 * User is a generic class to provide authorization and authentication methods
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class User extends DefaultComponent {

    /**
     *
     * Authentication driver loading in current request
     *
     * @var object
     */
    private static $_authDriver = null;

    /**
     *
     * Authorization (access control) driver loading in current request
     *
     * @var object
     */
    private static $_accDriver = null;

    /**
     *
     * If exist an authenticated user
     *
     * @var bool
     */
    public static $IsUserAuth = false;

    public static function init ($rest) {
        User::$_rest = $rest;

        $auth_driver = User::authDriver();
        $id = $auth_driver->getId();
        User::$IsUserAuth = !empty($id);

        //Check only auth users modules
        if (!User::$IsUserAuth) {

            $current_resource = $rest->RestReceive->getPrincipalResource();

            if(in_array($current_resource, Restos::$Properties->ResourceOnlyAuth)) {
                $rest->RestResponse->setHeader(HttpHeaders::$STATUS_CODE, HttpHeaders::getStatusCode('401'));
                $rest->RestResponse->send();
                exit;
            }
        }
        else {
            $data = Restos::getSession('resource', 'sessions', 'data');

            if(property_exists($data, 'lang') && !empty($data->lang)) {
                RestosLang::$CurrentLang = $data->lang;
            }
            else{
                $user_lang = User::get('lang');
                if(!empty($user_lang)) {
                    RestosLang::$CurrentLang = $user_lang;
                }
            }
        }
    }

    public static function authenticate ($params) {

        $auth_driver = User::authDriver();
        User::$IsUserAuth = $auth_driver->authenticate($params);

        return User::$IsUserAuth;

    }

    public static function close () {
        $auth_driver = User::authDriver();
        $auth_driver->close();
        User::$IsUserAuth = false;
    }

    public static function authDriver() {

        if (User::$_authDriver == null) {
            if (empty(User::$_rest)){
                Restos::log(Restos::LOG_ERROR, 'class_user (authenticate): component -User- was not initialized correctly');
                throw new Exception('Component -User- was not initialized correctly.');
            }

            $data = User::$_rest->getDriverData("Auth", 'Components');

            if($data != null) {

                $properties = $data->Properties;

                $data->Properties = $properties;
                User::$_authDriver = DriverManager::getDriver($data->Name, $data->Properties);
            }
            else {
                Restos::log(Restos::LOG_ERROR, 'class_user (authenticate): driver Auth was not configured correctly');
                throw new Exception('Driver Auth was not configured correctly');
            }
        }

        return User::$_authDriver;

    }

    public static function accDriver() {

        if (User::$_accDriver == null) {
            if (empty(User::$_rest)){
                Restos::log(Restos::LOG_INFO, 'class_user (authenticate): component -User- was not initialized correctly');
            }
            else {

                $data = User::$_rest->getDriverData("Access", 'Components');

                if($data != null) {
                    $properties = $data->Properties;

                    $data->Properties = $properties;

                    User::$_accDriver = DriverManager::getDriver($data->Name, $data->Properties);
                }
            }
        }

        return User::$_accDriver;
    }

    public static function id () {

        $auth_driver = User::authDriver();
        if (User::$IsUserAuth && $auth_driver != null) {
            return $auth_driver->getId();
        }
        else {
            return null;
        }
    }

    public static function get ($property) {

        $auth_driver = User::authDriver();
        if (User::$IsUserAuth && $auth_driver != null) {
            return $auth_driver->get($property);
        }
        else {
            return null;
        }
    }

    public static function can () {

        $numargs = func_num_args();
        $arg_list = func_get_args();

        $acc_driver = User::accDriver();

        if ($acc_driver != null) {
            return $acc_driver->can($arg_list);
        }
        else {
            // if not exist access driver, the access is invalid
            return false;
        }
    }
}
