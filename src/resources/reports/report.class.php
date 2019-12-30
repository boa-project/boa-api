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
Restos::using('resources.resources.resource');

/**
 * Class to manage the reports actions
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2019 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Report extends ComplexObject {

    const TYPE_VIEWS = 'views';
    const TYPE_SCORE = 'score';
    const TYPE_COMMENTS = 'comments';

    private $type;

    public function __construct($reporttype) {

        $this->type = $reporttype;
        $data = Restos::$DefaultRestGeneric->getDriverData("RCDefault");

        if($data != null) {
            $queryDriver = DriverManager::getDriver('reports', $data->Properties, 'resources.reports');
        }
        else {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotconfigured', false, 'RCDefault')));
        }

        if (!$queryDriver) {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotexists', false, $data->Name)));
        }

        parent::__construct($queryDriver, 'reports', 0);

    }

    public static function isValidType($report) {
        switch($report) {
            case self::TYPE_VIEWS:
            case self::TYPE_SCORE:
            case self::TYPE_COMMENTS:
                return true;
            default:
                return false;
        }
    }

    public function getDataList($timeinit, $timeend, $number, $start_on, $catalogueid = null) {
        $method = $this->type . 'Data';
        $rows = $this->_driver->$method($timeinit, $timeend, $number, $start_on);

        if (is_array($rows) && count($rows) > 0 && property_exists($rows[0], 'resource')) {
            foreach ($rows as $key => $row) {
                $row->catalogue = '';
                $row->title = '';

                try {
                    $resource = Resource::getUniversalResourceInfo($row->resource);
                    $catalogue = $resource->getCatalogue();

                    if ($catalogueid && $catalogue->alias != $catalogueid) {
                        unset($rows[$key]);
                    }
                    else {
                        $row->catalogue = $catalogue->alias;
                        if (property_exists($resource->manifest, 'title')) {
                            $row->title = $resource->manifest->title;
                        }
                        else if ($resource->manifest->is_a == 'dro') {
                            $row->title = $resource->metadata->general->title->none;
                        }
                    }
                } catch(Exception $e) {
                    if (empty($row->catalogue)) {
                        unset($rows[$key]);
                    }
                }

            }
        }

        return $rows;
    }
}
