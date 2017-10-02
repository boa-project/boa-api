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
 * Class to manage the Resources cron executions
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Cron_Resources extends RestosCron {

    public function __construct() {
    }

    public function execute (){

        Restos::using('resources.resources.resource');
        Restos::using('resources.resources.resources');

        User::id(1);

        $data = Restos::$DefaultRestGeneric->getDriverData("resources");

        $driver = DriverManager::getDriver('BoA', $data->Properties, 'resources.resources');

        if (!$driver) {
            $this->addLog(RestosLang::get('exception.drivernotexists', false, 'BoA'));
            return false;
        }

        $engines = Resources::getSearchEngines();

        foreach($engines as $engine) {
            $engineclass = 'SearchEngine_' . $engine->Code;

            if (!Restos::using('resources.resources.engines.' . $engine->Code . '.' . strtolower($engineclass))) {
                $this->addLog(RestosLang::get('searchengine.notinstalled', 'boa', $engine->Name));
                continue;
            }

            $engineobject = new $engineclass($driver, $engine->Parameters);

            $this->addLog(RestosLang::get('searchengine.cronexecute', 'boa', $engine->Name));
            $engineobject->cron($this);
        }


        return true;
    }

}