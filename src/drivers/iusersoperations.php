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
 * Interface iUsersOperations
 * Description of operations which need to be implemented by an Users driver
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
interface iUsersOperations {
    
    /**
     * 
     * Return all users in the moodle data base
     * 
     * @return array of users
     */
    public function getAllUsers();


    /**
     * 
     * Return an user as prototype
     * @param $resource_key
     * @return PrototypeFoafPerson
     */
    public function getPerson($resource_key);
}