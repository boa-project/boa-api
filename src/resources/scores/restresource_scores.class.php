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
Restos::using('resources.scores.score');
Restos::using('resources.scores.scores');
Restos::using('resources.resources.resource');

/**
 * Class to manage the scores
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2018 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class RestResource_Scores extends RestResource {

    /**
     * When request verb is post
     * @see resources/RestResource::onPost()
     */
    public function onPost(){

        $resources = $this->_restGeneric->RestReceive->getResources();

        if (!isset($resources->Resources->c) || // The catalog always is required.
                !isset($resources->Resources->resources) || // The resource always is required.
                $resources->isSpecificResources()) {
            return false;
        }

        if ($resources->isSpecificResources()){
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

        $value = !isset($data['value']) ? 1 : (int)$data['value'];

        $idscore = Restos::getSession('resource', 'scores', $resource->id, false);

        $score = null;
        try {
            if ($idscore) {
                $score = new Score($idscore);
            }
        }
        catch (Exception $e) {
            // Nothing to do, it is a validation.
        }

        if (!$score) {
            $params = new stdClass();
            $params->resource = $resource->id;
            $params->value = $value;
            $params->updated_at = time();

            $score = new Score();

            if($score->save($params)) {
                Restos::setSession('resource', 'scores', $resource->id, $score->id);
            }
            else {
                $this->_restGeneric->RestResponse->setHeader(HttpHeaders::$STATUS_CODE, HttpHeaders::getStatusCode('500'));
                $this->_restGeneric->RestResponse->Content = 0;
            }
            return true;
        }
        else {
            $score->value = $value;
            $score->updated_at = time();
            $score->save();
            return true;
        }

    }

}
