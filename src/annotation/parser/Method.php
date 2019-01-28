<?php
namespace Small\annotation\parser;

use Small\annotation\IParser;
use Small\http\HttpController;
use Doctrine\Common\Annotations\Annotation\Target;
use Small\server\http\RequestController;

/**
 * @Annotation
 * @Target("METHOD")
 * 规定可访问方式
 * Class Method
 * @package app\lib\annotation\parser
 */
class Method implements IParser {

    /**
     * ALL, GET, POST, AJAX_GET, AJAX_POST
     * @var string[]
     */
    public $type;

    public function __construct(array $values)
    {
        if(isset($values['value'])){
            $this->type = $values['value'];
        }
        $this->type = is_string($this->type) ? [$this->type] : $this->type;
    }

    /**
     * 实现注解的处理
     * @param $class
     * @param string $target
     * @param string $targetType
     */
    public function process($class, string $target, string $targetType = null)
    {
        if(strtolower($this->type[0])=="all"){
            return;
        }
        // TODO: Implement process() method.
        // 获取方法
        if($class instanceof RequestController){
            $request_method = $class->request->server['request_method'] ?? null;
        }else{
            $request_method = $_SERVER['REQUEST_METHOD'] ?? null;
        }
        $bool = $request_method!==null;
        $step = "";
        if($bool){
            $ajax = false;
            $bool = false;
            foreach ($this->type as $str){
                $bool = $bool ? $bool : stripos($str, $request_method)!==false;
                $ajax = $ajax ? $ajax : stripos($str, "ajax")!==false;
            }
            if($ajax){
                if($class instanceof RequestController){
                    $http_x_requested_with = $class->request->header['http_x_requested_with'] ?? null;
                }else{
                    $http_x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
                }
                if($http_x_requested_with == "XMLHttpRequest" && $bool){
                    //
                    return;
                }
            }elseif($bool){
                return;
            }
        }
        //未通过验证
        if($class instanceof HttpController){
            $class->response(lang("framework.distrust request method").$step);
        }
    }
}