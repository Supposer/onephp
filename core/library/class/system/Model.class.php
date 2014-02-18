<?php
/**
 * Model.class.php
 * @copyright (C) 2010-2012 YWCMS
 * @license http://www.b2bvip.com/
 * @lastmodify 2012-9-24
 * @author bin
 */
defined('IN_ONE') or exit('Access Denied');
class Model {
    // 当前数据库操作对象
    protected $db = null;
    // 当前缓存操作对象
    protected $dc = null;
    // 主表
    protected $table = null;
    // 主键名称
    protected $pk = null;
    // 主表字段信息
    protected $fields = array();
    // 查询表达式参数
    protected $options = array();
    // 数据信息
    protected $data = array();

    public function __construct($config) {
        if (empty($config) || !is_array($config)) {
            $this->db = get_instance_of('Db', 'factory', array(C('database_default')));
        } else {
            $this->db = get_instance_of('Db', 'factory', array($config));
        }
        $this->dc = get_dc();
    }

    /**
     * 
     * 获取系统表名
     * @param $table
     */
    public function getTable($table = '') {
        if (is_string($table) && trim($table) == '')
            return $table = null;
        if (is_array($table)) {
            $table = $this->_parseTable($table);
        } else {
            $table = $this->db->table($table);
        }
        return $table;
    }

    /**
     * 
     * 设置model表名
     * @param $table
     */
    public function setModelTable($table) {
        $this->table = parse_name($table);
    }

    /**
     * 
     * 获取模型主键
     */
    public function getModlePk() {
        static $pk = array();
        if ($this->pk) {
            return $this->pk;
        } else {
            if (!isset($pk[$this->table])) {
                $pk[$this->table] = $this->db->getPk($this->getModelTableName());
            }
            return $pk[$this->table];
        }
    }

    /**
     * 
     * 获取默认Model主表名
     */
    public function getModelTableName() {
        if (is_string($this->table) && !empty($this->table)) {
            $table = $this->db->table($this->table);
            return $table;
        } else {
            return '';
        }
    }

    /**
     * 
     * 直接执行SQL
     * @param $SQL
     */
    public function query($SQL) {
        $query = $this->db->query($SQL);
        $rows = array();
        while ($row = $this->db->fetchArray($query)) {
            $rows[] = $row;
        }
        if (!empty($query) && !($query === true)) {
            return $rows;
        } else {
            if ($query === true && $this->affectedRows()) {
                return $this->affectedRows();
            } else {
                return $query;
            }
        }
    }

    /**
     * 
     * 写入数据
     * @param array $data 插入数据
     * @param array|string $options 表达式
     * @param $replace 是否replace
     */
    public function add($data = array(), $options = array(), $replace = false) {
        if (empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if (!empty($this->data)) {
                $data = $this->data;
                // 重置数据
                $this->data = array();
            } else { //没有插入数据返回空
                return false;
            }
        }
        // 分析表达式
        $options = $this->_parseOptions($options);
        if (empty($data))
            return false;
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->_parseTable($options['table']) .
            ' ' . $this->_parseIns($data);
        // 写入数据到数据库
        $result = $this->db->update($sql);
        if ($result !== false) {
            $insertId = $this->insertId();
            if ($insertId) {
                return $insertId;
            }
        }
        return $result;
    }

    /**
     * 
     * 插入数据
     * @param $data
     */
    protected function _parseIns($data) {
        $values = array();
        $fields = array();
        $valuesarr = array();
        foreach ($data as $key => $val) {
            $value = $this->_parseValue($val);
            if (is_scalar($value)) { // 过滤非标量数据
                $values[] = $value;
                $fields[] = $this->_parseSep($key);
            } elseif (!empty($value) && is_array($value)) {
                if (empty($valuesarr)) {
                    $fields = array_keys($value);
                    foreach ($fields as $k => $v) {
                        $fields[$k] = $this->_parseSep($v);
                    }
                }
                $valuesarr[] = '(' . implode(',', $value) . ')';
            }
        }
        if (empty($valuesarr)) { //单条插入
            return '(' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        } else { //多条插入
            return '(' . implode(',', $fields) . ') VALUES ' . implode(',', $valuesarr);
        }
    }

