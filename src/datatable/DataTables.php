<?php

/**
 * PHP library for DataTables ServerSide.
 *
 * @author    JoseAlfredoRS <alfredors.developer@gmail.com>
 * @copyright 2019 - 2020 (c) JoseAlfredoRS - PHP-DataTablesServer
 * @license   https://opensource.org/licenses/MIT - The MIT License (MIT)
 * @link      https://github.com/JoseAlfredoRS/php-datatables
 * @since     1.0.0
 */

/**
 * DataTablesServer
 *
 * @since 1.0.0
 */

class DataTables
{
    # @object,  Instancia de la clase DB
    private $db;

    # @string,  Consulta ingresada por el usuario
    private $sql;

    # @string,  Nombre de la tabla y/o relaciones
    private $tables;

    # @string,  Clausula de la consulta final
    private $where;

    # @array,   Paramentros de la consulta
    private $params = [];

    # @array,   Columnas de la tabla seleccionada
    private $columns = [];

    # @string,  Columnas de la tabla seleccionada
    private $columnsString;

    # @array,   Resultado final de la consulta y/o clase
    private $response = [];

    # @int,     Numero total de registros
    private $iTotal;

    # @int,     Numero total de registros filtrados
    private $iFilteredTotal;

    # @string,  Clasusula de la consulta generada por Datatable
    private $sWhere;

    # @string,  Clasusula de la consulta generada por Datatable
    private $sOrder;

    # @string,  Clasusula de la consulta generada por Datatable
    private $sLimit;

    # @array,  Valores por defecto sAjaxSource de Datatable
    private $sAjaxSource = [
        'sEcho', 'iColumns', 'sColumns', 'iDisplayStart', 'iDisplayLength',
        'sSearch', 'bRegex', 'iSortCol_0', 'sSortDir_0', 'iSortingCols',
    ];

    # @array,  Columnas a ocultar
    private $hideColumn = [];

    # @array,  Columnas a agregar
    private $addColumn = [];

    # @array,  Columnas a editar
    private $editColumn = [];


    /**
     * Intancia de la clase Database
     *
     * @return object
     */
    public function __construct()
    {
        $this->db = new Database;
    }

    /**
     * Establece la consulta sql
     *
     * @param  string   $sql
     * @param  array    $params
     * @return
     */
    public function query($sql, $params = null)
    {
        $this->sql    = $sql;
        $this->params = $params;
        $this->validateQuery();
        $this->setParams();
        $this->setTables();
        $this->setColumns();
        $this->setWhere();
        return $this;
    }

    /**
     * Establece el nombre de la tablas y/o relaciones
     *
     * @return
     */
    private function setTables()
    {
        $sql = strtolower($this->sql);

        if (strpos($sql, 'where') !== false) {
            $tables = $this->getStringBetween($sql, 'from', 'where');
        } else {
            $posFrom = strpos($sql, 'from') + 4;
            $tables   = trim(substr($sql, $posFrom, strlen($sql) - $posFrom));
        }

        $this->tables = $tables;
    }

    /**
     * Establece el where de la consulta sql
     *
     * @return
     */
    private function setWhere()
    {
        $sql = strtolower($this->sql);

        if (strpos($sql, 'where') !== false) {
            $posWhere    = strpos($sql, 'where');
            $this->where = trim(substr($sql, $posWhere, strlen($sql) - $posWhere));
        }
    }

    /**
     * Establece los parametros de la consulta sql
     *
     * @return
     */
    private function setParams()
    {
        $sql = strtolower($this->sql);
        $params = $this->params;

        if (isset($params) && !$this->is_assoc($params)) {
            $this->params = [];
            for ($i = 0; $i < count($params); $i++) {
                $acu = $i + 1;
                $this->params[':param_' . $acu] = $params[$i];
            }

            $countParam = substr_count($sql, '?');
            for ($i = 0; $i < $countParam; $i++) {
                $acu = $i + 1;
                $posParam = strpos($sql, '?');
                $length   = strlen($sql);
                $sql      = substr($sql, 0, $posParam) . ':param_' . $acu . substr($sql, $posParam + 1, $length);
            }
        }

        $this->sql = $sql;
    }

