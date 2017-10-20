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
 * Class to manage the resources mapping
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class RestMapping_Resources extends RestMapping {

    /**
     * 
     * Construct
     * @param object or array $data
     */
    public function __construct($data) {
        parent::__construct($data, "resource", "resources");
    }

    public function getMapping($type, $resource = null) {

        if ($type == 'IMG' && $resource) {
            $path = $resource->getCustomIconPath();

            if ($path && file_exists($path)) {
                $parts = explode('.', $path);
                $ext = strtolower($parts[count($parts) - 1]);
                Restos::$DefaultRestGeneric->RestResponse->Type = $ext;

                return file_get_contents($path);
            }
            else {
                Restos::$DefaultRestGeneric->RestResponse->Type = 'PNG';
                return file_get_contents('resources/resources/assets/icon.png');
            }
        }
        else {
            return parent::getMapping($type);
        }

//         $parts = explode('.', $this->_data->file);
//
//         $ext = strtolower($parts[count($parts) - 1]);
//         if ($ext != strtolower($type)) {
//             if ($type == 'XML' || $type == 'JSON') {
//                 $res = array('type'=>$ext, 'expire_time'=>$this->_data->expire_time, 'content-type'=>RestosMimeTypes::getMimeType($ext));
//                 return $res;
//             }
//             return null;
//         }
//
//         $path_file = $this->_data->path . $this->_data->file;
//
//         $res = '';
//         if (file_exists($path_file)) {
//             $res = file_get_contents($path_file);
//         }
//
//         return $res;

    }


}
