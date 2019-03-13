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

//Include global resources
Restos::using('resources.counters.counter');
Restos::using('resources.counters.counters');

/**
 * Class to manage the counters
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2017 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class RestResource_Counters extends RestResource {

    /**
    * When request verb is get
    * @see resources/RestResource::onGet()
    * @return bool
    */
    public function onGet(){
        $resources = $this->_restGeneric->RestReceive->getResources();

        // The catalog always is required.
        if (!isset($resources->Resources->c)) {
            return false;
        }

        if ($resources->isSpecificResources()){
            return false;
        }
        else {
            $params = $this->_restGeneric->RestReceive->getParameters();
            $counter = isset($params['q']) ? $params['q'] : null;

            // Require filter counters.
            if (empty($counter)) {
                $data = array();
            }
            else {

                $number     = (isset($params['(n)']) && is_numeric($params['(n)'])) ? intval($params['(n)']) : 20;
                $start_on   = (isset($params['(s)']) && is_numeric($params['(s)'])) ? intval($params['(s)']) : null;

                $list = new Counters();
                $data = $list->getLastCounters($resources->Resources->c, $counter, $number, $start_on);
            }
        }

        Restos::using('resources.counters.restmapping_counters');
        $mapping = new RestMapping_Counters($data);

        $this->_restGeneric->RestResponse->Content = $mapping->getMapping($this->_restGeneric->RestResponse->Type);

        return true;
    }

}
