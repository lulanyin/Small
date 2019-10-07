<?php
/**
 * Create By Hunter
 * 2019/10/6 00:47:44
 */
namespace Small\Swoole\Mysql;


class Grammar extends \Small\DB\Grammar
{
    /**
     * @var Query
     */
    protected $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * 获取数据
     * @param null $total
     * @param int $offset
     * @return array|bool
     */
    public function get($total = null, $offset = 0)
    {
        //return parent::get($total, $offset); // TODO: Change the autogenerated stub
        list($queryString, $params) = $this->compileToQueryString();
        $this->flushParams($params);
        //判断查询语句、参数是否相同
        if(!is_null($total)){
            $queryString .= " limit {$offset},{$total}";
        }
        if($mysql = $this->query->getPdo()){
            $stmt = $mysql->prepare($queryString);
            $rows = [];
            if($stmt !== false){
                if($stmt->execute($this->params)){
                    $rows = $stmt->fetchAll();
                }
            }else{
                DB::Log($mysql->error);
            }
            $this->releasePDO();
            return $rows;
        }else{
            DB::Log('未获取到数据库');
            return [];
        }
    }

    /**
     * 获取行数
     * @return int
     */
    public function count()
    {
        //return parent::count(); // TODO: Change the autogenerated stub
        $columns = $this->query->columns;
        $bool = $this->query->checkAggregateQuery();
        $this->query->columns = !$bool ? ["count('')"] : $columns;
        list($queryString, $params) = $this->compileToQueryString();
        //如果原语句中，包含了 sum(), avg(), max()... group by，要统计行数，是不能直接使用  count(*)的
        //但是可以修改一下查询语句为  select count(*) from (原语句) temp_table
        $bool = $bool ? $bool : !empty($this->query->groupBy);
        if($bool){
            $queryString = "SELECT COUNT(*) as db_rows FROM ({$queryString}) TEMP_TABLE";
        }
        $this->flushParams($params);
        $this->query->columns = $columns;

        if($mysql = $this->query->getPdo()){
            $stmt = $mysql->prepare($queryString);
            $rows = [];
            if($stmt !== false){
                if($stmt->execute($this->params)){
                    $rows = $stmt->fetchAll();
                }
            }else{
                DB::Log($mysql->error);
            }
            $this->releasePDO();
            return !empty($rows) ? ($rows[0]['db_rows'] ?? 0) : 0;
        }else{
            DB::Log('未获取到数据库');
            return 0;
        }
    }

    /**
     * 执行
     * @param $type
     * @param null $queryString
     * @param null $params
     * @return bool
     */
    public function execute($type, $queryString = null, $params = null)
    {
        //return parent::execute($type, $queryString, $params); // TODO: Change the autogenerated stub
        if(is_null($queryString) && is_null($params)){
            list($queryString, $params) = $this->compileToQueryString($type);
            $this->flushParams($params);
        }else{
            $this->params = $params;
        }
        if($mysql = $this->query->getPdo('write')){
            $stmt = $mysql->prepare($queryString);
            if($stmt !== false){
                if($stmt->execute($this->params)){
                    $this->query->affectRows += $mysql->affected_rows;
                }
                if(stripos($type, 'insert')===0 && !empty($mysql->insert_id)){
                    $this->query->lastInsertId[] = $mysql->insert_id;
                }
            }else{
                DB::Log($mysql->error);
                return false;
            }
        }else{
            DB::Log('未获取到数据库');
            return false;
        }
        return true;
    }

    /**
     * 释放连接回进程池
     */
    public function releasePDO()
    {
        //parent::releasePDO(); // TODO: Change the autogenerated stub
        if(!$this->query->getConnection()->inTransaction){
            Pool::putPool($this->query->connection);
            $this->query->connection = null;
        }
    }
}