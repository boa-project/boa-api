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

    private $_catalog = null;

    private $_path = null;

    private $_realpath = null;

    private $_manifest = null;

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

        $current_path = getcwd();
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

        $this->_catalog = $catalog;
        $this->_realpath = $realpath;
        $this->_path = $path;

        if (strpos($decodeid, '/') === false){
            // It is a root Object.
            $manifestPath = $path . $realpath . "/.manifest.published";
            $manifestText = file_get_contents($manifestPath);
            $json = json_decode($manifestText);

            if (!property_exists($json->manifest, 'is_a')) {
                $json->manifest->is_a = 'dco';
            }

            $manifest_object = $json->manifest;

            $customiconname = null;
            if (property_exists($manifest_object, 'customicon')) {
                $customiconname =  $manifest_object->customicon;
                $manifest_object->customicon = Restos::URIRest('c/' . $catalog_id . '/resources/' . $id . '.img');
            }

            if ($customiconname) {
                $manifest_object->customiconname = $customiconname;
            }

            $manifest_object->alternate = array();
            // If it has an specific file as entry point, explore by alternate files
            if (property_exists($manifest_object, 'entrypoint')) {
                if ($altern_path = $this->getAlternatePath()) {
                    $altern_path .= '/content/' . $manifest_object->entrypoint;

                    $manifest_object->alternate = $altern_path;
                    if (file_exists($altern_path)) {

                        $files = scandir($altern_path,  SCANDIR_SORT_NONE);
                        $files = array_values(array_diff($files, array('..', '.')));

                        $manifest_object->alternate = $files;
                    }
                }
            }

            $this->_manifest = $manifest_object;
        }
        else {
            // It is a specific file.
            $basedir = dirname($realpath);
            $filename = basename($realpath);
            $manifestPath = $path . $basedir . "/." . $filename . ".manifest.published";
            $manifestText = file_get_contents($manifestPath);
            $json = json_decode($manifestText);

            $this->_manifest = $json->manifest;
            $this->_manifest->entrypoint = $filename;

            if (!property_exists($this->_manifest, 'is_a')) {
                $this->_manifest->is_a = 'dro';
            }

            $this->_manifest->alternate = array();
            // Explore by alternate files.
            if ($altern_path = $this->getAlternatePath()) {

                $files = scandir($altern_path,  SCANDIR_SORT_NONE);
                $this->_manifest->alternate = array_values(array_diff($files, array('..', '.')));
            }
        }

        // The customicon name is set by defect if not exists.
        if (!property_exists($json->manifest, 'customicon') || empty($json->manifest->customicon)) {
            $json->manifest->customicon = Restos::URIRest('c/' . $catalog_id . '/resources/' . $id . '.img');
        }

        $this->clearManifest($json);
        $data = $json;
        $data->id = $decodeid;

        chdir($current_path);
        return $data;
    }

    public function getCustomIconPath() {

        if (!($this->_manifest)
                || !property_exists($this->_manifest, 'customiconname')
                || empty($this->_manifest->customiconname)) {

            if ($altern_path = $this->getAlternatePath()) {

                $icon_path = $altern_path . '/thumb.png';

                if (file_exists($icon_path)) {
                    return $icon_path;
                }
            }
            return null;
        }

        return $this->_path . $this->_realpath . '/src/' . $this->_manifest->customiconname;
    }

    /**
     * @param $full If false return only the root object path.
     */
    private function getAlternatePath($full = true) {

        if (!isset($this->_path) || !isset($this->_realpath)) {
            return null;
        }

        $dirs = explode('/', $this->_realpath);
        $path = $this->_path . $dirs[0] . '/.alternate';

        if (!$full) {
            return file_exists($path) ? $path : null;
        }

        $filename = basename($this->_realpath);
        $specific_path = ltrim($this->_realpath, $dirs[0]);
        $specific_path = $path . $specific_path;

        return file_exists($specific_path) ? $specific_path : null;
    }

    public function getContent($path) {

        $res = new stdClass();
        $res->body = '';
        $res->type = null;

        if (isset($path)) {
            if (strpos($path, '.') !== false) {
                $parts = explode('.', $path);
                $ext = strtolower(array_pop($parts));
                $res->type = $ext;
            }

            if ($this->_manifest->is_a == 'dro') {
                $basepath = $path = $this->_path . $this->_realpath;
                $parts = explode('.', $path);
                $ext = strtolower(array_pop($parts));
                $res->type = $ext;
                Restos::$DefaultRestGeneric->RestResponse->setHeader('content-disposition', 'Content-disposition: inline; filename="' . $this->_manifest->entrypoint . '"');
            }
            else {
                $basepath = $this->_path . $this->_realpath . '/content';
                $path = realpath($basepath . '/' . $path);
            }

            $pos_basepath = strpos($path, $basepath);
            if ($pos_basepath !== false &&  $pos_basepath === 0) {
                if (is_dir($path)) {
                    // To guarantee that the last one char is '/'.
                    $path = rtrim($path, '/') . '/';
                    if (!empty($this->_manifest->entrypoint)) {
                        $path .= $this->_manifest->entrypoint;

                        $parts = explode('.', $path);
                        $ext = strtolower(array_pop($parts));
                        $res->type = $ext;
                        Restos::$DefaultRestGeneric->RestResponse->setHeader('content-disposition', 'Content-disposition: inline; filename="' . $this->_manifest->entrypoint . '"');
                    }
                    else {
                        $path .= 'index.html';
                        $res->type = 'html';
                    }
                }

                if (file_exists($path)) {
                    $res->body = file_get_contents($path);
                    return $res;
                }
            }
        }

        Restos::throwException(null, RestosLang::get('notfound'), 404);

    }

    private function clearManifest($json){
        unset($json->manifest->id);
        unset($json->manifest->status);
        unset($json->manifest->lastupdated);
    }
}
