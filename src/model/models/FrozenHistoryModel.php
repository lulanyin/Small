<?php
namespace Small\model\models;
use Small\model\ModelBuilder;

class FrozenHistoryModel extends ModelBuilder{

    //主表名
    protected $tableName = "frozen_history";
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



    public static function frozen($uid, $reason){
        $history = [
            "uid"   => $uid,
            "reason"    => $reason
        ];
        $m = new static();
        @$m->mainQuery()->insert($history);
        //冻结
        $um = new UserModel();
        $um->mainQuery()
            ->where("uid", $uid)
            ->update([
                "frozen"    => 1
            ]);
        //冻结后，发送短信 ?
    }

    public static function getLastHistory($uid){
        $m = new static();
        $reason = $m->mainQuery()
            ->where("uid", $uid)
            ->orderBy("id", "desc")
            ->pluck("reason");
        return !empty($reason) ? $reason : null;
    }

}