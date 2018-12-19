<?php
namespace Small\lib\sms{

    use Small\Config;

    class SmsCn extends SMS {

        public static function sendMessage($phone, $type, $temp_id = null, $message = null){

            $setting = Config::get("private.sms");
            $temp_id = $temp_id ?? ($setting['temp_id'] ?? 0);
            if($result = self::insertMessage($phone, $type, $temp_id, $message)){
                list($id, $message) = $result;
                if(is_numeric($message)){
                    $message = '{"code":"'.$message.'"}';
                }
                $url = "http://api.sms.cn/sms/?";
                $query = [
                    "ac"        => "send",//如果是国际短信，这里需要修改
                    "uid"       => $setting['account'],
                    "pwd"       => $setting['secret'],
                    "mobile"    => $phone,
                    "template"  => $temp_id,
                    "content"   => $message
                ];
                $list = [];
                foreach ($query as $key=>$value){
                    $list[] = "{$key}={$value}";
                }
                $url .= join("&", $list);
                $result = file_get_contents($url);
                if( mb_detect_encoding($result, array('ASCII','UTF-8','GB2312','GBK')) != 'UTF-8' ) {
                    $result = iconv('GBK','UTF-8',$result);
                }
                $result = json_decode($result, true);
                if(!empty($result)){
                    static::setState($id, $result['stat']);
                }
                return $message;
            }
            return false;
        }
    }

}