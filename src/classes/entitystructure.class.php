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
 * Class EntityStructure
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class EntityStructure {

    private $_attributes = array();
    
    private $_requireds = array();
    

    public function setAttribute($attribute) {
        if($attribute->IsRequired) {
            $this->_requireds[] = $attribute;
        }
        
        $this->_attributes[$attribute->Name] = $attribute;
    }
    
    public function getAttribute($name) {
        if(isset($this->_attributes[$name])) {
            return $this->_attributes[$name];
        }
        
        return null;
    }

    public function validateEntity ($entity, $only_field_exists = false, $correct_data = false, $correct_structure = false) {
    
        //All id validations
        if (property_exists($entity, 'id') && $entity->id !== null && (!$entity->id || !is_numeric($entity->id))) {
            return false;
        }
        
        foreach($this->_attributes as $attribute) {

            if ($attribute->Name == 'id') {
                continue;
            }
            
            if (!property_exists($entity, $attribute->Name) && $only_field_exists) {
                continue;
            }
            
            if (property_exists($entity, $attribute->Name)) {
            
                if ($entity->{$attribute->Name} === null && $attribute->IsRequired) {
                    if ($correct_data && $attribute->Default !== null && $attribute->Default !== '') {
                        $entity->{$attribute->Name} = $attribute->Default;
                    }
                    else {
                        return false;
                    }
                }
                
                if ($entity->{$attribute->Name} !== null) {
                    //Valide length
                    if ($attribute->Length > 0 && strlen($entity->{$attribute->Name}) > $attribute->Length) {
                        if ($correct_data) {
                            $entity->{$attribute->Name} = substr($entity->{$attribute->Name}, 0, $attribute->Length);
                        }
                        else {
                            return false;
                        }
                    }
                    
                    //Valid value type
                    switch ($attribute->Type) {
                        case EntityAttribute::TYPE_INT:
                            if (!is_numeric($entity->{$attribute->Name})) {
                                if ($correct_data && $entity->{$attribute->Name} == '' && !$attribute->IsRequired) {
                                    $entity->{$attribute->Name} = null;
                                }
                                else {
                                    return false;
                                }
                            }
                            break;
                    }
                    
                    //Valid value in collection
                    if (count($attribute->Values) > 0) {
                        $valid = false;
                        foreach($attribute->Values as $one_value) {
                            if ($one_value == $entity->{$attribute->Name}) {
                                $valid = true;
                                break;
                            }
                        }
                        
                        if (!$valid) {
                            if ($correct_data) {
                                if ($attribute->Default !== null && $attribute->Default !== '') {
                                    $entity->{$attribute->Name} = $attribute->Default;
                                }
                                else {
                                    $entity->{$attribute->Name} = $attribute->Values[0];
                                }
                            }
                            else {
                                return false;
                            }
                        }
                    }
                    
                }
            }
            else if ($attribute->IsRequired) {
                if ($correct_data && $attribute->Default !== null && $attribute->Default !== '') {
                    $entity->{$attribute->Name} = $attribute->Default;
                }
                else {
                    return false;
                }
            }
        }
        
        $filds_in_entity = (array)$entity;

        if ($correct_structure) {
            foreach ($filds_in_entity as $key=>$field) {
                if (!isset($this->_attributes[$key])) {
                    unset($entity->$key);
                }
            }
        }

        return true;
    }


}

class EntityAttribute {

    const TYPE_STRING  = 'string';
    const TYPE_INT     = 'int';
    const TYPE_BOOL    = 'bool';
    const TYPE_LONG    = 'long';
    const TYPE_DOUBLE  = 'double';
    
    public $Name;
    
    public $IsRequired;
    
    public $Type;
    
    public $Length;
    
    public $Default;
    
    public $Values;

    public function __construct($name, $is_required = false, $type = EntityAttribute::TYPE_STRING, $length = 0, $default = null, $values = null) {
        $this->Name = $name;
        $this->IsRequired = $is_required;
        $this->Type = $type;
        $this->Length = $length;
        $this->Default = $default;
        $this->Values = is_array($values) ? $values : array();
    }
}