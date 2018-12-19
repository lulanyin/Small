<?php
namespace Small\model\models;
use Small\model\ModelBuilder;

class VerifyCodeModel extends ModelBuilder{
    protected $tableName = "verify_code";

    public static function make($code){
        $m = new static();
        $data = [
            "session_id"    => session_id(),
            "value"         => $code,
            "ipv4"          => getIP()
        ];
        $db = $m->mainQuery()->insert($data);
        if($db){
            return $db->getLastInsertId();
        }else{
            return false;
        }
    }

    public static function verify($id, $value){
        $m = new static();
        $row = $m->mainQuery()->where("id", $id)->where('`value`', $value)->where('`use`', 0)->first();
        if(!empty($row)){
            self::drop($id);
            return true;
        }else{
            return false;
        }
    }

    public static function drop($id){
        $m = new static();
        @$m->mainQuery()->where("id", $id)->delete();
    }
}