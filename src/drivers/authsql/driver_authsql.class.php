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

Restos::using('drivers.iauthpersistenceoperations');
Restos::using('drivers.sql.driver_sql');

/**
 * Class Driver_authsql
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Driver_authsql extends Driver_sql implements iAuthPersistenceOperations {
    
    /**
     *
     * Password salt
     * @var string
     */
    protected $_salt = '';

    /**
     * 
     * 
     * @param object $properties
     * @throws Exception - ConnectionString is required.
     * @throws Exception - Others in PEAR MDB2.
     */
    public function __construct($properties){

        parent::__construct($properties);

        if (property_exists($properties, 'PasswordSalt')) {
            $this->_salt = $properties->PasswordSalt;
        }
    }


    /**
     * 
     * Authenticate an user
     * @param $data Array with username and password keys
     * @return bool true if user exist, false in other case
     */
    public function authenticate($data){

        if (!isset($data['username']) || !isset($data['password'])) {
            Restos::log(Restos::$LOG_DEBUG, 'driver_authsql (authenticate): username or password does not exists');
            return false;
        }

        $user = null;

        $sql = "SELECT id, lang FROM " . $this->_prefix . "users WHERE username = " . $this->_connection->DB()->quote($data['username'], 'text') . " AND password = " . $this->_connection->DB()->quote($this->saltPassword($data['password']), 'text');

        $row = $this->_connection->getRow($sql);
        
        if (is_object($row)) {
            if (!isset($data['restart_session']) || $data['restart_session'] == true) {
                $user = new stdClass();
                $user->id           = $row->id;
                $user->username     = $data['username'];
                $user->lang         = $row->lang;
                
                Restos::resetSession();
                Restos::setSession('driver', 'authsql', 'user', $user);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     * Checks user password, not used for authentication but for methods 
     * that requires enforcing security
     * @param $data Array with username and password keys
     * @return bool true if user exist, false in other case
     */
    public function checkCredentials($data){

        if (!isset($data['username']) || !isset($data['password'])) {
            Restos::log(Restos::$LOG_DEBUG, 'driver_authsql (check credentials): username or password does not exists');
            return false;
        }

        $user = null;

        $sql = "SELECT id, lang FROM " . $this->_prefix . "users WHERE username = " . $this->_connection->DB()->quote($data['username'], 'text') . " AND password = " . $this->_connection->DB()->quote($this->saltPassword($data['password']), 'text');

        $row = $this->_connection->getRow($sql);
        
        if (is_object($row)) {
            $user = new stdClass();
            $user->id           = $row->id;
            $user->username     = $data['username'];
            $user->lang         = $row->lang;
            
            Restos::resetSession();
            Restos::setSession('driver', 'authsql', 'user', $user);
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     * Close the user authenticated session
     */
    public function close(){
        Restos::resetSession();
    }
    
    /**
     * 
     * Get an identification of current user
     *
     * @return int user identification, if exists an user in session; null in other case
     */
    public function getId(){
        if ($usr = Restos::getSession('driver', 'authsql', 'user')) {
            return $usr->id;
        }
        return null;
    }

    /**
     * 
     * Get a property value in current user data
     *
     * @return mixed property value, if exists; null in other case
     */
    public function get($property){
        if ($usr = Restos::getSession('driver', 'authsql', 'user')) {
            if (property_exists($usr, $property)) {
                return $usr->$property;
            }
        }
        return null;
    }
    
    /**
     *
     * @return string Password modified with a salt
     */
    public function saltPassword($password) {
        return MD5($this->_salt . $password);
    }
    
}
