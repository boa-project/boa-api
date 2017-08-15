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
 * Interface iPersistenceOperations
 * Description of operations which need to be implemented by a Persistence driver
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
interface iPersistenceOperations {
    
    /**
     * 
     * Return a record as object
     * @param string $entity Table name
     * @param array $conditions
     * @return object
     */
    public function getEntity($entity, $conditions);

    /**
     * 
     * Insert a record
     * @param string $entity Table name
     * @param array $values
     * @return int New record ID
     */
    public function insert($entity, $values);

    /**
     * 
     * Update a record
     * @param string $entity Table name
     * @param array $values
     * @param array $conditions
     * @param bool $onlyone
     * @return bool true if successful, false in other case
     */
    public function update($entity, $values, $conditions, $onlyone = true);

    /**
     * 
     * Delete records
     * @param string $entity Table name
     * @param array $conditions
     * @param bool $onlyone
     * @return bool true if successful, false in other case
     */
    public function delete($entity, $conditions, $onlyone = true);
 
}

/**
 *
 * Custom exception to indicate when an object to be saved has a unique attribute with equal value in other entity
 */
class UniqueViolationException extends Exception { }

/**
 *
 * Custom exception to indicate when an object to be saved don't satisface a required relation
 */
class RelationViolationException extends Exception { }