    /**
     * 
     * 查询数据(多条)
     * @param $options
     */
    public function select($options = array()) {
        if (is_numeric($options) || is_string($options)) {
            // 根据主键查询记录
            $pk = $this->getModlePk();
            if (is_string($pk) && !empty($pk)) {
                if (strpos($options, ',')) {
                    $where = array();
                    $inpk = explode(',', $options);
                    foreach ($inpk as $value) {
                        $where[] = $this->_parseValue($value);
                    }
                    $where = 'IN ' . $this->_parseSep($pk) . '(' . implode(',', $where) . ')';
                } else {
                    $where = $this->_parseSep($pk) . '=' . $this->_parseValue($options);
                }
                $options = array();
                $options['where'] = $where;
            } else {
                $options = array();
            }
        } elseif ($options === false) { //用于子查询 不查询只返回SQL
            $this->_parseOptions($options);
            return $this->buildSelectSql($options);
        }
        $this->_parseOptions($options);
        $sql = $this->buildSelectSql($options);
        if (isset($options['cache']) && intval($options['cache']) >= 0) {
            $cache_ttl = intval($options['cache']);
            $md5_sql = md5($sql);
            if ($result = $this->dc->get($md5_sql)) {
                return $result;
            } else {
                $result = $this->query($sql);
                $this->dc->set($md5_sql, $result, $cache_ttl);
            }
        }
        $result = $this->query($sql);
        return $result;

    }

    /**
     * 
     * 读取数据(单条)
     */
    public function getOne($options = array()) {
        if (is_numeric($options) || is_string($options)) {
            // 根据主键查询记录
            $pk = $this->getModlePk();
            if (is_string($pk) && !empty($pk)) {
                if (strpos($options, ',')) {
                    $where = array();
                    $inpk = explode(',', $options);
                    foreach ($inpk as $value) {
                        $where[] = $this->_parseValue($value);
                    }
                    $where = 'IN ' . $this->_parseSep($pk) . '(' . implode(',', $where) . ')';
                } else {
                    $where = $this->_parseSep($pk) . '=' . $this->_parseValue($options);
                }
                $options = array();
                $options['where'] = $where;
            } else {
                $options = array();
            }
        } elseif ($options === false) { //用于子查询 不查询只返回SQL
            $this->_parseOptions($options);
            return $this->buildSelectSql($options);
        }
        $this->_parseOptions($options);
        $sql = $this->buildSelectSql($options);
        if (isset($options['cache']) && intval($options['cache']) >= 0) {
            $cache_ttl = intval($options['cache']);
            $md5_sql = 'get_one' . md5($sql);
            if ($result = $this->dc->get($md5_sql)) {
                return $result;
            } else {
                $result = $this->db->getOne($sql);
                $this->dc->set($md5_sql, $result, $cache_ttl);
            }
        }
        $result = $this->db->getOne($sql);
        return $result;
    }

    public function getValue($field) {
        $options['field'] = $field;
        if (is_array($field)) {
            $field = $this->_parseField($field);
        }
        $options = $this->_parseOptions($options);
        if (!strpos($field, ',') && !empty($field) && !is_array($field)) {
            $sql = $this->buildSelectSql($options);
            if (isset($options['cache']) && intval($options['cache']) >= 0) {
                $cache_ttl = intval($options['cache']);
                $md5_sql = 'get_value' . md5($sql);
                if (!$result = $this->dc->get($md5_sql)) {
                    $result = $this->db->getValue($sql);
                    $this->dc->set($md5_sql, $result, $cache_ttl);
                }
            }
            $result = $this->db->getValue($sql);
            if ($result !== false) {
                return $result;
            }
        }
        return null;
    }

    /**
     * 
     * 查询组装
     * @param $options
     */
    public function buildSelectSql($options = array()) {
        return $this->_parseSql($this->db->build_select_sql, $options);
    }

    /**
     * 
     * 查询SQL组装 join
     * @param $join
     */
    public function join($join) {
        if (is_array($join)) {
            $this->options['join'] = $join;
        } elseif (!empty($join)) {
            $this->options['join'][] = $join;
        }
        return $this;
    }

    public function cache($ttl = 0) { //缓存
        $this->options['cache'] = intval($ttl);
        return $this;
    }

