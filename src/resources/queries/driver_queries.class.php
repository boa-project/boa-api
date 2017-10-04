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
 * Class to Queries operations
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2017 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Driver_queries extends Driver_sql {

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

    public function getLastQueries($catalog, $query, $order, $number, $start_on) {

        $sql_order = '';
        if (is_array($order) && count($order) > 0) {
            foreach ($order as $key=>$value) {
                $sql_order .= '`' . $key . '` ' . $value . ', ';
            }
        }
        else if (!empty($order) && is_string($order)) {
            $sql_order .= '`' . $order . '`, ';
        }

        if (!empty($sql_order)) {
            $sql_order = rtrim($sql_order, ', ');
            $sql_order = ' ORDER BY ' . $sql_order;
        }

        // Limited records.
        $sql_number = '';
        if (is_numeric($number)) {
            $sql_number = ' LIMIT ';

            if (is_numeric($start_on)) {
                $sql_number .= intval($start_on) . ', ';
            }

            $sql_number .= intval($number);
        }


        $sql = "SELECT query, AVG(size) AS size, MAX(time) AS time, COUNT(1) AS attempt FROM {$this->_prefix}queries WHERE catalog = :catalog AND size > 0 AND query LIKE :query GROUP BY query {$sql_order} {$sql_number}";

        $params = array(':catalog' => $catalog, ':query' => $query);

        return $this->_connection->getListSQL($sql, null,  $params);

    }
}