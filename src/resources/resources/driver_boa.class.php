<?php
// This file is part of BoA - https://github.com/boa-project
//
// BoA is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// BoA is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with BoA.  If not, see <http://www.gnu.org/licenses/>.
//
// The latest code can be found at <https://github.com/boa-project/>.

/**
 * Class to connect with BoA System
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Driver_BoA {

    /**
     *
     * Properties of the driver, with application level
     * @var object
     */
    protected $_properties;

    /**
     *
     *
     * @param object $properties
     */
    public function __construct ($properties) {
        if(!is_object($properties)) {
            throw new Exception('Properties object is required.');
        }

        $this->_properties = $properties;
    }

    /**
     * Return the specífic catalogue from BoA project
     *
     */
    public function getCatalogue($id) {
        return $this->getApiBoACall("catalogs/$id");
    }

    /**
     * Return all catalogues into BoA project
     *
     */
    public function getCataloguesList() {
        return $this->getApiBoACall("catalogs");
    }

    /**
     * Prepare a call to BoA api for getting catalogs list or specific catalog
     *
     */
    private function getApiBoACall($path) {
        $service_url = $this->_properties->BoAAPI;
        Restos::using('classes.curl');

        $ch = new Curl();
        $ch->setHeader(['Content-Type: application/json']);

        $user = md5(gethostname());
        $pwd = "";
        $ch->setopt(array("HTTPAUTH" => CURLAUTH_BASIC, "USERPWD" => "$user:$pwd"));

        $url = rtrim($service_url, '/') . "/" . $path;
        $response = $ch->get($url);
        if ($ch->errno){
            throw new Exception($ch->error, 1);
        }
        if (isset($ch->info) && isset($ch->info["http_code"]) && $ch->info["http_code"] > 201){ //There is an http status error code
            throw new Exception($response, 1);
        }

        return json_decode($response);
    }
}