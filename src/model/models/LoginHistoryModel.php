<?php
/**
 * Created by PhpStorm.
 * User: Hunter
 * Date: 2018/4/28
 * Time: 上午12:07
 */
namespace Small\model\models{

    use Small\model\ModelBuilder;

    class LoginHistoryModel extends ModelBuilder {
        //主表名
        protected $tableName = "login_history";
        //主表别名
        protected $as = "h";
        //关联查询的表
        protected $join = [
            "user u"
        ];
        //关联相询方式, inner, left, right
        protected $joinType = [
            "inner"
        ];
        //关联条件
        protected $joinWhere = [
            ["h.uid", "u.uid"]
        ];

        /**
         * 由TOKEN获取登录记录
         * @param $token
         * @return array
         */
        public function getHistoryByToken($token){
            return $this->mainQuery()->where("token", $token)->first();
        }

        /**
         * 获取规定时间段内的错误次数
         * @param $uid
         * @param int $seconds
         * @return int
         */
        public function getErrorHistory($uid, $seconds = 3600){
            $time = time()-$seconds;
            $m = new LoginHistoryModel();
            $rows = $m->mainQuery("h")
                ->where("h.uid", $uid)
                ->where("h.clean", 0)
                ->where("success", 0)
                ->whereDateTimeStartAt("h.login_time", $time)
                ->rows();
            return !empty($rows) ? $rows : 0;
        }

        /**
         * 保存TOKEN
         * @param $uid
         * @param int $time
         * @param null $token
         * @return null|string
         */
        public function saveToken($uid, $time = 86400, $token = null){
            $now = time();
            if(is_null($token)){
                $token = $this->getToken($uid);
            }
            //如果此UID已存在此TOKEN，则直接更新时间即可
            $m = new LoginHistoryModel();
            $history = $m->mainQuery()->where("uid", $uid)
                ->where("token", $token)->first();
            if(!empty($history)){
                $m->mainQuery()
                    ->where("id", $history['id'])
                    ->update([
                        "success"   => 1,
                        "clean"     => 1,
                        "error_code"=> 0,
                        "login_ip"  => getIP(),
                        "exp_time"  => time2datetime($now + $time)
                    ]);
            }else{
                $this->saveHistory($uid, 0, $token, $time);
            }
            return $token;
        }

        /**
         * 保存登录记录，错误的也在这记录
         * @param $uid
         * @param $error
         * @param null $token
         * @param int $time
         */
        public function saveHistory($uid, $error, $token = null, $time = 0){
            $now = time();
            $data = [
                "uid"       => $uid,
                "login_ip"  => getIP(),
                "login_time"=> time2datetime($now),
                "success"   => $error==0 ? 1 : 0,
                "clean"     => 0,
                "error_code"=> $error,
                "token"     => $token ?? 'NULL',
                "exp_time"  => $token ? time2datetime($now+$time) : time2datetime($now)
            ];
            $m = new LoginHistoryModel();
            $m->mainQuery()
                ->insert($data);
        }

        /**
         * 生成TOKEN
         * @param $uid
         * @return string
         */
        public function getToken($uid){
            $now = time();
            return md5(sha1($uid).$now);
        }

    }
}