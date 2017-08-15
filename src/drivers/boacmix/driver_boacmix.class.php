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


/**
 * Class Driver_boacmix
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Driver_boacmix {

    const CONTEXT_APP = 0;

    /**
     * 
     * Properties of the driver, with application level
     * @var object
     */
    private $_properties;

    /**
     * 
     * Config file location
     * @var string
     */
    private $_config = 'drivers/boacmix/configuration.json';
    
    /**
     *
     * Roles
     * @var array
     */
    private $_roles;
    
    /**
     *
     * Capabilities
     * @var object
     */
    private $_capabilities;

    /**
     *
     * Access
     * @var object
     */
    private $_access;

    /**
     *
     * Contexts
     * @var object
     */
    private $_contexts;
    
    /**
     *
     * Temporal user roles storage
     * @var array
     */
    private $_store_userroles = array();

    /**
     * 
     * Object to manage SQL conexions and queries 
     * @var Connector_relationaldb
     */
    private $_connection;

    /**
     *
     * Table name prefix
     * @var string
     */
    private $_prefix = '';
    
    /**
     *
     * Capability with access to all actions
     * @var string
     */
    private $_global_capability = 'app:all';

    /**
     * 
     * 
     * @param object $properties
     * @throws Exception - ConnectionString is required.
     * @throws Exception - Others in PEAR MDB2.
     */
    public function __construct($properties){
        if(!is_object($properties) || !isset($properties->ConnectionString)){
            throw new Exception('ConnectionString is required.');
        }

        $this->_properties = $properties;
        
        if (property_exists($properties, 'FileSource')) {
            $this->_config = $properties->FileSource;
        }

        $options = array();
        if (!empty($properties->Options)) {
            $options = (array)$properties->Options;
        }

        if (property_exists($properties, 'Prefix')) {
            $this->_prefix = $properties->Prefix;
        }

        if (file_exists($this->_config)) {
            $data_string = file_get_contents($this->_config);
        }
        else {
            $data_string = '{}';
        }

        $data = json_decode($data_string);
        
        if (!$data || !is_object($data)) {
            $data = new stdClass();
        }
        
        if (!property_exists($data, 'Roles')) {
            $data->Roles = new stdClass();
        }

        if (!property_exists($data, 'Capabilities')) {
            $data->Capabilities = new stdClass();
        }
        
        if (!property_exists($data, 'Contexts')) {
            $data->Contexts = new stdClass();
        }
        
        $this->_roles = get_object_vars ($data->Roles);
        $this->_capabilities = $data->Capabilities;
        $this->_access = $data->Roles;
        $this->_contexts = $data->Contexts;
        
        Restos::using('data_handlers.relationaldb.connector_relationaldb');
        $connector = new Connector_relationaldb($properties->ConnectionString, $options);
        
        $this->_connection = $connector;
    }


    /**
     * 
     * if a rol has a capability in a especific context
     * @param array $data Capability data. 0 position is tha capability name
     * @return bool
     */
    public function can($data){

        if (!is_array($data) || count($data) == 0 || !is_string($data[0])) {
            Restos::log(Restos::LOG_DEBUG, 'driver_boacmix (can): Capability can not empty and must be a string');
            return false;
        }

        if (!property_exists($this->_capabilities, $data[0])) {
            Restos::log(Restos::LOG_DEBUG, 'driver_boacmix (can): Capability ' . $data[0] . ' does not exists');
            return false;
        }

        $capability_id = $this->_capabilities->$data[0];
        $capability_name = $data[0];
        
        $contexts = array();
        if (count($data) > 1) {
            
            //If is used the syntax: can('capability', Driver_boacmix::CONTEXT_APP, $id) //$id can be empty or null
            if (is_int($data[1]) && count($data) == 2) {
                $contexts = array($data[1] => null);
            }
            else if (is_int($data[1]) && count($data) == 3) {
                $contexts = array($data[1] => $data[2]);
            }
            else if (is_array($data[1])) {
                $contexts = $data[1];
            }
            else {
                Restos::log(Restos::LOG_DEBUG, 'driver_boacmix (can): Bad parameters number (' . count($data[0]) . ') or types. Capability: ' . $data[0]);
                return false;
            }
        }

        $userid = User::id();
        
        if (empty($userid)) {
            return false;
        }
        
        if (!isset($this->_store_userroles[$userid]) || !is_array($this->_store_userroles[$userid])) {

            $sql = 'SELECT role AS name, context, elementid FROM ' . $this->_prefix . 'roles_assigned WHERE user_id = ' . $userid;

            $rows = $this->_connection->getListSQL($sql);
            
            $roles = array();
            if (is_array($rows)) {
                $roles = $rows;
            }
            
            $this->_store_userroles[$userid] = $roles;
        }
        
        foreach ($this->_store_userroles[$userid] as $role) {
            if(isset($this->_roles[$role->name])) {
            
                //If the user has the global "full access" capability
                if($capability_name != 'sud:admin' && in_array($this->_capabilities->{$this->_global_capability}, $this->_access->{$role->name})) {
                    return true;
                }
                
                //If the user has the particular capability
                if( in_array($capability_id, $this->_access->{$role->name})) {
                    if (count($contexts) > 0) {
                        foreach ($contexts as $level => $element_id) {

                            if ($level == $role->context) {
                                if (!empty($element_id)) {
                                    if ($element_id == $role->elementid) {
                                        return true;
                                    }
                                }
                                else {
                                    return true;
                                }
                            }
                            else if ($level < $role->context && empty($element_id)) {
                                return true;
                            }
                        }
                    }
                    else {
                        if ($role->context <= self::CONTEXT_APP) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }

    public function getAccessElements($context, $role = null, $user_id = null) {
    
        $where = 'context = ' . $context;
        
        if ($role) {
            $where .= " AND role = '" . $role . "'";
        }
        
        if ($user_id) {
            $where = " user_id = '" . $user_id . "' AND " . $where;
        }
        else if ($local_user_id = User::id()) {
            $where = " user_id = '" . $local_user_id . "' AND " . $where;
        }
        
        $sql = 'SELECT role AS name, context, elementid FROM ' . $this->_prefix . 'roles_assigned WHERE ' . $where;

        $rows = $this->_connection->getListSQL($sql);
        
        $roles = array();
        if (is_array($rows)) {
            $roles = $rows;
        }
        
        return $roles;
    
    }

    public function getAccessCapability($capability, $context = null, $user_id = null) {
    
        $elements = array();

        if (!$this->_capabilities->$capability) {
            return $elements;
        }
    
        $includes_roles = array();
        $roles_names = array();
        foreach($this->_roles as $r_name=>$role) {
            if (in_array($this->_capabilities->$capability, $role) || 
                    ($capability != 'sud:admin' && in_array($this->_capabilities->{$this->_global_capability}, $role))) {
                $includes_roles[] = $role;
                $roles_names[] = "'" . $r_name . "'";
            }
        }
        
        if (count($roles_names) == 0) {
            return $elements;
        }
    
        $where = 'role in (' . implode(',', $roles_names) . ')';
        
        if ($context) {
            $where .= ' AND context <= ' . $context;
        }
        
        if ($user_id) {
            $where = " user_id = '" . $user_id . "' AND " . $where;
        }
        else if ($local_user_id = User::id()) {
            $where = " user_id = '" . $local_user_id . "' AND " . $where;
        }
        
        $sql = 'SELECT role AS name, context, elementid FROM ' . $this->_prefix . 'roles_assigned WHERE ' . $where;

        $rows = $this->_connection->getListSQL($sql);
        
        if (is_array($rows)) {
            foreach($rows as $row) {
                if ($row->context == self::CONTEXT_APP) {
                    return array($row);
                }
            }
            $elements = $rows;
        }
        
        return $elements;
    
    }

    public function getRoles() {
        return $this->_roles;
    }
}
