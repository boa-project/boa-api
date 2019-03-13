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
Restos::using('resources.comments.comment');
Restos::using('resources.comments.comments');
Restos::using('resources.resources.resource');

/**
 * Class to manage the comments
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2018 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class RestResource_Comments extends RestResource {

    /**
    * When request verb is get
    * @see resources/RestResource::onGet()
    * @return bool
    */
    public function onGet(){
        $resources = $this->_restGeneric->RestReceive->getResources();

        if (!isset($resources->Resources->c) || // The catalog always is required.
                !isset($resources->Resources->resources) || // The resource always is required.
                $resources->isSpecificResources()) {
            return false;
        }

        if ($resources->isSpecificResources()){
            return false;
        }

        try {
            $resource = new Resource($resources->Resources->c, $resources->Resources->resources);
        }
        catch(Exception $e) {
            Restos::throwException(null, RestosLang::get('notfound'), 404);
        }

        $params = $this->_restGeneric->RestReceive->getParameters();
        $number     = (isset($params['(n)']) && is_numeric($params['(n)'])) ? intval($params['(n)']) : 20;
        $start_on   = (isset($params['(s)']) && is_numeric($params['(s)'])) ? intval($params['(s)']) : null;

        $data = new Comments(array('resource' => $resource->id), array('updated_at' => 'DESC'), $number, $start_on);

        Restos::using('resources.comments.restmapping_comments');
        $mapping = new RestMapping_Comments($data->getPrototype('comments', 2));

        $this->_restGeneric->RestResponse->Content = $mapping->getMapping($this->_restGeneric->RestResponse->Type);

        return true;
    }

    /**
     * When request verb is post
     * @see resources/RestResource::onPost()
     */
    public function onPost() {

        $resources = $this->_restGeneric->RestReceive->getResources();

        if (!isset($resources->Resources->c) || // The catalog always is required.
                !isset($resources->Resources->resources) || // The resource always is required.
                $resources->isSpecificResources()) {
            return false;
        }

        if ($resources->isSpecificResources()) {
            Restos::throwException(null, RestosLang::get('response.400.post.specificnotallowed'), 400);
        }

        try {
            $resource = new Resource($resources->Resources->c, $resources->Resources->resources);
        }
        catch(Exception $e) {
            Restos::throwException(null, RestosLang::get('notfound'), 404);
        }

        $data = $this->_restGeneric->RestReceive->getProcessedContent();
        $data = array_merge($data, $this->_restGeneric->RestReceive->getParameters());

        if (empty($data['content'])) {
            Restos::throwException(null, RestosLang::get('comments.contentrequired', 'boa'), 400);
        }

        $comment = new Comment();

        $params = new stdClass();
        $params->resource = $resource->id;
        $params->owner = empty($data['name']) ? RestosLang::get('comments.unknown', 'boa') : $data['name'];
        $params->content = $data['content'];
        $params->updated_at = time();

        if(!$comment->save($params)) {
            $this->_restGeneric->RestResponse->setHeader(HttpHeaders::$STATUS_CODE, HttpHeaders::getStatusCode('500'));
            $this->_restGeneric->RestResponse->Content = 0;
        }

        return true;
    }

}
