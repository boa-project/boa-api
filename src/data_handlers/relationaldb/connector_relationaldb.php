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
 * Class Connector_relationaldb
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Connector_relationaldb {

    /**
     *
     * Show all error messages
     * @var integer
     */
    const E_ALL     = 10;

    /**
     *
     * Show only debug error messages
     * @var integer
     */
    const E_DEBUG   = 20;

    /**
     *
     * Show short description of error messages
     * @var integer
     */

    const E_NORMAL  = 50;

    /**
     *
     * The Data Source Name
     * @var string
     */
    private $_DSN;

    /**
     *
     * The Data Source Name Key. Created in order to protect DSN if error are publised
     * @var string
     */
    private $_DSNKey;

    /**
     *
     * An associative array of option names and their values.
     * @var array
     */
    private $_options = array();

    /**
     *
     * Object to execute query in database
     * @var MDB2
     */
    public static $DB = array();

    /**
     *
     * A level to display info about the error
     * The levels are:
     * - 10 : All
     * - 20 : develop
     * - 50 : short
     *
     * @var integer
     */
    public static $ErrorLevel = 10;

    /**
     *
     * An associative array of loaded modules
     * @var array
     */
    private $_loaded_modules = array();

    /**
     *
     * Contruct
     * @param string $DNS The Data Source Name
     * @param array $options An associative array of option names and their values.
     */
    public function __construct($DSN, $options = array()){

        include_once 'MDB2.php';

        if (RESTOS_DEBUG_MODE) {
            Connector_relationaldb::$ErrorLevel = Connector_relationaldb::E_ALL;
        }

        $this->_DSN = $DSN;
        $this->_DSNKey = MD5($DSN);

        if(is_array($options)){
            $this->_options = $options;
        }

        if (!isset(self::$DB[$this->_DSNKey]) || !self::$DB[$this->_DSNKey]) {
            $mdb2 = MDB2::connect($this->_DSN, $this->_options); //removed the & (php5.4)

            if (self::isPearError($mdb2)) { // pear bug #18663
                Connector_relationaldb::throwException($mdb2);
            }

            $mdb2->loadModule('Extended', null, false);
            self::$DB[$this->_DSNKey] = $mdb2;
        }
    }

    public function DB () {
        return self::$DB[$this->_DSNKey];
    }

    /**
     *
     * Throw a new exception according to current error level
     * @param PEAR error $e
     * @throws Exception
     */
    public static function throwException($e){
        $matches = array();
        $code = $e->getCode();

        $res = preg_match( "/\[native code\: ([0-9]*)\]/i", $e->getUserInfo(), $matches);
        if ($res !== false) {
            if (is_array($matches) && count($matches) >= 2) {
                $code = $matches[1];
            }
        }

        switch (Connector_relationaldb::$ErrorLevel) {
            case Connector_relationaldb::E_ALL:
                $msg = $e->getMessage() . ". " . $e->getUserInfo();
                break;
            default:
                $msg = $e->getMessage();
        }

        var_dump($msg);

        $ne = new Exception($msg, $e->getCode());

        switch ($code) {
            case 1062:
                Restos::throwException($ne, RestosLang::get('exception.db.uniqueviolation'), 3001);
                break;
            case 1451:
                Restos::throwException($ne, RestosLang::get('exception.db.deleterelationviolation'), 3002);
                break;
            case 1452:
                Restos::throwException($ne, RestosLang::get('exception.db.relationviolation'), 3002);
                break;
            case 1048:
                Restos::throwException($ne, RestosLang::get('exception.db.cannotbenull'), 3003);
                break;
            case 1146:
                Restos::throwException($ne, RestosLang::get('exception.db.entitynotexists'), 3004);
                break;
            default:
                Restos::throwException($ne, RestosLang::get('exception.db.error'), 3000);
        }

    }

    /**
     *
     * Destruct
     * Disconnect the data base if this is connected
     */
    public function __destruct(){

        if (isset(self::$DB[$this->_DSNKey]) && is_object(self::$DB[$this->_DSNKey]) && method_exists(self::$DB[$this->_DSNKey], 'disconnect')) {
//        echo "desconectando \n";
            self::$DB[$this->_DSNKey]->disconnect();
        }
    }

    /**
     *
     * Fetch the first row of data returned from a query.
     * @param string $sql - the SQL query
     * @param array $types - that contains the types of the columns in the result set
     * @param array $params - if supplied, prepare/execute will be used with this array as execute parameters
     * @param array $param_types - that contains the types of the values defined in $params
     * @param int $fetch_mode - the fetch mode to use
     *
     */
    public function getRow($sql, $types = null, $params = array(), $param_types = null, $fetch_mode = MDB2_FETCHMODE_OBJECT) {

        $row = self::$DB[$this->_DSNKey]->extended->getRow($sql, $types, $params, $param_types, $fetch_mode);

        if (self::isPearError($row)) { // pear bug #18663
            Connector_relationaldb::throwException($row);
        }

        return $row;
    }

    /**
     *
     * Return a record as object
     * @param string $table Table name
     * @param array $conditions
     * @return object
     */
    public function getEntity($table, $conditions){

        $condition = '';
        if (is_array($conditions) && count($conditions) > 0) {
            $final_conditions = array();
            $condition = ' WHERE ';
            foreach ($conditions as $key=>$value) {
                if ($value === NULL) {
                    $condition .= '`' . $key . '` IS NULL AND ';
                }
                else {
                    $condition .= '`' . $key . '` = :' . $key . ' AND ';
                    $final_conditions[$key] = $value;
                }
            }

            $condition = rtrim($condition, ' AND ');
            $conditions = $final_conditions;
        }

        $sql = 'SELECT * FROM ' . $table . $condition;

        $row = $this->getRow($sql, null, $conditions);

        if (is_object($row)) {
            return $row;
        }

        return null;
    }

    /**
     *
     * Return an unic field value
     * @param string $table Table name
     * @field string $field field name to return
     * @param array $conditions
     * @return object
     */
    public function getValue($table, $field, $conditions){

        $condition = '';
        if (is_array($conditions) && count($conditions) > 0) {
            $condition = ' WHERE ';
            foreach ($conditions as $key=>$value) {
                $condition .= '`' . $key . '` = :' . $key . ' AND ';
            }

            $condition = rtrim($condition, ' AND ');
        }

        $sql = 'SELECT `' . $field . '` AS value FROM ' . $table . $condition;

        $val = self::$DB[$this->_DSNKey]->extended->getOne($sql, null, $conditions);

        if (self::isPearError($val)) { // pear bug #18663
            Connector_relationaldb::throwException($val);
        }

        return $val;
    }

    /**
     *
     * Return an unic field value
     * @param string $sql SQL Sentence
     * @param array $conditions_values
     * @return object
     */
    public function getValueSQL($sql, $conditions_values){

        $val = self::$DB[$this->_DSNKey]->extended->getOne($sql, null, $conditions_values);

        if (self::isPearError($val)) { // pear bug #18663
            Connector_relationaldb::throwException($val);
        }

        return $val;
    }

    /**
     *
     * Fetch all the rows returned from a table with conditions.
     * @param string $table - the table name
     * @param array $conditions - that conditions are required
     * @param array $order - fields to order the data
     * @param int $number
     * @param int start_on
     * @return data on success, a MDB2 error on failure
     */
    public function getList ($table, $conditions, $order = NULL, $number = null, $start_on = null) {

        $condition_values = array();
        if (is_object($table)) {
            $tables = $table->Main;

            $selected_fields = '';

            //load fields of default table
            if (is_array($table->SelectedFields)) {
                foreach($table->SelectedFields as $field) {
                    $selected_fields .= ', ' . $table->Main . '.' . ($field == '*' ? '*' : '`' . $field  . '`');
                }
            }
            else {
                $selected_fields = $table->Main . '.' . ($table->SelectedFields == '*' ? '*' : '`' . $table->SelectedFields . '`');
            }

            $condition = '';
            if (is_array($conditions) && count($conditions) > 0) {
                $k = 0;
                foreach ($conditions as $key=>$value) {
                    $k++;
                    if (is_array($value)) {
                        if (isset($value['key'])) {
                            $key = $value['key'];
                        }

                        if ($k > 1) {
                            if(isset($value['connector'])) {
                                $connector = $value['connector'];
                            }
                            else {
                                $connector = 'AND';
                            }
                        }
                        else {
                            $connector = '';
                        }

                        $condition .= ' ' . $connector . ' ' . $table->Main . '.`' . $key . '` ' . $value['operator'] . ' :field' . $k;
                        $condition_values['field' . $k] = $value['value'];
                    }
                    else {
                        if ($k > 1) {
                            $connector = 'AND';
                        }
                        else {
                            $connector = '';
                        }

                        $condition .= ' ' . $connector . ' ' . $table->Main . '.`' . $key . '` = :field' . $k;
                        $condition_values['field' . $k] = $value;
                    }
                }
            }

            $sql_order = '';
            if (is_array($order) && count($order) > 0) {
                foreach ($order as $key=>$value) {
                    $sql_order .= $table->Main . '.`' . $key . '` ' . $value . ', ';
                }
            }
            else if (!empty($order) && is_string($order)) {
                $sql_order .= $table->Main . '.`' . $order . '`, ';
            }

            $dependences = $table->getDependences();
            if (is_array($dependences) && count($dependences) > 0) {
                foreach($dependences as $value) {

                    $tables .= $value->Integrity == DependenceEntitiesTree::INTEGRITY_STRONG ? ' INNER ' : ($value->Integrity == DependenceEntitiesTree::INTEGRITY_DEPENDENCYTO ? ' LEFT ' : ' RIGHT ');

                    $tables .= ' JOIN ';

                    if ($value->Entity == $value->Alias) {
                        $tables .= $value->Entity;
                    }
                    else {
                        $tables .= $value->Entity . ' AS ' . $value->Alias;
                    }

                    $tables .= ' ON ' . $value->EntityTo . '.`' . $value->FieldTo . '` ' . $value->RelationalOperator . $value->Alias . '.`' . $value->FieldFrom . '`';

                    //load fields of specific tables
                    if (is_array($value->SelectedFields)) {
                        foreach($value->SelectedFields as $field) {
                            if (!empty($field)) {
                                $selected_fields .= ', ' . $value->Alias . '.' . ($field == '*' ? '*' : '`' . $field  . '`');
                            }
                        }
                    }
                    else if (!empty($value->SelectedFields)) {
                        $selected_fields .= ', ' . $value->Alias . '.' . ($value->SelectedFields == '*' ? '*' : '`' . $value->SelectedFields  . '`');
                    }

                    if (is_array($value->Conditions) && count($value->Conditions) > 0) {

                        $k = 0;
                        foreach ($value->Conditions as $condition_key=>$condition_value) {
                            $k++;
                            if (is_array($condition_value)) {

                                if (isset($condition_value['key'])) {
                                    $condition_key = $condition_value['key'];
                                }

                                if ($k > 1 || !empty($condition)) {
                                    if(isset($condition_value['connector'])) {
                                        $connector = $condition_value['connector'];
                                    }
                                    else {
                                        $connector = 'AND';
                                    }
                                }
                                else {
                                    $connector = '';
                                }

                                $condition .= ' ' . $connector . ' ' . $value->Alias . '.`' . $condition_key . '` ' . $condition_value['operator'] . ' :' . $value->Alias . '_field' . $k;
                                $condition_values[$value->Alias . '_field' . $k] = $condition_value['value'];
                            }
                            else {
                                if ($k > 1 || !empty($condition)) {
                                    $connector = 'AND';
                                }
                                else {
                                    $connector = '';
                                }
                                $condition .= ' ' . $connector . ' ' . $value->Alias . '.`' . $condition_key . '` = :' . $value->Alias . '_field' . $k;
                                $condition_values[$value->Alias . '_field' . $k] = $condition_value;
                            }
                        }
                    }

                    if (is_array($value->Order) && count($value->Order) > 0) {
                        foreach ($value->Order as $order_key=>$order_value) {
                            $sql_order .= $value->Alias . '.`' . $order_key . '` ' . $order_value . ', ';
                        }
                    }
                    else if (!empty($value->Order) && is_string($value->Order)) {
                        $sql_order .= $value->Alias . '.`' . $value->Order . '`, ';
                    }

                }
            }

            if (!empty($condition)) {
                //$condition = rtrim($condition, ' AND ');
                $condition = ' WHERE ' . $condition;
            }

            if (!empty($sql_order)) {
                $sql_order = rtrim($sql_order, ', ');
                $sql_order = ' ORDER BY ' . $sql_order;
            }

            $sql = 'SELECT ' . trim($selected_fields, ',') . ' FROM ' . $tables . ' ' . $condition . $sql_order;
        }
        else {
            $condition = '';
            if (is_array($conditions) && count($conditions) > 0) {
                $condition = ' WHERE ';
                $k = 0;
                foreach ($conditions as $key=>$value) {
                    $k++;
                    if (is_array($value)) {
                        if (isset($value['key'])) {
                            $key = $value['key'];
                        }

                        if ($k > 1) {
                            if(isset($value['connector'])) {
                                $connector = $value['connector'];
                            }
                            else {
                                $connector = 'AND';
                            }
                        }
                        else {
                            $connector = '';
                        }

                        $condition .= ' ' . $connector . ' `' . $key . '` ' . $value['operator'] . ' :field' . $k;
                        $condition_values['field' . $k] = $value['value'];
                    }
                    else {
                        if ($k > 1) {
                            $connector = 'AND';
                        }
                        else {
                            $connector = '';
                        }

                        $condition .= ' ' . $connector. ' `' . $key . '` = :field' . $k;
                        $condition_values['field' . $k] = $value;
                    }
                }

            }

            $sql_order = '';
            if (is_array($order) && count($order) > 0) {
                $sql_order = ' ORDER BY ';
                foreach ($order as $key=>$value) {
                    $sql_order .= '`' . $key . '` ' . $value . ', ';
                }

                $sql_order = rtrim($sql_order, ', ');
            }
            else if (!empty($order) && is_string($order)) {
                $sql_order = ' ORDER BY `' . $order . '`';
            }

            $sql = 'SELECT * FROM ' . $table . $condition . $sql_order;

        }

        //Limited records
        if (is_numeric($number)) {
            $sql_number = ' LIMIT ';

            if (is_numeric($start_on)) {
                $sql_number .= intval($start_on) . ', ';
            }

            $sql_number .= intval($number);

            $sql .= $sql_number;
        }

        $res = $this->getListSQL($sql, null, $condition_values);

        if (!is_array($res)) {
            return array();
        }

        return $res;
    }

    /**
     *
     * Count the rows returned from a table with conditions.
     * @param string $table - the table name
     * @param array $conditions - that conditions are required
     * @return int on success, a MDB2 error on failure
     */
    public function countList ($table, $conditions) {

        $condition_values = array();
        if (is_object($table)) {
            $tables = $table->Main;

            $condition = '';
            if (is_array($conditions) && count($conditions) > 0) {
                $k = 0;
                foreach ($conditions as $key=>$value) {
                    $k++;
                    if (is_array($value)) {
                        if (isset($value['key'])) {
                            $key = $value['key'];
                        }

                        if ($k > 1) {
                            if(isset($value['connector'])) {
                                $connector = $value['connector'];
                            }
                            else {
                                $connector = 'AND';
                            }
                        }
                        else {
                            $connector = '';
                        }

                        $condition .= ' ' . $connector . ' ' . $table->Main . '.`' . $key . '` ' . $value['operator'] . ' :field' . $k;
                        $condition_values['field' . $k] = $value['value'];
                    }
                    else {
                        if ($k > 1) {
                            $connector = 'AND';
                        }
                        else {
                            $connector = '';
                        }

                        $condition .= ' ' . $connector . ' ' . $table->Main . '.`' . $key . '` = :field' . $k;
                        $condition_values['field' . $k] = $value;
                    }
                }
            }

            $dependences = $table->getDependences();
            if (is_array($dependences) && count($dependences) > 0) {
                foreach($dependences as $value) {

                    $tables .= $value->Integrity == DependenceEntitiesTree::INTEGRITY_STRONG ? ' INNER ' : ($value->Integrity == DependenceEntitiesTree::INTEGRITY_DEPENDENCYTO ? ' LEFT ' : ' RIGHT ');

                    $tables .= ' JOIN ';

                    if ($value->Entity == $value->Alias) {
                        $tables .= $value->Entity;
                    }
                    else {
                        $tables .= $value->Entity . ' AS ' . $value->Alias;
                    }

                    $tables .= ' ON ' . $value->EntityTo . '.`' . $value->FieldTo . '` ' . $value->RelationalOperator . $value->Alias . '.`' . $value->FieldFrom . '`';

                    if (is_array($value->Conditions) && count($value->Conditions) > 0) {
                        $k = 0;
                        foreach ($value->Conditions as $condition_key=>$condition_value) {
                            $k++;
                            if (is_array($condition_value)) {

                                if (isset($condition_value['key'])) {
                                    $condition_key = $condition_value['key'];
                                }

                                if ($k > 1 || !empty($condition)) {
                                    if(isset($condition_value['connector'])) {
                                        $connector = $condition_value['connector'];
                                    }
                                    else {
                                        $connector = 'AND';
                                    }
                                }
                                else {
                                    $connector = '';
                                }

                                $condition .= ' ' . $connector . ' ' . $value->Alias . '.`' . $condition_key . '` ' . $condition_value['operator'] . ' :' . $value->Alias . '_field' . $k;

                                $condition_values[$value->Alias . '_field' . $k] = $condition_value['value'];
                            }
                            else {
                                if ($k > 1 || !empty($condition)) {
                                    $connector = 'AND';
                                }
                                else {
                                    $connector = '';
                                }
                                $condition .= ' ' . $connector . ' ' . $value->Alias . '.`' . $condition_key . '` = :' . $value->Alias . '_field' . $k;
                                $condition_values[$value->Alias . '_field' . $k] = $condition_value;

                            }
                        }
                    }
                }
            }

            if (!empty($condition)) {
                //$condition = rtrim($condition, ' AND ');
                $condition = ' WHERE ' . $condition;
            }

        }
        else {
            $tables = $table;
            $condition = '';
            if (is_array($conditions) && count($conditions) > 0) {
                $condition = ' WHERE ';
                $k = 0;
                foreach ($conditions as $key=>$value) {
                    $k++;
                    if (is_array($value)) {
                        if (isset($value['key'])) {
                            $key = $value['key'];
                        }

                        if ($k > 1) {
                            if(isset($value['connector'])) {
                                $connector = $value['connector'];
                            }
                            else {
                                $connector = 'AND';
                            }
                        }
                        else {
                            $connector = '';
                        }

                        $condition .= ' ' . $connector . ' `' . $key . '` ' . $value['operator'] . ' :field' . $k;
                        $condition_values['field' . $k] = $value['value'];
                    }
                    else {
                        if ($k > 1) {
                            $connector = 'AND';
                        }
                        else {
                            $connector = '';
                        }

                        $condition .= ' ' . $connector. ' `' . $key . '` = :field' . $k;
                        $condition_values['field' . $k] = $value;
                    }
                }

            }

        }

        $sql = 'SELECT COUNT(*) FROM ' . $tables . $condition;

        $val = self::$DB[$this->_DSNKey]->extended->getOne($sql, null, $condition_values);

        if (self::isPearError($val)) { // pear bug #18663
            Connector_relationaldb::throwException($val);
        }

        return $val;
    }

    /**
     *
     * Fetch all the rows returned from a query.
     * @param string $sql - the SQL query
     * @param array $types - that contains the types of the columns in the result set
     * @param array $params - if supplied, prepare/execute will be used with this array as execute parameters
     * @param array $param_types - that contains the types of the values defined in $params
     * @param int $fetch_mode - the fetch mode to use
     * @param $rekey - if set to true, the $all will have the first column as its first dimension
     * @param $force_array - used only when the query returns exactly two columns. If true, the values of the returned array will be one-element arrays instead of scalars.
     * @param $group - if true, the values of the returned array is wrapped in another array. If the same key value (in the first column) repeats itself, the values will be appended to this array instead of overwriting the existing values.
     * @return data on success, a MDB2 error on failure
     */
    public function getListSQL ($sql, $types = null, $params = array(), $param_types = null, $fetch_mode = MDB2_FETCHMODE_OBJECT, $rekey = false, $force_array = false, $group = false) {

        $res = self::$DB[$this->_DSNKey]->extended->getAll($sql, $types, $params, $param_types, $fetch_mode, $rekey, $force_array, $group);

        if (self::isPearError($res)) { // pear bug #18663
            Connector_relationaldb::throwException($res);
        }

        return $res;
    }

    /**
     *
     * Update only one record
     * @param string $table
     * @param array $fields
     * @param array $condition
     */
    public function update_record($table, array $fields, array $condition){

        $where = '';
        foreach ($condition as $key=>$value) {

            $where .= $key . ' = ' . self::$DB[$this->_DSNKey]->quote($value) . ' AND ';
        }
        $where = rtrim($where, ' AND ');

        if (!empty($where)) {
            //to modify only one record
            self::$DB[$this->_DSNKey]->setLimit(1);

            $affectedRows = self::$DB[$this->_DSNKey]->extended->autoExecute($table, $fields, MDB2_AUTOQUERY_UPDATE, $where);

            if (self::isPearError($affectedRows)) { // pear bug #18663
                Connector_relationaldb::throwException($affectedRows);
            }

            return $affectedRows;
        }

        return false;
    }

    /**
     *
     * Update records
     * @param string $table
     * @param array $condition
     * @param array $fields
     */
    public function update($table, array $fields, array $condition){

        $where = '';
        foreach ($condition as $key=>$value) {

            $where .= $key . ' = ' . self::$DB[$this->_DSNKey]->quote($value) . ' AND ';
        }
        $where = rtrim($where, ' AND ');

        $affectedRows = self::$DB[$this->_DSNKey]->extended->autoExecute($table, $fields, MDB2_AUTOQUERY_UPDATE, $where);

        if (self::isPearError($affectedRows)) { // pear bug #18663
            Connector_relationaldb::throwException($affectedRows);
        }

        return $affectedRows;
    }

    /**
     *
     * Insert one record
     * @param string $table Name of the table into which a new row was inserted
     * @param array $fields
     * @param bool $return_auto If return autoincrement ID
     * @return bool Returns the autoincrement ID if supported and is required or the operation result
     */
    public function insert_record($table, array $fields, $return_auto = false){

        $result = self::$DB[$this->_DSNKey]->extended->autoExecute($table, $fields, MDB2_AUTOQUERY_INSERT);

        if (self::isPearError($result)) { // pear bug #18663
            Connector_relationaldb::throwException($result);
        }

        if($result && $return_auto) {
            $id = self::$DB[$this->_DSNKey]->getAfterID(0, $table);

            if (self::isPearError($id)) { // pear bug #18663
                Connector_relationaldb::throwException($id);
            }

            return $id;
        }

        return $result;
    }

    /**
     *
     * Delete only one record
     * @param string $table
     * @param array $condition
     */
    public function delete_record($table, array $condition){

        $where = '';
        foreach ($condition as $key=>$value) {

            $where .= $key . ' = ' . self::$DB[$this->_DSNKey]->quote($value) . ' AND ';
        }
        $where = rtrim($where, ' AND ');

        if (!empty($where)) {
            //to delete only one record
            self::$DB[$this->_DSNKey]->setLimit(1);

            $affectedRows = self::$DB[$this->_DSNKey]->extended->autoExecute($table, null, MDB2_AUTOQUERY_DELETE, $where);

            if (self::isPearError($affectedRows)) { // pear bug #18663
                Connector_relationaldb::throwException($affectedRows);
            }

            return true;
        }

        return false;
    }

    /**
     *
     * Delete records
     * @param string $table
     * @param array $condition
     */
    public function delete($table, array $condition){

        $where = '';
        foreach ($condition as $key=>$value) {

            $where .= $key . ' = ' . self::$DB[$this->_DSNKey]->quote($value) . ' AND ';
        }
        $where = rtrim($where, ' AND ');

        $affectedRows = self::$DB[$this->_DSNKey]->extended->autoExecute($table, null, MDB2_AUTOQUERY_DELETE, $where);

        if (self::isPearError($affectedRows)) { // pear bug #18663
            Connector_relationaldb::throwException($affectedRows);
        }

        return true;
    }

    /**
     * Excecute a SQL sentence.
     *
     * @param string $sql
     * @param array $datatypes
     * @param array $parameters
     * @return int affected rows counter
     */
    public function excecute($sql, array $datatypes, array $parameters){

        $sth = self::$DB[$this->_DSNKey]->prepare($sql, $datatypes, MDB2_PREPARE_MANIP);
        $affectedRows = $sth->execute($parameters);

        if (self::isPearError($affectedRows)) { // pear bug #18663
            Connector_relationaldb::throwException($affectedRows);
        }

        return $affectedRows;
    }

    public function table_info ($table) {
        if (!in_array('Reverse', $this->_loaded_modules)) {
            self::$DB[$this->_DSNKey]->loadModule('Reverse', null, true);
            $this->_loaded_modules[] = 'Reverse';
        }

        $def = self::$DB[$this->_DSNKey]->tableInfo($table, null);
        return $def;
    }

    /**
     *
     * Check is the passed object is a pear error
     * @param object $err
     */
    static function isPearError($err){
    	return is_a($err, 'PEAR_Error');
    }
}
