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
 * Class to manage default operation in complex objects
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class ComplexObject {

    /**
     *
     * Persistence driver object
     * @var object
     */
    private $_driver; 
        
    /**
     *
     * Entity name
     * @var string
     */
    private $_entity;
        
    /**
     *
     * Entity data
     * @var object
     */
    private $_data;
    
    /**
     *
     * Array of required fields
     * @var array
     */
    protected $_structure;
    
    /**
     *
     * Object construct
     *
     * @param object $driver Persistence driver object
     * @param string $entity_name Entity name
     * @param int $id Object identification
     * @throws ObjectNotFoundException
     * @throws DriverNotAvailableException
     */
    public function __construct($driver, $entity_name, $id) {
        $this->_driver = $driver;
        $this->_entity = $entity_name;
        
        if (empty($driver) || !is_object($driver)) {
            throw new DriverNotAvailableException(RestosLang::get('exception.drivernotavailable'));
        }

        $this->_structure = $driver->getEntityStructure($entity_name);
        
        if (!empty($id)) {
            $res = $driver->getEntity($entity_name, array('id'=>$id));

            if ($res && is_object($res)) {
                $this->_data = $res;
            }
            else {
                throw new ObjectNotFoundException(RestosLang::get('exception.objectnotfound'));
            }
        }
        else {
            $this->_data = new stdClass();
        }
    }
    
    public function loadByFilter($conditions) {
        $res = $this->_driver->getEntity($this->_entity, $conditions);

        if ($res && is_object($res)) {
            $this->_data = $res;
        }
        else {
            throw new ObjectNotFoundException(RestosLang::get('exception.objectnotfound'));
        }
    }
    
    /**
     *
     * Load data dependences
     *
     * @param int $depth Depth of relations to load
     */
    public function loadDependences ($depth = 0) {
    }

    /**
     *
     * Load an array with the object data
     *
     * @param string $resource_name The name that identifies the resource. If is not specified, entoty name is used
     * @param int $return_core is a sum of properties to return. 1 to created_at; 2 to updated_at; 4 to created_by; 8 to updated_by
     * @return array Object prototype
     */
    public function getPrototype ($resource_name = '', $return_core = 0) {

        $prototype = null;

        if (is_object($this->_data) && isset($this->_data->id)) {
            
            $prototype = $this->_data;
            
            if (empty($resource_name)) {
                $resource_name = $this->_entity;
            }

            //$prototype->about = Restos::URIRest($resource_name . '/' . $this->_data->id);

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
    
    public function setData ($data) {
        if (is_object($data)) {
            $this->_data = $data;
        }
        else if (is_array($data)) {
            $this->_data = (object)$data;
        }
        else {
            throw new BadDataTypeException();
        }
    }

    public function getData () {
        return $this->_data;
    }

    /**
     *
     * Persist current object data. If object exist this is updated else it is created
     *
     * @param object $data Optional, data to persist
     * @return bool True if object is saved, false in other case
     */
    public function save ($data = null) {
        if ($data && is_object($data)) {
            $this->_data = $data;
        }
        
        $values = $this->dataToValues();
        unset($values['id']);
        
        if (!property_exists($this->_data, 'id') || empty($this->_data->id)) {
            $id = $this->_driver->insert($this->_entity, $values);
            if($id) {
                $this->_data->id = $id;
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return $this->_driver->update($this->_entity, $values, array('id'=>$this->_data->id));
        }
    }
    
    /**
     *
     * Delete current object data.
     *
     * @return bool True if object is deleted, false in other case
     */
    public function remove () {
        
        if (empty($this->_data) || !property_exists($this->_data, 'id') || empty($this->_data->id)) {
            $this->_data = new stdClass();
            
            return true;
        }
        else {
            return $this->_driver->delete($this->_entity, array('id'=>$this->_data->id));
        }
    }

    /**
     *
     * Clean and change data before a percistence operation
     * 
     * @return array
     */
    public function dataToValues() {
        //implement in inherit classes
        return (array)$this->_data;
    }

    public function isValid($data = null, $only_field_exists = false){
        if ($data && is_object($data)) {
            $this->_data = $data;
        }
        
        return $this->_structure->validateEntity($this->_data, $only_field_exists);
    }

    public function validate($data = null, $only_field_exists = false){
        if ($data && is_object($data)) {
            $this->_data = $data;
        }
        
        return $this->_structure->validateEntity($this->_data, $only_field_exists, true, true);
    }
    
    public function unsetField ($field) {
        if (isset($this->_data) && property_exists($this->_data, $field)){
            unset($this->_data->$field);
        }
    }

    /**
     *
     * In order to validate if property exist in current entity
     */
    public function property_exists($name) {
        if (property_exists($this, $name)){
            return true;
        }
        else if (isset($this->_data) && property_exists($this->_data, $name)){
            return true;
        }
        else if(method_exists($this, 'get' . $name)) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     *
     * Overwriting special method "get"
     */
    public function __get($name) {
        if (property_exists($this, $name)){
            return $this->$name;
        }
        else if (isset($this->_data) && property_exists($this->_data, $name)){
            return $this->_data->$name;
        }
        else if(method_exists($this, 'get' . $name)) {
            return call_user_func(array($this, 'get' . $name));
        }
        else {
            throw new Exception('propertie_or_method_not_found: ' . get_class($this) . '->'. $name);
        }
    }

    /**
     *
     * Overwriting special method "put"
     */
    public function __set($name, $value) {
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

}

/**
 *
 * Custom exception to indicate when an object is not found
 */
class ObjectNotFoundException extends Exception { }

/**
 *
 * Custom exception to indicate when a driver is null or not is an object
 */
class DriverNotAvailableException extends Exception { }

/**
 *
 * Custom exception to indicate when a bad data type is received
 */
class BadDataTypeException extends Exception {
    public function __construct($message = "", $code = 0, $previous = NULL) {
        if (empty($message)) {
            $message = RestosLang::get('exception.baddatatype');
        }
        parent::__construct($message, $code, $previous);
    }
}
