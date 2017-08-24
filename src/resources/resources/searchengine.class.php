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
 * Class to manage the type of search engines
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2016 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class SearchEngine {

    protected $_parameters;

    protected $_driver;

    public function __construct($driver, $parameters) {

        $this->_driver     = $driver;
        $this->_parameters = $parameters;
    }

    public function queryExecute ($query = null, $number = null, $start_on = null, $groups = null) {
        return array();
    }

    public function cron (RestosCron $cron) {
        $cron->addLog(RestosLang::get('searchengine.cronnotimplemented', 'boa'));
    }
}
