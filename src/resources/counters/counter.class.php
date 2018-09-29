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

//Include global dependences
Restos::using('resources.boacomplexobject');

/**
 * Class to manage the counter actions
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2017 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Counter extends ComplexObject {

    const TYPE_VIEWS = 'views';

    const TYPE_GRADE = 'finalgrade';

    const TYPE_COMMENTS = 'comments';

    public function __construct($id = 0) {

        $data = Restos::$DefaultRestGeneric->getDriverData("RCDefault");

        if($data != null) {
            $queryDriver = DriverManager::getDriver('counters', $data->Properties, 'resources.counters');
        }
        else {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotconfigured', false, 'RCDefault')));
        }

        if (!$queryDriver) {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotexists', false, $data->Name)));
        }

        parent::__construct($queryDriver, 'counters', $id);

    }

    public function registerView($resource) {
        $this->_driver->increase(self::TYPE_VIEWS, 1, $resource->id);
    }

}
