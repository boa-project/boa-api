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
 * Class RestResource
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class RestResource {
    
    /**
     * 
     * Driver to connect for get data
     * @var object
     */
    protected $_queryDriver;
    
    /**
     * 
     * @var RestGeneric
     */
    protected $_restGeneric;
    
    public function __construct($rest_generic = null){
        $this->_restGeneric = $rest_generic;
    }
    
    /**
     * When request verb is GET
     * @return bool
     */
    public function onGet(){
        return false;
    }

    /**
     * When request verb is POST
     * @return bool
     */
    public function onPost(){
        return false;
    }

    /**
     * When request verb is PUT
     * @return bool
     */
    public function onPut(){
        return false;
    }

    /**
     * When request verb is DELETE
     * @return bool
     */
    public function onDelete(){
        return false;
    }

   /**
     * When request verb is OPTIONS
     * @return bool
     */
    public function onOptions(){
        return true;
    }
}