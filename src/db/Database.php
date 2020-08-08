<?php

/**
 * PHP library for DataBase.
 *
 * @author    JoseAlfredoRS <alfredors.developer@gmail.com>
 * @copyright 2019 - 2020 (c) JoseAlfredoRS - PHP-DataBase
 * @license   https://opensource.org/licenses/MIT - The MIT License (MIT)
 * @link      https://github.com/JoseAlfredoRS/php-datatables
 * @since     1.0.0
 */

/**
 * DataBase handler.
 *
 * @since 1.0.0
 */

class Database
{
    # @object, The PDO object
    public $pdo;

    # @object, PDO statement object
    public $sQuery;

    # @array,  The database settings
    public $settings;

    # @bool ,  Connected to the database
    public $bConnected = false;

    # @object, Object for logging exceptions	
    public $log;

    # @array, The parameters of the SQL query
    public $parameters = [];

    /**
     *   Default Constructor 
     *
     *	1. Instantiate Log class.
     *	2. Connect to database.
     *	3. Creates the parameter array.
     */
    public function __construct()
    {
        $this->log = new Log();
        $this->Connect();
        $this->parameters = array();
    }

    /**
     *	This method makes connection to the database.
     *	
     *	1. Reads the database settings from a ini file. 
     *	2. Puts  the ini content into the settings array.
     *	3. Tries to connect to the database.
     *	4. If connection failed, exception is displayed and a log file gets created.
     */
    private function Connect()
    {
        $this->settings = parse_ini_file("Settings.php");
        $dsn            = $this->settings["DB_DRIVER"] . ':dbname=' . $this->settings["DB_DATABASE"] . ';host=' . $this->settings["DB_HOST"] . ';charset=' . $this->settings["DB_CHARSET"];
        try {
            # Read settings from INI file, set UTF8
            // $this->pdo = new PDO($dsn, $this->settings["DB_USERNAME"], $this->settings["DB_PASSWORD"], array(
            //     PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            // ));
            $this->pdo = new PDO($dsn, $this->settings["DB_USERNAME"], $this->settings["DB_PASSWORD"]);

            # We can now log any exceptions on Fatal error. 
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            # Disable emulation of prepared statements, use REAL prepared statements instead.
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            # Connection succeeded, set the boolean to true.
            $this->bConnected = true;
        } catch (PDOException $e) {
            # Write into log
            echo $this->ExceptionLog($e->getMessage());
            die();
        }
    }
    /*
     *   You can use this little method if you want to close the PDO connection
     *
     */
    public function CloseConnection()
    {
        # Set the PDO object to null to close the connection
        # http://www.php.net/manual/en/pdo.connections.php
        $this->pdo = null;
    }

    /**
     *	Every method which needs to execute a SQL query uses this method.
     *	
     *	1. If not connected, connect to the database.
     *	2. Prepare Query.
     *	3. Parameterize Query.
     *	4. Execute Query.	
     *	5. On exception : Write Exception into the log + SQL query.
     *	6. Reset the Parameters.
     */
    public function Init($query, $parameters = "")
    {
        # Connect to database
        if (!$this->bConnected) {
            $this->Connect();
        }


        try {
            # Prepare query
            $this->sQuery = $this->pdo->prepare($query);

            if (is_array($parameters)) {

                switch ($this->is_assoc($parameters)) {
                    case true:
                        # Add parameters to the parameter array	
                        $this->bindMore($parameters);

                        # Bind parameters
                        if (!empty($this->parameters)) {
                            foreach ($this->parameters as $param => $value) {
                                if (is_int($value[1])) {
                                    $type = PDO::PARAM_INT;
                                } else if (is_bool($value[1])) {
                                    $type = PDO::PARAM_BOOL;
                                } else if (is_null($value[1])) {
                                    $type = PDO::PARAM_NULL;
                                } else {
                                    $type = PDO::PARAM_STR;
                                }
                                // Add type when binding the values to the column
                                $this->sQuery->bindValue($value[0], $value[1], $type);
                            }
                        }
                        break;

                    case false:
                        # Add parameters to the parameter array	
                        $this->parameters = $parameters;

                        # Bind parameters
                        if (!empty($this->parameters)) {
                            $values = $this->parameters;
                            for ($i = 0; $i < count($this->parameters); $i++) {
                                if (is_int($values[$i])) {
                                    $type = PDO::PARAM_INT;
                                } else if (is_bool($values)) {
                                    $type = PDO::PARAM_BOOL;
                                } else if (is_null($values)) {
                                    $type = PDO::PARAM_NULL;
                                } else {
                                    $type = PDO::PARAM_STR;
                                }
                                // Add type when binding the values to the column
                                $this->sQuery->bindParam($i + 1, $values[$i], $type);
                            }
                        }
                        break;
                }
            }

            # Execute SQL 
            $this->sQuery->execute();
        } catch (PDOException $e) {
            # Write into log and display Exception
            $this->ExceptionLog($e->getMessage(), $query);
            // die();
            throw new Exception($e->getMessage());
        }

        # Reset the parameters
        $this->parameters = array();
        $this->table = null;
        $this->where = null;
        $this->valuesWhere = array();
        $this->select = null;
        $this->orderBy = null;
        $this->limit = null;
        $this->groupBy = null;
    }

