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

Restos::using('resources.resources.searchengine');

/**
 * Class to manage the Solr search engine
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class SearchEngine_solr extends SearchEngine {

    public function queryExecute ($oData = null, $number = null, $start_on = null, $groups = null) {
        //ToDo: execute query
        // Available: $this->_parameters (ResourcesConfiguration.resources.Properties.Engines[solr].Parameters)
        // Can use $this->_driver (it is a Driver_BoA object) in order to get one catalogue or the catalogue list
        return array();
    }

    public function cron (RestosCron $cron) {
        // ToDo: build index
        // Use the next method in order to add log messages:
        $cron->addLog(RestosLang::get('searchengine.solr.XX', 'boa'));
    }
}