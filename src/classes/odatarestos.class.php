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
 
Restos::using('third_party.parensparser.parensparser');

/**
 * Class oDataRestos. Process oData parameters received in request
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */

class oDataRestos extends Entity {
    
    private $_filter;
    private $_filter_valid;
    private $_components;
    
    private $_orderby;
    private $_order_fields;
    
    private $_valid_operators = array('eq', 'ne', 'neq', 'gt', 'ge', 'gte', 'lt', 'le', 'lte', 'mod', 'like', 'notlike');
    private $_operators_sql   = array('eq'=>'=', 'ne'=>'<>', 'neq'=>'<>', 'gt'=>'>', 'ge'=>'>=', 'gte'=>'>=', 'lt'=>'<', 'le'=>'<=', 'lte'=>'<=', 'mod'=>'%', 'like'=>'like', 'notlike'=>'not like');
    
    /**
     * Constructor
     * Initialize the general params
     */
    public function __construct($odata_uri = ''){
    
        $this->_components = array();
        $this->_order_fields = array();
        
        if (!empty($odata_uri)) {
            //ToDo: parse $odata_uri and assign in properties
        }
    }
    
    public function setFilter($value) {
    
        $this->_filter = $value;

        $value = $this->_preparse($value);
        
        $pp = new ParensParser();
        $sub_expressions = $pp->parse($value['expression']);

        $to_replace = array();
        foreach($value['values'] as $key=>$one) {
            $to_replace[$key] = '{$ODATARESTOS' . $key . '}';
        }
        
        array_walk_recursive($sub_expressions, 'replace_in_array', array($to_replace, $value['values']));

        //var_dump($sub_expressions);
        //exit;

        $operations = $this->_processExpression($sub_expressions);
        
        if (!is_array($operations)) {
            $operations = array($operations);
        }
        
        //First operation is not connected
        //$operations[0]->connector = '';
        //var_dump($operations);
        //exit;
        $this->_components = $operations;
        
        return;
    }

    public function getFilterFields() {
        return $this->_components;
    }

    public function applyFilter($elements) {
    
        if (!is_array($this->_components) || count($this->_components) == 0) {
            return $elements;
        }
    
        $new_elements = array();


        if (is_array($elements)) {
            foreach ($elements as $element) {

                $accomplish = true;
                if (is_object($element)) {
                    
                    $accomplish = $this->_applySubFilter($element, $accomplish);
                    
                    if ($accomplish) {
                        $new_elements[] = $element;
                    }
                }
            }
        }
        
        return $new_elements;
    
    }
    
    private function _applySubFilter($element, $components) {

        $connector = '';
        $accomplish = null;
        foreach($components as $component) {
            
            if (!property_exists($element, $component->left)) {
                if (RESTOS_DEBUG_MODE) {
                    Restos::throwException(null, 'Filter field not found: ' . $component->left, 400);
                }
                break;
            }
            
            switch($component->operator) {
                case 'eq':
                    $eval = $element->{$component->left} == $component->right;
                    break;
                case 'ne':
                    $eval = $element->{$component->left} != $component->right;
                    break;
                case 'gt':
                    $eval = $element->{$component->left} > $component->right;
                    break;
                case 'ge':
                    $eval = $element->{$component->left} >= $component->right;
                    break;
                case 'lt':
                    $eval = $element->{$component->left} < $component->right;
                    break;
                case 'le':
                    $eval = $element->{$component->left} <= $component->right;
                    break;
                case 'mod':
                    $eval = $element->{$component->left} % $component->right;
                    break;
            }
            
            $connector = $component->connector;

            if ($connector == '') {
                $accomplish = $eval;
            }
            else if ($connector == 'and') {
                $accomplish = $accomplish && $eval;
            }
            else if ($accomplish && $connector == 'or') {
                break;
            }
            else if ($eval && $connector == 'or') {
                $accomplish = true;
                break;
            }
            
        }
        
        return $accomplish;
    }

