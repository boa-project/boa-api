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
 * Class to Counters operations
 *
 * @author David Herney <davidherney@gmail.com>
 * @package BoA.Api
 * @copyright  2017 BoA Project
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero GPL v3 or later
 */
class Driver_counters extends Driver_sql {

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

    public function getSocialCounters($resource, $types) {

        if (count($types) == 0) {
            return null;
        }

        $types = "'" . implode("','", $types) . "'";
        $sql = "SELECT type, value, context FROM {$this->_prefix}counters WHERE resource = :resource AND type IN ($types)";

        $params = array(':resource' => $resource);

        return $this->_connection->getListSQL($sql, null,  $params);

    }

    public function increase($type, $increase, $resource) {
        $sql = "UPDATE {$this->_prefix}counters SET value = value + :increase, updated_at = :updated_at WHERE resource = :resource AND type = :type";

        $datatypes = array('integer', 'text', 'text', 'integer');
        $parameters = array(':increase' => (int)$increase,
                            ':resource' => $resource,
                            ':type' => $type,
                            ':updated_at' => time());

        $affected = $this->_connection->excecute($sql, $datatypes, $parameters);

        if (!$affected) {
            $this->insert('counters', array('resource' => $resource, 'type' => $type, 'value' => $increase, 'updated_at' => time()));
        }
    }
}
