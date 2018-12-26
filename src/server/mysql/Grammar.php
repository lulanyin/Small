<?php
namespace Small\server\mysql;

use Small\Config;
use Small\lib\util\Arr;
use Swoole\Coroutine\MySQL;

/**
 *
 * Class Grammar
 * @package app\commend\server\src\mysql
 */
class Grammar{

    /**
     * 查询类
     * @var Query
     */
    public $query;
    /**
     * SQL预处理参数化的参数值
     * @var array
     */
    public $params = [];
    public $tempParams = [];

    /**
     * 处理结果语句
     * @var array
     */
    public $queryString = [];

    /**
     * 本次查询行数
     * @var null
     */
    public $total = null;
    /**
     * 查询偏移量
     * @var int
     */
    public $offset = 0;
    /**
     * 连接信息
     * @var array
     */
    public $connectionInfo = [];

    /**
     * 查询类型
     * @var string
     */
    public $selectType = "read";

    /**
     *
     * Grammar constructor.
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * 获取记录
     * @param null $total
     * @param int $offset
     * @return array
     */
    public function get($total=null, $offset=0){
        $this->total = $total;
        $this->offset = $offset;
        list($queryString, $params) = $this->compileToQueryString();
        if(!empty($this->query->result)){
            //判断查询语句、参数是否相同
            if($this->query->result_query==$queryString && $params==$this->query->result_query_params){
                return is_numeric($total) ? array_slice($this->query->result, $offset, min($total, count($this->query->result))) : $this->query->result;
            }
        }
        if(!is_null($total)){
            $queryString .= " limit {$offset},{$total}";
        }
        $this->flushParams($params);
        if($mysql = $this->getConnection()){
            if($stmt = $mysql->prepare($queryString)){
                $result = [];
                if($stmt->execute($params)){
                    $result = $stmt->fetchAll();
                }else{
                    DB::log("ID : [{$mysql->errno}]error : [{$mysql->error}]\r\nsql : {$queryString}\r\n".var_export($params, true));
                }
                $this->release(Pool::READ);
                return $result;
            }else{
                //错误
                $this->query->error = $mysql->error;
                $this->query->error_id = $mysql->errno;
                DB::log("ID : [{$mysql->errno}]error : [{$mysql->error}]\r\nsql : {$queryString}\r\n".var_export($params, true));
                return [];
            }
        }else{
            return [];
        }
    }

    public function count(){
        $columns = $this->query->columns;
        $columnsString = join(",", $columns);
        $bool = stripos($columnsString, " sum(")
            || stripos($columnsString, " avg(")
            || stripos($columnsString, " count(")
            || stripos($columnsString, " max(")
            || stripos($columnsString, " min(")
            || !empty($this->query->groupBy);
        $this->query->columns = !$bool ? ["count('') as rows"] : $columns;
        list($queryString, $params) = $this->compileToQueryString();
        //如果原语句中，包含了 sum(), avg(), max()... group by，要统计行数，是不能直接使用  count(*)的
        //但是可以修改一下查询语句为  select count(*) from (原语句) temp_table
        //$bool = $bool ? $bool : !empty($this->query->groupBy);
        if($bool){
            $queryString = "SELECT COUNT(*) as rows FROM ({$queryString}) TEMP_TABLE";
        }
        $this->flushParams($params);
        $this->query->columns = $columns;
        if($mysql = $this->getConnection()){
            if($stmt = $mysql->prepare($queryString)){
                $rows = 0;
                if($stmt->execute($params)){
                    $result = $stmt->fetch();
                    if(!empty($result)){
                        $rows = $result['rows'];
                    }
                }else{
                    DB::log("ID : [{$mysql->errno}]error : [{$mysql->error}]\r\nsql : {$queryString}\r\n".var_export($params, true));
                }
                $this->release(Pool::READ);
                return $rows;
            }else{
                //错误
                $this->query->error = $mysql->error;
                $this->query->error_id = $mysql->errno;
                DB::log("ID : [{$mysql->errno}]error : [{$mysql->error}]\r\nsql : {$queryString}\r\n".var_export($params, true));
                return 0;
            }
        }else{
            return 0;
        }
    }

    public function rows(){
        return $this->count();
    }

    public function insert(){
        list($queryString, $params) = $this->compileToQueryString('insert');
        $mysql = $this->getConnection(Pool::WRITE);
        $needTransaction = !$this->query->transaction;
        if($needTransaction){
            $this->query->transaction = true;
            $mysql->begin();
        }
        foreach ($queryString as $key=>$q){
            if(!$this->execute('insert', $q, isset($params[$key]) ? $params[$key] : [])){
                if($needTransaction) {
                    $mysql->rollBack();
                    $this->query->transaction = false;
                }
                return false;
            }
        }
        if($needTransaction) {
            $mysql->commit();
            $this->query->transaction = false;
        }
        return true;
    }