    private function _preparse ($value) {
        
        //Expresión tomada de: http://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
        $expression = "/('[^'\\\\]*(?:\\\\.[^'\\\\]*)*')/s";
        $slices = preg_split($expression, $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $value = '';
        $position = 0;
        $quot_values = array();
        foreach($slices as $one) {
            if (!empty($one) && substr($one, 0, 1) == "'" && substr($one, -1) == "'") {
                $value .= '{$ODATARESTOS' . $position . '}';
                $quot_values[$position] = $one;
                $position++;
            }
            else {
                $value .= $one;
            }
        }
        
        //var_dump($slices);
        //exit;
        
        $ie = 0;
        $i = 0;
        while ($i < strlen($value)) {
            
            if ($value[$i] == '(' || $i == 0) {
                $ie = $i;
            }
            
            if ((substr($value, $i, 4) == ' or ' || substr($value, $i, 5) == ' and ') && isset($value[$i - 1]) && $value[$i - 1] != ')') {
                $value = substr($value, 0, $ie) . '(' . substr($value, $ie, $i - $ie) . ')' . substr($value, $i);
            }
            
            if (substr($value, $i, 4) == ' or ' && isset($value[$i + 4]) && $value[$i + 4] != '(') {
                
                $k = $i + 4;
                while ($k < strlen($value) && $value[$k] != ')' && substr($value, $k, 4) != ' or ' && substr($value, $k, 5) != ' and ') {
                    $k++;
                }
                
                $value = substr($value, 0, $i + 4) . '(' . substr($value, $i + 4, $k - ($i + 4)) . ')' . substr($value, $k);
            }
            else if (substr($value, $i, 5) == ' and ' && isset($value[$i + 5]) && $value[$i + 5] != '(') {
                
                $k = $i + 5;
                while ($k < strlen($value) && $value[$k] != ')' && substr($value, $k, 4) != ' or ' && substr($value, $k, 5) != ' and ') {
                    $k++;
                }
                
                $value = substr($value, 0, $i + 5) . '(' . substr($value, $i + 5, $k - ($i + 5)) . ')' . substr($value, $k);
            }

            
            $i++;
        }
        
        //echo $value;
        //exit;
        
        return array('expression'=>$value, 'values'=>$quot_values);
    }
    
    private function _processExpression ($expressions) {
        $operations = array();
        foreach($expressions as $expression) {
            if (is_array($expression)) {
                $operations[] = $this->_processExpression($expression);
            }
            else {
                $op = $this->_builOperation($expression);
                if (count($op) == 0) {
                    //$op = $op[0];
                }
                else {                
                    $operations[] = $op;
                }
            }
        }
        
        if (count($operations) > 1) {
            return $operations;
        }
        else {
            if (count($operations) == 1) {
                return $operations[0];
            }
            else {
                return null;
            }
        }
    }

    private function _builOperation ($value) {
        
        if (trim($value) == 'or' || trim($value) == 'and') {
            return $value;
        }
        
        $operations = array();
        if (!empty($value)) {
            if ($res = $this->_parcerOperation('and', $value)) {
                $operations[] = $res;
            }
        }
        
        return $operations;
        
        //Solo se maneja una expresión a la vez, lo siguiente no se está utilizando
        
        $parts = explode(' or ', $value);
        
        $operations = array();
        foreach($parts as $part) {
            $optional = explode(' and ', $part);
            
            if (count($optional) == 1) {
                if ($res = $this->_parcerOperation('or', $optional[0])) {
                    $operations[] = $res;
                }
            }
            else {
                foreach($optional as $sentence) {
                    if (!empty($sentence)) {
                        if ($res = $this->_parcerOperation('and', $sentence)) {
                            $operations[] = $res;
                        }
                    }
                }
            }
        }
        
        //$operations[0]->connector = '';
        return $operations;
    }

    private function _parcerOperation($connector, $operation) {
        $op = new stdClass();
        //$op->connector = $connector;
        
        $parts = explode(' ', trim($operation), 3);
        
        if (count($parts) < 2) {
            if (RESTOS_DEBUG_MODE) {
                Restos::throwException(null, 'Bad filter operation: ' . $operation, 400);
            }
            return false;
        }
        
        if (!in_array($parts[1], $this->_valid_operators)) {
            if (RESTOS_DEBUG_MODE) {
                Restos::throwException(null, 'Bad operator: ' . $parts[1], 400);
            }
            return false;
        }
        
        $op->left = trim($parts[0]);
        $op->operator = trim($parts[1]);
        
        if (count($parts) < 3) {
            $op->right = '';
        }
        else {
            $op->right = trim(trim($parts[2], "'"));
        }

        return $op;
    }

    public function hasFilter() {
        return is_array($this->_components) && count($this->_components) > 0;
    }
    
    public function hasOrderBy() {
        return is_array($this->_order_fields) && count($this->_order_fields) > 0;
    }

    public function setOrderBy($value) {
        $this->_orderby = $value;
        
        $fields = explode(',', $value);
        
        foreach($fields as $field) {
            $ord = new stdClass();
            
            $ord->field = trim($field);
            $ord->direction = $ord->field[0] == '-' ? 'DESC' : 'ASC';
            $ord->field = ltrim($ord->field, '-');
            $this->_order_fields[] = $ord;
        }
    }

    public function getOrderBy() {
        return $this->_order_fields;
    }

    public function operator2SQL($operator) {
        if (isset($this->_operators_sql[$operator])) {
            return $this->_operators_sql[$operator];
        }
        else {
            return false;
        }
    }

    public function arraySQLConditions($start_index = 1) {

        $conditions = array();
        if ($this->hasFilter()) {
            $conditions = $this->_arraySQLCondition($this->FilterFields, $start_index);
        }
        //var_dump($conditions);
        //exit;
        return $conditions;
    }
    
    private function _arraySQLCondition ($fields, $start_index = 1) {
        $conditions = array();
        $connector = 'AND';
        foreach($fields as $field) {
            if (is_array($field)) {
                $more_conditions = $this->_arraySQLCondition($field);
                
                foreach($more_conditions as $one) {
                    $conditions['field_' . $start_index] = $one;
                }
            }
            else if (is_object($field)){
                $conditions['field_' . $start_index] = array();
                $conditions['field_' . $start_index]['key'] = $field->left;
                $conditions['field_' . $start_index]['operator'] = $this->operator2SQL($field->operator);
                $conditions['field_' . $start_index]['value'] = $field->right;
                $conditions['field_' . $start_index]['connector'] = empty($field->connector) ? 'AND' : $field->connector;
            }
            else {
                $connector = strtoupper(trim($field));
            }
            $start_index++;
        }
        
        return $conditions;
    }

    public function mapSQLConditions($start_index = 1) {

        $conditions = array();
        if ($this->hasFilter()) {
            $conditions = $this->_mapSQLCondition($this->FilterFields, $start_index);
        }
        //var_dump($conditions);
        //exit;
        return $conditions;
    }
    
    private function _mapSQLCondition ($fields, $start_index = 1) {
        $conditions = array();
        $connector = 'AND';
        foreach($fields as $field) {
            if (is_array($field)) {
                $conditions['field_' . $start_index] = $this->_mapSQLCondition($field);
            }
            else if (is_object($field)){
                $new_field = clone($field);
                $new_field->operator = $this->operator2SQL($field->operator);
                $new_field->key = $new_field->left;
                $new_field->value = $new_field->right;
                $conditions['field_' . $start_index] = $new_field;
            }
            else {
                $conditions['field_' . $start_index] = strtoupper(trim($field));
            }
            $start_index++;
        }
        
        return $conditions;
    }

    public function arraySQLOrder($start_index = 1) {
        if ($this->hasOrderBy()) {
            $order = array();
            foreach($this->OrderBy as $field) {
                $order[$field->field] = $field->direction;
            }
        }
        else {
            $order = null;
        }
        
        return $order;
    }
}


//Temporal function to process an string in an array recursively
function replace_in_array (&$item, $key, $values) {
    $item = str_replace($values[0], $values[1], $item);
}
