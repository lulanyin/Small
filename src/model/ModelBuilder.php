<?php
/**
 *
 * Created by PhpStorm.
 * User: Hunter
 * Date: 2018/4/13 下午5:39
 */
namespace Small\model{

    use Small\App;
    use Small\server\mysql\Query;
    use Small\lib\util\Arr;
    use DB\DB;
    use DB\Query\QueryBuilder;
    use Small\Config;

    abstract class ModelBuilder {

        protected $db = null;
        protected $data = [];
        protected $as = null;
        protected $tableName;
        protected $join = null;
        protected $joinWhere = [];
        protected $joinType = [];
        public $lastInsertId;
        public $ModelErrorInfo;
        public $cacheReload = false;
        protected $pool = false;

        private $hasTablePrefix = true;

        public function __construct(){
            $this->pool = App::$server;
            $this->db = $this->getNewDB();
            $this->hasTablePrefix = !empty($this->db->connection->prefix['read']);
        }

        public function setAttribute($name, $value){
            $this->data[$name] = $value;
        }

        public function getAttribute($name){
            return Arr::get($this->data, $name);
        }

        public function __set($name, $value)
        {
            // TODO: Implement __set() method.
            $this->setAttribute($name, $value);
        }

        /**
         * 动态获取
         * @param $name
         * @return array|mixed
         */
        public function __get($name)
        {
            // TODO: Implement __get() method.
            return $this->getAttribute($name);
        }

        /**
         * 检测数据对象的值
         * @access public
         * @param string $name 名称
         * @return boolean
         */
        public function __isset($name)
        {
            try {
                if (array_key_exists($name, $this->data)) {
                    return true;
                } else {
                    $this->getAttribute($name);
                    return true;
                }
            } catch (\InvalidArgumentException $e) {
                return false;
            }

        }

        /**
         * 销毁数据对象的值
         * @access public
         * @param string $name 名称
         * @return void
         */
        public function __unset($name)
        {
            unset($this->data[$name]);
        }

        /**
         * 查询别名
         * @param $name
         * @return $this
         */
        public function as_($name){
            $this->as = $name;
            return $this;
        }


        /**
         * 动态重载方法
         * @param $method
         * @param $arguments
         * @return QueryBuilder
         */
        public function __call($method, $arguments)
        {
            // TODO: Implement __call() method.
            $db = $this->mainQuery(1);
            if(!empty($this->join)){
                foreach ($this->join as $k=>$j){
                    $db = $db->join($j, $this->joinWhere[$k], $this->joinType[$k]);
                }
            }
            if (method_exists($this, $method)) {
                array_unshift($arguments, $db);
                return call_user_func_array([$this, $method], $arguments);
            } else {
                return call_user_func_array([$db, $method], $arguments);
            }
        }

        /**
         * 新数据插入
         * @param array $data
         * @return int
         */
        public function save($data = []){
            if(empty($this->data) && empty($data)){
                $this->ModelErrorInfo = "数据为空，不执行插入";
                return 0;
            }
            $query = $this->db->from($this->tableName);
            $query->set(!empty($this->data) ? array_merge($this->data, $data) : $data);
            if($query->insert()){
                $this->lastInsertId = $query->getLastInsertId();
                return 1;
            }else{
                $this->ModelErrorInfo = $query->getError();
                return 0;
            }
        }

        /**
         * 插入数据
         * @param array $data
         * @return int
         */
        public function submitChange($data = []){
            return $this->save($data);
        }

        /**
         * 获取错误信息
         * @return mixed
         */
        public function getError(){
            return $this->ModelErrorInfo;
        }

        /**
         * 获取一个新的连接
         * @return bool|QueryBuilder|Query
         */
        private function getNewDB(){
            if(!is_null($this->db)){
                return $this->db->newQuery();
            }
            if($this->pool){
                return new Query();
            }
            $config = Config::get("private.mysql");
            //非服务端多线程，使用DB
            if(!DB::getConnection()){
                DB::init($config);
            }
            return DB::getQuery();
        }

        /**
         * 主表的QueryBuilder
         * @param bool $as
         * @return QueryBuilder|Query|null
         */
        public function mainQuery($as=false){
            $db = $this->getNewDB();
            if(!$db){
                return null;
            }
            $tableName = $this->tableName;//$this->hasTablePrefix ? "`{$this->tableName}`" : $this->tableName;
            return $db->newQuery()->from($tableName.($as ? (is_string($as) ? " {$as}" : (!empty($this->as) ? " {$this->as}" : "")) : ""));
        }
        public function getQuery($as=0){
            return $this->mainQuery($as);
        }

        /**
         * 返回一个空的QueryBuilder
         * @return bool|QueryBuilder
         */
        public function emptyQuery(){
            return $this->getNewDB();
        }

        //对表进行上锁，仅当前类可用，其它类的操作，请等待 sleep 100毫秒，再执行（查询不受限，仅增、删、改），若依旧锁，则10次等待后，退出，通知客户端错误，所有更改回滚
        public function lock($tableName=null){
            $this->db->connection->lock(is_null($tableName) ? $this->tableName : $tableName);
        }

        public function unLock($tableName=null){
            $this->db->connection->unLock(is_null($tableName) ? $this->tableName : $tableName);
        }
    }
}