    /**
     * 
     * SQL组装方法
     * @param $sql 
     * @param $options
     */
    protected function _parseSql($sql, $options = array()) {
        if (isset($options['page']) && intval($options['page']) > 0) {
            $page = intval($options['page']);
            $temp = array();
            $limit = explode(',', $options['limit']);
            $offset = 0;
            $rows = $limit[0] = intval($limit[0]) > 0 ? intval($limit[0]) : 0;
            if (isset($limit[1])) {
                $offset = $rows;
                $rows = intval($limit[1]) > 0 ? intval($limit[1]) : 1;
            }
            $temp[0] = ($page - 1) * $rows + $offset;
            $temp[1] = $rows;
            $options['limit'] = implode(',', $temp);
        }
        $sql = str_replace(array(
            '%TABLE%',
            '%DISTINCT%',
            '%FIELD%',
            '%JOIN%',
            '%WHERE%',
            '%GROUP%',
            '%HAVING%',
            '%ORDER%',
            '%LIMIT%',
            '%UNION%',
            '%DATA%'), array(
            $this->_parseTable($options['table']),
            $this->_parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
            $this->_parseField(isset($options['field']) ? $options['field'] : '*'),
            $this->_parseJoin(isset($options['join']) ? $options['join'] : ''),
            $this->_parseWhere(isset($options['where']) ? $options['where'] : ''),
            $this->_parseGroup(isset($options['group']) ? $options['group'] : ''),
            $this->_parseHaving(isset($options['having']) ? $options['having'] : ''),
            $this->_parseOrder(isset($options['order']) ? $options['order'] : ''),
            $this->_parseLimit(isset($options['limit']) ? $options['limit'] : ''),
            $this->_parseUnion(isset($options['union']) ? $options['union'] : ''),
            $this->_parseSet(isset($options['data']) ? $options['data'] : ''),
            ), $sql);
        return $sql;
    }

    /**
     * 
     * 获取table组装字符串
     * @param array|string $table
     */
    protected function _parseTable($tables) {
        if (is_array($tables)) { // 支持别名定义
            $array = array();
            foreach ($tables as $alias => $table) {
                if ($table = $this->getTable($table)) {
                    if (is_numeric($alias)) {
                        $array[] = $table;
                    } else {
                        $array[] = $table . ' AS ' . $this->_parseSep($alias);
                    }
                }
            }
            $tables = $array;
        } elseif (is_string($tables)) {
            $tables = explode(',', $tables);
        }
        if (empty($tables)) {
            //获取MODEL默认主表
            $tables[] = $this->getModelTableName();
        }
        return implode(',', $tables);
    }

