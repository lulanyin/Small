<?php
namespace Small\Annotation\Parser;

use Small\Annotation\IParser;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * 规定可访问方式
 * Class Method
 * @package Small\Annotation\Parser
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
     * @param string|null $targetType
     */
    public function process($class, string $target, string $targetType = null)
    {
        if(strtolower($this->type[0])=="all"){
            return;
        }
        // TODO: Implement process() method.
        // 获取方法
        $request_method = $_SERVER['REQUEST_METHOD'] ?? null;
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
                $http_x_requested_with = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
                if($http_x_requested_with == "XMLHttpRequest" && $bool){
                    //
                    return;
                }
            }elseif($bool){
                return;
            }
        }
        response(lang("framework.distrust request method").$step);
    }
}