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
 * Class to manage the resource action
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Resource {

    private $_query_driver;

    public function __construct() {

        $data = Restos::$DefaultRestGeneric->getDriverData("RCDefault");

        $this->query_driver = DriverManager::getDriver('resources', $data->Properties, 'resources.resources');

        if (!$this->query_driver) {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotexists', false, $data->Name)));
        }

    }
    
    public function excecute($query = null, $number = null, $start_on = null) {
        return $this->query_driver->getResults($query, $number, $start_on);
    }

}
