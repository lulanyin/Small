<?php
namespace Small\lib\view;

use Small\Config;
use Small\http\HttpController;
use Small\server\http\RequestController;

class View{

    //模板数据
    public $data = [];
    //类对象
    public $controller = false;
    //模板
    public $template = null;
    //原始地址路劲，用于匹配模板
    public $path = null;

    /**
     * View constructor.
     * @param HttpController|RequestController $controller
     * @param string $method
     * @param array $path
     */
    public function __construct($controller, string $method, array $path)
    {
        $this->controller = $controller;
        $this->path = $path;
        $this->assign('method', $method);
        $this->assign('me', $controller->user);
        $this->assign("url_path", join("/", $path));
    }

    public function assign($name, $value){
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * 展示模板
     * @return string
     */
    public function fetch(){
        $templatePath = Config::get("define.views")."/".str_replace("_", null, $this->path[0]);
        //公开配置，可用于模板
        $configs = Config::get("public");
        $domain = Config::get("domain");
        $smarty = new \Smarty();
        $smarty->left_delimiter = "{";
        $smarty->right_delimiter = "}";
        $smarty->setTemplateDir($templatePath);
        $smarty->setCacheDir(CACHE."/cache");
        $smarty->setCompileDir(CACHE."/compile");
        $smarty->assign("configs", $configs);
        $smarty->assign("domain", $domain);
        foreach ($this->data as $key=>$value){
            $smarty->assign($key, $value);
        }
        $template = !empty($this->template) ? $this->template : (join("/", array_slice($this->path, 1)).".html");
        try{
            return $smarty->fetch($template);
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

}