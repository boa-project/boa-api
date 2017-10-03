<?php

/*
 *  This file is part of Restos software
 *
 *  Restos is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Restos is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Restos.  If not, see <http://www.gnu.org/licenses/>.
 */

Restos::using('drivers.ipersistenceoperations');

/**
 * Class Driver_sql
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Driver_sql implements iPersistenceOperations {

    /**
     *
     * Properties of the driver, with application level
     * @var object
     */
    protected $_properties;

    /**
     *
     * Object to manage SQL conexions and queries
     * @var Connector_relationaldb
     */
    protected $_connection;

    /**
     *
     * Table name prefix
     * @var string
     */
    protected $_prefix = '';

    /**
     *
     *
     * @param object $properties
     * @throws Exception - ConnectionString is required.
     * @throws Exception - Others in PEAR MDB2.
     */
    public function __construct($properties){
        if(!is_object($properties)) {
            $properties = Restos::$Properties->Defaults->DBProperties;
        }

        if (!isset($properties->ConnectionString)){
            if (property_exists(Restos::$Properties->Defaults->DBProperties, 'ConnectionString')) {
                $properties->ConnectionString = Restos::$Properties->Defaults->DBProperties->ConnectionString;
            }
            else {
                throw new Exception('ConnectionString is required.');
            }
        }

        $this->_properties = $properties;

        $options = array();
        if (!empty($properties->Options)) {
            $options = (array)$properties->Options;
        }

        if (property_exists($properties, 'Prefix')) {
            $this->_prefix = $properties->Prefix;
        }

        Restos::using('data_handlers.relationaldb.connector_relationaldb');
        $connector = new Connector_relationaldb($properties->ConnectionString, $options);

        $this->_connection = $connector;
    }


    /**
     *
     * Return a record as object
     * @param string $entity Table name
     * @param array $conditions
     * @return object
     */
    public function getEntity($entity, $conditions){

        return $this->_connection->getEntity($this->_prefix . $entity, $conditions);

    }

    /**
     *
     * Return the entities length
     * @param string $entity Table name
     * @param array $conditions
     * @return int
     */
    public function countEntityRecords($entity, $conditions = null) {

        if (is_object($entity)) {
            if (!$entity->Prefixed) {
                $entity->Main = $this->_prefix . $entity->Main;

                $dependences = $entity->getDependences();
                if (is_array($dependences) && count($dependences) > 0) {
                    foreach($dependences as $value) {
                        if ($value->Alias == $value->Entity) {
                            $value->Alias = $this->_prefix . $value->Entity;
                        }

                        $value->Entity = $this->_prefix . $value->Entity;
                        $value->EntityTo = $this->_prefix . $value->EntityTo;
                    }
                }

                $entity->Prefixed = true;
            }
        }
        else {
            $entity = $this->_prefix . $entity;
        }

        return $this->_connection->countList($entity, $conditions);
    }

    /**
     *
     * Return a list of records as object
     * @param string $entity Table name
     * @param array $conditions
     * @param array or string $order
     * @param int $number
     * @param int start_on
     * @return object
     */
    public function getList($entity, $conditions = null, $order = null, $number = null, $start_on = null){

        if (is_object($entity)) {
            if (!$entity->Prefixed) {
                $entity->Main = $this->_prefix . $entity->Main;

                $dependences = $entity->getDependences();
                if (is_array($dependences) && count($dependences) > 0) {
                    foreach($dependences as $value) {
                        if ($value->Alias == $value->Entity) {
                            $value->Alias = $this->_prefix . $value->Entity;
                        }

                        $value->Entity = $this->_prefix . $value->Entity;
                        $value->EntityTo = $this->_prefix . $value->EntityTo;
                    }
                }
                $entity->Prefixed = true;
            }
        }
        else {
            $entity = $this->_prefix . $entity;
        }

        return $this->_connection->getList($entity, $conditions, $order, $number, $start_on);

    }

    /**
     *
     * Insert a record
     * @param string $entity Table name
     * @param array $values
     * @return int New record ID
     */
    public function insert($entity, $values) {
        return $this->_connection->insert_record($this->_prefix . $entity, $values, true);
    }

    /**
     *
     * Update records
     * @param string $entity Table name
     * @param array $values
     * @param array $conditions
     * @param bool $onlyone
     * @return bool true if successful, false in other case
     */
    public function update($entity, $values, $conditions, $onlyone = true) {
        if ($onlyone) {
            return $this->_connection->update_record($this->_prefix . $entity, $values, $conditions);
        }
        else {
            return $this->_connection->update($this->_prefix . $entity, $values, $conditions);
        }
    }

    /**
     *
     * Delete records
     * @param string $entity Table name
     * @param array $conditions
     * @param bool $onlyone
     * @return bool true if successful, false in other case
     */
    public function delete($entity, $conditions, $onlyone = true) {
        if ($onlyone) {
            return $this->_connection->delete_record($this->_prefix . $entity, $conditions);
        }
        else {
            return $this->_connection->delete($this->_prefix . $entity, $conditions);
        }
    }

    public function getEntityStructure($entity) {
        $def = $this->_connection->table_info($this->_prefix . $entity);

        $structure = new EntityStructure();
        if (is_array($def)) {

            foreach($def as $field) {

                //Value length only to varchar fields
                $len = $this->_SQLEquivalentType($field['type']) == EntityAttribute::TYPE_STRING && isset($field['length']) ? $field['length'] : 0;

                $default = isset($field['default']) ? $field['default'] : null;

                $atr = new EntityAttribute($field['name'], $field['notnull'], $this->_SQLEquivalentType($field['type']), $len, $default);

                $structure->setAttribute($atr);
            }
        }

        return $structure;
    }

    private function _SQLEquivalentType ($type) {
        switch ($type) {
            case 'int':
            case 'integer':
            case 'smallint':
            case 'tinyint':
            case 'mediumint':
                return EntityAttribute::TYPE_INT;
            case 'decimal':
                return EntityAttribute::TYPE_DOUBLE;
            default:
                return EntityAttribute::TYPE_STRING;
        }
    }

    public function getProperty($key) {
        if (property_exists($this->_properties, $key)) {
            return $this->_properties->$key;
        }

        return null;
    }

    public function getProperties() {
        return $this->_properties;
    }
}
