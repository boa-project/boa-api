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
 * Class DependenceEntitiesTree
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class DependenceEntitiesTree {

    public $Main;
    public $SelectedFields = '*';
    public $Prefixed = false;
    
    private $_dependences = array();
    
    const INTEGRITY_STRONG = 'STRONG';
    const INTEGRITY_DEPENDENCYTO = 'DEPENDENCYTO';
    const INTEGRITY_DEPENDENCYFROM = 'DEPENDENCYFROM';

    const OPERATOR_EQUAL = '=';
    const OPERATOR_NOTEQUAL = '!=';
    
    const ORDER_LESSTOGREATER = 'ASC';
    const ORDER_GREATERTOLESS = 'DESC';

    public function __construct($main) {
        $this->Main = $main;
    }
    
    public function AddDependence ($entity, $entity_to, $field_from, $field_to, $relational_operator = NULL, $integrity = NULL, $selected_fields = '', $conditions = NULL, $order = NULL, $alias = NULL) {
        if (!$integrity) {
            $integrity = self::INTEGRITY_STRONG;
        }
        
        if (!$alias) {
            $alias = $entity;
        }

        if (!$relational_operator) {
            $relational_operator = self::OPERATOR_EQUAL;
        }

        $dependence = new stdClass();
        $dependence->Entity = $entity;
        $dependence->EntityTo = $entity_to;
        $dependence->FieldFrom = $field_from;
        $dependence->FieldTo = $field_to;
        $dependence->RelationalOperator = $relational_operator;
        $dependence->Integrity = $integrity;
        $dependence->SelectedFields = $selected_fields;
        $dependence->Conditions = $conditions;
        $dependence->Order = $order;
        $dependence->Alias = $alias;

        $this->_dependences[] = $dependence;
        
    }
    
    public function getDependences (){
        return $this->_dependences;
    }
    
}


