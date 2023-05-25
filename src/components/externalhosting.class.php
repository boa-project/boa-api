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
 * ExternalHosting is a generic class to provide access by external hostings by diferents client access
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class ExternalHosting extends DefaultComponent {

    public static function init ($rest) {
        ExternalHosting::$_rest = $rest;

        $data = $rest->getDriverData("ExternalHosting", 'Components');

        if ($data && $data->Properties && property_exists($data->Properties, 'HostAllowed') && !empty($data->Properties->HostAllowed)) {

            header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS, HEAD ');
            header('Access-Control-Allow-Origin: ' . $data->Properties->HostAllowed);
            header('Access-Control-Allow-Headers: Origin,X-Requested-With,Content-Type,Accept,X-Observation-blockedkey');
            header('Access-Control-Allow-Credentials: true');

            if ($rest->RestReceive->isOptions()) {
                $rest->RestResponse->send();
                exit;
            }

        }
    }

}
