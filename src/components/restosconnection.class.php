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
 * User is a generic class to provide authorization and authentication in Restos according IETF specification.
 * See http://www.ietf.org/rfc/rfc2617.txt
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class RestosConnection extends DefaultComponent {
    
    /**
     *
     * Current friend system key
     *
     * @var string
     */
    private static $_current_key = null;
    
    /**
     *
     * Custom header authenticate name
     *
     * @var string
     */
    private static $_auth_header_name = 'Authenticate-Token';

    /**
     * According to http://www.ietf.org/rfc/rfc2617.txt
     *
     * Header received:
     * Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==
     * Header response:
     * WWW-Authenticate: Basic realm="WallyWorld"
     */
    public static function init ($rest) {
        RestosConnection::$_rest = $rest;
        $auth = $rest->RestReceive->getHeader('Authorization');
        
        if ($auth) {
            $parts = explode(' ', $auth);
            if (count($parts) == 2 && strtolower($parts[0]) == 'basic') {
                $auth_decode = base64_decode($parts[1]);
                
                $parts2 = explode(':', $auth_decode);

                if (count($parts2) == 2) {
                    //"realm" is a special name for define the string assigned by the server to identify the protection space of the Request-URI
                    if (strtolower($parts2[0]) == 'realm') {
                        if ($realm = Restos::getSession('component', 'restosconnection', 'realm')) {
                            if ($realm === $parts2[1]) {
                                self::loadAvailableResources(Restos::getSession('component', 'restosconnection', 'system'));
                                return;
                            }
                        }
                    }
                    else {
                        //Validate the credentials
                        if ($system = RestosConnection::authenticate($parts2[0], $parts2[1])) {
                            $realm = RestosConnection::newRealm();
                            Restos::setSession('component', 'restosconnection', 'realm', $realm);
                            Restos::setSession('component', 'restosconnection', 'system', $system);
                            $rest->RestResponse->setHeader(self::$_auth_header_name, HttpHeaders::getRestosCustom(self::$_auth_header_name, $realm));
                            self::loadAvailableResources($system);
                            return;
                        }
                    }
                    $rest->RestResponse->setHeader(HttpHeaders::$WWW_AUTHENTICATE, HttpHeaders::getBasicWWWAuthenticateRealm(Restos::URIRest('')));
                }
                else {
                    $rest->RestResponse->Content = RestosLang::get('response.401.badcredentials');
                }
            }
            else {
                $rest->RestResponse->Content = RestosLang::get('response.401.onlybasicauthorization');
            }

            $rest->RestResponse->setHeader(HttpHeaders::$STATUS_CODE, HttpHeaders::getStatusCode('401'));
            $rest->RestResponse->send();
            exit;
        }
    }

    public static function authenticate ($system_name, $key) {
    
        if (empty($system_name) || empty($key)) {
            return null;
        }
        
        $system_name = strtolower($system_name);
        
        $friends = RestosConnection::$_rest->getProperty('FriendSystems');
        if (is_array($friends)){
            foreach($friends as $system){
                //The system name is case-insensitive
                if (strtolower($system->Name) == $system_name) {
                    if($system->Key == $key) {
                        return $system;
                    }
                    
                    //It not ending if key is wrong in first time because a system can use different keys to different capabilities
                }
            }
        }
        
        return null;
    }

    protected static function newRealm() {

        return md5(time());
    }
    
    private static function loadAvailableResources ($system) {

        if (is_object($system) && isset($system->Resources) && is_array($system->Resources)){
            $list = array();
            foreach($system->Resources as $resource){
                $list[$resource->Name] = $resource;
            }
            
            Restos::$AvailableResources = array_merge(Restos::$AvailableResources, $list);
        }
    }

}
