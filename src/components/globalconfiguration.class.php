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
 * GlobalConfiguration is a generic class to custom general configurations, for example: timezone
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class GlobalConfiguration extends DefaultComponent {

    public static function init ($rest) {

        GlobalConfiguration::$_rest = $rest;

        $data = $rest->getDriverData("GlobalConfiguration", 'Components');

        if ($data && $data->Properties) {
            
            if (property_exists($data->Properties, 'Timezone') && !empty($data->Properties->Timezone)) {
                date_default_timezone_set($data->Properties->Timezone);
            }

        }
    }
    
    public static function available() {
        return true;
    }

}
