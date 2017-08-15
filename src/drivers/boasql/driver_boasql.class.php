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

Restos::using('drivers.sql.driver_sql');

/**
 * Class Driver_boasql
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Driver_boasql extends Driver_sql {

    public function countEntityRecords($entity, $conditions = null) {

        if (is_object($entity)) {
            if (!$entity->Prefixed) {
                $entity->Main = $this->_prefix . $entity->Main;

                $dependences = $entity->getDependences();
                if (is_array($dependences) && count($dependences) > 0) {
                    foreach($dependences as $value) {
                        if ($value->Alias == $value->Entity) {
                            $value->Alias = $this->_prefix . $value->Entity;
                        }

                        $value->Entity = $this->_prefix . $value->Entity;
                        $value->EntityTo = $this->_prefix . $value->EntityTo;
                    }
                }

                $entity->Prefixed = true;
            }
        }
        else {
            $entity = $this->_prefix . $entity;
        }

        return $this->_connection->countList($entity, $conditions);
    }

}