    /**
     * Verifica si un array es asociativo
     *
     * @param  array $array
     * @return boolean
     */
    private function is_assoc($array)
    {
        foreach (array_keys($array) as $key) {
            if (!is_int($key)) return true;
        }
        return false;
    }

    /**
     * Obtiene una subcadena seleccionada entre dos cadenas
     *
     * @param  string $str
     * @param  string $from
     * @param  string $to
     * @return string
     */
    private function getStringBetween($str, $from, $to)
    {
        $sub = substr($str, strpos($str, $from) + strlen($from), strlen($str));
        return substr($sub, 0, strpos($sub, $to));
    }

    /**
     * Realiza la ejecucion del DatatableServer
     *
     * @return
     */
    public function loadDatatable()
    {
        // Obtener el número total de filas en la tabla
        $this->iTotal = $this->db->single("SELECT COUNT(*) FROM " . $this->tables . $this->where, $this->params);

        $arrayInter = array_intersect($this->sAjaxSource, array_keys($_REQUEST));

        if ($arrayInter !== $this->sAjaxSource) {
            $this->createQueryByAjax();
        } else {
            $this->createQueryByAjaxSource();
        }

        $sQuery = implode(' ', [
            0 => 'SELECT SQL_CALC_FOUND_ROWS',
            1 => $this->columnsString,
            2 => 'FROM',
            3 => $this->tables,
            4 => $this->sWhere,
            5 => $this->sOrder,
            6 => $this->sLimit,
        ]);

        $this->sQuery =  preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $sQuery);

        // Obtener las "n" primeros registros
        $this->response = $this->db->query($this->sQuery, $this->params, PDO::FETCH_ASSOC);

