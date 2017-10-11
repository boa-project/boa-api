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
 * Class to manage the resource action
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Resource extends ComplexObject {

    private $_query_driver;

    public function __construct($catalog_id, $id) {

        $data = Restos::$DefaultRestGeneric->getDriverData("resources");

        $this->_query_driver = DriverManager::getDriver('BoA', $data->Properties, 'resources.resources');

        if (!$this->_query_driver) {
            Restos::throwException(new Exception(RestosLang::get('exception.drivernotexists', false, 'BoA')));
        }

        $data = $this->loadData($catalog_id, $id);
        $data->about = Restos::URIRest('c/' . $catalog_id . '/resources/' . $id);
        $this->Data = $data;

    }


    private function loadData($catalog_id, $id){
        $catalog = $this->_query_driver->getCatalogue($catalog_id);

        if ($catalog == null){
            Restos::throwException(null, RestosLang::get('searchengine.catalognotfound', 'boa', $catalog_id), 404);
        }

        $path = realpath($catalog->path);
        if ($path === false || !file_exists($path)){
            Restos::throwException(null, RestosLang::get('searchengine.catalogpathnotfound', 'boa', $catalog_id), 404);
        }

        $decodeid = base64_decode($id, true);

        if ($decodeid === false) {
            Restos::throwException(null, RestosLang::get('searchengine.badid', 'boa', $id), 404);
        }

        $manifest = "{}";
        $metadataPath = "";

        chdir($path);
        $realpath = realpath($decodeid);

        if ($realpath === false){
            Restos::throwException(null, RestosLang::get('searchengine.badid', 'boa', $id), 404);
        }

        $path .= (substr($path, -1) === '/' ? '' : '/');
        $realpath = str_replace($path, '', $realpath);

        if (!file_exists($path . $realpath)){
            Restos::throwException(null, RestosLang::get('notfound'), 404);
        }

        if (strpos($decodeid, '/') === false){
            $manifestPath = $path . $realpath . "/.manifest";
            $manifest = file_get_contents($manifestPath);
            $metadataPath = $path . $realpath . "/.metadata";
        }
        else {
            $basedir = dirname($realpath);
            $filename = basename($realpath);
            $metadataPath = $path . $basedir . "/." . $filename . ".metadata";
        }

        $metadata = "{}";
        if (file_exists($metadataPath)) {
            $metadata = file_get_contents($metadataPath);
        }
        $data = json_decode("{\"manifest\":$manifest,\"metadata\":$metadata}");
        $data->id = $decodeid;
        return $data;
    }

}
