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
Restos::using('resources.queries.query');
Restos::using('resources.resources.restmapping_resources');

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
                $resource = new Resource($resources->Resources->c, $resources->getResourceId());
                $data = $resource->getPrototype();


                if ($this->_restGeneric->RestResponse->Type == 'IMG') {
                    $mapping = new RestMapping_Resources($data);
                    $this->_restGeneric->RestResponse->Content = $mapping->getMapping($this->_restGeneric->RestResponse->Type, $resource);
                    return true;
                }

            }
            catch (ObjectNotFoundException $e) {
                Restos::throwException(null, RestosLang::get('notfound'), 404);
            }

            $content_path = $this->_restGeneric->RestReceive->getURIParameters();

            if (isset($content_path)) {

                $content = $resource->getContent($content_path, false);

                if (!$content->path) {
                    Restos::throwException(null, RestosLang::get('notfound'), 404);
                }

                try {
                    if (!Resource::isPreviewPath($content_path)) {
                        $view = Restos::getSession('resource', 'counters/view', $resource->id, false);
                        if (!$view) {
                            Restos::setSession('resource', 'counters/view', $resource->id, true);
                            $counter = new Counter();
                            $counter->registerView($resource);
                        }
                    }
                }
                catch (Exception $e) {
                    // Nothing to do.
                }

                if ($content->type) {
                    $this->_restGeneric->RestResponse->Type = $content->type;
                }

                $mapping = new RestMapping_Resources($data);
                $this->_restGeneric->RestResponse->Content = $mapping->putContent($content);

                return true;
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
                $sort       = isset($params['(sort)']) ? $params['(sort)'] : null;
                $mode       = (isset($params['(mode)']) && preg_match("/^(full|basic)$/i", ($params['(mode)'])) ? $params['(mode)'] : 'basic'); //Mode, default to basic

                $filters = new stdClass();
                $filters->specification = isset($params['(spec)']) ? $params['(spec)'] : null;

                // (meta) parameter can be a string or a single array or a multi-valued array.
                // Example:
                // (meta)=metadata.technical.format // The metadata exists
                // (meta)[metadata.technical.format]=video // Only with format: video
                // (meta)[metadata.technical.format][]=video&(meta)[metadata.technical.format][]=audio // Videos and Audios
                $filters->metas = isset($params['(meta)']) ? $params['(meta)'] : null;

                if (!is_array($filters->metas)) {
                    //$filters->metas[$filters->metas] = "*";
                }

                if (isset($params['(ext)'])) {
                    $extensions = explode(',', $params['(ext)']);
                    foreach($extensions as $ext) {
                        $filters->extensions[] = trim($ext);
                    }
                }
                else {
                    $filters->extensions = null;
                }

                $filters->user          = isset($params['(user)']) ? $params['(user)'] : null;
                $filters->connection    = isset($params['(conn)']) ? $params['(conn)'] : null;

                if (strpos($resources->Resources->c, '[') === 0) {
                    $catalog = explode('|', rtrim(ltrim($resources->Resources->c, '['), ']'));

                }
                else {
                    $catalog = $resources->Resources->c;
                }

                $filters->catalog = $catalog;

                $engine = isset($params['(engine)']) ? $params['(engine)'] : null;

                $resources_list = new Resources($engine);
                $data = $resources_list->execute($query, $number, $start_on, $filters, $mode, $sort);

                $executed_queries = Restos::getSession('resource', 'queries', 'executed', array());
                // Only save the first 1000 queries in a session.
                if (count($executed_queries) < 1000 && !in_array(strtolower($query), $executed_queries)) {
                    $save_query = new Query();
                    $save_query->catalog = $resources->Resources->c;
                    $save_query->query = $query;
                    $save_query->size = count($data);
                    $save_query->time = time();

                    try {
                        $save_query->save();
                        $executed_queries[] = strtolower($query);
                        Restos::setSession('resource', 'queries', 'executed', $executed_queries);
                    }
                    catch(Exception $e) {};
                }
            }
        }

        $mapping = new RestMapping_Resources($data);

        $this->_restGeneric->RestResponse->Content = $mapping->getMapping($this->_restGeneric->RestResponse->Type);

        return true;
    }

    /**
    * When request verb is head
    * @see resources/RestResource::onHead()
    * @return bool
    */
    public function onHead(){
        $resources = $this->_restGeneric->RestReceive->getResources();

        // The catalog always is required.
        if (!isset($resources->Resources->c)) {
            return false;
        }

        if ($resources->isSpecificResources()) {

            try {
                $resource = new Resource($resources->Resources->c, $resources->getResourceId());
                $data = $resource->getPrototype();

                // Only confirm the existence of the resource.
                return true;
            }
            catch (ObjectNotFoundException $e) {
                Restos::throwException(null, RestosLang::get('notfound'), 404);
            }

        }

        // If the request is not specific, then it is a search and require a confirmation exist.
        return true;
    }

}
