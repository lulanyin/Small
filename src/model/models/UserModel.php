<?php
/**
 * Created by PhpStorm.
 * User: Hunter
 * Date: 2018/4/28
 * Time: 上午12:31
 */
namespace Small\model\models{

    use Small\model\ModelBuilder;

    class UserModel extends ModelBuilder{
        //主表名
        protected $tableName = "user";
        //主表别名
        protected $as = "u";
        //关联查询的表
        protected $join = [
            "user_group g"
        ];
        //关联相询方式, inner, left, right
        protected $joinType = [
            "inner"
        ];
        //关联条件
        protected $joinWhere = [
            ["u.group_id", "g.group_id"]
        ];
    }
}