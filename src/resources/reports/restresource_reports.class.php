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
Restos::using('resources.reports.report');
Restos::using('resources.reports.reports');

/**
 * Class to manage the reports
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2019 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class RestResource_Reports extends RestResource {

    /**
    * When request verb is get
    * @see resources/RestResource::onGet()
    * @return bool
    */
    public function onGet(){

        $resources = $this->_restGeneric->RestReceive->getResources();

        if (!$resources->isSpecificResources()){
            return false;
        }
        else {

            $params = $this->_restGeneric->RestReceive->getParameters();

            $accesstoken = Restos::$DefaultRestGeneric->getProperty("ResourcesConfiguration->reports->AccessToken");

            if ($accesstoken) {
                if (!isset($params['token']) || $params['token'] != $accesstoken) {
                    Restos::throwException(null, RestosLang::get('reports.accesstoken.invalid', 'boa'));
                }
            }

            $reporttype = $resources->getResourceId();

            if (!Report::isValidType($reporttype)) {
                return false;
            }

            // Require filter reports.
            $timeinit   = (isset($params['timeinit']) && is_numeric($params['timeinit'])) ?
                                intval($params['timeinit']) :
                                time() - (3600 * 24);

            $timeend    = (isset($params['timeend']) && is_numeric($params['timeend'])) ? intval($params['timeend']) : time();

            $number     = (isset($params['(n)']) && is_numeric($params['(n)'])) ? intval($params['(n)']) : 20;
            $start_on   = (isset($params['(s)']) && is_numeric($params['(s)'])) ? intval($params['(s)']) : null;

            $report = new Report($reporttype);
            $data = $report->getDataList($timeinit, $timeend, $number, $start_on);
        }

        Restos::using('resources.reports.restmapping_reports');
        $mapping = new RestMapping_Reports($data);

        $this->_restGeneric->RestResponse->Content = $mapping->getMapping($this->_restGeneric->RestResponse->Type);

        return true;
    }

}
