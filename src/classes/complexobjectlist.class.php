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

Restos::using('classes.complexobject');

/**
 * Class to manage default operation in list of complex objects
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class ComplexObjectList {

    /**
     *
     * Persistence driver object
     * @var object
     */
    private $_driver; 
        
    /**
     *
     * Entity data
     * @var Mixed
     */
    private $_entity;
        
    /**
     *
     * Entity name
     * @var string
     */
    private $_entity_name;
        
    /**
     *
     * Entity data
     * @var array
     */
    private $_data;
    
    /**
     *
     * Object construct
     *
     * @param object $driver Persistence driver object
     * @param mixed $entity Entity name or DependenceEntitiesTree
     * @throws ObjectNotFoundException
     * @throws DriverNotAvailableException
     */
    public function __construct($driver, $entity, $autoload_all = false, $conditions = null, $order = null, $number = null, $start_on = null) {
        $this->_driver = $driver;
        $this->_entity = $entity;
        $this->_entity_name = is_object($entity) ? $entity->Main : $entity;
        
        if (empty($driver) || !is_object($driver)) {
            throw new DriverNotAvailableException(RestosLang::get('exception.drivernotavailable'));
        }
        
        if ($autoload_all) {
            $res = $driver->getList($entity, $conditions, $order, $number, $start_on);

            if (is_array($res)) {
                if(is_array($res)) {
                    $this->_data = $res;
                }
                else {
                    $this->_data = array();
                }
            }
            else {
                throw new ObjectNotFoundException(RestosLang::get('exception.objectnotfound'));
            }
        }
        else {
            $this->_data = array();
        }
    }
    
    /**
     *
     * Load an array with the object data
     *
     * @param string $resource_name The name that identifies the resource. If is not specified, entity name is used
     * @param int $return_core is a sum of properties to return. 1 to created_at; 2 to updated_at; 4 to created_by; 8 to updated_by
     * @return array Object prototype
     */
    public function getPrototype ($resource_name = '', $return_core = 0) {

        $prototypes = null;

        if (is_array($this->_data)) {
            
            $prototypes = array();
            foreach($this->_data as $data) {
                if ($prototype = $this->_prototype($data, $resource_name, $return_core)) {
                    $prototypes[] = $prototype;
                }
            }
        }
        
        return $prototypes;
    }
    
    /**
     *
     * @param int $return_core is a sum of properties to return. 1 to created_at; 2 to updated_at; 4 to created_by; 8 to updated_by
     */
    protected function _prototype ($data, $resource_name, $return_core = 0) {
        $prototype = null;
        if (is_object($data) && isset($data->id)) {
            if (empty($resource_name)) {
                $resource_name = $this->_entity_name;
            }

            $prototype                 = $data;//(array)$data;
            //$prototype->about          = Restos::URIRest($resource_name . '/' . $data->id);

            if (!in_array($return_core, array(1, 3, 5, 7, 9, 11, 13, 15))) {
                unset($prototype->created_at);
            }

            if (!in_array($return_core, array(2, 3, 6, 10, 7, 11, 14, 15))) {
                unset($prototype->updated_at);
            }

            if (!in_array($return_core, array(4, 5, 6, 12, 7, 13, 14, 15 ))) {
                unset($prototype->created_by);
            }

            if (!in_array($return_core, array(8, 9, 10, 11, 12, 13, 14, 15))) {
                unset($prototype->updated_by);
            }
        }
        return $prototype;
    }
    
    /**
     *
     */
    public function getPrototypeByKey ($key, $resource_name = '') {
        foreach($this->_data as $data) {
            if (is_object($data) && isset($data->id) && $data->id == $key) {
                return $this->_prototype($data, $resource_name);
            }
        }
        return null;
    }
    
    public function length () {
        return $this->_data && is_array($this->_data) ? count($this->_data) : 0;
    }

    public function items ($index = null) {
        if ($index === null) {
            return $this->_data;
        }
        else if (isset($this->_data[$index])) {
            return $this->_data[$index];
        }
        
        return null;
    }
    
    public function unsetItem ($index) {
        if (isset($this->_data[$index])){
            unset($this->_data[$index]);
        }
    }

    /**
     *
     * Overwriting special method "get"
     */
    public function __get($name)
    {
        if (property_exists($this, $name)){
            return $this->$name;
        }
        else if (is_object($this->_data) && property_exists($this->_data, $name)){
            return $this->_data->$name;
        }
        else if(method_exists($this, 'get' . $name)) {
            return call_user_func(array($this, 'get' . $name));
        }
        else if(method_exists($this, $name)) {
            return call_user_func(array($this, $name));
        }
        else {
            throw new Exception('propertie_or_method_not_found: ' . get_class($this) . '->'. $name);
        }
    }

    /**
     *
     * Overwriting special method "put"
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)){
            $this->$name = $value;
        }
        else if(method_exists($this, 'set' . $name)) {
            return call_user_func(array($this, 'set' . $name), $value);
        }
        else if (is_object($this->_data)){
            $this->_data->$name = $value;
        }
    }

    public function count($conditions) {
        return $this->_driver->countEntityRecords($this->_entity, $conditions);
    }
}
