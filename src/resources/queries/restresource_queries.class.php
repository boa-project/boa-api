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
Restos::using('resources.queries.query');
Restos::using('resources.queries.queries');

/**
 * Class to manage the queries
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2017 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class RestResource_Queries extends RestResource {

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
            $query = isset($params['q']) ? $params['q'] : null;

            // Require filter queries.
            if (empty($query)) {
                $data = array();
            }
            else {

                // Get the more recent queries.
                $order = array('size' => 'DESC', 'time' => 'DESC');

                $conditions = array('catalog' => $resources->Resources->c,
                                    'query' => array('operator' => 'like', 'value' => "%$query%"),
                                    'size' => array('operator' => '>', 'value' => 0));
                $number     = (isset($params['(n)']) && is_numeric($params['(n)'])) ? intval($params['(n)']) : 20;
                $start_on   = (isset($params['(s)']) && is_numeric($params['(s)'])) ? intval($params['(s)']) : null;

                // Only can get max 20 queries.
                if ($number > 20) {
                    $number = 20;
                }

                $data = new Queries($conditions, $order, $number, $start_on);
                $data = $data->getPrototype();
            }
        }

        Restos::using('resources.queries.restmapping_queries');
        $mapping = new RestMapping_Queries($data);

        $this->_restGeneric->RestResponse->Content = $mapping->getMapping($this->_restGeneric->RestResponse->Type);

        return true;
    }

}
