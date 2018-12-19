<?php
namespace Small\websocket;


use Small\lib\cache\Cache;
use Small\model\models\UserModel;

abstract class WebSocketController{

    /**
     * server worker
     * @var \swoole_websocket_server
     */
    public $ws;

    /**
     * 客户端
     * @var int
     */
    public $fd;

    /**
     * @var \swoole_http_request
     */
    public $request;

    /**
     * @var \swoole_websocket_frame
     */
    public $frame;

    /**
     * 收到的消息原文
     * @var array
     */
    public $data = [];

    /**
     * 会员简单资料
     * @var array
     */
    public $user = [];

    /**
     * 当前自动回应的事件
     * @var string
     */
    public $event;

    /**
     * 异步任务ID
     * @var int
     */
    public $task_id;

    /**
     * 异步任务线程ID
     * @var int
     */
    public $reactor_id;

    public function __construct(\swoole_websocket_server $server = null)
    {
        $this->ws = $server;
    }

    /**
     * 默认入口
     * @param mixed ...$args
     * @return mixed
     */
    abstract public function index(...$args);

    /**
     * 输出消息结果
     * @param $event
     * @param $error
     * @param $message
     * @param array $data
     */
    public function response($event = null, $error=1, $message='', $data=[]){
        $json = $this->processMessage($event, $error, $message, $data);
        $this->fd = $this->fd ?? $this->frame->fd;
        $this->push($json);
    }

    /**
     * 根据UID推送消息
     * @param $uid
     * @param string $event
     * @param int $error
     * @param string $message
     * @param array $data
     */
    public function pushToUid($uid, $event = null, $error=1, $message='', $data=[]){
        $json = $this->processMessage($event, $error, $message, $data);
        if($fd = Cache::get("ws_uid_".$uid)){
            if(is_int($fd)){
                $this->push($json, $fd);
            }
        }
    }

    /**
     * 发送给分组
     * @param $group
     * @param $event
     * @param $data
     * @param int $exists_fd
     */
    public function pushToGroup(string $group, string $event, array $data, int $exists_fd = null){
        $json = $this->processMessage($event, $data);
        //
        if($fdList = Cache::get("ws_group_{$group}")){
            foreach ($fdList as $fd){
                if($exists_fd && $exists_fd==$fd){

                }else{
                    $this->push($json, $fd);
                }
            }
        }
    }

    /**
     * 处理消息
     * @param string $event
     * @param int $error
     * @param string $message
     * @param array $data
     * @return array
     */
    private function processMessage($event = null, $error=1, $message='', $data=[]){
        $data = is_array($error) || is_object($error) ? $error : (is_array($message) || is_object($message) ? $message : $data);
        $message = is_string($error) ? $error : (is_array($message) || is_object($message) ? null : $message);
        $error = is_array($error) || is_object($error) ? 0 : (is_string($error) ? 1 : ($error==1 || $error==0 ? $error : $error));
        //检测是Ajax访问，还是正常的GET,POST，如果是Ajax，使用json输出，如果是正常的GET,POST，则使用页面结果展示输出
        $json = [
            "event"     => $event ?? $this->event,
            "error"     => $error,
            "message"   => $message,
            "data"      => $data
        ];
        return $json;
    }

    /**
     * 推送消息给客户
     * @param $json
     * @param null $fd
     */
    private function push($json, $fd = null){
        $fd = $fd ?? $this->fd;
        if($this->ws->exist($fd)){
            $this->ws->push($fd, is_array($json) || is_object($json) ? json_encode($json, JSON_UNESCAPED_UNICODE) : $json);
        }
    }

    /**
     * 关闭当前连接
     */
    public function close(){
        $this->ws->close($this->fd);
    }

    /**
     * 保存客户端资料
     * @param $token
     * @param $info
     */
    public function setToken($token, $info){
        Cache::set("ws_fd_".$this->fd, $token);
        Cache::set("ws_uid_".$info['uid'], $this->fd);
    }

    /**
     * 读取客户端资料
     * @return mixed|null
     */
    public function getToken(){
        if($token = Cache::get("ws_fd_{$this->fd}")){
            return $token;
        }
        return null;
    }

    /**
     * 删除对应的TOKEN
     */
    public function dropToken(){
        if($info = $this->getCacheUser()){
            Cache::remove("ws_uid_".$info['uid']);
        }
        Cache::remove("ws_fd_".$this->fd);
    }

    /**
     * 获取数据
     * @param $key
     * @param null $default
     * @param null $message
     * @return array|mixed|null|string
     */
    public function getData($key, $default = null, $message = null){
        $value = Arr::get((array)$this->data, $key, $default);
        if(!empty($message) && $value==null){
            $this->response(null, $message);
        }
        return $value;
    }

    /**
     * 获取对应缓存中的USER数据
     * @return null
     */
    public function getCacheUser(){
        if($token = $this->getToken()){
            if($info = Cache::select(0)->get($token)){
                $expTime = $info['exp_time'] ?? 0;
                if($expTime > time()){
                    $this->user = $info;
                    return $info;
                }else{
                    //TOKEN 已过期
                    $this->response(null, -1, "Token 无效");
                }
            }
        }
        return null;
    }

    /**
     * 获取完整的会员数据
     * @return null|array
     */
    public function getFullUser(){
        if($info = $this->getCacheUser()){
            $m = new UserModel(true);
            $temp = $m->where("u.uid", $info['uid'])->first();
            if(!empty($temp)){
                if($temp['frozen'] == 0){
                    return $temp;
                }
            }
        }
        return null;
    }

    /**
     * 退出最后一个聊天分组（如果存在）
     */
    public function leaveGroup(){
        if($lastGroup = Cache::get("ws_group_{$this->fd}")){
            if($fdList = Cache::get("ws_group_{$lastGroup}")){
                $index = array_search($this->fd, $fdList);
                if($index !== false){
                    unset($fdList[$index]);
                }
                if(empty($fdList)){
                    Cache::remove("ws_group_{$lastGroup}");
                }else{
                    Cache::set("ws_group_{$lastGroup}", $fdList);
                }
            }
        }
    }

    /**
     * 加入某个分组
     * @param $name
     * @param $chatEnable
     */
    public function joinGroup($name, $chatEnable = true){
        $this->leaveGroup();
        Cache::update("ws_group_{$name}", [$this->fd], true);
        Cache::set("ws_group_{$this->fd}", $name);
        if($chatEnable){
            Cache::set("ws_group_{$name}_enable", "1");
        }else{
            Cache::remove("ws_group_{$name}_enable");
        }
    }

    /**
     * 检测分组
     * @param $name
     * @return int
     */
    public function checkGroup($name){
        if($group = Cache::get("ws_group_{$name}")){
            if($enable = Cache::get("ws_group_{$name}_enable")){
                return 1;
            }
            return 2;
        }
        return 0;
    }

    /**
     * 客户端关闭连接，将清空所有有关此客户端的所有保存的数据
     */
    public function exit(){
        //退出分组
        $this->leaveGroup();
        //删除最后记录的分组记录
        Cache::remove("ws_group_{$this->fd}");
        //
        $this->dropToken();
    }
}