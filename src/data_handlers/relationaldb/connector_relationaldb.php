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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Thin handle stored in Connector_relationaldb::$DB.
 * Preserves legacy DB()->quote() / disconnect() used by drivers.
 */
class Relationaldb_Connection {

    /** @var Connection */
    public $dbal;

    public function __construct(Connection $dbal) {
        $this->dbal = $dbal;
    }

    /**
     * Quote a value for interpolation (MDB2-compatible including surrounding quotes).
     *
     * @param mixed $value
     * @param string|null $type Legacy MDB2 type hint; integer/boolean return unquoted ints
     * @return string
     */
    public function quote($value, $type = null) {
        if ($value === null) {
            return 'NULL';
        }
        if ($type === 'integer' || $type === 'boolean') {
            return (string) (int) $value;
        }
        return $this->dbal->quote((string) $value);
    }

    public function disconnect() {
        $this->dbal->close();
    }

    /**
     * @return Connection
     */
    public function getConnection() {
        return $this->dbal;
    }
}

// MDB2 fetch-mode constants kept so default method signatures stay valid.
if (!defined('MDB2_FETCHMODE_ORDERED')) {
    define('MDB2_FETCHMODE_ORDERED', 1);
}
if (!defined('MDB2_FETCHMODE_ASSOC')) {
    define('MDB2_FETCHMODE_ASSOC', 2);
}
if (!defined('MDB2_FETCHMODE_OBJECT')) {
    define('MDB2_FETCHMODE_OBJECT', 3);
}

