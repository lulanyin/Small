<?php
namespace app\projectExtend;

class globalBusiness {

    /**
     * 统一方法入口
     * @param string $event
     * @param array $data
     */
    public static function after(string $event, $data = []){
        switch ($event){
            case "register" :
                self::register($data);
                break;
        }
    }

    /**
     * 账号注册成功后执行
     * @param $data
     */
    private static function register($data){

    }

}