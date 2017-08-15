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
 * Class Entity. It is a base to other class with properties, to easy implement get-set properties.
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Entity {

    public function __get($name)
    {
        if (property_exists($this, $name)){
            return $this->$name;
        }
        else if(method_exists($this, 'get' . $name)) {
            return call_user_func(array($this, 'get' . $name));
        }
        else {
            throw new Exception('propertie_or_method_not_found: ' . get_class($this) . '->'. $name);
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)){
            $this->$name = $value;
        }
        else if(method_exists($this, 'set' . $name)) {
            return call_user_func(array($this, 'set' . $name), $value);
        }
        else {
            throw new Exception('propertie_or_method_not_found: ' . get_class($this) . '->'. $name);
        }
    }

}