    /**
     *	@void 
     *
     *	Add the parameter to the parameter array
     *	@param string $para  
     *	@param string $value 
     */
    public function bind($para, $value)
    {
        $this->parameters[sizeof($this->parameters)] = [":" . ltrim($para, ':'), $value];
    }
    /**
     *	@void
     *	
     *	Add more parameters to the parameter array
     *	@param array $parray
     */
    public function bindMore($parray)
    {
        if (empty($this->parameters) && is_array($parray)) {
            $columns = array_keys($parray);
            foreach ($columns as $i => &$column) {
                $this->bind($column, $parray[$column]);
            }
        }
    }
    /**
     *  If the SQL query  contains a SELECT or SHOW statement it returns an array containing all of the result set row
     *	If the SQL statement is a DELETE, INSERT, or UPDATE statement it returns the number of affected rows
     *
     *   	@param  string $query
     *	@param  array  $params
     *	@param  int    $fetchmode
     *	@return mixed
     */
    public function query($query, $params = null, $fetchmode = PDO::FETCH_OBJ)
    {
        $query = trim(str_replace("\r", " ", $query));

        $this->Init($query, $params);

        $rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $query));

        # Which SQL statement is used 
        $statement = strtolower($rawStatement[0]);

        if ($statement === 'select' || $statement === 'show') {
            return $this->sQuery->fetchAll($fetchmode);
        } elseif ($statement === 'insert' || $statement === 'update' || $statement === 'delete') {
            return $this->sQuery->rowCount();
        } else {
            return NULL;
        }
    }

    /**
     *  Returns the last inserted id.
     *  @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Starts the transaction
     * @return boolean, true on success or false on failure
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     *  Execute Transaction
     *  @return boolean, true on success or false on failure
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     *  Rollback of Transaction
     *  @return boolean, true on success or false on failure
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     *  Comprueba si una transacción está activa 
     *  @return boolean, true active or false no active
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * testPDO
     *
     * @return
     */
    public function testPDO()
    {
        return $this->pdo;
    }

    /**
     *	Returns an array which represents a column from the result set 
     *
     *	@param  string $query
     *	@param  array  $params
     *	@return array
     */
    public function column($query, $params = null, $fetchmode = PDO::FETCH_COLUMN)
    {
        $this->Init($query, $params);
        $Columns = $this->sQuery->fetchAll($fetchmode);
        return $Columns;
        // $column = null;
        // foreach ($Columns as $cells) {
        //     $column[] = $cells[0];
        // }
        // return $column;
    }

    /**
     *	Returns an array which represents a row from the result set 
     *
     *	@param  string $query
     *	@param  array  $params
     *   	@param  int    $fetchmode
     *	@return array
     */
    public function row($query, $params = null, $fetchmode = PDO::FETCH_OBJ)
    {
        $this->Init($query, $params);
        $result = $this->sQuery->fetch($fetchmode);
        $this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued,
        return $result;
    }
    /**
     *	Returns the value of one single field/column
     *
     *	@param  string $query
     *	@param  array  $params
     *	@return string
     */
    public function single($query, $params = null)
    {
        $this->Init($query, $params);
        $result = $this->sQuery->fetchColumn();
        $this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
        return $result;
    }
    /**
     * Retorna la cantidad de filas
     *
     * @return int
     */
    private function rowCount($query, $params = null)
    {
        $this->Init($query, $params);
        $result = $this->sQuery->rowCount();
        $this->sQuery->closeCursor(); // Frees up the connection to the server so that other SQL statements may be issued
        return $result;
    }
    /**	
     * Writes the log and returns the exception
     *
     * @param  string $message
     * @param  string $sql
     * @return string
     */
    private function ExceptionLog($message, $sql = "")
    {
        $exception = 'Unhandled Exception. <br />';
        $exception .= $message;
        $exception .= "<br /> You can find the error back in the log.";

        if (!empty($sql)) {
            # Add the Raw SQL to the Log
            $message .= "\r\nRaw SQL : " . $sql;
        }
        # Write into log
        $this->log = new Log();
        $this->log->write($message);

        return $exception;
    }

    private function is_assoc($array)
    {
        foreach (array_keys($array) as $key) {
            if (!is_int($key)) return true;
        }
        return false;
    }

    /**
     * table
     *
     * @var mixed
     */
    private $table;
    /**
     * where
     *
     * @var mixed
     */
    private $where;
    /**
     * valuesWhere
     *
     * @var array
     */
    private $valuesWhere = array();
    /**
     * select
     *
     * @var mixed
     */
    private $select;
    /**
     * orderBy
     *
     * @var mixed
     */
    private $orderBy;
    /**
     * limit
     *
     * @var mixed
     */
    private $limit;
    /**
     * groupBy
     *
     * @var mixed
     */
    private $groupBy;

    /**
     * statement
     *
     * @param  mixed $query
     * @param  mixed $params
     * @param  mixed $fetchmode
     * @return
     */
    public function statement($query, $params = null, $fetchmode = PDO::FETCH_OBJ)
    {
        $query = trim(strtolower($query));

        if (substr($query, 0, 6) != 'select') {
            $this->Init($query, $params);
            if (strlen(strstr($query, '@')) > 0)
                return $this->sQuery->closeCursor();
            return ($this->sQuery->columnCount() > 0) ? $this->sQuery->fetchAll($fetchmode) : NULL;
        }

        return $this->query($query, $params, $fetchmode);
    }

    /**
     * table
     *
     * @param  mixed $name
     * @return
     */
    public function table($name)
    {
        $this->table = $name;
        return $this;
    }

    /**
     * where
     *
     * @param  mixed $conditions
     * @return
     */
    public function where(...$conditions)
    {
        $condition = count($conditions) == 2 ? '=' : $conditions[1];
        $setWhere = reset($conditions) . ' ' . strtoupper($condition) . ' ?';
        $this->valuesWhere[] = end($conditions);
        $this->where .= (empty($this->where)) ? ' WHERE ' . $setWhere : ' AND ' . $setWhere;
        return $this;
    }

    /**
     * orWhere
     *
     * @param  mixed $conditions
     * @return
     */
    public function orWhere(...$conditions)
    {
        $condition   = count($conditions) == 3 ? $conditions[1] : '=';
        $setWhere = reset($conditions) . ' ' . strtoupper($condition) . ' ?';
        $this->valuesWhere[] = end($conditions);
        $this->where .= (empty($this->where)) ? ' WHERE ' . $setWhere : ' OR ' . $setWhere;
        return $this;
    }

    /**
     * whereIn
     *
     * @param  mixed $field
     * @param  mixed $values
     * @return
     */
    public function whereIn($field, $values = [])
    {
        $setWhere = $field . ' IN(' . substr(str_repeat(",?", count($values)), 1) . ') ';
        foreach ($values as $value) {
            $this->valuesWhere[] = $value;
        }
        $this->where .= (empty($this->where)) ? ' WHERE ' . $setWhere : ' AND ' . $setWhere;
        return $this;
    }

    /**
     * whereNotIn
     *
     * @param  mixed $field
     * @param  mixed $values
     * @return
     */
    public function whereNotIn($field, $values = [])
    {
        $setWhere = $field . ' NOT IN(' . substr(str_repeat(",?", count($values)), 1) . ') ';
        foreach ($values as $value) {
            $this->valuesWhere[] = $value;
        }
        $this->where .= (empty($this->where)) ? ' WHERE ' . $setWhere : ' AND ' . $setWhere;
        return $this;
    }

    /**
     * groupBy
     *
     * @param  mixed $fields
     * @return
     */
    public function groupBy(...$fields)
    {
        $fields = implode(',', $fields);
        $this->groupBy = ' GROUP BY ' . $fields;
        return $this;
    }

    /**
     * orderBy
     *
     * @param  mixed $column
     * @param  mixed $order
     * @return
     */
    public function orderBy($column, $order = 'ASC')
    {
        $this->orderBy .= empty($this->orderBy) ? ' ORDER BY ' : ', ';
        $this->orderBy .= $column . ' ' . $order;
        return $this;
    }

    /**
     * limit
     *
     * @param  mixed $rows
     * @return
     */
    public function limit(...$rows)
    {
        $this->limit = ' LIMIT ' . implode(',', $rows);
        return $this;
    }

    /**
     * take
     *
     * @param  mixed $rows
     * @return
     */
    public function take(...$rows)
    {
        $this->limit($rows);
        return $this;
    }

    /**
     * exists
     *
     * @return
     */
    public function exists()
    {
        return empty($this->get()) ? false : true;
    }

    /**
     * doesntExist
     *
     * @return
     */
    public function doesntExist()
    {
        return empty($this->get()) ? true : false;
    }

    /**
     * select
     *
     * @param  mixed $fields
     * @return
     */
    public function select(...$fields)
    {
        if (is_null($this->table) && substr(trim($fields[0]), 0, 6) == 'SELECT') {
            $query  = reset($fields);
            $params = isset($fields[1]) ? $fields[1] : null;
            return $this->query($query, $params);
        }
        $this->select = implode(',', $fields);
        return $this;
    }

    /**
     * insert
     *
     * @param  mixed $params
     * @return
     */
    public function insert(...$params)
    {
        switch (func_num_args()) {
            case 1:
                $params = reset($params);
                $fields = implode(',', array_keys($params));
                $values = array_values($params);
                $params = substr(str_repeat(',?', count(array_keys($params))), 1);
                $query = "INSERT INTO " . $this->table . " ($fields) VALUES ($params)";
                return $this->query($query, $values);
                break;

            case 2:
                if (is_null($this->table) && substr(trim(strtoupper($params[0])), 0, 6) == 'INSERT') {
                    $query  = reset($params);
                    $params = isset($params[1]) ? $params[1] : null;
                    return $this->query($query, $params);
                }
                break;
        }
    }

    /**
     * insertGetId
     *
     * @param  mixed $params
     * @return
     */
    public function insertGetId($params = [])
    {
        $this->insert($params);
        return $this->lastInsertId();
    }

    /**
     * update
     *
     * @param  mixed $params
     * @return
     */
    public function update(...$params)
    {
        switch (func_num_args()) {
            case 1:
                $setUpdate = "";
                $params = reset($params);
                foreach ($params as $key => $value) {
                    $setUpdate .= " $key=?,";
                }
                $setUpdate = substr($setUpdate, 1, -1);
                $values = array_merge(array_values($params), $this->valuesWhere);
                $query = "UPDATE " . $this->table . " SET $setUpdate" . $this->where;
                return $this->query($query, $values);
                break;

            case 2:
                if (is_null($this->table) && substr(trim(strtoupper($params[0])), 0, 6) == 'UPDATE') {
                    $query  = reset($params);
                    $params = isset($params[1]) ? $params[1] : null;
                    return $this->query($query, $params);
                }
                break;
        }
    }

    /**
     * get
     *
     * @return
     */
    public function get()
    {
        $fields = $this->select ? $this->select : '*';
        $values = $this->valuesWhere;
        $query = "SELECT $fields FROM " . $this->table . $this->where . $this->orderBy . $this->limit . $this->groupBy;
        return $this->query($query, $values);
    }

    /**
     * first
     *
     * @return
     */
    public function first()
    {
        $fields = $this->select ? $this->select : '*';
        $values = $this->valuesWhere;
        $query = "SELECT $fields FROM " . $this->table . $this->where . $this->orderBy . $this->limit . $this->groupBy;
        return $this->row($query, $values);
    }

    /**
     * value
     *
     * @param  mixed $field
     * @return
     */
    public function value($field)
    {
        $values = $this->valuesWhere;
        $query = "SELECT $field FROM " . $this->table . $this->where . $this->orderBy . $this->limit . $this->groupBy;
        return $this->row($query, $values, PDO::FETCH_NUM)[0];
    }

    /**
     * find
     *
     * @param  mixed $array
     * @return
     */
    public function find(...$array)
    {
        switch (func_num_args()) {
            case 1:
                $values = [reset($array)];
                $where = ' WHERE id = ?';
                break;

            case 2:
                $values = [end($array)];
                $where = ' WHERE ' . reset($array) . ' = ?';
                break;
        }
        $fields = $this->select ? $this->select : '*';
        $query = "SELECT $fields FROM " . $this->table . $where;
        return $this->row($query, $values);
    }

    /**
     * firstWhere
     *
     * @param  mixed $field
     * @param  mixed $value
     * @return
     */
    public function firstWhere($field, $value)
    {
        return $this->find($field, $value);
    }

    /**
     * pluck
     *
     * @param  mixed $fields
     * @return
     */
    public function pluck(...$fields)
    {
        $fetchmode = count($fields) == 2 ? PDO::FETCH_KEY_PAIR : PDO::FETCH_COLUMN;
        $fields = !empty($fields) ? implode(',', array_reverse($fields)) : '*';
        $values = $this->valuesWhere;
        $query = "SELECT $fields FROM " . $this->table . $this->where . $this->orderBy . $this->limit . $this->groupBy;
        return $this->column($query, $values, $fetchmode);
    }

    /**
     * count
     *
     * @return
     */
    public function count()
    {
        $fields = $this->select ? $this->select : '*';
        $values = $this->valuesWhere;
        $query = "SELECT $fields FROM " . $this->table . $this->where . $this->orderBy . $this->limit . $this->groupBy;
        return $this->rowCount($query, $values);
    }
}