    /**
     * 
     * 是否查询不相同的数据
     * @param $distinct
     */
    protected function _parseDistinct($distinct) {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * 
     * 查询字段组装
     * @param array|string $fields
     */
    protected function _parseField($fields) {
        if (is_string($fields) && strpos($fields, ',')) {
            $fields = explode(',', $fields);
        }
        if (is_array($fields)) {
            // 完善数组方式传字段名的支持
            // 支持 'field1'=>'field2' 这样的字段别名定义
            $array = array();
            foreach ($fields as $alias => $field) {
                if (!is_numeric($alias)) {
                    $array[] = $this->_parseSep($field) . ' AS ' . $this->_parseSep($alias);
                } else {
                    $array[] = $this->_parseSep($field);
                }
            }
            $fieldsStr = implode(',', $array);
        } elseif (is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->_parseSep($fields);
        } else {
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }

    /**
     * 
     * join组装
     * @param $join
     */
    protected function _parseJoin($join) {
        $joinStr = '';
        if (!empty($join)) {
            if (is_array($join)) {
                foreach ($join as $key => $_join) {
                    if (stripos($_join, 'JOIN') !== false) {
                        $joinStr .= ' ' . $_join;
                    } else {
                        $joinStr .= ' LEFT JOIN ' . $_join;
                    }
                }
            } else {
                $joinStr .= ' LEFT JOIN ' . $join;
            }
        }
        //将__TABLE_NAME__这样的字符串替换成正规的表名,并且带上前缀和后缀
        //$joinStr = preg_replace("/__([A-Z_-][A-Z0-9_-]+)__/esU","\$this->getTable(strtolower('$1'))",$joinStr);
        return $joinStr;
    }

    /**
     * 
     * 条件组装
     * @param array|string $where
     */
    protected function _parseWhere($where) { //暂支持字符串增加
        $whereStr = '';
        if (is_string($where)) {
            // 直接使用字符串条件
            $whereStr = $where;
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * 
     * GROUP分组组装
     * @param $group
     */
    protected function _parseGroup($group) {
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    /**
     * 
     * HAVING 子句组装
     * @param $having
     */
    protected function _parseHaving($having) {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * 
     * 排序组装
     * @param $order
     */
    protected function _parseOrder($order) {
        if (is_array($order)) {
            $array = array();
            foreach ($order as $key => $val) {
                if (is_numeric($key)) {
                    $array[] = $this->_parseSep($val) . ' ASC';
                } else {
                    if (strtoupper($val) == 'ASC') {
                        $val = 'ASC';
                    } else {
                        $val = 'DESC';
                    }
                    $array[] = $this->_parseSep($key) . ' ' . $val;
                }
            }
            $order = implode(',', $array);
        }
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * 
     * 分页组装
     */
    protected function _parseLimit($limit) {
        if (!empty($limit)) {
            $temp = array();
            $limit = explode(',', $limit);
            $temp[0] = intval($limit[0]) > 0 ? intval($limit[0]) : 0;
            if (isset($limit[1])) {
                $temp[1] = intval($limit[1]) > 0 ? intval($limit[1]) : 1;
            }
            $limit = implode(',', $temp);
        }
        return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * 
     * UNION SQL组装
     * @param $union
     */
    protected function _parseUnion($union) {
        if (empty($union))
            return '';
        if (isset($union['_all'])) {
            $str = 'UNION ALL ';
            unset($union['_all']);
        } else {
            $str = 'UNION ';
        }
        foreach ($union as $u) {
            $sql[] = $str . (is_array($u) ? $this->buildSelectSql($u) : $u);
        }
        return implode(' ', $sql);
    }

    public function edit($data = '', $options = array()) { //修改数据
        if (is_numeric($options) || is_string($options)) {
            // 根据主键更新记录
            $pk = $this->getModlePk();
            if (is_string($pk) && !empty($pk)) {
                if (strpos($options, ',')) {
                    $where = array();
                    $inpk = explode(',', $options);
                    foreach ($inpk as $value) {
                        $where[] = $this->_parseValue($value);
                    }
                    $where = 'IN ' . $this->_parseSep($pk) . '(' . implode(',', $where) . ')';
                } else {
                    $where = $this->_parseSep($pk) . '=' . $this->_parseValue($options);
                }
                $options = array();
                $options['where'] = $where;
            } else {
                $options = array();
            }
        }
        if (empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if (!empty($this->data)) {
                $data = $this->data;
                // 清除数据
                $this->data = array();
            } else {
                return false;
            }
        }

        $this->_parseOptions($options);
        if (!isset($options['where']) || empty($options['where']) || empty($data)) {
            return false;
        }
        $options['data'] = $data;
        $sql = $this->_parseSql($this->db->build_upload_sql, $options);
        $result = $this->db->update($sql); //update()返回影响条数，无修改则返回0行
        if ($result) {
            $affected_rows = $this->affectedRows();
        } else {
            $affected_rows = 0;
        }
        return $result;
    }

    /**
     * 
     * 更新数据
     * @param $data
     */
    protected function _parseSet($data) {
        $set = array();
        foreach ($data as $key => $val) {
            $value = $this->_parseValue($val);
            if (is_scalar($value)) {
                // 过滤非标量数据
                $set[] = $this->_parseSep($key) . '=' . $value;
            }
        }
        return ' SET ' . implode(',', $set);
    }

    /**
     * 
     * 删除数据
     * @param $options
     */
    public function delete($options = array()) {
        if (is_numeric($options) || is_string($options)) {
            // 根据主键删除记录
            $pk = $this->getModlePk();
            if (is_string($pk) && !empty($pk)) {
                if (strpos($options, ',')) {
                    $where = array();
                    $inpk = explode(',', $options);
                    foreach ($inpk as $value) {
                        $where[] = $this->_parseValue($value);
                    }
                    $where = $this->_parseSep($pk) . ' IN ' . '(' . implode(',', $where) . ')';
                } else {
                    $where = $this->_parseSep($pk) . '=' . $this->_parseValue($options);
                }
                $options = array();
                $options['where'] = $where;
            } else {
                $options = array();
            }
        }
        $this->_parseOptions($options);
        if (!isset($options['where']) || empty($options['where'])) {
            return false;
        }
        $sql = $this->_parseSql($this->db->build_delete_sql, $options);
        return $this->db->update($sql);
    }

    /**
     * 
     * 修改个别数据
     * @param $field 字段名
     * @param $value 值
     */
    public function setField($field, $value = '') {
        if (is_array($field)) {
            $data = $field;
        } else {
            $data[$field] = $value;
        }
        return $this->edit($data);
    }

    /**
     * 
     * 统计数据+1
     * @param $field 字段名
     * @param $step 加多少
     */
    public function setInc($field, $step = 1) {
        return $this->setField($field, array('exp', $field . '+' . $step));
    }

    /**
     * 
     * 统计数据-1
     * @param $field 字段名
     * @param $step 减多少
     */
    public function setDec($field, $step = 1) {
        return $this->setField($field, array('exp', $field . '-' . $step));
    }

    /**
     * 
     * 并合参数
     * @param $options 表达式参数
     */
    protected function _parseOptions(&$options = array()) {
        if (is_array($options)) { //表达式参数合并
            $options = array_merge($this->options, $options);
        } else {
            $options = $this->options;
        }
        // 查询过后清空sql表达式组装 避免影响下次查询
        $this->options = array();
        return $options;
    }

    /**
     * 
     * 查询插入值分析
     * @param $value
     */
    protected function _parseValue($value) {
        if (is_string($value)) {
            $value = '\'' . $value . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) ==
        'exp') {
            $value = $value[1];
        } elseif (is_array($value)) {
            $value = array_map(array($this, '_parseValue'), $value);
        } elseif (is_null($value) || $value === false) {
            $value = 'null';
        }
        return $value;
    }

    /**
     * 
     * 表名字段分隔符
     * @param $var
     */
    protected function _parseSep($var) {
        if (is_string($var)) {
            $var = $this->db->parseSep($var);
        } elseif (isset($var[0]) && is_string($var[0]) && strtolower($var[0]) == 'exp') {
            $var = $var[1];
        } else {
            $var = $this->db->parseSep($var[0]);
        }
        return $var;
    }

    /**
     * 
     * 上次SQL影响的行数
     */
    public function affectedRows() {
        return $this->db->affectedRows();
    }

    /**
     * 
     * 新插入ID
     */
    public function insertId() {
        return $this->db->insertId();
    }

    /**
     * 
     * 上一次执行的SQL
     */
    public function lastSql() {
        return $this->db->last_sql;
    }

    /**
     * 
     * 查询次数
     */
    public function queryNum() {
        return $this->db->queryNum;
    }

    /**
     * 
     * 清除影响查询参数
     */
    public function queryClear() {
        $this->data = array();
        $this->options = array();
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     * @param string $method 方法名称
     * @param array $args 调用参数
     */
    protected function __call($method, $args) {
        if (in_array(strtolower($method), array(
            'table',
            'where',
            'order',
            'limit',
            'page',
            'having',
            'group',
            'lock',
            'distinct',
            'field'), true)) {
            // 连贯操作的实现
            $this->options[strtolower($method)] = $args[0];
            return $this;
        } elseif (in_array(strtolower($method), array(
            'count',
            'sum',
            'min',
            'max',
            'avg'), true)) {
            // 统计查询的实现
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->getvalue(array(array('exp', strtoupper($method) . '(' . $this->
                        _parseSep($field) . ') AS ' . $this->_parseSep($method))));
        } else {
            return;
        }
    }

    /**
     * 
     * 私有属性可读取的
     * @param $property_name
     */
    public function __get($property_name) {
        $read = array('table');
        if (in_array($property_name, $read)) {
            return $this->$property_name;
        }
    }

    /**
     * 
     * 私有属性可修改的
     * @param $property_name
     * @param $value
     */
    public function __set($property_name, $value) {
        if ($property_name == 'data') {
            $this->data = array_merge($this->data, $value);
        } else {
            $this->data[$property_name] = $value;
        }
    }
}