        // Devolverá un número que indica cuántas filas hubiera devuelto si no se hubiese usado la cláusula LIMIT.
        $this->iFilteredTotal = $this->db->single('SELECT FOUND_ROWS()');
    }

    /**
     * Genera las clausulas en caso la peticion del Datatable sea sAjaxSource
     *
     * @return
     */
    public function createQueryByAjaxSource()
    {
        $this->draw = isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 1;

        $columns    = $this->queryColumns();
        $endColumns = $this->columns;

        foreach ($this->hideColumn as $column) {
            $clave = array_search($column, $endColumns);
            unset($columns[$clave], $endColumns[$clave]);
            $endColumns = array_values($endColumns);
        }

        $columns = array_values($columns);

        // Paginación
        if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
            $this->sLimit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
        } else {
            $this->sLimit = 'LIMIT 0, 1000';
        }

        // Ordenar
        if (isset($_GET['iSortCol_0'])) {
            $this->sOrder = 'ORDER BY  ';
            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if (isset($columns[intval($_GET['iSortCol_' . $i])])) {
                    if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == 'true') {
                        $sortDir = (strcasecmp($_GET['sSortDir_' . $i], 'ASC') == 0) ? 'ASC' : 'DESC';
                        $column  = $columns[intval($_GET['iSortCol_' . $i])];
                        $column  = substr_count($column, '`') == 2 ? $column . ' ' : '`' . $column . '` ';
                        $this->sOrder .= $column . $sortDir . ", ";
                    }
                }
            }

            $this->sOrder = substr_replace($this->sOrder, "", -2);
            if ($this->sOrder == "ORDER BY") {
                $this->sOrder = "";
            }
        }

        /* 
		 * Filtración
		 * NOTA: esto no coincide con el filtro incorporado de DataTables que lo hace palabra por palabra en
		 * cualquier campo. Es posible hacerlo aquí, pero preocupado por la eficiencia en tablas muy grandes,
		 * y la funcionalidad de expresiones regulares de MySQL es muy limitada
		 */
        if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
            $this->sWhere = "WHERE (";
            for ($i = 0; $i < count($columns); $i++) {
                if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true") {
                    $acu = $i + 1;
                    $column = substr_count($columns[$i], '`') == 2 ? $columns[$i] : '`' . $columns[$i] . '`';
                    $this->sWhere .= $column . ' LIKE :search_' . $acu . ' OR ';
                }
            }
            $this->sWhere = substr_replace($this->sWhere, "", -3);
            $this->sWhere .= ')';
        }

        // Filtrado individual de columnas
        for ($i = 0; $i < count($columns); $i++) {
            if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
                if ($this->sWhere == '') {
                    $this->sWhere = 'WHERE ';
                } else {
                    $this->sWhere .= ' AND ';
                }
                $column = substr_count($columns[$i], '`') == 2 ? $columns[$i] : '`' . $columns[$i] . '`';
                $this->sWhere .= $column . ' LIKE :search' . $i . ' ';
            }
        }

        if (empty($this->sWhere)) {
            $this->sWhere = $this->where;
        } else {
            $this->sWhere = empty($this->where) ? $this->sWhere : $this->where . ' AND ' . ltrim($this->sWhere, 'WHERE ');
        }

        // Parámetros de enlace
        if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
            for ($i = 0; $i < count($columns); $i++) {
                $acu = $i + 1;
                $this->params[':search_' . $acu] = '%' . $_GET['sSearch'] . '%';
            }
        }
        for ($i = 0; $i < count($columns); $i++) {
            if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
                $this->params[':search' . $i] = '%' . $_GET['sSearch_' . $i] . '%';
            }
        }
    }

    /**
     * Genera las clausulas en caso la peticion del Datatable sea Ajax
     *
     * @return
     */
    public function createQueryByAjax()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $_METHOD = $_POST;
                break;

            case 'GET':
                $_METHOD = $_GET;
                break;

            default:
                throw new Exception('El metodo de envio no es valido.');
                break;
        }

        $this->draw = isset($_METHOD['draw']) ? intval($_METHOD['draw']) : 1;

        $columns    = $this->queryColumns();
        $endColumns = $this->columns;

        foreach ($this->hideColumn as $column) {
            $clave = array_search($column, $endColumns);
            unset($columns[$clave], $endColumns[$clave]);
            $endColumns = array_values($endColumns);
        }

        $columns = array_values($columns);

        // Paginación
        if (isset($_METHOD['start']) && $_METHOD['length'] != '-1') {
            $this->sLimit = 'LIMIT ' . intval($_METHOD['start']) . ', ' . intval($_METHOD['length']);
        } else {
            $this->sLimit = 'LIMIT 0, 1000';
        }

        // Ordenar
        if (isset($_METHOD['order'])) {

            $this->sOrder = 'ORDER BY  ';

            $order_colum = $_METHOD['order'][0]['column'];
            $order_value = $_METHOD['order'][0]['dir'];
            if (isset($columns[intval($order_colum)])) {
                if ($_METHOD['columns'][$order_colum]['orderable'] == 'true') {
                    $sortDir = (strcasecmp($order_value, 'ASC') == 0) ? 'ASC' : 'DESC';
                    $column  = $columns[intval($order_colum)];
                    $column  = substr_count($column, '`') == 2 ? $column . ' ' : '`' . $column . '` ';
                    $this->sOrder .= $column . $sortDir . ", ";
                }
            }

            $this->sOrder = substr_replace($this->sOrder, "", -2);
            if ($this->sOrder == "ORDER BY") {
                $this->sOrder = "";
            }
        }

        /* 
		 * Filtración
		 * NOTA: esto no coincide con el filtro incorporado de DataTables que lo hace palabra por palabra en
		 * cualquier campo. Es posible hacerlo aquí, pero preocupado por la eficiencia en tablas muy grandes,
		 * y la funcionalidad de expresiones regulares de MySQL es muy limitada
		 */
        if (isset($_METHOD['search']['value']) && $_METHOD['search']['value'] != "") {
            $this->sWhere = "WHERE (";
            for ($i = 0; $i < count($columns); $i++) {
                if (isset($_METHOD['columns'][$i]['searchable']) && $_METHOD['columns'][$i]['searchable'] == "true") {
                    $acu = $i + 1;
                    $column = substr_count($columns[$i], '`') == 2 ? $columns[$i] : '`' . $columns[$i] . '`';
                    $this->sWhere .= $column . ' LIKE :search_' . $acu . ' OR ';
                }
            }
            $this->sWhere = substr_replace($this->sWhere, "", -3);
            $this->sWhere .= ')';
        }

        // Filtrado individual de columnas
        for ($i = 0; $i < count($columns); $i++) {
            if (
                isset($_METHOD['columns'][$i]['searchable']) && $_METHOD['columns'][$i]['searchable'] == "true"
                && $_METHOD['columns'][$i]['search']['value'] != ''
            ) {
                if ($this->sWhere == '') {
                    $this->sWhere = 'WHERE ';
                } else {
                    $this->sWhere .= ' AND ';
                }
                $column = substr_count($columns[$i], '`') == 2 ? $columns[$i] : '`' . $columns[$i] . '`';
                $this->sWhere .= $column . ' LIKE :search' . $i . ' ';
            }
        }

        if (empty($this->sWhere)) {
            $this->sWhere = $this->where;
        } else {
            $this->sWhere = empty($this->where) ? $this->sWhere : $this->where . ' AND ' . ltrim($this->sWhere, 'WHERE ');
        }

        // Parámetros de enlace
        if (isset($_METHOD['search']['value']) && $_METHOD['search']['value'] != "") {
            for ($i = 0; $i < count($columns); $i++) {
                $acu = $i + 1;
                $this->params[':search_' . $acu] = '%' . $_METHOD['search']['value'] . '%';
            }
        }
        for ($i = 0; $i < count($columns); $i++) {
            if (
                isset($_METHOD['columns'][$i]['searchable']) && $_METHOD['columns'][$i]['searchable'] == "true"
                && $_METHOD['columns'][$i]['search']['value'] != ''
            ) {
                $this->params[':search' . $i] = '%' . $_METHOD['columns'][$i]['search']['value'] . '%';
            }
        }
    }

    /**
     * Ejecuta las consultas y genera resultados
     *
     * @return
     */
    public function generate()
    {
        $this->loadDatatable();
        $this->setEditcolumn();
        $this->setAddColumn();
        $this->setHideColumn();
        $this->setResponseData();
        return $this;
    }

    /**
     * Permite la edición de columnas
     *
     * @param  string $column
     * @param  callable $callback
     * @return
     */
    public function edit($column, callable $callback)
    {
        array_push($this->editColumn, [
            $column, $callback
        ]);
    }

    /**
     * Establece y edita las columnas
     *
     * @return 
     */
    private function setEditcolumn()
    {
        foreach ($this->editColumn as $value) {
            $column     = current($value);
            $callback   = next($value);
            if (!in_array($column, $this->columns)) {
                throw new Exception("La columna {$column} no se encuentra registrada");
                return;
            }
            $array = [];
            foreach ($this->response as $data) {
                $resp = call_user_func($callback, $data);
                $data[$column] = $resp;
                $array[] = $data;
            }
            $this->response = $array;
        }
    }

    /**
     * Agrega columnas adicionales para uso personalizado
     *
     * @param  string $column
     * @param  callable $callback
     * @return
     */
    public function add($column, callable $callback)
    {
        array_push($this->addColumn, [
            $column, $callback
        ]);
    }

    /**
     * Estable y agrega las columnas
     *
     * @return
     */
    private function setAddColumn()
    {
        foreach ($this->addColumn as $value) {
            $column     = current($value);
            $callback   = next($value);
            if (in_array($column, $this->columns)) {
                throw new Exception("La columna {$column} ya se encuentra registrada");
                return;
            }
            array_push($this->columns, $column);
            $array = [];
            foreach ($this->response as $data) {
                $resp = call_user_func($callback, $data);
                $data[$column] = $resp;
                $array[] = $data;
            }
            $this->response = $array;
        }
    }

    /**
     * Elimina la columna de la salida
     * Es útil cuando solo necesita usar los datos en los métodos add () o edit ().
     *
     * @param  string $column
     * @return
     */
    public function hide($column)
    {
        array_push($this->hideColumn, $column);
    }

    /**
     * Estalece y oculta las columnas
     *
     * @return
     */
    public function setHideColumn()
    {
        foreach ($this->hideColumn as $column) {
            if (!in_array($column, $this->columns)) {
                throw new Exception("La columna {$column} no se encuentra registrada");
                return;
            }
            $clave = array_search($column, $this->columns);
            unset($this->columns[$clave]);
            $this->columns = array_values($this->columns);
            $array = [];
            foreach ($this->response as $data) {
                $index = array_keys($data)[$clave];
                unset($data[$index]);
                $array[] = $data;
            }
            $this->response = $array;
        }
    }

    /**
     * Retorna los campos originales de la consulta sql
     *
     * @return array
     */
    private function queryColumns()
    {
        $sql = strtolower($this->sql);

        $fieldString = $this->getStringBetween($sql, 'select', 'from');
        $fieldString = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $fieldString);
        $fieldString = trim($fieldString);

        $columns = [];
        if ($fieldString !== '*') {
            $fieldArray = explode(',', $fieldString);
            foreach ($fieldArray as $value) {
                $value = trim($value);
                if (strpos($value, ' as ') !== false) {
                    array_push($columns, substr($value, 0, strpos($value, ' as ')));
                } else if (strpos($value, ' ') !== false) {
                    array_push($columns, substr($value, 0, strpos($value, ' ')));
                } else {
                    array_push($columns, $value);
                }
            }
        } else {
            if (strpos($sql, 'where') !== false) {
                $posWhere = strpos($sql, 'where');
                $sql   = trim(substr($sql, 0, $posWhere));
            }
            $columns = $this->db->row($sql . ' LIMIT 1', [], PDO::FETCH_ASSOC);
            $columns = array_keys($columns);
        }
        return $columns;
    }

    /**
     * Establece las columnas de la consulta sql
     *
     * @return
     */
    private function setColumns()
    {
        $sql = strtolower($this->sql);

        $fieldString         = $this->getStringBetween($sql, 'select', 'from');
        $this->columnsString = $fieldString;
        if (strpos($sql, 'where') !== false) {
            $posWhere = strpos($sql, 'where');
            $sql   = trim(substr($sql, 0, $posWhere));
        }
        $columns = $this->db->row($sql . ' LIMIT 1', [], PDO::FETCH_ASSOC);
        $this->columns = array_keys($columns);
    }

    /**
     * Verifica que la consulta ingresada sea correcta
     *
     * @return
     */
    private function validateQuery()
    {
        $sql = strtolower($this->sql);

        foreach (array('limit', 'group by', 'having') as $value) {
            if (strpos($sql, $value) !== false) {
                throw new Exception('La consulta no debe contener la clausule "' . strtoupper($value) . '".', 1);
                return;
            }
        }

        try {
            $this->db->single($this->sql, $this->params);
        } catch (Exception $th) {
            return $th->getMessage();
        }
    }

    /**
     * Devuelve la cadena de consulta sql creada por la biblioteca (para fines de desarrollo)
     *
     * @return
     */
    public function getQuery()
    {
        return $this->sQuery;
    }

    /**
     * Devuelve nombres de columna (para fines de desarrollo)
     *
     * @return
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Genera el resultado final
     *
     * @return
     */
    private function setResponseData()
    {
        $this->response = array(
            "draw"            =>  $this->draw,
            "recordsTotal"    =>  $this->iTotal,
            "recordsFiltered" =>  $this->iFilteredTotal,
            "data"            =>  array_map(function ($array) {
                return array_values($array);
            }, $this->response)
        );
    }

    /**
     * Devuelve la salida como json
     * Debe llamarse después de generate ()
     *
     * @return json
     */
    public function toJson()
    {
        header("Access-Control-Allow-Origin: *");
        header('Content-type: application/json;');
        return json_encode($this->response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }

    /**
     * Devuelve la salida como array
     * Debe llamarse después de generate ()
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
