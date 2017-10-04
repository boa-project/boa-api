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
 * Class to manage the queries action
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2017 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Queries extends ComplexObjectList {

    public function __construct() {

        $data = Restos::$DefaultRestGeneric->getDriverData("RCDefault");

        $queryDriver = null;
        if($data != null) {
            $queryDriver = DriverManager::getDriver('queries', $data->Properties, 'resources.queries');
        }
        else {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotconfigured', false, 'RCDefault')));
        }

        if (!$queryDriver) {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotexists', false, $data->Name)));
        }

        parent::__construct($queryDriver, 'queries', false);
    }

    public function getLastQueries($catalog, $query, $number, $start_on = 0) {
        // Get the more recent queries.
        $order = array('attempt' => 'DESC', 'size' => 'DESC', 'time' => 'DESC');

        // Only can get max 20 queries.
        if ($number > 20) {
            $number = 20;
        }

        $query = "%$query%";

        return $this->_driver->getLastQueries($catalog, $query, $order, $number, $start_on);
    }

}