/**
 * Class Connector_relationaldb
 *
 * Doctrine DBAL adapter behind the historic RestOS relational connector API.
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class Connector_relationaldb {

    const E_ALL     = 10;
    const E_DEBUG   = 20;
    const E_NORMAL  = 50;

    /** @var string */
    private $_DSN;

    /** @var string */
    private $_DSNKey;

    /** @var array */
    private $_options = array();

    /**
     * Shared connections keyed by md5(DSN).
     * @var array<string, Relationaldb_Connection>
     */
    public static $DB = array();

    /** @var integer */
    public static $ErrorLevel = 10;

    /**
     * @param string $DSN Legacy mysqli://… or mysql://… DSN from properties.json
     * @param array $options Unused options retained for API compatibility
     */
    public function __construct($DSN, $options = array()) {

        if (RESTOS_DEBUG_MODE) {
            Connector_relationaldb::$ErrorLevel = Connector_relationaldb::E_ALL;
        }

        $this->_DSN = $DSN;
        $this->_DSNKey = md5($DSN);

        if (is_array($options)) {
            $this->_options = $options;
        }

        if (!isset(self::$DB[$this->_DSNKey]) || !self::$DB[$this->_DSNKey]) {
            try {
                $params = self::parseDsnToParams($this->_DSN);
                $dbal = DriverManager::getConnection($params);
                // Force connect so failures map through throwException early.
                // DBAL 4: Connection::connect() is protected — native handle open is enough.
                $dbal->getNativeConnection();
                self::$DB[$this->_DSNKey] = new Relationaldb_Connection($dbal);
            } catch (\Throwable $e) {
                Connector_relationaldb::throwException($e);
            }
        }
    }

    /**
     * @return Relationaldb_Connection
     */
    public function DB() {
        return self::$DB[$this->_DSNKey];
    }

    /**
     * @return Connection
     */
    private function dbal() {
        return self::$DB[$this->_DSNKey]->dbal;
    }

    /**
     * Parse legacy MDB2 / compose DATABASE_URL into Doctrine DBAL params.
     *
     * Accepts: mysqli://user:pass@host:3306/db?charset=utf8
     *          mysql://…, pdo-mysql://…, pdo_mysql://…
     *
     * @param string $dsn
     * @return array
     */
    public static function parseDsnToParams($dsn) {
        if (!is_string($dsn) || $dsn === '') {
            throw new InvalidArgumentException('Database DSN is empty.');
        }

        // In-memory SQLite (unit tests / local fixtures).
        if ($dsn === 'pdo_sqlite:///:memory:'
            || $dsn === 'sqlite:///:memory:'
            || $dsn === 'sqlite::memory:'
        ) {
            return array(
                'driver' => 'pdo_sqlite',
                'memory' => true,
            );
        }

        if (preg_match('#^pdo_sqlite://(.+)$#i', $dsn, $sqliteMatch)) {
            $path = $sqliteMatch[1];
            if ($path === '/:memory:' || $path === ':memory:') {
                return array('driver' => 'pdo_sqlite', 'memory' => true);
            }
            return array(
                'driver' => 'pdo_sqlite',
                'path' => $path,
            );
        }

        if (!preg_match('#^[a-z0-9_+-]+://#i', $dsn)) {
            throw new InvalidArgumentException('Invalid database DSN (missing scheme).');
        }

        // Normalize mysqli / pdo_mysql / pdo-mysql → mysql for parse_url
        $normalized = preg_replace('#^(mysqli|pdo[_-]mysql|mysql):#i', 'mysql:', $dsn);
        $parts = parse_url($normalized);
        if ($parts === false || empty($parts['host'])) {
            throw new InvalidArgumentException('Invalid database DSN.');
        }

        $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
        $query = array();
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $charset = !empty($query['charset']) ? $query['charset'] : 'utf8mb4';

        $params = array(
            'driver'   => 'pdo_mysql',
            'host'     => $parts['host'],
            'user'     => isset($parts['user']) ? rawurldecode($parts['user']) : '',
            'password' => isset($parts['pass']) ? rawurldecode($parts['pass']) : '',
            'dbname'   => $dbname,
            'charset'  => $charset,
        );

        if (!empty($parts['port'])) {
            $params['port'] = (int) $parts['port'];
        }

        return $params;
    }

    /**
     * Alias kept for docs / tests that refer to DBAL naming.
     *
     * @param string $dsn
     * @return array
     */
    public static function parseDsnToDbalParams($dsn) {
        return self::parseDsnToParams($dsn);
    }

    /**
     * Throw a RestOS DB exception with MySQL native code mapping.
     *
     * @param \Throwable|object $e
     * @throws Exception
     */
    public static function throwException($e) {
        $code = 0;
        $msg = '';

        if ($e instanceof \Throwable) {
            $msg = $e->getMessage();
            $code = self::extractMysqlNativeCode($e);
            $ne = new Exception($msg, is_numeric($e->getCode()) ? (int) $e->getCode() : 0);
        } else {
            // Legacy PEAR_Error-shaped object (should not appear on DBAL path).
            $code = method_exists($e, 'getCode') ? $e->getCode() : 0;
            $userInfo = method_exists($e, 'getUserInfo') ? $e->getUserInfo() : '';
            $matches = array();
            if (is_string($userInfo) && preg_match('/\[native code\: ([0-9]*)\]/i', $userInfo, $matches)
                && is_array($matches) && count($matches) >= 2) {
                $code = $matches[1];
            }
            switch (Connector_relationaldb::$ErrorLevel) {
                case Connector_relationaldb::E_ALL:
                    $msg = (method_exists($e, 'getMessage') ? $e->getMessage() : '') . '. ' . $userInfo;
                    break;
                default:
                    $msg = method_exists($e, 'getMessage') ? $e->getMessage() : 'database error';
            }
            $ne = new Exception($msg, method_exists($e, 'getCode') ? $e->getCode() : 0);
        }

        if (Connector_relationaldb::$ErrorLevel == Connector_relationaldb::E_ALL && RESTOS_DEBUG_MODE) {
            // Keep historic debug visibility without forcing dump on every production path.
        }

        switch ((int) $code) {
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
     * @param \Throwable $e
     * @return int
     */
    private static function extractMysqlNativeCode(\Throwable $e) {
        $cur = $e;
        while ($cur) {
            if ($cur instanceof \PDOException && isset($cur->errorInfo[1])) {
                return (int) $cur->errorInfo[1];
            }
            $cur = $cur->getPrevious();
        }
        return 0;
    }

    public function __destruct() {
        if (isset(self::$DB[$this->_DSNKey]) && is_object(self::$DB[$this->_DSNKey])
            && method_exists(self::$DB[$this->_DSNKey], 'disconnect')) {
            self::$DB[$this->_DSNKey]->disconnect();
            unset(self::$DB[$this->_DSNKey]);
        }
    }

    /**
     * Normalize named params: strip leading ":" from keys (MDB2 accepted both forms).
     *
     * @param array|null $params
     * @return array
     */
    public static function normalizeQueryParams($params) {
        if (!is_array($params) || count($params) === 0) {
            return array();
        }
        $out = array();
        foreach ($params as $key => $value) {
            if (is_string($key) && isset($key[0]) && $key[0] === ':') {
                $out[substr($key, 1)] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    /**
     * @param array|null $params
     * @return array
     */
    private function normalizeParams($params) {
        return self::normalizeQueryParams($params);
    }

    /**
     * @param mixed $fetch_mode
     * @return bool
     */
    private function wantsObjectFetch($fetch_mode) {
        return $fetch_mode === MDB2_FETCHMODE_OBJECT || $fetch_mode === 3 || $fetch_mode === null;
    }

    /**
     * Fetch the first row of data returned from a query.
     *
     * @param string $sql
     * @param array|null $types Ignored (API compat)
     * @param array $params
     * @param array|null $param_types Ignored (API compat)
     * @param int $fetch_mode MDB2_FETCHMODE_* (default OBJECT)
     * @return object|array|null
     */
    public function getRow($sql, $types = null, $params = array(), $param_types = null, $fetch_mode = MDB2_FETCHMODE_OBJECT) {
        try {
            $result = $this->dbal()->executeQuery($sql, $this->normalizeParams($params));
            $row = $result->fetchAssociative();
            if ($row === false) {
                return null;
            }
            if ($this->wantsObjectFetch($fetch_mode)) {
                return (object) $row;
            }
            if ($fetch_mode === MDB2_FETCHMODE_ORDERED || $fetch_mode === 1) {
                return array_values($row);
            }
            return $row;
        } catch (\Throwable $e) {
            Connector_relationaldb::throwException($e);
        }
    }

    /**
     * @param string $table
     * @param array $conditions
     * @return object|null
     */
    public function getEntity($table, $conditions) {

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
     * @param string $table
     * @param string $field
     * @param array $conditions
     * @return mixed
     */
    public function getValue($table, $field, $conditions) {

        $condition = '';
        if (is_array($conditions) && count($conditions) > 0) {
            $condition = ' WHERE ';
            foreach ($conditions as $key=>$value) {
                $condition .= '`' . $key . '` = :' . $key . ' AND ';
            }

            $condition = rtrim($condition, ' AND ');
        }

        $sql = 'SELECT `' . $field . '` AS value FROM ' . $table . $condition;

        return $this->getValueSQL($sql, $conditions);
    }

    /**
     * @param string $sql
     * @param array $conditions_values
     * @return mixed
     */
    public function getValueSQL($sql, $conditions_values) {
        try {
            $val = $this->dbal()->fetchOne($sql, $this->normalizeParams($conditions_values));
            return $val === false ? null : $val;
        } catch (\Throwable $e) {
            Connector_relationaldb::throwException($e);
        }
    }

    /**
     * Fetch all rows from a table with conditions (SQL builder unchanged).
     *
     * @param string|object $table
     * @param array $conditions
     * @param array|string|null $order
     * @param int|null $number
     * @param int|null $start_on
     * @return array
     */
    public function getList ($table, $conditions, $order = NULL, $number = null, $start_on = null) {

        $condition_values = array();
        if (is_object($table)) {
            $tables = $table->Main;

            $selected_fields = '';

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
     * @param string|object $table
     * @param array $conditions
     * @return int
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

        $val = $this->getValueSQL($sql, $condition_values);

        return $val;
    }

    /**
     * Fetch all rows from a query.
     *
     * @param string $sql
     * @param array|null $types Ignored
     * @param array $params
     * @param array|null $param_types Ignored
     * @param int $fetch_mode
     * @param bool $rekey
     * @param bool $force_array
     * @param bool $group
     * @return array
     */
    public function getListSQL ($sql, $types = null, $params = array(), $param_types = null, $fetch_mode = MDB2_FETCHMODE_OBJECT, $rekey = false, $force_array = false, $group = false) {

        try {
            $result = $this->dbal()->executeQuery($sql, $this->normalizeParams($params));
            $rows = $result->fetchAllAssociative();

            if ($rekey && count($rows) > 0) {
                $keyed = array();
                foreach ($rows as $row) {
                    $values = array_values($row);
                    $key = array_shift($values);
                    if (count($values) === 1 && !$force_array) {
                        $value = $values[0];
                    } else {
                        if ($this->wantsObjectFetch($fetch_mode)) {
                            $value = (object) array_combine(array_slice(array_keys($row), 1), $values);
                        } elseif ($fetch_mode === MDB2_FETCHMODE_ORDERED || $fetch_mode === 1) {
                            $value = $values;
                        } else {
                            $value = array_combine(array_slice(array_keys($row), 1), $values);
                        }
                    }
                    if ($group) {
                        $keyed[$key][] = $value;
                    } else {
                        $keyed[$key] = $value;
                    }
                }
                return $keyed;
            }

            if ($this->wantsObjectFetch($fetch_mode)) {
                return array_map(static function ($row) {
                    return (object) $row;
                }, $rows);
            }
            if ($fetch_mode === MDB2_FETCHMODE_ORDERED || $fetch_mode === 1) {
                return array_map('array_values', $rows);
            }
            return $rows;
        } catch (\Throwable $e) {
            Connector_relationaldb::throwException($e);
        }
    }

    /**
     * Build and run UPDATE / INSERT / DELETE mimicking MDB2 Extended::autoExecute.
     *
     * @param string $table
     * @param array|null $fields
     * @param string $mode insert|update|delete
     * @param string|false $where
     * @param int|null $limit
     * @return int Affected rows
     */
    private function autoExecute($table, $fields, $mode, $where = false, $limit = null) {
        $conn = $this->dbal();

        if ($mode === 'insert') {
            if (!is_array($fields) || count($fields) === 0) {
                throw new InvalidArgumentException('insert requires fields');
            }
            $cols = array_keys($fields);
            $placeholders = array();
            $params = array();
            $i = 0;
            foreach ($fields as $value) {
                $name = 'p' . $i;
                $placeholders[] = ':' . $name;
                $params[$name] = $value;
                $i++;
            }
            $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
            return $conn->executeStatement($sql, $params);
        }

        if ($mode === 'update') {
            if (!is_array($fields) || count($fields) === 0) {
                throw new InvalidArgumentException('update requires fields');
            }
            $sets = array();
            $params = array();
            $i = 0;
            foreach ($fields as $col => $value) {
                $name = 'p' . $i;
                $sets[] = $col . ' = :' . $name;
                $params[$name] = $value;
                $i++;
            }
            $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets);
            if ($where) {
                $sql .= ' WHERE ' . $where;
            }
            if ($limit !== null) {
                $sql .= ' LIMIT ' . (int) $limit;
            }
            return $conn->executeStatement($sql, $params);
        }

        if ($mode === 'delete') {
            $sql = 'DELETE FROM ' . $table;
            if ($where) {
                $sql .= ' WHERE ' . $where;
            }
            if ($limit !== null) {
                $sql .= ' LIMIT ' . (int) $limit;
            }
            return $conn->executeStatement($sql);
        }

        throw new InvalidArgumentException('Unknown autoExecute mode');
    }

    /**
     * @param array $condition
     * @return string
     */
    private function buildWhereQuoted(array $condition) {
        $where = '';
        foreach ($condition as $key => $value) {
            $where .= $key . ' = ' . $this->DB()->quote($value) . ' AND ';
        }
        return rtrim($where, ' AND ');
    }

    /**
     * @param string $table
     * @param array $fields
     * @param array $condition
     * @return int|false
     */
    public function update_record($table, array $fields, array $condition){

        $where = $this->buildWhereQuoted($condition);

        if (!empty($where)) {
            try {
                return $this->autoExecute($table, $fields, 'update', $where, 1);
            } catch (\Throwable $e) {
                Connector_relationaldb::throwException($e);
            }
        }

        return false;
    }

    /**
     * @param string $table
     * @param array $fields
     * @param array $condition
     * @return int
     */
    public function update($table, array $fields, array $condition){

        $where = $this->buildWhereQuoted($condition);

        try {
            return $this->autoExecute($table, $fields, 'update', $where);
        } catch (\Throwable $e) {
            Connector_relationaldb::throwException($e);
        }
    }

    /**
     * @param string $table
     * @param array $fields
     * @param bool $return_auto
     * @return mixed
     */
    public function insert_record($table, array $fields, $return_auto = false){

        try {
            $result = $this->autoExecute($table, $fields, 'insert');

            if ($result && $return_auto) {
                return $this->dbal()->lastInsertId();
            }

            return $result;
        } catch (\Throwable $e) {
            Connector_relationaldb::throwException($e);
        }
    }

    /**
     * @param string $table
     * @param array $condition
     * @return bool
     */
    public function delete_record($table, array $condition){

        $where = $this->buildWhereQuoted($condition);

        if (!empty($where)) {
            try {
                $this->autoExecute($table, null, 'delete', $where, 1);
                return true;
            } catch (\Throwable $e) {
                Connector_relationaldb::throwException($e);
            }
        }

        return false;
    }

    /**
     * @param string $table
     * @param array $condition
     * @return bool
     */
    public function delete($table, array $condition){

        $where = $this->buildWhereQuoted($condition);

        try {
            $this->autoExecute($table, null, 'delete', $where);
            return true;
        } catch (\Throwable $e) {
            Connector_relationaldb::throwException($e);
        }
    }

    /**
     * Execute a manipulation SQL statement.
     *
     * @param string $sql
     * @param array $datatypes Ignored (API compat)
     * @param array $parameters
     * @return int affected rows
     */
    public function excecute($sql, array $datatypes, array $parameters){

        try {
            return $this->dbal()->executeStatement($sql, $this->normalizeParams($parameters));
        } catch (\Throwable $e) {
            Connector_relationaldb::throwException($e);
        }
    }

    /**
     * Table column metadata in MDB2 tableInfo-like shape for Driver_sql::getEntityStructure.
     *
     * @param string $table
     * @return array
     */
    public function table_info ($table) {
        try {
            // DBAL 4 removed AbstractPlatform::getName(); detect via class name.
            $platformClass = strtolower(get_class($this->dbal()->getDatabasePlatform()));
            $isMysql = (strpos($platformClass, 'mysql') !== false || strpos($platformClass, 'mariadb') !== false);

            if ($isMysql) {
                // MySQL SHOW COLUMNS → MDB2-like field meta for Driver_sql::getEntityStructure.
                $safe = str_replace('`', '``', $table);
                $rows = $this->dbal()->fetchAllAssociative('SHOW COLUMNS FROM `' . $safe . '`');
                $def = array();
                foreach ($rows as $row) {
                    $rawType = (string) ($row['Type'] ?? '');
                    $length = null;
                    $mapped = $rawType;
                    if (preg_match('/^([a-zA-Z]+)(?:\((\d+)\))?/', $rawType, $m)) {
                        $mapped = strtolower($m[1]);
                        if (isset($m[2])) {
                            $length = (int) $m[2];
                        }
                    }
                    $notnull = isset($row['Null']) && strtoupper((string) $row['Null']) === 'NO';
                    $def[] = array(
                        'name'    => $row['Field'] ?? '',
                        'type'    => $mapped,
                        'notnull' => $notnull,
                        'length'  => $length,
                        'default' => array_key_exists('Default', $row) ? $row['Default'] : null,
                        'flags'   => $notnull ? 'not_null' : '',
                    );
                }
                return $def;
            }

            // Portable path (SQLite unit tests, etc.).
            $columns = $this->dbal()->createSchemaManager()->listTableColumns($table);
            $def = array();
            foreach ($columns as $column) {
                $short = strtolower((new \ReflectionClass($column->getType()))->getShortName());
                $short = preg_replace('/type$/', '', $short);
                if ($short === 'string') {
                    $short = 'varchar';
                } elseif ($short === 'integer' || $short === 'smallint' || $short === 'bigint') {
                    $short = 'integer';
                }
                $def[] = array(
                    'name'    => $column->getName(),
                    'type'    => $short !== '' ? $short : 'varchar',
                    'notnull' => $column->getNotnull(),
                    'length'  => $column->getLength(),
                    'default' => $column->getDefault(),
                    'flags'   => $column->getNotnull() ? 'not_null' : '',
                );
            }
            return $def;
        } catch (\Throwable $e) {
            Connector_relationaldb::throwException($e);
        }
    }

    /**
     * @param mixed $err
     * @return bool
     * @deprecated PEAR errors are no longer produced on the DBAL path
     */
    static function isPearError($err){
        return is_object($err) && (is_a($err, 'PEAR_Error') || is_a($err, 'MDB2_Error'));
    }
}
