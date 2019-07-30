<?php
namespace Small\View;

use Small\Config;
use Small\Http\HttpController;

/**
 * 视图类，用于输出模板
 * Class View
 * @package Small\View
 */
class View{

    /**
     * 赋值到模板上使用的数据
     * @var array
     */
    public $data = [];

    /**
     * 控制器
     * @var HttpController
     */
    public $controller = null;

    /**
     * 模板
     * @var string
     */
    public $template = null;

    /**
     * 原始地址路径，用于自动匹配模板
     * @var array|null
     */
    public $path = null;

    /**
     * View constructor.
     * @param HttpController $controller
     * @param string $method
     * @param array $path
     */
    public function __construct($controller, string $method, array $path)
    {
        $this->controller = $controller;
        $this->path = $path;
        $this->assign('method', $method);
        if(isset($controller->user)){
            $this->assign('me', $controller->user);
        }
        $this->assign("url_path", join("/", $path));
        $this->template = $controller->template;
    }

    /**
     * 赋值变量到模板使用
     * @param string $name
     * @param $value
     * @return View
     */
    public function assign(string $name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * 展示模板
     * @return string
     */
    public function fetch()
    {
        $templatePath = Config::get("define.views")."/".str_replace("_", null, $this->path[0]);
        //公开配置，可用于模板
        $configs = Config::get("public");
        $domain = Config::get("domain");
        $smarty = new \Smarty();
        $smarty->left_delimiter = "{";
        $smarty->right_delimiter = "}";
        $smarty->setTemplateDir($templatePath);
        $smarty->setCacheDir(defined("CACHE") ? CACHE."/cache" : __DIR__."/../../runtime/cache/cache");
        $smarty->setCompileDir(defined("CACHE") ? CACHE."/compile" : __DIR__."/../../runtime/cache/compile");
        $smarty->assign("configs", $configs);
        $smarty->assign("domain", $domain);
        $this->assign('get', $_GET);
        $this->assign('post', $_POST);
        foreach ($this->data as $key=>$value){
            $smarty->assign($key, $value);
        }
        $template = !empty($this->template) ? $this->template : (join("/", array_slice($this->path, 1)));
        $template = strrchr($template, ".html") == ".html"
        || strrchr($template, ".htm") == ".htm"
        || strrchr($template, ".tpl") == ".tpl" ? $template : ($template.".html");
        try{
            return $smarty->fetch($template);
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

}