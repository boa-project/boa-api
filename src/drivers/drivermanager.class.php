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
 * Class DriverManager
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class DriverManager {

    public function __construct() {
        return $this->DriverManager();
    }

    public function DriverManager () {
    }

    public static function getDriver ($driver_name, $properties, $location = null) {
        if($location && Restos::using( strtolower($location . '.driver_' . $driver_name))){
            $class = 'driver_' . $driver_name;
            return new $class($properties);
        }
        else if(Restos::using(strtolower('drivers.' . $driver_name . '.driver_' . $driver_name))){
            $class = 'driver_' . $driver_name;
            return new $class($properties);
        }
        else {
            return false;
        }
    }
}
