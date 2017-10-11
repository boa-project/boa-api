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

/**
 * Class to manage the resources action
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Resources extends ComplexObjectList {

    private $_engineobject;
    private $_query_driver;

    public function __construct($engine) {

        $data = Restos::$DefaultRestGeneric->getDriverData("resources");

        $this->_query_driver = DriverManager::getDriver('BoA', $data->Properties, 'resources.resources');

        if (!$this->_query_driver) {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotexists', false, 'BoA')));
        }

        if (empty($engine)) {
            if (property_exists($data->Properties, 'DefaultEngine')) {
                $engine = $data->Properties->DefaultEngine;
            }
            else {
                Restos::throwException(null, RestosLang::get('searchengine.empty', 'boa'), 400);
            }
        }

        $engines = array();
        if (property_exists($data->Properties, 'Engines')) {
            $engines = $data->Properties->Engines;
        }

        $enabled = false;
        $parameters = null;
        foreach($engines as $one) {
            if ($one->Code == $engine) {
                $enabled = $one->Enabled;
                $parameters = $one->Parameters;
                break;
            }
        }

        if (!$enabled) {
            Restos::throwException(null, RestosLang::get('searchengine.notenabled', 'boa', $engine), 400);
        }

        $engineclass = 'SearchEngine_' . $engine;

        if (!Restos::using('resources.resources.engines.' . $engine . '.' . strtolower($engineclass))) {
            Restos::throwException(null, RestosLang::get('searchengine.notinstalled', 'boa', $engine), 500);
        }

        $this->_engineobject = new $engineclass($this->_query_driver, $parameters);

    }

    public function execute($query = null, $number = null, $start_on = null, $filters = null, $mode = null) {
        $list = $this->_engineobject->queryExecute($query, $number, $start_on, $filters, $mode);

        foreach($list as $one) {
            $one->about = Restos::URIRest('c/' . $one->catalog_id . '/resources/' . base64_encode($one->id));
        }

        return $list;
    }

    public static function getSearchEngines () {
        $data = Restos::$DefaultRestGeneric->getDriverData("resources");

        $engines = array();

        if (property_exists($data->Properties, 'Engines')) {
            $engines = $data->Properties->Engines;
        }

        foreach($engines as $k => $one) {
            if (!$one->Enabled) {
                unset($engines[$k]);
                break;
            }
        }

        return $engines;
    }

}
