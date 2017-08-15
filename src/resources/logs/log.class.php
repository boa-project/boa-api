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

 //Include global dependences 
Restos::using('resources.boacomplexobject');

/**
 * Class to manage Log records
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Log extends BoAComplexObject {

    private static $_factory_entity = null;

    public function __construct($id = 0) {

        parent::__construct('logs', $id);

    }

    public static function write($user_id, $module, $operation, $data = null) {
        try {
            if (Log::$_factory_entity == null) {
                Log::$_factory_entity = new Log();
            }

            $log_data = new stdClass();
            $log_data->user_id = $user_id;
            $log_data->time = time();
            $log_data->module = $module;
            $log_data->operation = $operation;

            if ($data) {
                $log_data->data = $data;
            }

            Log::$_factory_entity->save($log_data);
        }
        catch (Exception $e) {
            if (RESTOS_DEBUG_MODE) {
                die($e->getMessage());
            }
        }
    }
}
