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

//Include dependences
Restos::using('resources.counters.counter');

/**
 * Class to manage the counters actions
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2017 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Counters extends ComplexObjectList {

    public function __construct() {

        $data = Restos::$DefaultRestGeneric->getDriverData("RCDefault");

        $queryDriver = null;
        if($data != null) {
            $queryDriver = DriverManager::getDriver('counters', $data->Properties, 'resources.counters');
        }
        else {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotconfigured', false, 'RCDefault')));
        }

        if (!$queryDriver) {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotexists', false, $data->Name)));
        }

        parent::__construct($queryDriver, 'counters', false);
    }

    public function getSocialCounters($resource) {

        $types = array(Counter::TYPE_VIEWS, Counter::TYPE_SCORE, Counter::TYPE_COMMENTS);
        $counters = $this->_driver->getSocialCounters($resource, $types);

        $social = new stdClass();
        foreach ($types as $type) {
            if ($type == Counter::TYPE_SCORE) {
                if (!property_exists($social, $type)) {
                    $value = array();
                }

                foreach ($counters as $counter) {
                    if ($counter->type == $type) {
                        $value[$counter->context] = (int)$counter->value;
                    }
                }
            }
            else {
                $value = 0;
                foreach ($counters as $counter) {
                    if ($counter->type == $type) {
                        $value = (int)$counter->value;
                        break;
                    }
                }
            }

            $social->$type = $value;
        }

        return $social;
    }

}
