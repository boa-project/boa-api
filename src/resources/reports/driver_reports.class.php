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
 * Class to Reports operations
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2017 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Driver_reports extends Driver_sql {

    /**
     *
     * Properties of the driver, with application level
     * @var object
     */
    protected $_properties;

    /**
     *
     *
     * @param object $properties
     */
    public function __construct ($properties) {

        parent::__construct($properties);
    }

    public function viewsData($timeinit, $timeend, $number, $start_on) {

        $sql = "SELECT resource, value, updated_at FROM {$this->_prefix}counters WHERE type = 'views' AND updated_at >= :timeinit AND updated_at <= :timeend ";

        //Limited records.
        if (is_numeric($number)) {
            $sql_number = ' LIMIT ';

            if (is_numeric($start_on)) {
                $sql_number .= intval($start_on) . ', ';
            }

            $sql_number .= intval($number);

            $sql .= $sql_number;
        }

        $params = array(':timeinit' => $timeinit, ':timeend' => $timeend);

        return $this->_connection->getListSQL($sql, null, $params);

    }

    public function scoreData($timeinit, $timeend, $number, $start_on) {

        $sql = "SELECT resource, value, updated_at FROM {$this->_prefix}counters WHERE type = 'score' AND context = 'avg' AND updated_at >= :timeinit AND updated_at <= :timeend GROUP BY resource";

        //Limited records.
        if (is_numeric($number)) {
            $sql_number = ' LIMIT ';

            if (is_numeric($start_on)) {
                $sql_number .= intval($start_on) . ', ';
            }

            $sql_number .= intval($number);

            $sql .= $sql_number;
        }

        $params = array(':timeinit' => $timeinit, ':timeend' => $timeend);

        return $this->_connection->getListSQL($sql, null, $params);

    }

    public function commentsData($timeinit, $timeend, $number, $start_on) {

        $sql = "SELECT resource, owner, content, updated_at FROM {$this->_prefix}comments WHERE updated_at >= :timeinit AND updated_at <= :timeend";

        //Limited records.
        if (is_numeric($number)) {
            $sql_number = ' LIMIT ';

            if (is_numeric($start_on)) {
                $sql_number .= intval($start_on) . ', ';
            }

            $sql_number .= intval($number);

            $sql .= $sql_number;
        }

        $params = array(':timeinit' => $timeinit, ':timeend' => $timeend);

        return $this->_connection->getListSQL($sql, null, $params);

    }
}
