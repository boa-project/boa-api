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
Restos::using('resources.logs.log');
Restos::using('resources.resources.resource');
Restos::using('resources.resources.resources');

/**
 * Class to manage the resources
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class RestResource_Resources extends RestResource {

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
            try {
                $data = new Resource($resources->getResourceId());
            }
            catch (ObjectNotFoundException $e) {
                Restos::throwException(null, RestosLang::get('notfound'), 404);
            }
        }
        else {

            $params = $this->_restGeneric->RestReceive->getParameters();
            $query = isset($params['q']) ? $params['q'] : null;

            if (!$query) {
                $data = array();
            }
            else {

                $number     = (isset($params['(n)']) && is_numeric($params['(n)'])) ? $params['(n)'] : null;
                $start_on   = (isset($params['(s)']) && is_numeric($params['(s)'])) ? $params['(s)'] : null;

                $filters = new stdClass();
                $filters->specification = isset($params['(spec)']) ? $params['(spec)'] : null;
                $filters->metas         = isset($params['(meta)']) ? $params['(meta)'] : null;
                $filters->extensions    = isset($params['(ext)']) ? $params['(ext)'] : null;
                $filters->user          = isset($params['(user)']) ? $params['(user)'] : null;
                $filters->connection    = isset($params['(conn)']) ? $params['(conn)'] : null;

                $engine = isset($params['(engine)']) ? $params['(engine)'] : null;

                $resources = new Resources($engine);
                $data = $resources->execute($query, $number, $start_on, $filters);
            }
        }

        Restos::using('resources.resources.restmapping_resources');
        $mapping = new RestMapping_Resources($data);

        $this->_restGeneric->RestResponse->Content = $mapping->getMapping($this->_restGeneric->RestResponse->Type);

        return true;
    }

}