    public function insertIfNotExists(){
        return $this->execute('insert where not exists');
    }

    public function update(){
        return $this->execute('update');
    }

    public function delete(){
        return $this->execute('delete');
    }

    public function truncate(){
        return $this->execute('truncate');
    }

    public function execute($type, $queryString=null, $params=null, $reconnectTime=0){
        if(is_null($queryString) && is_null($params)){
            list($queryString, $params) = $this->compileToQueryString($type);
            $this->flushParams($params);
        }else{
            $this->params = $params;
        }
        if($mysql = $this->getConnection(Pool::WRITE)){
            if($stmt = $mysql->prepare($queryString)){
                if($stmt->execute($params)){
                    if(strripos($type, 'insert')===0){
                        $this->query->lastInsertId[] = $mysql->insert_id;
                    }
                }else{
                    DB::log("ID : [{$mysql->errno}]error : [{$mysql->error}]\r\nsql : {$queryString}\r\n".var_export($params, true));
                }
                $this->query->affectRows += $mysql->affected_rows;
                $this->release(Pool::WRITE);
                return true;
            }else{
                //错误
                $this->query->error = $mysql->error;
                $this->query->error_id = $mysql->errno;
                DB::log("ID : [{$mysql->errno}]error : [{$mysql->error}]\r\nsql : {$queryString}\r\n".var_export($params, true));
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 获取数据库连接
     * @param string $type
     * @return null| MySQL
     */
    public function getConnection($type="read"){
        $this->query->mysql =  !is_null($this->query->mysql) ? $this->query->mysql : Pool::getPool($type=="read" ? Pool::READ : Pool::WRITE);
        return $this->query->mysql;
    }


    /**
     * 编译查询语句
     * @param string $type
     * @return array
     */
    public function compileToQueryString($type='select'){
        $this->tempParams = $this->params = [];
        $this->selectType = $type=='select' ? 'read' : 'write';
        $tableName = $this->compileTable($type=='select' ? 'read' : 'write');
        switch ($type){
            case "delete" :
                //删除, 暂时实现基础的语句： delete from {table name}{where}
                list($where, $params) = $this->compileWhere();// where里边包含了语句和参数
                $this->tempParams = array_merge($this->tempParams, $params);
                if(strripos($tableName, " ")){
                    $tableName = substr($tableName, 0, strripos($tableName, " "));
                }
                $queryString = "delete from {$tableName}{$where}";
                break;
            case "update" :
                //更新，暂时实现基础的语句：update {table name} set {columns} {where}
                list($columns, $params1) = $this->compileSet($type);
                $this->tempParams = array_merge($this->tempParams, $params1);
                list($where, $params2) = $this->compileWhere();// where里边包含了语句和参数
                $this->tempParams = array_merge($this->tempParams, $params2);
                if(strripos($tableName, " ")){
                    $tableName = substr($tableName, 0, strripos($tableName, " "));
                }
                $queryString = "update {$tableName} set {$columns}{$where}";
                $params = array_merge($params1, $params2);
                break;
            case "insert" :
                //插入，insert into {table name}(columns) values({$values})
                list($columns, $values, $params) = $this->compileSet($type);
                //$this->tempParams = array_merge($this->tempParams, $params);
                $queryString = [];
                if(strripos($tableName, " ")){
                    $tableName = substr($tableName, 0, strripos($tableName, " "));
                }
                foreach ($columns as $key=>$c){
                    $queryString[] = "insert into {$tableName} {$c} values {$values[$key]};";
                }
                break;
            case "insert where not exists" :
                //插入数据，当记录不存在时
                //insert into {table name}(columns) select {values} from TEMP1 where not exists(select {table name}{where})
                list($columns, $values, $params1) = $this->compileSet($type);
                $this->tempParams = array_merge($this->tempParams, $params1);
                list($where, $params2) = $this->compileWhere();// where里边包含了语句和参数
                $this->tempParams = array_merge($this->tempParams, $params2);
                if(strripos($tableName, " ")){
                    $tableName = substr($tableName, 0, strripos($tableName, " "));
                }
                $queryString = "insert into {$tableName} {$columns} select {$values} from dual where not exists(select * from {$tableName}{$where});";
                $params = array_merge($params1, $params2);
                break;
            case "truncate" :
                if(strripos($tableName, " ")){
                    $tableName = substr($tableName, 0, strripos($tableName, " "));
                }
                $queryString = "truncate table {$tableName}";
                $params = [];
                break;
            default :
                //默认查询
                $columns    = $this->compileColumns();
                $join       = $this->compileJoin();
                list($where, $params1) = $this->compileWhere();
                $this->tempParams = array_merge($this->tempParams, $params1);
                $groupBy    = $this->compileGroupBy();
                $orderBy    = $this->compileOrderBy();
                $having     = $this->compileHaving();
                list($union, $params2) = $this->compileUnion();
                $this->tempParams = array_merge($this->tempParams, $params2);
                $params3 = [];
                if(is_array($tableName)){
                    $this->tempParams = array_merge($this->tempParams, $tableName[1]);
                    $params3 = $tableName[1];
                    $tableName = $tableName[0];
                }
                $queryString = "select {$columns} from {$tableName}{$join}{$where}{$groupBy}{$orderBy}{$having}{$union}";
                $params = array_merge($params1, $params2, $params3);
                break;
        }
        $this->queryString[$type] = [$queryString, $params];
        return [$queryString, $params];
    }

    /**
     * 解析查询表名
     * @param string $type
     * @param string $tableName
     * @return string|array
     */
    public function compileTable($type='read', $tableName=''){
        $table = !empty($tableName) ? $tableName : $this->query->tableName;
        if(is_array($table)){
            $query = $table['query'];
            $as = $table['as'];
            list($queryString, $params) = $query->compileToQueryString();
            return ["($queryString) ".($as ?? ''), $params];
        }else{
            $tablePrefix = $this->getTablePrefix($type);
            $table = preg_replace("/\s+/"," ",$table);
            $table = trim($table);
            if(empty($tablePrefix)){
                if(stripos($table, " ")>0){
                    $list = explode(" ", $table, 2);
                    if(in_array($list[0], ['order', 'group'])){
                        $table = "`{$list[0]}`".(isset($list[1]) ? " {$list[1]}" : "");
                    }
                }else{
                    $table = "`{$table}`";
                }
            }
            return stripos($table, ".") || stripos($table, "`")>0 || stripos($table, "`")===0 ? $table : (($tablePrefix.$table));
        }
    }

    /**
     * 编译查询的字段
     * @return string
     */
    private function compileColumns(){
        $columns = $this->query->columns;
        $columnString = join(",", $columns);
        $columnString =  str_replace(", ", ",", $columnString);
        return $columnString;
    }

    /**
     * 编译关联查询
     * @return string
     */
    private function compileJoin(){
        $joins = $this->query->joins;
        $string = [];
        foreach($joins as $j){
            if(!empty($j['table'])){
                $table = $this->compileTable('read', $j['table']);
                if(is_string($j["on"])){
                    $onWhere = $j["on"];
                }else{
                    $column = $j['on'][0];
                    $operator = isset($j['on'][2]) ? $j['on'][1] : '=';
                    $value = isset($j['on'][2]) ? $j['on'][2] : $j['on'][1];
                    $onWhere = "{$column}{$operator}{$value}";
                }
                $string[] = " {$j['type']} join {$table}".($j['status']=='nested' ? $j['as'] : '')." on {$onWhere}";
            }
        }
        return join("", $string);
    }

    /**
     * 编译 where 条件
     * @param bool $Nested
     * @return array
     */
    public function compileWhere($Nested=false){
        $string = [];
        foreach ($this->query->wheres as $where){
            switch ($where['type']){
                case "Sub" :
                    if(isset($where['query']) && $where['query'] instanceof Query){
                        list($queryString, $params) = $where['query']->compileToQueryString();
                        $string[] = [
                            'string' => !empty($queryString) ? "({$queryString})" : "",
                            'boolean' => $where['boolean'],
                            "params" => $params
                        ];
                    }
                    break;
                case "Nested" :
                    list($queryString, $params) = $this->compileWhereNested($where);
                    $string[] = [
                        'string' => !empty($queryString) ? "({$queryString})" : "",
                        'boolean' => $where['boolean'],
                        "params" => $params
                    ];
                    break;
                case "Raw" :
                    $string[] = [
                        'string' => !empty($where['sql']) ? $where['sql'] : "",
                        'boolean' => $where['boolean'],
                        'params' => []
                    ];
                    break;
                default :
                    $string[] = $this->compileWhereString($where);
                    break;
            }
        }
        if(!empty($string)){
            $whereString = $params = [];
            foreach($string as $key=>$str){
                if(!empty($str['string'])){
                    //条件 or / and
                    $boolean = strtolower($str['boolean']);
                    $boolean = in_array($boolean, ['or', 'and']) ? $boolean : 'and';
                    //条件语句
                    $whereString[] = ($key===0 ? "" : (!empty($whereString[$key-1]) ? " " : "").$boolean." ").$str['string'];
                    //参数化的值合并
                    if(!empty($str['params'])){
                        $params = array_merge($params, $str['params']);
                    }
                }
            }
            $where = !empty($whereString) ? (($Nested ? "" : " where ").join("", $whereString)) : null;
            return [$where, $params];
        }
        return [null, []];

    }

    /**
     * 多重查询条件
     * @param $where
     * @return array
     */
    public function compileWhereNested($where){
        if(isset($where['query']) && ($where['query'] instanceof Query)){
            return $where['query']->grammar->compileWhere(true);
        }
        return [null, null];
    }

    /**
     * 将条件解析成字符串
     * @param array $where
     * @return array
     */
    private function compileWhereString(array $where){
        switch($where['type']){
            case "NotIn" :
            case "In" :
                if(isset($where['query']) && ($where['query'] instanceof Query)){
                    list($value, $params) = $where['query']->compileToQueryString();
                }else{
                    $params = [];
                    $value = $where['value'];
                    $value = is_array($value) || is_object($value) ? $value : explode(",", $value);
                    $string = [];
                    foreach ($value as $key=>$val){
                        $params[] = $val;
                        $string[] = "?";
                    }
                    $value = join(",", $string);
                }
                return [
                    "string" => "{$where['column']} ".($where['type']=='NotIn' ? 'not ' : '')."in ({$value})",
                    "boolean" => $where['boolean'],
                    'params' => $params
                ];
                break;
            case "Null" :
            case "NotNull" :
                return [
                    "string" => "{$where['column']} is ".($where['type']=='NotNull' ? "not " : "")."null",
                    "boolean" => $where['boolean'],
                    'params' => []
                ];
                break;
            case "NotBetween" :
            case "Between" :
                $value = $where['value'];
                $value = is_array($value) ? $value : explode(" and ", $value);
                $params = [$value[0], $value[1]];
                $value = "? and ?";
                return [
                    "string" => "{$where['column']} ".($where['type']=='NotBetween' ? "not " : "")."between {$value}",
                    "boolean" => $where['boolean'],
                    'params' => $params
                ];
                break;
            case "NotExists" :
            case "Exists" :
                if(isset($where['query']) && ($where['query'] instanceof Query)){
                    list($value, $params) = $where['query']->compileToQueryString();
                    return [
                        "string" => ($where['type']=='NotExists' ? 'not ' : '')."exists ({$value})",
                        "boolean" => $where['boolean'],
                        'params' => $params
                    ];
                }else{
                    return [
                        'string'=>'',
                        'boolean'=>$where['boolean'],
                        'params' => []
                    ];
                }
                break;
            case "FindInSet" :
                $params = [];
                if(is_numeric($where['value']) || strripos($where['value'], ".")){
                    $value = $where['value'];
                }else{
                    $params[] = $where['value'];
                    $value = "?";
                }
                return [
                    'string' => "find_in_set({$value}, {$where['column']})",
                    "boolean" => $where['boolean'],
                    'params' => $params
                ];
                break;
            case "NotLike" :
            case "Like" :
                $params = [];
                $value = strripos($where["value"], "'")===0 ? substr($where["value"], 1) : $where["value"];
                $value = strrchr($value, "'")==="'" ? substr($value, -1) : $value;
                $params[] = $value;
                $value = "?";
                return [
                    'string' => "{$where['column']} ".($where['type']=='NotLike' ? 'not ' : '')."like {$value}",
                    'boolean' => $where['boolean'],
                    'params' => $params
                ];
                break;
            default :
                $params = [];
                if(is_numeric($where['value'])){
                    $value = $where['value'];
                }else {
                    $params[] = $where['value'];
                    $value = "?";
                }
                return [
                    'string' => "{$where['column']}{$where['operator']}{$value}",
                    'boolean' => $where['boolean'],
                    'params' => $params
                ];
                break;
        }
    }

    /**
     * 解析 group by sql 语句
     * @return string
     */
    public function compileGroupBy(){
        return !empty($this->query->groupBy) ? " group by ".trim(join(",",$this->query->groupBy)) : '';
    }

    /**
     * 解析 order by sql 语句
     * @return string
     */
    public function compileOrderBy(){
        $orderBy = $this->query->orderBy;
        $string = [];
        foreach($orderBy as $o){
            if(!empty($o['column'])){
                $string[] = preg_replace("/\s+/","",$o['column'])." ".strtolower($o['type']);
            }
        }
        return !empty($string) ? (" order by ".join(",",$string)) : '';
    }

    /**
     * 解析 HAVING 侧重
     * @return string
     */
    public function compileHaving(){
        $having = $this->query->having;
        $string = [];
        foreach($having as $h){
            if(!empty($h['column'])){
                $string[] = $h['column'].$h['operator'].$h['value'];
            }
        }
        return !empty($string) ? " having ".join(",", $string) : "";
    }

    /**
     * 解析 UNION 联合查询
     * @return array
     */
    public function compileUnion(){
        $unions = $this->query->unions;
        $string = $params = [];
        foreach($unions as $u){
            list($queryString, $params_arr) = $u['query']->grammar->compileToQueryString();
            if(!empty($queryString)){
                $string[] = "union ".($u['all'] ? 'all ' : '')."({$queryString})";
                if(!empty($params)){
                    $params = array_merge($params, $params_arr);
                }
            }
        }
        $queryString = !empty($string) ? " ".join(" ",$string) : "";
        return [$queryString, $params];
    }


    /**
     * 处理 set
     * @param $type
     * @return array
     */
    public function compileSet(&$type){
        switch($type){
            case "update" :
                //仅支持基础的一条语句更新
                $columns = $values = $params = [];
                $sets = is_array(end($this->query->sets)) ? end($this->query->sets) : $this->query->sets;
                foreach($sets as $set){
                    $columns[] = "`{$set['field']}`=".($set['include_field']===true ? $set['value'] : "?");
                    if($set['include_field']===false){
                        //$params[$fieldName] = $set['value'];
                        $params[] = $set['value'];
                    }
                }
                return [join(",",$columns), $params];
                break;
            case "insert" :
                //支持多条记录插入，但每条记录的字段必须一样
                $sets = is_array(end($this->query->sets)) ? $this->query->sets : [$this->query->sets];
                $columns = $values = $params = [];
                foreach($sets as $key=>$set_array){
                    $thisColumns = $thisValues = $thisParams = [];
                    foreach($set_array as $set){
                        $thisColumns[] = "`{$set['field']}`";
                        $thisValues[] = $set['include_field']===true ? $set['value'] : "?";
                        if($set['include_field']===false){
                            $thisParams[] = $set["value"];
                        }
                    }
                    $columns[] = "(".join(",", $thisColumns).")";
                    $values[] = "(".join(",", $thisValues).")";
                    $params[] = $thisParams;
                }
                return [$columns, $values, $params];
                break;
            case "insert where not exists" :
                //仅支持1条数据插入，请勿多条插入：insert into TABLE (f1, f2, fn) select 'f1_val', 'f2_val', 'fn_val' from dual where not exists (select * from TABLE where {$where})
                $sets = is_array(end($this->query->sets)) ? end($this->query->sets) : $this->query->sets;
                $columns = $values = $params = [];
                foreach($sets as $set){
                    $columns[] = "`{$set['field']}`";
                    $values[] = $set['include_field']===true ? $set['value'] : "?";
                    if($set['include_field']===false){
                        $params[] = $set['value'];
                    }
                }
                return ["(".join(",", $columns).")", join(",", $values), $params];
                break;
            default :
                return array('', [], []);
                break;
        }
    }

    /**
     * 获取表名前缀，正常开发，不建议使用前缀！没必要！但这里做个兼容
     * @param string $type
     * @return null
     */
    public function getTablePrefix($type="read"){
        $config = Config::get("server.mysql");
        $setting = !empty($config[$type]) ? $config[$type] : Arr::get($config, "default");
        if(!empty($setting)){
            return $setting["prefix"] ?? null;
        }
        return null;
    }

    /**
     * 刷新参数值
     * @param $addParams
     */
    public function flushParams($addParams){
        $this->params = is_null($this->params) ? [] : $this->params;
        $this->params = !empty($addParams) ? array_merge($this->params, $addParams) : $this->params;
    }

    /**
     * 参数名防重复
     * @param $name
     * @return string
     */
    public function getTempParamName($name){
        $name = strtolower($name);
        $name = preg_match("/^[a-z0-9][a-z0-9_]*[a-z0-9]$/i",$name) ? $name : md5($name);
        return isset($this->tempParams[$name]) ? $this->getTempParamName(count($this->tempParams)."_".$name) : $name;
    }

    /**
     * 自动释放数据库连接
     * @param $type
     */
    public function release($type=Pool::READ){
        //必须不在事务中
        if(!$this->query->transaction && !is_null($this->query->mysql)){
            Pool::putPool($this->query->mysql, $type);
            $this->query->mysql = null;
        }
    }
}