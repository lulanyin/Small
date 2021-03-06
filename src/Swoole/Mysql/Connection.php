<?php
/**
 * Create By Hunter
 * 2019/10/6 00:50:49
 */
namespace Small\Swoole\Mysql;

use Small\Util\Arr;
use Swoole\Coroutine\MySQL;

class Connection extends \Small\DB\Connection
{
    /**
     * @var MySQL[]
     */
    public $pdo = [
        'read'      => null,
        'write'     => null
    ];

    /**
     * 连接数据库
     * @param null $config
     * @param string $type
     * @return false|mixed|MySQL
     */
    public function connect($config = null, $type = 'read')
    {
        //return parent::connect($config, $type); // TODO: Change the autogenerated stub
        if(isset($this->pdo[$type]) && $this->pdo[$type] instanceof MySQL){
            return $this->pdo[$type];
        }
        $this->configs[$type] = $config ?? $this->configs[$type];
        $config = $this->configs[$type];
        if(empty($config)){
            return null;
        }
        //如果存在其中一个
        if(($type == 'read' && isset($this->pdo['write']) && $this->pdo['write'] instanceof MySQL) || ($type == 'write' && isset($this->pdo['read']) && $this->pdo['read'] instanceof MySQL)){
            if($config == $this->configs[$type == 'read' ? 'write' : 'read']){
                //配置相同
                $this->pdo[$type] = $this->pdo[$type == 'read' ? 'write' : 'read'];
                return $this->pdo[$type];
            }
        }
        //取参数
        $host = Arr::get($config, 'host', '127.0.0.1');
        $port = Arr::get($config, 'port', 3306);
        $user = Arr::get($config, 'user', Arr::get($config, 'username', 'root'));
        $pass = Arr::get($config, 'pass', Arr::get($config, 'password', ''));
        $database = Arr::get($config, 'database', Arr::get($config, 'dbname'));
        $charset = Arr::get($config, 'charset', 'utf8mb4');
        $timeout = Arr::get($config, 'timeout', 5);
        $timeout = is_numeric($timeout) && $timeout>=0 ? ceil($timeout) : 5;

        $mysql = new MySQL();
        //连接
        $resource = $mysql->connect([
            'host'      => $host,
            'port'      => $port,
            'user'      => $user,
            'password'  => $pass,
            'database'  => $database,
            'charset'   => $charset,
            'timeout'   => $timeout,
            'fetch_mode'=>$this->fetchModel
        ]);
        if(!$resource){
            //无法连接数据库
            DB::Log('数据库无法连接!');
        }
        $this->pdo[$type] = $resource == false ? null : $mysql;
        return  $this->pdo[$type];
    }

    /**
     * 获取连接
     * @param string $type
     * @return false|MySQL
     */
    public function getPdo($type = 'read')
    {
        $mysql = $this->pdo[$type] ?? $this->connect(null, $type);
        print_r($mysql);
        echo PHP_EOL;
        if(is_null($mysql)){
            return $this->connect($type);
        }elseif(!$mysql->connected){
            return $this->reconnect($type);
        }
        return $mysql;
    }

    /**
     * 开始事务
     * @return false|MySQL
     */
    public function begin()
    {
        //return parent::begin(); // TODO: Change the autogenerated stub
        $pdo = $this->getPdo('write');
        if(!$this->inTransaction)
        {
            $pdo->begin();
        }
        $this->inTransaction = true;
        return $pdo;
    }
}