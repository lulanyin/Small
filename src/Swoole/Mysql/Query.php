<?php
/**
 * Create By Hunter
 * 2019/10/6 00:46:31
 */
namespace Small\Swoole\Mysql;

use Swoole\Coroutine\MySQL;

class Query extends \Small\DB\Query{

    /**
     * @var Connection
     */
    public $connection;

    public function __construct(Connection $connection = null)
    {
        //parent::__construct($connection);
        $this->connection = $connection;
        $this->grammar = new Grammar($this);
    }

    public function newQuery()
    {
        return new Query($this->connection);
    }

    /**
     * 获取连接，注意这里是多进程的，需要有连接池
     * @return Connection
     */
    public function getConnection()
    {
        //return parent::getConnection(); // TODO: Change the autogenerated stub
        if(is_null($this->connection)){
            $this->connection = DB::getConnection();
        }
        return $this->connection;
    }

    /**
     * @param string $type
     * @return false|MySQL
     */
    public function getPdo($type = 'read')
    {
        //return parent::getPdo($type); // TODO: Change the autogenerated stub
        $con = $this->getConnection();
        return $con->getPdo($type);
    }
}